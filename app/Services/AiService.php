<?php

namespace App\Services;

/**
 * Thin wrapper around Claude (Anthropic) and ChatGPT (OpenAI) using
 * CodeIgniter's CURLRequest — no SDK, one code path for both providers.
 * Used to enrich a vehicle profile from the little we know (VIN, make, model…).
 */
class AiService
{
    /** Fields the AI is allowed to suggest (mapped to vehicle columns). */
    public const ENRICH_FIELDS = [
        'vehicle_type', 'derivative', 'body_type', 'doors', 'colour',
        'fuel', 'transmission', 'power_displacement', 'gvm', 'tare',
        'paint_finish', 'seating',
    ];

    public function isConfigured(): bool
    {
        if (! setting('Ai.enabled')) {
            return false;
        }

        return setting('Ai.provider') === 'openai'
            ? trim((string) setting('Ai.openaiKey')) !== ''
            : trim((string) setting('Ai.claudeKey')) !== '';
    }

    /**
     * Ask the model to fill spec fields it can confidently infer.
     *
     * @param array<string, mixed> $known  Known vehicle fields (vin, make, model…)
     *
     * @return array<string, mixed>  ['ok'=>bool, 'fields'=>[...], 'provider'=>..., 'error'=>?]
     */
    public function enrichVehicle(array $known): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'AI is not configured. Set it up under Settings.'];
        }

        $system = 'You are a vehicle-specification expert for a South African workshop. '
            . 'Given identifying details of a vehicle (VIN, make, model, engine number, year), '
            . 'return your best factual estimate of its specifications. '
            . 'Base answers on the VIN/make/model — the VIN world-manufacturer code and model are your main evidence. '
            . 'Only fill a field if you are reasonably confident; use null when unsure. '
            . 'NEVER guess registration number, licence number, owner, odometer/km, or any date. '
            . 'Respond with ONLY a JSON object, no prose, no markdown fences.';

        $fields = implode(', ', self::ENRICH_FIELDS);
        $user = "Vehicle details:\n" . json_encode($known, JSON_PRETTY_PRINT)
            . "\n\nReturn a JSON object with these keys (null if unknown): {$fields}.\n"
            . "Guidance: vehicle_type e.g. 'Passenger vehicle'; derivative is the full variant/trim "
            . "(e.g. '2.4 GD-6 RB DSL AT'); body_type e.g. 'Station wagon', 'Hatchback', 'SUV'; "
            . "doors as a number; power_displacement e.g. '110KW/2393CCM'; gvm and tare in kg with unit; "
            . "fuel e.g. 'Diesel'/'Petrol'; transmission 'Manual'/'Automatic'; seating as a number.";

        try {
            $raw = setting('Ai.provider') === 'openai'
                ? $this->callOpenAi($system, $user)
                : $this->callClaude($system, $user);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $data = $this->decodeJson($raw);
        if ($data === null) {
            return ['ok' => false, 'error' => 'AI returned an unreadable response.'];
        }

        // Keep only allowed, non-empty fields.
        $out = [];
        foreach (self::ENRICH_FIELDS as $k) {
            if (array_key_exists($k, $data) && $data[$k] !== null && $data[$k] !== '' && strtolower((string) $data[$k]) !== 'null') {
                $out[$k] = is_scalar($data[$k]) ? trim((string) $data[$k]) : $data[$k];
            }
        }

        return ['ok' => true, 'provider' => setting('Ai.provider'), 'fields' => $out];
    }

    /**
     * Draft a professional vehicle-conclusion paragraph from the report.
     *
     * @param array<string,mixed> $report
     * @return array{ok:bool,text?:string,error?:string}
     */
    public function conclusion(array $report): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'AI is not configured. Set it up under Settings.'];
        }

        $system = 'You are a senior vehicle inspector writing the concluding summary of a used-vehicle '
            . 'condition report for a South African workshop. Write a concise, professional paragraph (3–6 sentences) '
            . 'summarising the overall condition, the most important findings, and a clear recommendation. '
            . 'Be factual and neutral; do not invent issues not listed. Plain text only, no markdown, no headings.';

        try {
            $text = $this->ask($system, $this->reportDigest($report), false);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return ['ok' => true, 'text' => trim($text)];
    }

    /**
     * Produce a narrative summary + urgency triage of the findings.
     *
     * @param array<string,mixed> $report
     * @return array{ok:bool,summary?:string,triage?:array<int,array<string,string>>,error?:string}
     */
    public function insights(array $report): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'AI is not configured. Set it up under Settings.'];
        }

        $system = 'You are a vehicle-inspection analyst. From the report data, return ONLY a JSON object: '
            . '{"summary": "2-4 sentence plain-language overview for the customer", '
            . '"triage": [{"ref":"1.1","name":"Item","urgency":"now|soon|monitor","action":"short recommended action"}]}. '
            . 'urgency=now for safety/roadworthiness issues, soon for items to repair before purchase/finance, '
            . 'monitor for minor items. Only include items that actually have a finding. No prose outside the JSON, no markdown fences.';

        try {
            $raw  = $this->ask($system, $this->reportDigest($report), true);
            $data = $this->decodeJson($raw);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
        if ($data === null) {
            return ['ok' => false, 'error' => 'AI returned an unreadable response.'];
        }

        $triage = [];
        foreach (($data['triage'] ?? []) as $t) {
            $u = strtolower((string) ($t['urgency'] ?? ''));
            $triage[] = [
                'ref'     => (string) ($t['ref'] ?? ''),
                'name'    => (string) ($t['name'] ?? ''),
                'urgency' => in_array($u, ['now', 'soon', 'monitor'], true) ? $u : 'monitor',
                'action'  => (string) ($t['action'] ?? ''),
            ];
        }

        return ['ok' => true, 'summary' => trim((string) ($data['summary'] ?? '')), 'triage' => $triage, 'provider' => setting('Ai.provider')];
    }

    /**
     * Describe / assess a single photo (tread, damage, part condition).
     *
     * @return array{ok:bool,text?:string,error?:string}
     */
    public function analyzePhoto(string $base64, string $mime, string $context = ''): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'AI is not configured. Set it up under Settings.'];
        }

        $prompt = 'You are a vehicle inspector. In one short sentence (max ~15 words), describe what this photo shows '
            . 'and any visible defect or condition. Be specific and factual. Plain text only.'
            . ($context ? " Context: this photo is for the checklist item \"{$context}\"." : '');

        try {
            $text = setting('Ai.provider') === 'openai'
                ? $this->callOpenAiVision($prompt, $base64, $mime)
                : $this->callClaudeVision($prompt, $base64, $mime);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        return ['ok' => true, 'text' => trim($text)];
    }

    /** Compact text digest of a report for prompting. */
    private function reportDigest(array $report): string
    {
        $v = $report['vehicle'] ?? [];
        $c = service('intelligence')->compute($report);

        $lines = [];
        $lines[] = 'Vehicle: ' . trim(($v['year'] ?? '') . ' ' . ($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
        $lines[] = 'Condition score: ' . $c['score'] . '/100. Verdict: ' . ($c['verdict']['text'] ?? '');
        $lines[] = 'Severity counts — major: ' . $c['counts']['major'] . ', lesser: ' . $c['counts']['lesser'] . ', minor: ' . $c['counts']['minor'] . '.';
        $lines[] = 'Findings:';
        foreach ($c['findings'] as $f) {
            $lines[] = '- [' . ($f['ref'] ?? '') . '] ' . ($f['name'] ?? '') . ': ' . trim((string) ($f['comment'] ?? '') ?: 'noted') . ' (severity: ' . ($f['status'] ?? '') . ')';
        }
        if (count($c['findings']) === 0) {
            $lines[] = '- None; vehicle is all-clear on rated items.';
        }

        return implode("\n", $lines);
    }

    /** Provider-agnostic text/JSON ask. */
    private function ask(string $system, string $user, bool $json): string
    {
        return setting('Ai.provider') === 'openai'
            ? $this->callOpenAi($system, $user, $json)
            : $this->callClaude($system, $user);
    }

    // ------------------------------------------------------------------

    private function callClaude(string $system, string $user): string
    {
        $client = service('curlrequest', ['timeout' => 60, 'http_errors' => false]);
        $res = $client->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => trim((string) setting('Ai.claudeKey')),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'body' => json_encode([
                'model'      => setting('Ai.claudeModel') ?: 'claude-opus-4-8',
                'max_tokens' => 1024,
                'system'     => $system,
                'messages'   => [['role' => 'user', 'content' => $user]],
            ]),
        ]);

        $json = json_decode($res->getBody(), true);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('Claude API: ' . ($json['error']['message'] ?? ('HTTP ' . $res->getStatusCode())));
        }

        return (string) ($json['content'][0]['text'] ?? '');
    }

    private function callOpenAi(string $system, string $user, bool $json = true): string
    {
        $client = service('curlrequest', ['timeout' => 60, 'http_errors' => false]);
        $body = [
            'model'    => setting('Ai.openaiModel') ?: 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user . ($json ? "\n\nRespond in JSON." : '')],
            ],
        ];
        if ($json) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $res = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . trim((string) setting('Ai.openaiKey')),
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode($body),
        ]);

        $data = json_decode($res->getBody(), true);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('OpenAI API: ' . ($data['error']['message'] ?? ('HTTP ' . $res->getStatusCode())));
        }

        return (string) ($data['choices'][0]['message']['content'] ?? '');
    }

    private function callClaudeVision(string $prompt, string $base64, string $mime): string
    {
        $client = service('curlrequest', ['timeout' => 60, 'http_errors' => false]);
        $res = $client->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => trim((string) setting('Ai.claudeKey')),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'body' => json_encode([
                'model'      => setting('Ai.claudeModel') ?: 'claude-opus-4-8',
                'max_tokens' => 300,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $base64]],
                        ['type' => 'text', 'text' => $prompt],
                    ],
                ]],
            ]),
        ]);

        $json = json_decode($res->getBody(), true);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('Claude API: ' . ($json['error']['message'] ?? ('HTTP ' . $res->getStatusCode())));
        }

        return (string) ($json['content'][0]['text'] ?? '');
    }

    private function callOpenAiVision(string $prompt, string $base64, string $mime): string
    {
        $client = service('curlrequest', ['timeout' => 60, 'http_errors' => false]);
        $res = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . trim((string) setting('Ai.openaiKey')),
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model'    => setting('Ai.openaiModel') ?: 'gpt-4o',
                'messages' => [[
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $mime . ';base64,' . $base64]],
                    ],
                ]],
            ]),
        ]);

        $json = json_decode($res->getBody(), true);
        if ($res->getStatusCode() >= 400) {
            throw new \RuntimeException('OpenAI API: ' . ($json['error']['message'] ?? ('HTTP ' . $res->getStatusCode())));
        }

        return (string) ($json['choices'][0]['message']['content'] ?? '');
    }

    /**
     * Decode a JSON object from a model reply, tolerating ```json fences / stray prose.
     *
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $raw): ?array
    {
        $raw = trim($raw);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Extract the first {...} block.
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
