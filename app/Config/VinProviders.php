<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * VIN History Check data sources (feature: vinCheck). Shapes + defaults only
 * — the admin's actual choices (enabled flags, API keys) persist in the
 * `settings` table via setting('VinProviders.<key>') and are edited on the
 * Settings page. All paid slots default OFF; nothing is billed until an
 * admin pastes a key in and ticks "enabled".
 *
 * The FREE source is NHTSA's vPIC registry — genuinely free, no key, no
 * rate-limit billing. It decodes VIN structure (make/model/year/body/engine)
 * well for any manufacturer that registers global VIN patterns with NHTSA,
 * but it is a US registry: coverage/accuracy for SA-market-only models can
 * be thin, and it never returns accident/stolen/service history — nobody
 * publishes that for free anywhere.
 *
 * The three paid slots are named, pre-wired adapters (see app/Services/Vin/)
 * for providers with self-serve API keys as of the last time this was
 * checked. Vendors change their endpoints/response shape without notice —
 * verify against their current docs when you sign up, and adjust the
 * matching adapter class if a field comes back empty that their docs say
 * should be there. Each adapter stores the raw provider response in the
 * report alongside whatever it managed to map, so nothing is lost even if
 * the mapping goes stale.
 */
class VinProviders extends BaseConfig
{
    /** Free: NHTSA vPIC VIN decode (specs only, no history). */
    public bool $freeEnabled = true;

    /**
     * Paid slot 1 — VinAudit (vinaudit.com). US NMVTIS-backed: title/salvage
     * status, reported theft, accident/damage records, odometer history.
     * Strong for US-market VINs; verify SA coverage before relying on it.
     */
    public bool   $p1Enabled = false;
    public string $p1Label   = 'VinAudit';
    public string $p1ApiKey  = '';
    public string $p1BaseUrl = 'https://api.vinaudit.com/v2/vinhistory';

    /**
     * Paid slot 2 — CarsXE (carsxe.com). Vehicle history + specs API:
     * title checks, theft records, event history.
     */
    public bool   $p2Enabled = false;
    public string $p2Label   = 'CarsXE';
    public string $p2ApiKey  = '';
    public string $p2BaseUrl = 'https://api.carsxe.com/history';

    /**
     * Paid slot 3 — Didit South Africa NATIS registry validation
     * (didit.me). Matches a VIN/chassis number against the SA national
     * vehicle register (NATIS) — confirms the VIN is real and registered,
     * and returns the matched registry fields. It is a registry match, not
     * an accident/theft history report — SAPS stolen-vehicle data and SA
     * accident history are not exposed as a public API by anyone at the
     * time this was wired up.
     */
    public bool   $p3Enabled = false;
    public string $p3Label   = 'Didit NATIS (South Africa)';
    public string $p3ApiKey  = '';
    public string $p3BaseUrl = 'https://verification.didit.me/v3/database-validation/';
}
