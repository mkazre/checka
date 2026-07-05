<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Thin mPDF wrapper. Renders an HTML string (usually a rendered view) to a
 * PDF and either streams it inline, forces a download, or returns the bytes.
 */
class PdfService
{
    private function make(): Mpdf
    {
        @ini_set('memory_limit', '512M'); @set_time_limit(120);
        $tmp = WRITEPATH . 'cache/mpdf';
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        return new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 12,
            'margin_bottom' => 14,
            'margin_left'   => 10,
            'margin_right'  => 10,
            'tempDir'       => $tmp,
            'default_font'  => 'dejavusans',
        ]);
    }

    /**
     * Render HTML to PDF and return the raw bytes.
     */
    public function toString(string $html, string $title = 'Report'): string
    {
        $pdf = $this->make();
        $pdf->SetTitle($title);
        $pdf->WriteHTML($html);

        return $pdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * Render HTML to PDF and stream it to the browser (inline preview).
     */
    public function stream(string $html, string $filename, string $title = 'Report'): void
    {
        $pdf = $this->make();
        $pdf->SetTitle($title);
        $pdf->WriteHTML($html);
        $pdf->Output($filename, Destination::INLINE);
    }

    /**
     * Render HTML to PDF and force a download.
     */
    public function download(string $html, string $filename, string $title = 'Report'): void
    {
        $pdf = $this->make();
        $pdf->SetTitle($title);
        $pdf->WriteHTML($html);
        $pdf->Output($filename, Destination::DOWNLOAD);
    }
}
