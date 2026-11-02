<?php

namespace App\Services\Admin;

use App\Enums\Admin\AuditEvent;
use App\Models\Admin\Country;
use App\Services\Hrm\DesignationService;

/**
 * Every write to a country, as {@see DesignationService}. Countries are
 * group-wide: no company is stamped.
 *
 * **No `deletionBlocker()` yet** (ARCHITECTURE.md §9.14): nothing references
 * a country, so a guard could only return null. The first table that gains a
 * `country_id` (clients, employees) adds the blocker here, with its
 * `restrictOnDelete()` foreign key, and the bulk writes start reporting the
 * rows it keeps.
 */
class CountryService
{
    public function __construct(private readonly AuditRecorder $audits) {}

    /**
     * @param  array{name: string, iso2: string, iso3: string, numeric_code: string|null, dial_code: string|null, currency_code: string|null, nationality: string|null, status: string}  $attributes
     */
    public function create(array $attributes): Country
    {
        return Country::create($attributes);
    }

    /**
     * @param  array{name?: string, iso2?: string, iso3?: string, numeric_code?: string|null, dial_code?: string|null, currency_code?: string|null, nationality?: string|null, status?: string}  $attributes
     */
    public function update(Country $country, array $attributes): void
    {
        $country->update($attributes);
    }

    /**
     * Soft delete: the country moves to Deleted and keeps its name and ISO
     * codes reserved.
     */
    public function delete(Country $country): void
    {
        $country->delete();
    }

    public function restore(Country $country): void
    {
        $country->restore();
    }

    /**
     * Delete for good, recording what the country was first.
     */
    public function forceDelete(Country $country): void
    {
        $this->audits->record($country, AuditEvent::ForceDeleted, [
            'name' => $country->name,
            'iso2' => $country->iso2,
            'iso3' => $country->iso3,
        ]);

        $country->forceDelete();
    }

    /**
     * Soft delete each country, one at a time so each is audited.
     *
     * @param  iterable<Country>  $countries
     * @return int how many were deleted
     */
    public function bulkDelete(iterable $countries): int
    {
        return $this->each($countries, $this->delete(...));
    }

    /**
     * Delete each soft-deleted country for good, recording each.
     *
     * @param  iterable<Country>  $countries
     * @return int how many were deleted
     */
    public function bulkForceDelete(iterable $countries): int
    {
        return $this->each($countries, $this->forceDelete(...));
    }

    /**
     * @param  iterable<Country>  $countries
     * @param  callable(Country): void  $write
     */
    private function each(iterable $countries, callable $write): int
    {
        $done = 0;

        foreach ($countries as $country) {
            $write($country);
            $done++;
        }

        return $done;
    }
}
