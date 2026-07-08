<?php

namespace App\Controllers;

use App\Models\InspectionModel;
use App\Models\InspectionValueModel;
use App\Models\PhotoModel;
use App\Models\TemplateModel;
use App\Models\VehicleModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Inspections extends BaseController
{
    public function index()
    {
        return redirect()->to('dashboard');
    }

    /** Step 1: choose a template + vehicle. */
    public function new()
    {
        return view('inspections/new', [
            'title'     => 'New Inspection',
            'templates' => model(TemplateModel::class)->where('is_active', 1)->findAll(),
            'vehicles'  => model(VehicleModel::class)->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    /** Step 2: create the inspection + snapshot the template into value rows. */
    public function create()
    {
        $templateId = (int) $this->request->getPost('template_id');
        $template   = model(TemplateModel::class)->withTree($templateId);
        if ($template === null) {
            return redirect()->back()->with('error', 'Please choose a valid template.');
        }

        // Vehicle: either an existing one, or a new quick-capture.
        $vehicleId = (int) $this->request->getPost('vehicle_id');
        if ($vehicleId === 0) {
            $vehicleId = model(VehicleModel::class)->insert([
                'make'       => $this->request->getPost('make'),
                'model'      => $this->request->getPost('model'),
                'year'       => $this->request->getPost('year') ?: null,
                'colour'     => $this->request->getPost('colour'),
                'vin'        => $this->request->getPost('vin'),
                'plate'      => $this->request->getPost('plate'),
                'odometer'   => $this->request->getPost('odometer'),
                'seller'     => $this->request->getPost('seller'),
                'created_by' => auth()->id(),
            ], true);
        }

        $uid = bin2hex(random_bytes(8));

        $inspectionId = model(InspectionModel::class)->insert([
            'report_uid'      => $uid,
            'template_id'     => $templateId,
            'vehicle_id'      => $vehicleId,
            'mechanic_id'     => auth()->id(),
            'status'          => 'draft',
            'inspection_date' => $this->request->getPost('inspection_date') ?: date('Y-m-d'),
            'conclusion'      => 'Kindly be advised that only a visual inspection was performed, and the vehicle was not disassembled. The vehicle is road tested, where possible, up to the local speed limit.',
            'created_by'      => auth()->id(),
        ], true);

        $this->snapshotTemplate((int) $inspectionId, $template);

        return redirect()->to('inspections/' . $uid . '/capture')
            ->with('message', 'Inspection created — start capturing.');
    }

    /** Capture / edit screen. */
    public function capture(string $uid)
    {
        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('inspections/capture', [
            'title'      => 'Capture Inspection',
            'uid'        => $uid,
            'report'     => $report,
            'ratings'    => CHECKA_RATINGS,
            'compute'    => service('intelligence')->compute($report),
            'stockImage' => (new \App\Services\VehicleImageService())->imageFor($report['vehicle'] ?? []),
        ]);
    }

    /** Persist captured values (posted as arrays keyed by value id). */
    public function update(string $uid)
    {
        $model      = model(InspectionModel::class);
        $inspection = $model->findByUid($uid);
        if ($inspection === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->persistCapture($inspection);
        $this->recompute($uid);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true]);
        }

        return redirect()->to('inspections/' . $uid . '/capture')->with('message', 'Saved.');
    }

    /** Save all posted capture fields onto the inspection + its value rows. */
    private function persistCapture(array $inspection): void
    {
        $model      = model(InspectionModel::class);
        $valueModel = model(InspectionValueModel::class);
        $ratings    = $this->request->getPost('rating')  ?? [];
        $comments   = $this->request->getPost('comment') ?? [];
        $texts      = $this->request->getPost('text')    ?? [];
        $numbers    = $this->request->getPost('number')  ?? [];
        $costs      = $this->request->getPost('cost')    ?? [];
        $choices    = $this->request->getPost('choice')  ?? [];

        foreach ($valueModel->forInspection((int) $inspection['id']) as $row) {
            $id    = (string) $row['id'];
            $patch = [];

            if (array_key_exists($id, $ratings)) {
                $patch['rating'] = in_array($ratings[$id], ['ok', 'minor', 'lesser', 'major'], true) ? $ratings[$id] : 'ok';
            }
            if (array_key_exists($id, $comments)) {
                $patch['comment'] = $comments[$id];
            }
            if (array_key_exists($id, $texts)) {
                // Checkbox fields post an array of selected options.
                $patch['value_text'] = is_array($texts[$id]) ? implode(', ', $texts[$id]) : $texts[$id];
            }
            if (array_key_exists($id, $numbers)) {
                $patch['value_number'] = $numbers[$id] !== '' ? $numbers[$id] : null;
            }
            if (array_key_exists($id, $costs)) {
                $patch['cost'] = $costs[$id] !== '' ? $costs[$id] : null;
            }
            if (array_key_exists($id, $choices)) {
                $patch['choice'] = $choices[$id] !== '' ? $choices[$id] : null;
            }

            if ($patch !== []) {
                $valueModel->update($row['id'], $patch);
            }
        }

        // Vehicle-context meta + conclusion + signature.
        $meta = [
            'dealer'    => $this->request->getPost('meta_dealer'),
            'mechanic'  => $this->request->getPost('meta_mechanic'),
            'km_before' => $this->request->getPost('meta_km_before'),
            'km_after'  => $this->request->getPost('meta_km_after'),
        ];

        $update = [
            'conclusion' => $this->request->getPost('conclusion'),
            'meta'       => json_encode(array_filter($meta, static fn ($v) => $v !== null && $v !== '')),
        ];

        if ($sig = $this->request->getPost('signature')) {
            $stored = $this->storeSignature((int) $inspection['id'], $sig);
            if ($stored !== null) {
                $update['signature_path'] = $stored;
            }
        }

        $model->update($inspection['id'], $update);
    }

    /** Handle a single photo upload (AJAX). */
    public function uploadPhoto(string $uid)
    {
        $inspection = model(InspectionModel::class)->findByUid($uid);
        if ($inspection === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Inspection not found']);
        }

        $file = $this->request->getFile('photo');
        if ($file === null || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No valid file uploaded']);
        }

        // Compare raw bytes — getSizeByUnit('kb'|'mb') runs number_format() which
        // adds a thousands-comma at >=1000, and PHP then string-compares it,
        // wrongly failing anything over ~1 MB. getSize() is a plain integer.
        $maxKb    = (int) (env('checka.uploadMaxKb') ?: 30720); // 30 MB
        $maxBytes = $maxKb * 1024;
        if ($file->getSize() > $maxBytes) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'File too large — max ' . round($maxKb / 1024) . ' MB (this file is ' . round($file->getSize() / 1048576, 1) . ' MB)',
            ]);
        }

        // Read the mime BEFORE moving — after move() the temp file is gone.
        $mime    = (string) $file->getMimeType();
        $isVideo = str_starts_with($mime, 'video/');
        if (! $isVideo && ! str_starts_with($mime, 'image/')) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Only images or videos allowed']);
        }

        $dir = WRITEPATH . 'uploads/' . $inspection['id'];
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $newName  = $file->getRandomName();
        $file->move($dir, $newName);
        $relative = $inspection['id'] . '/' . $newName;

        // Down-scale the stored original (images only) so a 24 MP phone photo
        // (8–15 MB) is kept as a reasonable ~2000px, ~1–2 MB file — plenty for
        // zooming in on a report, but a fraction of the disk. Videos untouched.
        if (! $isVideo) {
            try {
                $maxDim = (int) (env('checka.imageMaxDim') ?: 2000);
                $img    = service('image')->withFile($dir . '/' . $newName);
                $props  = $img->getFile()->getProperties();
                if (($props['width'] ?? 0) > $maxDim || ($props['height'] ?? 0) > $maxDim) {
                    $img->resize($maxDim, $maxDim, true, 'auto') // keep aspect ratio, no crop
                        ->save($dir . '/' . $newName, 82);
                }
            } catch (\Throwable $e) {
                // Non-fatal: keep the full-size original if resizing fails.
                log_message('error', 'Image down-scale failed: ' . $e->getMessage());
            }
        }

        // Thumbnail: images resize themselves; videos use the client poster frame.
        $thumbRel = null;
        if (! $isVideo) {
            try {
                $thumbName = 'thumb_' . $newName;
                service('image')
                    ->withFile($dir . '/' . $newName)
                    ->resize(700, 700, true, 'auto') // keep aspect ratio, fit within 700x700 (no crop)
                    ->save($dir . '/' . $thumbName, 80);
                $thumbRel = $inspection['id'] . '/' . $thumbName;
            } catch (\Throwable $e) {
                $thumbRel = $relative; // fall back to full image
            }
        } else {
            $poster = $this->request->getFile('poster');
            if ($poster !== null && $poster->isValid() && str_starts_with((string) $poster->getMimeType(), 'image/')) {
                try {
                    $posterName = 'thumb_' . pathinfo($newName, PATHINFO_FILENAME) . '.jpg';
                    $poster->move($dir, $posterName);
                    service('image')->withFile($dir . '/' . $posterName)
                        ->resize(700, 700, true, 'auto')->save($dir . '/' . $posterName, 80);
                    $thumbRel = $inspection['id'] . '/' . $posterName;
                } catch (\Throwable $e) {
                    $thumbRel = null;
                }
            }
        }

        $valueId = $this->request->getPost('value_id') ?: null;
        $ref     = $this->request->getPost('ref') ?: null;
        $caption = trim((string) $this->request->getPost('caption')) ?: null;

        $photoId = model(PhotoModel::class)->insert([
            'inspection_id' => $inspection['id'],
            'value_id'      => $valueId,
            'ref'           => $ref,
            'caption'       => $caption,
            'path'          => $relative,
            'thumb_path'    => $thumbRel,
            'mime'          => $mime,
            'is_video'      => $isVideo ? 1 : 0,
            'taken_at'      => date('H:i'),
        ], true);

        return $this->response->setJSON([
            'ok'      => true,
            'id'      => $photoId,
            'url'     => site_url('media/' . $photoId),
            'thumb'   => site_url('media/' . $photoId . '/thumb'),
            'video'   => $isVideo,
            'caption' => $caption,
        ]);
    }

    /** AJAX: AI-draft the conclusion paragraph (feature: aiConclusion). */
    public function aiConclusion(string $uid)
    {
        if (! feature('aiConclusion')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'AI conclusion is disabled.']);
        }
        $report = model(InspectionModel::class)->assembleReport($uid);
        if ($report === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Report not found']);
        }

        return $this->response->setJSON((new \App\Services\AiService())->conclusion($report));
    }

    /** AJAX: AI-analyse a single photo and set it as the caption (feature: aiPhotoAnalysis). */
    public function aiAnalyzePhoto(int $id)
    {
        if (! feature('aiPhotoAnalysis')) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'error' => 'Photo analysis is disabled.']);
        }
        $photo = model(PhotoModel::class)->find($id);
        if ($photo === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Photo not found']);
        }
        if ($photo['is_video']) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Videos cannot be analysed.']);
        }

        $rel  = $photo['thumb_path'] ?: $photo['path'];
        $full = WRITEPATH . 'uploads/' . ltrim($rel, '/');
        if (! is_file($full)) {
            $full = WRITEPATH . 'uploads/' . ltrim($photo['path'], '/');
        }
        if (! is_file($full)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Image file missing']);
        }

        $info = @getimagesize($full);
        $mime = $info['mime'] ?? 'image/jpeg';
        $b64  = base64_encode((string) file_get_contents($full));

        $context = '';
        if ($photo['value_id']) {
            $row     = model(InspectionValueModel::class)->find((int) $photo['value_id']);
            $context = $row['field_name'] ?? '';
        }

        $res = (new \App\Services\AiService())->analyzePhoto($b64, $mime, $context);
        if ($res['ok'] ?? false) {
            model(PhotoModel::class)->update($id, ['caption' => $res['text']]);
        }

        return $this->response->setJSON($res);
    }

    /** AJAX: update a photo's caption. */
    public function captionPhoto(int $id)
    {
        if (model(PhotoModel::class)->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        $caption = trim((string) $this->request->getPost('caption'));
        model(PhotoModel::class)->update($id, ['caption' => $caption ?: null]);

        return $this->response->setJSON(['ok' => true]);
    }

    public function deletePhoto(int $id)
    {
        $photo = model(PhotoModel::class)->find($id);
        if ($photo !== null) {
            @unlink(WRITEPATH . 'uploads/' . $photo['path']);
            if ($photo['thumb_path']) {
                @unlink(WRITEPATH . 'uploads/' . $photo['thumb_path']);
            }
            model(PhotoModel::class)->delete($id);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true]);
        }

        return redirect()->back();
    }

    public function complete(string $uid)
    {
        $model      = model(InspectionModel::class);
        $inspection = $model->findByUid($uid);
        if ($inspection === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->persistCapture($inspection);

        $missing = $this->missingRequired((int) $inspection['id']);
        if ($missing !== []) {
            return redirect()->to('inspections/' . $uid . '/capture')
                ->with('error', 'Cannot complete — ' . count($missing) . ' required item(s) not filled: ' . implode(', ', array_slice($missing, 0, 12)) . (count($missing) > 12 ? '…' : ''));
        }

        $model->update($inspection['id'], ['status' => 'completed']);
        $this->recompute($uid);

        return redirect()->to('reports/' . $uid)->with('message', 'Inspection completed.');
    }

    public function delete(string $uid)
    {
        $model      = model(InspectionModel::class);
        $inspection = $model->findByUid($uid);
        if ($inspection !== null) {
            $model->delete($inspection['id']);
        }

        return redirect()->to('dashboard')->with('message', 'Inspection deleted.');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /** Copy a template tree into inspection_values (the capture-time snapshot). */
    private function snapshotTemplate(int $inspectionId, array $template): void
    {
        $valueModel = model(InspectionValueModel::class);
        $rows       = [];
        $sort       = 0;

        foreach ($template['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                $isRating = ($field['type'] ?? 'rating') === 'rating';
                $rows[]   = [
                    'inspection_id' => $inspectionId,
                    'field_id'      => $field['id'],
                    'section_idx'   => $section['idx'],
                    'section_title' => $section['title'],
                    'is_critical'   => $section['is_critical'],
                    'ref'           => $field['ref'],
                    'field_name'    => $field['name'],
                    'field_type'    => $field['type'],
                    'role'          => $field['role'],
                    'measure'       => $field['measure'],
                    'is_sub'        => $field['is_sub'],
                    'is_required'   => $field['is_required'] ?? 0,
                    'choices'       => $field['choices'] ?? null,
                    'rating'        => $isRating ? 'ok' : null,
                    'sort'          => $sort++,
                ];
            }
        }

        if ($rows !== []) {
            $valueModel->insertBatch($rows);
        }
    }

    /** Recompute and cache score + verdict on the inspection row. */
    private function recompute(string $uid): void
    {
        $model  = model(InspectionModel::class);
        $report = $model->assembleReport($uid);
        if ($report === null) {
            return;
        }
        $engine  = service('intelligence');
        $verdict = $engine->overallVerdict($report);

        $model->update($report['inspection']['id'], [
            'score'          => $engine->conditionScore($report),
            'verdict_status' => $verdict['status'],
            'verdict_text'   => $verdict['text'],
        ]);
    }

    /**
     * Return the refs of required items that have not been filled in.
     * "Filled" = an assessment choice (if the item has choices), otherwise a
     * value for value-type fields; rating items without choices always count.
     *
     * @return list<string>
     */
    private function missingRequired(int $inspectionId): array
    {
        $rows = model(InspectionValueModel::class)
            ->where('inspection_id', $inspectionId)
            ->where('is_required', 1)
            ->findAll();

        $missing = [];
        foreach ($rows as $r) {
            $hasChoices = ! empty($r['choices']);
            $filled     = false;

            if ($hasChoices) {
                $filled = trim((string) $r['choice']) !== '';
            } elseif (in_array($r['field_type'], ['text', 'textarea', 'number', 'date', 'dropdown', 'radio', 'checkbox'], true)) {
                $filled = trim((string) $r['value_text']) !== '' || (string) $r['value_number'] !== '';
            } else {
                // rating without choices — RAG is always set
                $filled = trim((string) $r['rating']) !== '';
            }

            if (! $filled) {
                $missing[] = $r['ref'] ?: $r['field_name'];
            }
        }

        return $missing;
    }

    /** Store a base64 signature data-URL as a PNG; return its relative path. */
    private function storeSignature(int $inspectionId, string $dataUrl): ?string
    {
        if (! preg_match('/^data:image\/(png|jpeg);base64,/', $dataUrl)) {
            return null;
        }
        $data = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if ($data === false) {
            return null;
        }

        $dir = WRITEPATH . 'uploads/' . $inspectionId;
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = 'signature.png';
        file_put_contents($dir . '/' . $name, $data);

        return $inspectionId . '/' . $name;
    }
}
