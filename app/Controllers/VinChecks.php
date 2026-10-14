<?php

namespace App\Controllers;

use App\Models\VehicleModel;
use App\Models\VinReportModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Services;
use Mpdf\QrCode\Output\Png;
use Mpdf\QrCode\QrCode;

/**
 * VIN History Check module (feature: vinCheck). Runs a VIN through the
 * enabled providers (free NHTSA decode + whichever paid providers are
 * switched on in Settings), archives every check, and lets a saved report
 * be viewed, downloaded as PDF, or shared by email / QR code.
 */
class VinChecks extends BaseController
{
    private function guard()
    {
        if (! feature('vinCheck')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /** Archive list. */
    public function index()
    {
        $this->guard();
        $search = trim((string) $this->request->getGet('q'));
        $model  = model(VinReportModel::class);

        return view('vin_checks/index', [
            'title'   => 'VIN History Checks',
            'q'       => $search,
            'reports' => $model->archive(100, 0, $search),
        ]);
    }

    /** Run-a-check form. */
    public function new()
    {
        $this->guard();
        $vehicle = null;
        if ($vid = (int) $this->request->getGet('vehicle_id')) {
            $vehicle = model(VehicleModel::class)->find($vid);
        }

        return view('vin_checks/new', [
            'title'   => 'Run VIN Check',
            'vin'     => (string) $this->request->getGet('vin'),
            'vehicle' => $vehicle,
        ]);
    }

    /** Runs the check and saves it to the archive. */
    public function create()
    {
        $this->guard();
        $vin = strtoupper(trim((string) $this->request->getPost('vin')));
        if (strlen($vin) < 11) {
            return redirect()->back()->withInput()->with('error', 'Enter a full VIN (17 characters) to run a check.');
        }

        // Several providers run synchronously, one after another — on
        // shared hosting the default execution time limit can be too tight
        // for that plus saving the report, which previously surfaced as a
        // generic "something went wrong" page even though the check itself
        // had actually completed.
        @set_time_limit(90);

        try {
            $data = Services::vinCheck()->run($vin);
            if (! $data['ok']) {
                $errs = array_map(static fn ($f) => $f['provider'] . ': ' . $f['error'], $data['providersFailed']);

                return redirect()->back()->withInput()->with('error', 'No provider returned data. ' . implode(' ', $errs));
            }

            $vehicleId = (int) $this->request->getPost('vehicle_id') ?: null;
            $uid       = bin2hex(random_bytes(10));

            model(VinReportModel::class)->insert([
                'report_uid'       => $uid,
                'vin'              => $vin,
                'vehicle_id'       => $vehicleId,
                'requested_by'     => auth()->id(),
                'headline'         => Services::vinCheck()->headline($data),
                'make'             => $data['specs']['make'] ?? null,
                'model'            => $data['specs']['model'] ?? null,
                'year'             => $data['specs']['year'] ?? null,
                'providers_used'   => implode(', ', $data['providersUsed']),
                'providers_failed' => json_encode($data['providersFailed']),
                'data_json'        => json_encode($data),
            ], true);

            return redirect()->to('vin-checks/' . $uid);
        } catch (\Throwable $e) {
            log_message('error', 'VIN check create() failed: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'The check could not be completed — please try again. If this keeps happening, check writable/logs for details.');
        }
    }

    /** Full report view (logged-in). */
    public function view(string $uid)
    {
        $this->guard();
        $row = model(VinReportModel::class)->findByUid($uid);
        if ($row === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('vin_checks/view', [
            'title'      => 'VIN Check — ' . $row['vin'],
            'row'        => $row,
            'data'       => model(VinReportModel::class)->payload($row),
            'shareUrl'   => site_url('vin-check/' . $uid),
            'showPhotos' => true,
        ]);
    }

    /** Public, read-only landing for the QR code / shared link — no login required. */
    public function publicView(string $uid)
    {
        $row = model(VinReportModel::class)->findByUid($uid);
        if ($row === null || ! feature('vinCheck')) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('vin_checks/share', [
            'title'      => 'VIN Check — ' . $row['vin'],
            'row'        => $row,
            'data'       => model(VinReportModel::class)->payload($row),
            'showPhotos' => false,
        ]);
    }

    /** Downloadable PDF. */
    public function pdf(string $uid)
    {
        $this->guard();
        $row = model(VinReportModel::class)->findByUid($uid);
        if ($row === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $pdf = Services::vinReportPdf()->build($row, model(VinReportModel::class)->payload($row));

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf['filename'] . '"')
            ->setBody($pdf['bytes']);
    }

    /** AJAX: email the report (link + PDF attachment + QR) to an address. */
    public function send(string $uid)
    {
        $this->guard();
        $row = model(VinReportModel::class)->findByUid($uid);
        if ($row === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'error' => 'Report not found.']);
        }

        $to = trim((string) $this->request->getPost('email'));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Enter a valid email address.']);
        }
        if (trim((string) setting('Notify.fromEmail')) === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Email sender is not configured in Settings.']);
        }

        $shareUrl = site_url('vin-check/' . $uid);
        $pdf      = Services::vinReportPdf()->build($row, model(VinReportModel::class)->payload($row));

        $email = \App\Services\Mailer::newEmail();
        $email->setFrom((string) setting('Notify.fromEmail'), (string) (setting('Notify.fromName') ?: 'CheckA+'));
        $email->setTo($to);
        $email->setSubject('VIN History Check — ' . $row['vin']);
        $email->setMessage($this->emailHtml($row, $shareUrl));

        $tmpPath = WRITEPATH . 'cache/mail_attach/' . bin2hex(random_bytes(8)) . '.pdf';
        if (! is_dir(dirname($tmpPath))) {
            @mkdir(dirname($tmpPath), 0775, true);
        }
        @file_put_contents($tmpPath, $pdf['bytes']);
        if (is_file($tmpPath)) {
            $email->attach($tmpPath, '', $pdf['filename']);
        }

        $sent = $email->send(false);
        if (is_file($tmpPath)) {
            @unlink($tmpPath);
        }

        if (! $sent) {
            log_message('error', 'VIN report email failed: ' . $email->printDebugger(['headers']));

            return $this->response->setJSON(['ok' => false, 'error' => 'Email could not be sent (check SMTP settings).']);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function delete(string $uid)
    {
        $this->guard();
        $model = model(VinReportModel::class);
        $row   = $model->findByUid($uid);
        if ($row !== null) {
            $model->delete($row['id']);
        }

        return redirect()->to('vin-checks')->with('message', 'VIN check deleted.');
    }

    /** Base64 PNG data URI of a QR code pointing at the public share link — used in the emailed HTML and the PDF. */
    public static function qrDataUri(string $url): string
    {
        try {
            $qr  = new QrCode($url);
            $out = new Png();

            return 'data:image/png;base64,' . base64_encode($out->output($qr, 240));
        } catch (\Throwable $e) {
            log_message('error', 'VIN check QR generation failed: ' . $e->getMessage());

            return '';
        }
    }

    private function emailHtml(array $row, string $link): string
    {
        $logo = base_url('assets/img/logo.png');
        $qr   = self::qrDataUri($link);

        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:auto;color:#2d2d2d">'
            . '<div style="text-align:center;border-bottom:3px solid #37b34a;padding:16px"><img src="' . esc($logo) . '" alt="CheckA+" style="height:46px"></div>'
            . '<div style="padding:22px 20px">'
            . '<h2 style="margin:0 0 6px">VIN History Check</h2>'
            . '<p style="color:#555;line-height:1.6">A VIN history check for <b>' . esc($row['vin']) . '</b>'
            . (! empty($row['headline']) ? ' — <b>' . esc($row['headline']) . '</b>' : '') . ' is attached as a PDF.</p>'
            . '<p style="text-align:center;margin:26px 0">'
            . '<a href="' . esc($link) . '" style="background:#37b34a;color:#fff;text-decoration:none;padding:13px 26px;border-radius:9px;font-weight:bold;display:inline-block">View report online</a></p>'
            . ($qr !== '' ? '<p style="text-align:center;margin:10px 0"><img src="' . $qr . '" width="140" height="140" alt="QR code"></p>' : '')
            . '<p style="color:#888;font-size:12px;line-height:1.5;text-align:center">Scan the QR code or open:<br>' . esc($link) . '</p>'
            . '</div>'
            . '<div style="text-align:center;color:#aaa;font-size:11px;padding:14px">Powered by CheckA+ by WheelsTurn</div></div>';
    }
}
