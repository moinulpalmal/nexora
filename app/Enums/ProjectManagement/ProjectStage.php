<?php

namespace App\Enums\ProjectManagement;

use App\Concerns\ProvidesOptions;
use App\Contracts\LabelledEnum;
use App\Enums\RecordStatus;

/**
 * Where a client project stands in its life.
 *
 * Every project starts at {@see self::initial()} and normally moves
 * Upcoming → Evaluation → Running → Completed, but a project is in exactly
 * **one** stage at a time and may move from any stage to any other — skipping
 * ahead or going back (owner, 2026-11-02). Leaving a {@see self::isClosed()}
 * stage is a *reopen*, which needs its own permission; that rule lives in the
 * service, not here.
 *
 * **Not the Active/Inactive flag.** A project also carries
 * {@see RecordStatus}: Inactive archives it out of the pickers
 * (hours, tasks, billing) at any stage, while `Cancelled` records the business
 * outcome. The two are independent.
 *
 * Kebab-case values, readable in the database and the audit log.
 */
enum ProjectStage: string implements LabelledEnum
{
    use ProvidesOptions;

    /** A lead has been noted; nothing assessed yet. */
    case Upcoming = 'upcoming';

    /** Being assessed or quoted before a contract, awaiting the client's decision. */
    case Evaluation = 'evaluation';

    /** Contracted and in delivery. */
    case Running = 'running';

    /** Paused, by the client or the organization. */
    case OnHold = 'on-hold';

    /** Delivered. */
    case Completed = 'completed';

    /** Lost before contract, or abandoned. */
    case Cancelled = 'cancelled';

    public static function id(): string
    {
        return 'project-stage';
    }

    /**
     * The stage every new project starts in.
     */
    public static function initial(): self
    {
        return self::Upcoming;
    }

    /**
     * Whether the project's life has ended here, so moving it out is a reopen.
     */
    public function isClosed(): bool
    {
        return match ($this) {
            self::Completed, self::Cancelled => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => __('Upcoming'),
            self::Evaluation => __('Evaluation'),
            self::Running => __('Running'),
            self::OnHold => __('On Hold'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }
}
