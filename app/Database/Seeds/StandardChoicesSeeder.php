<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Populates the standardised "assessment" radio options (choices) for each
 * item of the default WheelsTurn template. Wording is tailored per item.
 * Safe to re-run — it only fills fields that don't already have choices.
 */
class StandardChoicesSeeder extends Seeder
{
    /** ref => [option1, option2, option3, option4] */
    private function map(): array
    {
        return [
            // 1 Road Test
            '1.1' => ['Drives well', 'Minor concern', 'Significant fault', 'N/A'],
            // 2 Engine
            '2.1' => ['Level & condition good', 'Slightly low / discoloured', 'Low / contaminated', 'N/A'],
            '2.2' => ['No leaks', 'Minor seepage', 'Active leak', 'N/A'],
            '2.3' => ['Solid', 'Slight wear', 'Perished / broken', 'N/A'],
            '2.4' => ['No faults', 'Minor codes', 'Active fault lights', 'N/A'],
            '2.5' => ['Secure & tidy', 'Minor chafing', 'Damaged / loose', 'N/A'],
            '2.6' => ['Good', 'Minor wear', 'Split / leaking', 'N/A'],
            '2.7' => ['Runs smooth', 'Slight noise', 'Knock / rough', 'N/A'],
            '2.8' => ['Clean', 'Partially clean', 'Not washed', 'N/A'],
            // 3 Clutch
            '3.1' => ['No leaks / adjusted', 'Minor seepage', 'Leaking / out of adjustment', 'N/A'],
            '3.2' => ['Quiet', 'Slight noise', 'Noisy', 'N/A'],
            '3.3' => ['Engages cleanly', 'Slight shudder', 'Slipping', 'N/A'],
            // 4 Gearbox
            '4.1' => ['Shifts well', 'Slight notchiness', 'Crunches / noisy', 'N/A'],
            '4.2' => ['Shifts smoothly', 'Slight hesitation', 'Harsh / erratic', 'N/A'],
            '4.3' => ['No leaks', 'Minor seepage', 'Active leak', 'N/A'],
            '4.4' => ['Good', 'Fair', 'Poor', 'N/A'],
            '4.5' => ['Solid', 'Slight wear', 'Perished / broken', 'N/A'],
            '4.6' => ['Engages correctly', 'Slow to engage', 'Not working', 'N/A'],
            // 5 Suspension
            '5.1' => ['No play / quiet', 'Slight play', 'Excessive play / noise', 'N/A'],
            '5.2' => ['No leaks / firm', 'Slight seepage', 'Leaking / worn', 'N/A'],
            '5.3' => ['Secure', 'Slightly loose', 'Loose / damaged', 'N/A'],
            '5.4' => ['No play', 'Slight play', 'Worn / excessive play', 'N/A'],
            // 6 Drive Train
            '6.1' => ['No leaks', 'Minor seepage', 'Active leak', 'N/A'],
            '6.2' => ['Good', 'Slight wear', 'Worn / play', 'N/A'],
            '6.3' => ['Intact & quiet', 'Minor wear', 'Split boot / noisy', 'N/A'],
            '6.4' => ['Quiet / dry', 'Slight noise / seepage', 'Noisy / leaking', 'N/A'],
            // 7 Steering
            '7.1' => ['No leaks', 'Minor seepage', 'Active leak', 'N/A'],
            '7.2' => ['Centred / no play', 'Slight play', 'Excessive play', 'N/A'],
            '7.3' => ['Good', 'Slight wear', 'Worn / split boot', 'N/A'],
            '7.4' => ['Locks correctly', 'Slight notch', 'Faulty', 'N/A'],
            '7.5' => ['Light & smooth', 'Slightly heavy', 'Heavy / noisy', 'N/A'],
            '7.6' => ['Level good', 'Slightly low', 'Low', 'N/A'],
            // 8 Brakes
            '8.1' => ['No leaks / clean', 'Slightly discoloured', 'Leaking / contaminated', 'N/A'],
            '8.2' => ['Good life', 'Half worn', 'Replace', 'N/A'],
            '8.3' => ['Good life', 'Half worn', 'Replace', 'N/A'],
            '8.4' => ['Firm & effective', 'Slightly soft', 'Poor / spongy', 'N/A'],
            '8.5' => ['Holds firmly', 'Needs adjustment', 'Not holding', 'N/A'],
            '8.6' => ['Working', 'Warning intermittent', 'Not working', 'N/A'],
            // 9 Cooling
            '9.1' => ['Holds pressure', 'Slight seepage', 'Leaking / no pressure', 'N/A'],
            '9.2' => ['Good', 'Slightly perished', 'Cracked / leaking', 'N/A'],
            '9.3' => ['Works', 'Slow / intermittent', 'Not working', 'N/A'],
            '9.4' => ['Level & clean', 'Slightly low / dirty', 'Low / contaminated', 'N/A'],
            '9.5' => ['Regulating well', 'Slightly slow', 'Stuck / faulty', 'N/A'],
            '9.6' => ['Good', 'Minor corrosion', 'Leaking / blocked', 'N/A'],
            // 10 Tyres & Wheels
            '10.1' => ['No damage', 'Light scratches', 'Damaged / missing', 'N/A'],
            '10.2' => ['All present & torqued', 'Slightly loose', 'Missing / damaged', 'N/A'],
            '10.3' => ['Good (>4mm)', 'Fair (3–4mm)', 'Replace (<3mm)', 'N/A'],
            '10.3.2' => ['Within 5 yrs', '5–6 yrs', 'Over 6 yrs / aged', 'N/A'],
            '10.4' => ['Good (>4mm)', 'Fair (3–4mm)', 'Replace (<3mm)', 'N/A'],
            '10.4.2' => ['Within 5 yrs', '5–6 yrs', 'Over 6 yrs / aged', 'N/A'],
            '10.5' => ['Good (>4mm)', 'Fair (3–4mm)', 'Replace (<3mm)', 'N/A'],
            '10.5.2' => ['Within 5 yrs', '5–6 yrs', 'Over 6 yrs / aged', 'N/A'],
            '10.6' => ['Good (>4mm)', 'Fair (3–4mm)', 'Replace (<3mm)', 'N/A'],
            '10.6.2' => ['Within 5 yrs', '5–6 yrs', 'Over 6 yrs / aged', 'N/A'],
            // 11 Instrumentation
            '11.1' => ['All working', 'Minor fault', 'Not working', 'N/A'],
            '11.2' => ['All lit', 'Some dim / out', 'Not working', 'N/A'],
            '11.3' => ['Working', 'Weak / noisy', 'Not working', 'N/A'],
            '11.4' => ['Cools well', 'Weak cooling', 'Not cooling', 'N/A'],
            // 12 Lights
            '12.1' => ['All working', 'Some out', 'Not working', 'N/A'],
            '12.2' => ['All working', 'Some out', 'Multiple out', 'N/A'],
            '12.3' => ['Working', 'Intermittent', 'Not working', 'N/A'],
            // 13 Electrical
            '13.1' => ['Charging correctly', 'Slightly low', 'Not charging', 'N/A'],
            '13.2' => ['Good / clean', 'Ageing', 'Weak / corroded', 'N/A'],
            '13.3' => ['Secure & tidy', 'Minor wear', 'Damaged', 'N/A'],
            '13.4' => ['Good', 'Slightly worn', 'Cracked / glazed', 'N/A'],
            // 14 Exhaust
            '14.1' => ['No leaks', 'Minor blow', 'Leaking', 'N/A'],
            '14.2' => ['Secure', 'Slightly loose', 'Broken / missing', 'N/A'],
            '14.3' => ['Intact', 'Minor concern', 'Damaged / missing', 'N/A'],
            // 15 Fuel
            '15.1' => ['No leaks / damage', 'Minor surface damage', 'Leaking / damaged', 'N/A'],
            // 16 Wipers
            '16.1' => ['Clears well', 'Smears / weak', 'Not working', 'N/A'],
            '16.2' => ['Clears well', 'Smears / weak', 'Not working', 'N/A'],
            // 17 Doors & Windows
            '17.1' => ['All working', 'Stiff / minor', 'Faulty', 'N/A'],
            '17.2' => ['All working', 'Slow / one faulty', 'Multiple faulty', 'N/A'],
            '17.3' => ['No damage', 'Chip(s)', 'Cracked', 'N/A'],
            '17.4' => ['Opens / closes well', 'Stiff', 'Faulty', 'N/A'],
            '17.5' => ['Opens / closes well', 'Stiff', 'Faulty', 'N/A'],
            // 18 Interior
            '18.1' => ['All present', 'Some missing', 'Missing', 'N/A'],
            '18.2' => ['Present & stamped', 'Present, gaps', 'Missing', 'N/A'],
            '18.3' => ['All working', 'One faulty', 'Multiple faulty', 'N/A'],
            '18.4' => ['Works', 'Weak / intermittent', 'Not working', 'N/A'],
            '18.5' => ['Present & works', 'Present, not tested', 'Missing', 'N/A'],
            '18.6' => ['All working', 'Minor fault', 'Not working', 'N/A'],
            '18.7' => ['Good', 'Minor wear', 'Torn / damaged', 'N/A'],
            '18.8' => ['All working', 'Stiff', 'Faulty', 'N/A'],
            '18.9' => ['All working', 'One faulty', 'Multiple faulty', 'N/A'],
            '18.10' => ['Working', 'Weak', 'Not working', 'N/A'],
            // 19 Exterior
            '19.1' => ['No damage', 'Previous repair', 'Structural damage', 'N/A'],
            '19.2' => ['Good', 'Minor misalignment', 'Rust / damage', 'N/A'],
            '19.3' => ['No damage', 'Minor cosmetic', 'Significant damage', 'N/A'],
            '19.4' => ['No damage', 'Minor cosmetic', 'Significant damage', 'N/A'],
            '19.5' => ['No damage', 'Minor cosmetic', 'Significant damage', 'N/A'],
            '19.6' => ['No damage', 'Minor cosmetic', 'Significant damage', 'N/A'],
            // 20 Smoke Test
            '20.1' => ['Clean', 'Slight smoke', 'Excessive smoke', 'N/A'],
        ];
    }

    public function run(): void
    {
        $tpl = $this->db->table('templates')->where('is_default', 1)->get()->getRowArray();
        if (! $tpl) {
            return;
        }

        $sectionIds = $this->db->table('template_sections')
            ->select('id')->where('template_id', $tpl['id'])->get()->getResultArray();
        $sectionIds = array_column($sectionIds, 'id');
        if ($sectionIds === []) {
            return;
        }

        foreach ($this->map() as $ref => $choices) {
            $this->db->table('template_fields')
                ->whereIn('section_id', $sectionIds)
                ->where('ref', $ref)
                ->where('type', 'rating')
                ->groupStart()->where('choices', null)->orWhere('choices', '')->groupEnd()
                ->update(['choices' => json_encode($choices)]);
        }
    }
}
