<?php

namespace App\Controllers;

use App\Models\FieldModel;
use App\Models\SectionModel;
use App\Models\TemplateModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * The form builder. Admin-only (guarded by the group:admin filter in Routes).
 */
class Templates extends BaseController
{
    public function index()
    {
        return view('templates/index', [
            'title'     => 'Inspection Templates',
            'templates' => model(TemplateModel::class)->orderBy('id', 'ASC')->findAll(),
        ]);
    }

    public function builder(int $id)
    {
        $template = model(TemplateModel::class)->withTree($id);
        if ($template === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('templates/builder', [
            'title'     => 'Edit Template — ' . $template['name'],
            'template'  => $template,
            'fieldTypes' => FieldModel::TYPES,
            'roles'      => FieldModel::ROLES,
        ]);
    }

    public function create()
    {
        $id = model(TemplateModel::class)->insert([
            'name'        => $this->request->getPost('name') ?: 'Untitled Template',
            'description' => $this->request->getPost('description'),
            'created_by'  => auth()->id(),
        ], true);

        return redirect()->to('templates/' . $id . '/builder')->with('message', 'Template created.');
    }

    public function update(int $id)
    {
        model(TemplateModel::class)->update($id, [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'is_active'   => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return $this->done('Template updated.', 'templates/' . $id . '/builder');
    }

    public function delete(int $id)
    {
        $t = model(TemplateModel::class)->find($id);
        if ($t && $t['is_default']) {
            return redirect()->to('templates')->with('error', 'The default template cannot be deleted.');
        }
        model(TemplateModel::class)->delete($id);

        return redirect()->to('templates')->with('message', 'Template deleted.');
    }

    // ---------------- Sections ----------------

    public function addSection(int $templateId)
    {
        $sectionModel = model(SectionModel::class);
        $nextIdx      = (int) ($sectionModel->where('template_id', $templateId)->selectMax('idx')->first()['idx'] ?? 0) + 1;

        $sectionModel->insert([
            'template_id' => $templateId,
            'idx'         => $nextIdx,
            'title'       => $this->request->getPost('title') ?: ('Section ' . $nextIdx),
            'is_critical' => $this->request->getPost('is_critical') ? 1 : 0,
            'sort'        => $nextIdx,
        ]);

        return $this->done('Section added.', 'templates/' . $templateId . '/builder');
    }

    public function updateSection(int $id)
    {
        $model   = model(SectionModel::class);
        $section = $model->find($id);
        if ($section === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        $model->update($id, [
            'title'       => $this->request->getPost('title'),
            'is_critical' => $this->request->getPost('is_critical') ? 1 : 0,
        ]);

        return $this->done('Section updated.', 'templates/' . $section['template_id'] . '/builder');
    }

    public function deleteSection(int $id)
    {
        $model   = model(SectionModel::class);
        $section = $model->find($id);
        if ($section === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        $model->delete($id); // fields cascade via FK

        return $this->done('Section deleted.', 'templates/' . $section['template_id'] . '/builder');
    }

    // ---------------- Fields ----------------

    public function addField(int $sectionId)
    {
        $section = model(SectionModel::class)->find($sectionId);
        if ($section === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $fieldModel = model(FieldModel::class);
        $nextSort   = (int) ($fieldModel->where('section_id', $sectionId)->selectMax('sort')->first()['sort'] ?? 0) + 1;

        $fieldModel->insert([
            'section_id'   => $sectionId,
            'ref'          => $this->request->getPost('ref'),
            'name'         => $this->request->getPost('name') ?: 'New field',
            'type'         => $this->request->getPost('type') ?: 'rating',
            'role'         => $this->request->getPost('role') ?: null,
            'options'      => $this->normalizeOptions($this->request->getPost('options')),
            'measure'      => $this->request->getPost('measure') ?: null,
            'is_sub'       => $this->request->getPost('is_sub') ? 1 : 0,
            'allow_photos' => $this->request->getPost('allow_photos') !== null ? 1 : 0,
            'sort'         => $nextSort,
        ]);

        return $this->done('Field added.', 'templates/' . $section['template_id'] . '/builder');
    }

    public function updateField(int $id)
    {
        $fieldModel = model(FieldModel::class);
        $field      = $fieldModel->find($id);
        if ($field === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $fieldModel->update($id, [
            'ref'          => $this->request->getPost('ref'),
            'name'         => $this->request->getPost('name'),
            'type'         => $this->request->getPost('type') ?: 'rating',
            'role'         => $this->request->getPost('role') ?: null,
            'options'      => $this->normalizeOptions($this->request->getPost('options')),
            'measure'      => $this->request->getPost('measure') ?: null,
            'is_sub'       => $this->request->getPost('is_sub') ? 1 : 0,
            'allow_photos' => $this->request->getPost('allow_photos') !== null ? 1 : 0,
        ]);

        $section = model(SectionModel::class)->find($field['section_id']);

        return $this->done('Field updated.', 'templates/' . $section['template_id'] . '/builder');
    }

    public function deleteField(int $id)
    {
        $fieldModel = model(FieldModel::class);
        $field      = $fieldModel->find($id);
        if ($field === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        $section = model(SectionModel::class)->find($field['section_id']);
        $fieldModel->delete($id);

        return $this->done('Field deleted.', 'templates/' . $section['template_id'] . '/builder');
    }

    /** Persist a new order for sections and fields (AJAX drag-drop). */
    public function reorder()
    {
        $sections = $this->request->getPost('sections') ?? [];
        $fields   = $this->request->getPost('fields') ?? [];

        $sectionModel = model(SectionModel::class);
        foreach ($sections as $sort => $sectionId) {
            $sectionModel->update((int) $sectionId, ['sort' => (int) $sort]);
        }
        $fieldModel = model(FieldModel::class);
        foreach ($fields as $sectionId => $ids) {
            foreach ($ids as $sort => $fieldId) {
                $fieldModel->update((int) $fieldId, ['section_id' => (int) $sectionId, 'sort' => (int) $sort]);
            }
        }

        return $this->response->setJSON(['ok' => true]);
    }

    // ------------------------------------------------------------------

    private function normalizeOptions(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $parts = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $raw))));

        return $parts ? json_encode($parts) : null;
    }

    private function done(string $message, string $redirect)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'message' => $message]);
        }

        return redirect()->to($redirect)->with('message', $message);
    }
}
