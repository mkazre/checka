<?php

namespace Platform\Controllers;

use CodeIgniter\Controller;

abstract class BaseController extends Controller
{
    protected $helpers = ['form', 'url', 'platform'];

    protected function render(string $view, array $data = []): string
    {
        return view('Platform\Views\\' . $view, $data);
    }

    /** Send rows as a CSV download (Excel-friendly, formula-injection safe). */
    protected function csv(string $filename, array $header, iterable $rows)
    {
        $fh = fopen('php://temp', 'w+');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, $header, ',', '"', '');
        foreach ($rows as $r) {
            $r = array_map(static fn ($v) => (is_string($v) && preg_match('/^[=+\-@\t\r]/', $v)) ? "'" . $v : $v, array_values($r));
            fputcsv($fh, $r, ',', '"', '');
        }
        rewind($fh);
        $body = stream_get_contents($fh);
        fclose($fh);

        return $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($body);
    }
}
