<?php

namespace App\Services;

/**
 * Parses the decoded string from a South African vehicle-licence-disc
 * PDF417 barcode (eNaTIS format) into structured vehicle fields.
 *
 * The barcode decodes to a "%"-delimited string. Field order (eNaTIS):
 *   … | licence_no | registration | description | make | model | colour
 *       | VIN | engine_number | expiry(YYYY-MM-DD) | …
 *
 * Rather than trusting fixed indexes (which vary by disc version), we ANCHOR
 * on the VIN — a 17-char code that's unmistakable — and read the surrounding
 * fields relative to it, validating dates as we go. Everything is best-effort
 * and the caller shows it for confirmation before saving.
 */
class LicenceDiscParser
{
    /**
     * @return array<string, string> Parsed fields (only those confidently found)
     */
    public function parse(string $raw): array
    {
        $raw = trim($raw);
        // Discs use "%"; a few readers return "|". Support both.
        $delim  = (substr_count($raw, '%') >= substr_count($raw, '|')) ? '%' : '|';
        $tokens = array_map('trim', explode($delim, $raw));
        $tokens = array_values(array_filter($tokens, static fn ($t) => $t !== ''));

        $out = ['disc_raw' => $raw];

        // Find the VIN: 17 chars, letters+digits, no I/O/Q.
        $vinIndex = null;
        foreach ($tokens as $i => $t) {
            if (preg_match('/^[A-HJ-NPR-Z0-9]{17}$/i', $t)) {
                $vinIndex = $i;
                break;
            }
        }

        if ($vinIndex !== null) {
            $at = static function (array $tokens, int $i): ?string {
                return $tokens[$i] ?? null;
            };

            $out['vin']          = strtoupper($tokens[$vinIndex]);
            $out['licence_no']   = $at($tokens, $vinIndex - 6) ?? '';
            $out['plate']        = $at($tokens, $vinIndex - 5) ?? '';
            $out['body_type']    = $at($tokens, $vinIndex - 4) ?? '';
            $out['make']         = $at($tokens, $vinIndex - 3) ?? '';
            $out['model']        = $at($tokens, $vinIndex - 2) ?? '';
            $out['colour']       = $at($tokens, $vinIndex - 1) ?? '';
            $out['engine_number'] = $at($tokens, $vinIndex + 1) ?? '';
        }

        // Expiry / dates: collect all YYYY-MM-DD tokens; latest = expiry.
        $dates = [];
        foreach ($tokens as $t) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) {
                $dates[] = $t;
            }
        }
        if ($dates !== []) {
            sort($dates);
            $out['licence_expiry'] = end($dates);
            if (count($dates) > 1) {
                $out['date_of_test'] = $dates[0];
            }
        }

        // Clean up: drop empties, and don't let the engine field accidentally
        // capture a date.
        foreach (['engine_number', 'make', 'model', 'colour', 'body_type', 'licence_no', 'plate'] as $k) {
            if (isset($out[$k]) && ($out[$k] === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $out[$k]))) {
                unset($out[$k]);
            }
        }

        return $out;
    }
}
