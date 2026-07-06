<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * AI provider settings. Values here are defaults; the admin overrides them
 * on the Settings page and they persist in the `settings` table (read/written
 * via setting('Ai.<key>')).
 */
class Ai extends BaseConfig
{
    /** Active provider: 'claude' or 'openai'. */
    public string $provider = 'claude';

    /** Master on/off switch for AI features. */
    public bool $enabled = false;

    // --- Anthropic (Claude) ---
    public string $claudeKey   = '';
    public string $claudeModel = 'claude-opus-4-8';

    // --- OpenAI (ChatGPT) ---
    public string $openaiKey   = '';
    public string $openaiModel = 'gpt-4o';

    /**
     * Show a reference "ideal condition" hero image of the vehicle model,
     * fetched from Wikimedia/Wikipedia by make/model/year. Independent of the
     * AI provider switch above — no API key required.
     */
    public bool $showVehicleImage = false;
}
