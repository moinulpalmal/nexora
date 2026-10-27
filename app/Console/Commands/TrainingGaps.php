<?php

namespace App\Console\Commands;

use App\Services\Training\TrainingGapFinder;
use App\Services\Training\TrainingRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('training:gaps')]
#[Description('List every screen, guide, screenshot or translation the training module is missing')]
class TrainingGaps extends Command
{
    /**
     * Read-only and always exits 0: the list is a to-do. The gate is
     * `TrainingGapsTest`, which fails the suite on the same findings.
     */
    public function handle(TrainingGapFinder $finder): int
    {
        $gaps = $finder->find();
        $pending = count(array_filter(TrainingRegistry::all(), fn (array $page): bool => $page['status'] === 'pending'));
        $total = count(TrainingRegistry::all());

        $this->components->info(sprintf('%d registered screens, %d written, %d pending.', $total, $total - $pending, $pending));

        if ($gaps === []) {
            $this->components->info('No gaps.');

            return self::SUCCESS;
        }

        $this->table(
            ['Check', 'Subject', 'What to do'],
            array_map(fn (array $gap): array => [$gap['check'], $gap['subject'], $gap['message']], $gaps),
        );

        $this->components->warn(count($gaps).' gap(s).');

        return self::SUCCESS;
    }
}
