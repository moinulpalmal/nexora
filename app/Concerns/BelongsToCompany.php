<?php

namespace App\Concerns;

use App\Models\Admin\Company;
use App\Services\Admin\SelectedCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * A company-scoped model (ARCHITECTURE.md §9.10): every read confined to the
 * actor's selected company, and that company stamped on every create.
 *
 * - **Reads.** The `company` global scope applies whenever someone is signed
 *   in, so a list, an options query and a route-model binding all see one
 *   company — another company's row binds as a 404. A signed-in user with no
 *   selected company sees nothing (`company_id = 0` matches no row), which the
 *   page shows as "no company", never as "no data". Console code has no actor
 *   and is not scoped.
 * - **Writes.** `company_id` is filled from {@see SelectedCompany} when the
 *   caller did not set it. It is never fillable, so a browser-sent value is
 *   ignored rather than validated.
 * - **Counting for a guard** (§9.14) must drop the scope —
 *   `withoutGlobalScope('company')` — because whether a row is in use is a
 *   question about the database, not about what the actor can see.
 *
 * @mixin Model
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query): void {
            if (Auth::guest()) {
                return;
            }

            $query->where($query->qualifyColumn('company_id'), app(SelectedCompany::class)->id() ?? 0);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id') === null) {
                $model->setAttribute('company_id', app(SelectedCompany::class)->id());
            }
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
