<?php

namespace App\Commands;

use App\Models\InspectionModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Recomputes the cached condition score + verdict on every completed
 * inspection using the current InspectionIntelligence formula. Run this once
 * after a scoring-logic change so historical reports pick up the fix too.
 *
 *   php spark checka:rescore
 */
class CheckaRescore extends BaseCommand
{
    protected $group       = 'Checka';
    protected $name        = 'checka:rescore';
    protected $description = 'Recompute the cached condition score + verdict on every completed inspection.';

    public function run(array $params): int
    {
        $model  = model(InspectionModel::class);
        $engine = service('intelligence');

        $rows = $model->where('status', 'completed')->findAll();
        CLI::write('Recomputing scores for ' . count($rows) . ' completed inspection(s)…');

        foreach ($rows as $row) {
            $report = $model->assembleReport($row['report_uid']);
            if ($report === null) {
                continue;
            }
            $verdict = $engine->overallVerdict($report);
            $model->update($row['id'], [
                'score'          => $engine->conditionScore($report),
                'verdict_status' => $verdict['status'],
                'verdict_text'   => $verdict['text'],
            ]);
            CLI::write("  #{$row['id']} {$row['report_uid']} → score {$model->find($row['id'])['score']}", 'dark_gray');
        }

        CLI::write(CLI::color('Done.', 'green'));

        return EXIT_SUCCESS;
    }
}
