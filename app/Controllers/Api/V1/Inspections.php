<?php

namespace App\Controllers\Api\V1;

use App\Models\InspectionModel;
use App\Models\InspectionValueModel;
use App\Models\TemplateModel;
use App\Models\VehicleModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * Inspection API for the future React / Flutter client. Returns the same
 * data model as the web report so the two stay in lock-step.
 */
class Inspections extends ResourceController
{
    protected $modelName = InspectionModel::class;
    protected $format    = 'json';

    public function index()
    {
        return $this->respond(['data' => $this->model->listWithVehicle($this->request->getGet('status'))]);
    }

    public function show($uid = null)
    {
        $report = $this->model->assembleReport((string) $uid);

        return $report ? $this->respond(['data' => $report]) : $this->failNotFound('Inspection not found');
    }

    /** Full computed report (score, verdict, tyres, body map, findings). */
    public function report($uid = null)
    {
        $report = $this->model->assembleReport((string) $uid);
        if ($report === null) {
            return $this->failNotFound('Inspection not found');
        }

        return $this->respond([
            'data'    => $report,
            'compute' => service('intelligence')->compute($report),
        ]);
    }

    public function create()
    {
        $body       = $this->request->getJSON(true) ?: $this->request->getPost();
        if (limit_reached('inspections_month', model(InspectionModel::class)->where('created_at >=', date('Y-m-01 00:00:00'))->countAllResults())) {
            return $this->fail('Your package\'s monthly inspection limit (' . plan_limit('inspections_month') . ') has been reached.', 402);
        }
        $templateId = (int) ($body['template_id'] ?? 0);
        $template   = model(TemplateModel::class)->withTree($templateId);
        if ($template === null) {
            return $this->failValidationErrors('A valid template_id is required.');
        }

        $vehicleId = (int) ($body['vehicle_id'] ?? 0);
        if ($vehicleId === 0 && ! empty($body['vehicle'])) {
            $vehicleId = model(VehicleModel::class)->insert($body['vehicle'] + ['created_by' => auth()->id()], true);
        }

        $uid = bin2hex(random_bytes(8));
        $id  = $this->model->insert([
            'report_uid'      => $uid,
            'template_id'     => $templateId,
            'vehicle_id'      => $vehicleId ?: null,
            'mechanic_id'     => auth()->id(),
            'status'          => 'draft',
            'inspection_date' => $body['inspection_date'] ?? date('Y-m-d'),
            'created_by'      => auth()->id(),
        ], true);

        $this->snapshot((int) $id, $template);

        return $this->respondCreated(['data' => $this->model->assembleReport($uid)]);
    }

    public function update($uid = null)
    {
        $inspection = $this->model->findByUid((string) $uid);
        if ($inspection === null) {
            return $this->failNotFound('Inspection not found');
        }
        $body       = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $valueModel = model(InspectionValueModel::class);

        foreach (($body['values'] ?? []) as $v) {
            if (empty($v['id'])) {
                continue;
            }
            $patch = array_intersect_key($v, array_flip(['rating', 'comment', 'value_text', 'value_number', 'cost']));
            if ($patch !== []) {
                $valueModel->update((int) $v['id'], $patch);
            }
        }

        $update = [];
        foreach (['conclusion', 'status', 'inspection_date'] as $k) {
            if (isset($body[$k])) {
                $update[$k] = $body[$k];
            }
        }
        if (isset($body['meta'])) {
            $update['meta'] = json_encode($body['meta']);
        }
        if ($update !== []) {
            $this->model->update($inspection['id'], $update);
        }

        // recompute cache
        $report  = $this->model->assembleReport((string) $uid);
        $engine  = service('intelligence');
        $verdict = $engine->overallVerdict($report);
        $this->model->update($inspection['id'], [
            'score'          => $engine->conditionScore($report),
            'verdict_status' => $verdict['status'],
            'verdict_text'   => $verdict['text'],
        ]);

        return $this->respond(['data' => $this->model->assembleReport((string) $uid)]);
    }

    public function delete($uid = null)
    {
        $inspection = $this->model->findByUid((string) $uid);
        if ($inspection === null) {
            return $this->failNotFound('Inspection not found');
        }
        $this->model->delete($inspection['id']);

        return $this->respondDeleted(['uid' => $uid]);
    }

    private function snapshot(int $inspectionId, array $template): void
    {
        $rows = [];
        $sort = 0;
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
            model(InspectionValueModel::class)->insertBatch($rows);
        }
    }
}
