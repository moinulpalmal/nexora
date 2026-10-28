<?php

namespace Tests\Fixtures\Imports;

use App\Concerns\Audited;
use App\Concerns\HasStatus;
use App\Contracts\Auditable;
use App\Observers\ActorObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * The import engine's stand-in master table — a code and a name, unique on the
 * code — until a real master table imports (ARCHITECTURE.md §9.13). Shaped like
 * one: audited, actor-stamped, retired by status. Its table is created by
 * {@see ImportFixtures::createTables()} inside the tests that use it.
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $code
 * @property string $name
 * @property int|null $inserted_by
 * @property int|null $last_updated_by
 */
#[ObservedBy(ActorObserver::class)]
#[Fillable(['company_id', 'code', 'name', 'status'])]
class Widget extends Model implements Auditable
{
    use Audited, HasStatus;

    protected $table = 'test_widgets';
}
