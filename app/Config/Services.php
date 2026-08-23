<?php

namespace Config;

use App\Services\InspectionIntelligence;
use App\Services\PdfService;
use App\Services\ReportPdfService;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 */
class Services extends BaseService
{
    /**
     * The Checka scoring / verdict / tyre / body-map engine.
     * Mirrors the JS logic from the report sample — one source of truth
     * used by both the web report and the PDF.
     */
    public static function intelligence(bool $getShared = true): InspectionIntelligence
    {
        if ($getShared) {
            return static::getSharedInstance('intelligence');
        }

        return new InspectionIntelligence();
    }

    /**
     * mPDF wrapper used to render inspection reports to PDF.
     */
    public static function pdf(bool $getShared = true): PdfService
    {
        if ($getShared) {
            return static::getSharedInstance('pdf');
        }

        return new PdfService();
    }

    /**
     * Builds the inspection report PDF from an assembled report array.
     * Shared by the admin download, the client-portal download, and the
     * emailed attachment.
     */
    public static function reportPdf(bool $getShared = true): ReportPdfService
    {
        if ($getShared) {
            return static::getSharedInstance('reportPdf');
        }

        return new ReportPdfService();
    }
}
