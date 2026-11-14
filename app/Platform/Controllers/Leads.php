<?php

namespace Platform\Controllers;

/** Website enquiries (contact form) and sign-ups. */
class Leads extends BaseController
{
    public function index()
    {
        $src = (string) $this->request->getGet('source');
        $b   = db_connect('platform')->table('leads')->orderBy('id', 'DESC');
        if ($src !== '') {
            $b->where('source', $src);
        }

        return $this->render('leads/index', ['rows' => $b->get(500)->getResultArray(), 'src' => $src]);
    }

    public function export()
    {
        $rows = array_map(static fn ($r) => [$r['created_at'], $r['source'], $r['name'], $r['email'], $r['phone'], $r['company'], $r['topic'], $r['message'], $r['handled'] ? 'yes' : 'no'],
            db_connect('platform')->table('leads')->orderBy('id', 'DESC')->get()->getResultArray());

        return $this->csv('leads-' . date('Y-m-d') . '.csv', ['Date', 'Source', 'Name', 'Email', 'Phone', 'Company', 'Topic / package', 'Message', 'Handled'], $rows);
    }

    public function handled(int $id)
    {
        $db  = db_connect('platform');
        $row = $db->table('leads')->where('id', $id)->get()->getRowArray();
        if ($row) {
            $db->table('leads')->where('id', $id)->update(['handled' => $row['handled'] ? 0 : 1]);
        }

        return redirect()->back();
    }
}
