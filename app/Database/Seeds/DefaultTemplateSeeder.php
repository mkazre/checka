<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the default "WheelsTurn Standard Inspection" template — the exact
 * 20-section checklist from the design sample, with semantic roles/measures
 * pre-tagged so the report intelligence (score, tyre health, body map) works
 * out of the box. Admins can clone/edit this in the form builder.
 */
class DefaultTemplateSeeder extends Seeder
{
    /** Sections {1,2,5,6,7,8} are safety-critical (weigh double in the score). */
    private array $critical = [1, 2, 5, 6, 7, 8];

    public function run(): void
    {
        // Don't double-seed.
        $existing = $this->db->table('templates')->where('is_default', 1)->get()->getRowArray();
        if ($existing) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('templates')->insert([
            'name'        => 'WheelsTurn Standard Inspection',
            'description' => 'The standard 20-section vehicle inspection checklist.',
            'version'     => '1.0',
            'is_active'   => 1,
            'is_default'  => 1,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
        $templateId = $this->db->insertID();

        foreach ($this->template() as $section) {
            $this->db->table('template_sections')->insert([
                'template_id' => $templateId,
                'idx'         => $section['n'],
                'title'       => $section['title'],
                'is_critical' => in_array($section['n'], $this->critical, true) ? 1 : 0,
                'sort'        => $section['n'],
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
            $sectionId = $this->db->insertID();

            $sort = 0;
            foreach ($section['items'] as $item) {
                $this->db->table('template_fields')->insert([
                    'section_id'   => $sectionId,
                    'ref'          => $item['ref'],
                    'name'         => $item['name'],
                    'type'         => $item['type'] ?? 'rating',
                    'role'         => $item['role'] ?? null,
                    'options'      => isset($item['options']) ? json_encode($item['options']) : null,
                    'measure'      => $item['measure'] ?? null,
                    'is_sub'       => ! empty($item['sub']) ? 1 : 0,
                    'allow_photos' => isset($item['photos']) ? (int) $item['photos'] : 1,
                    'sort'         => $sort++,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }
    }

    /**
     * The checklist. Ratings by default; sub-info fields are `text`.
     * Roles: tread_depth, dot_code, body_zone_*, service_date, service_km.
     */
    private function template(): array
    {
        return [
            ['n' => 1, 'title' => 'Road Test', 'items' => [
                ['ref' => '1.1', 'name' => 'Road Test'],
            ]],
            ['n' => 2, 'title' => 'Engine Checks', 'items' => [
                ['ref' => '2.1', 'name' => 'Oil Level and Condition'],
                ['ref' => '2.2', 'name' => 'Oil Leaks'],
                ['ref' => '2.3', 'name' => 'Engine Mountings'],
                ['ref' => '2.4', 'name' => 'Diagnostic & Dashboard Fault Messages'],
                ['ref' => '2.5', 'name' => 'Pipe and Harness Routings'],
                ['ref' => '2.6', 'name' => 'Turbo and Boost Pipes Condition'],
                ['ref' => '2.7', 'name' => 'Engine Condition / Noise'],
                ['ref' => '2.8', 'name' => 'Undercarriage Washed'],
            ]],
            ['n' => 3, 'title' => 'Clutch Checks', 'items' => [
                ['ref' => '3.1', 'name' => 'Hydraulic Leaks / Cable and Adjustment'],
                ['ref' => '3.2', 'name' => 'Pressure and Release Bearing Noise'],
                ['ref' => '3.3', 'name' => 'Clutch Slipping / Shudder'],
            ]],
            ['n' => 4, 'title' => 'Gearbox Checks', 'items' => [
                ['ref' => '4.1', 'name' => 'Manual Synchro Noise and Operation'],
                ['ref' => '4.2', 'name' => 'Auto Shift Patterns'],
                ['ref' => '4.3', 'name' => 'Gearbox Oil Leaks'],
                ['ref' => '4.4', 'name' => 'Gearbox Condition'],
                ['ref' => '4.5', 'name' => 'Gearbox Mounting'],
                ['ref' => '4.6', 'name' => '4x4 Functionality (if applicable)'],
            ]],
            ['n' => 5, 'title' => 'Suspension Checks', 'items' => [
                ['ref' => '5.1', 'name' => 'Struts and Mountings — Noise and Free Play'],
                ['ref' => '5.2', 'name' => 'Shock Absorbers for Leaks and Operation'],
                ['ref' => '5.3', 'name' => 'Suspension Mountings and Bolts'],
                ['ref' => '5.4', 'name' => 'Ball Joints and Stabilizer Links'],
            ]],
            ['n' => 6, 'title' => 'Drive Train Checks', 'items' => [
                ['ref' => '6.1', 'name' => 'Oil Leaks — Drive Train'],
                ['ref' => '6.2', 'name' => 'Condition of Propshaft and Universals'],
                ['ref' => '6.3', 'name' => 'Drive Shafts Noise, Boots and Seals'],
                ['ref' => '6.4', 'name' => 'Differential for Noise / Leaks'],
            ]],
            ['n' => 7, 'title' => 'Steering Checks', 'items' => [
                ['ref' => '7.1', 'name' => 'Oil Leaks — Steering'],
                ['ref' => '7.2', 'name' => 'Centralised and Play'],
                ['ref' => '7.3', 'name' => 'Rack-ends, Tie-rod Ends & Boots'],
                ['ref' => '7.4', 'name' => 'Operation (locking)'],
                ['ref' => '7.5', 'name' => 'Power-Steering Operation'],
                ['ref' => '7.6', 'name' => 'Power-Steering Oil Level (if applicable)'],
            ]],
            ['n' => 8, 'title' => 'Brake Checks', 'items' => [
                ['ref' => '8.1', 'name' => 'Brake Fluid Leaks and Fluid Condition'],
                ['ref' => '8.2', 'name' => 'Front Pads and Discs'],
                ['ref' => '8.3', 'name' => 'Rear Drums / Pads and Discs'],
                ['ref' => '8.4', 'name' => 'Footbrake Function'],
                ['ref' => '8.5', 'name' => 'Handbrake Function'],
                ['ref' => '8.6', 'name' => 'ABS Functionality'],
            ]],
            ['n' => 9, 'title' => 'Cooling System Check', 'items' => [
                ['ref' => '9.1', 'name' => 'Cooling System Cap (Leaks and Pressure)'],
                ['ref' => '9.2', 'name' => 'Condition of Pipes and Hoses'],
                ['ref' => '9.3', 'name' => 'Operation of Electric Fan / Visco Coupling'],
                ['ref' => '9.4', 'name' => 'Coolant Level and Condition'],
                ['ref' => '9.5', 'name' => 'Thermostat Check'],
                ['ref' => '9.6', 'name' => 'Radiator'],
            ]],
            ['n' => 10, 'title' => 'Check Tyres and Wheels', 'items' => [
                ['ref' => '10.1', 'name' => 'Mags, Caps, Trims'],
                ['ref' => '10.2', 'name' => 'Wheel Nuts / Studs'],
                ['ref' => '10.3', 'name' => 'Tread Depth (Front Right)', 'role' => 'tread_depth', 'measure' => 'mm'],
                ['ref' => '10.3.1', 'name' => 'Tyre Size', 'sub' => true, 'type' => 'text', 'measure' => 'size', 'photos' => 0],
                ['ref' => '10.3.2', 'name' => 'Production Date', 'sub' => true, 'role' => 'dot_code', 'measure' => 'week/yr'],
                ['ref' => '10.4', 'name' => 'Tread Depth (Front Left)', 'role' => 'tread_depth', 'measure' => 'mm'],
                ['ref' => '10.4.1', 'name' => 'Tyre Size', 'sub' => true, 'type' => 'text', 'measure' => 'size', 'photos' => 0],
                ['ref' => '10.4.2', 'name' => 'Production Date', 'sub' => true, 'role' => 'dot_code', 'measure' => 'week/yr'],
                ['ref' => '10.5', 'name' => 'Tread Depth (Rear Left)', 'role' => 'tread_depth', 'measure' => 'mm'],
                ['ref' => '10.5.1', 'name' => 'Tyre Size', 'sub' => true, 'type' => 'text', 'measure' => 'size', 'photos' => 0],
                ['ref' => '10.5.2', 'name' => 'Production Date', 'sub' => true, 'role' => 'dot_code', 'measure' => 'week/yr'],
                ['ref' => '10.6', 'name' => 'Tread Depth (Rear Right)', 'role' => 'tread_depth', 'measure' => 'mm'],
                ['ref' => '10.6.1', 'name' => 'Tyre Size', 'sub' => true, 'type' => 'text', 'measure' => 'size', 'photos' => 0],
                ['ref' => '10.6.2', 'name' => 'Production Date', 'sub' => true, 'role' => 'dot_code', 'measure' => 'week/yr'],
            ]],
            ['n' => 11, 'title' => 'Instrumentation Checks', 'items' => [
                ['ref' => '11.1', 'name' => 'Operation'],
                ['ref' => '11.2', 'name' => 'Illumination'],
                ['ref' => '11.3', 'name' => 'Heater and Fan Operation'],
                ['ref' => '11.4', 'name' => 'Aircon Operation'],
            ]],
            ['n' => 12, 'title' => 'Light Checks', 'items' => [
                ['ref' => '12.1', 'name' => 'Boot Lights & Interior Lights'],
                ['ref' => '12.2', 'name' => 'Head Lamps, Indicators, Park, Fog, Brake Lights'],
                ['ref' => '12.3', 'name' => 'Hazard Lights'],
            ]],
            ['n' => 13, 'title' => 'Electrical Checks', 'items' => [
                ['ref' => '13.1', 'name' => 'Alternator and Charge Rate'],
                ['ref' => '13.2', 'name' => 'Battery Condition and Terminals'],
                ['ref' => '13.3', 'name' => 'Harness Condition and Routing'],
                ['ref' => '13.4', 'name' => 'V-Belt Condition'],
            ]],
            ['n' => 14, 'title' => 'Exhaust System Checks', 'items' => [
                ['ref' => '14.1', 'name' => 'Leaks'],
                ['ref' => '14.2', 'name' => 'Mountings and Hangers'],
                ['ref' => '14.3', 'name' => 'Catalytic Converter'],
            ]],
            ['n' => 15, 'title' => 'Fuel System Checks', 'items' => [
                ['ref' => '15.1', 'name' => 'Tank and Pipes for Leaks and Damage'],
            ]],
            ['n' => 16, 'title' => 'Wiper System Checks', 'items' => [
                ['ref' => '16.1', 'name' => 'Front Wiper and Washer Operation'],
                ['ref' => '16.2', 'name' => 'Rear Wiper and Washer Operation'],
            ]],
            ['n' => 17, 'title' => 'Doors and Windows', 'items' => [
                ['ref' => '17.1', 'name' => 'Door Locks and Handles Operation'],
                ['ref' => '17.2', 'name' => 'Window Operation'],
                ['ref' => '17.3', 'name' => 'Windscreen'],
                ['ref' => '17.4', 'name' => 'Bonnet Operation'],
                ['ref' => '17.5', 'name' => 'Boot Operation and Lock'],
            ]],
            ['n' => 18, 'title' => 'Interior Checks', 'items' => [
                ['ref' => '18.1', 'name' => 'Spare Wheel, Tool Kit, Jack, Spanner, Triangle'],
                ['ref' => '18.2', 'name' => 'Service Book'],
                ['ref' => '18.2.1', 'name' => 'Service Date', 'sub' => true, 'type' => 'text', 'role' => 'service_date', 'measure' => 'date', 'photos' => 0],
                ['ref' => '18.2.2', 'name' => 'Service Kilometer', 'sub' => true, 'type' => 'text', 'role' => 'service_km', 'measure' => 'km', 'photos' => 0],
                ['ref' => '18.3', 'name' => 'Safety Belt Functioning'],
                ['ref' => '18.4', 'name' => 'Key Remote Operation'],
                ['ref' => '18.5', 'name' => 'Spare Key'],
                ['ref' => '18.6', 'name' => 'Radio, Speakers and Steering Controls'],
                ['ref' => '18.7', 'name' => 'Interior Trim and Upholstery'],
                ['ref' => '18.8', 'name' => 'Seat Adjusters'],
                ['ref' => '18.9', 'name' => 'Rear View and Side View Mirror Operation'],
                ['ref' => '18.10', 'name' => 'Hooter'],
            ]],
            ['n' => 19, 'title' => 'Exterior Checks', 'items' => [
                ['ref' => '19.1', 'name' => 'Structural Damage (visual only)'],
                ['ref' => '19.2', 'name' => 'Body Work, Panel Misalignment and Rust'],
                ['ref' => '19.3', 'name' => 'Scratches and Dents — Front', 'role' => 'body_zone_front'],
                ['ref' => '19.4', 'name' => 'Scratches and Dents — Back', 'role' => 'body_zone_back'],
                ['ref' => '19.5', 'name' => 'Scratches and Dents — Right', 'role' => 'body_zone_right'],
                ['ref' => '19.6', 'name' => 'Scratches and Dents — Left', 'role' => 'body_zone_left'],
            ]],
            ['n' => 20, 'title' => 'Smoke Test', 'items' => [
                ['ref' => '20.1', 'name' => 'Smoke Test (Cold and Warm Start)'],
            ]],
        ];
    }
}
