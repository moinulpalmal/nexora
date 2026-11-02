<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkRequest;
use App\Http\Requests\ProjectManagement\CertificationIndexRequest;
use App\Http\Requests\ProjectManagement\CertificationRequest;
use App\Models\ProjectManagement\Certification;
use App\Models\ProjectManagement\CertificationCategory;
use App\Services\ProjectManagement\CertificationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Projects → Certifications: a group-wide catalogue of standards
 * (ARCHITECTURE.md §9.10), shaped as {@see TenderTypeController} with a
 * group-wide category offered as {@see SupplierController} offers supplier
 * categories. The page works whether or not a company is selected. Every
 * write arrives from a dialog on it and answers `back()` with a toast.
 *
 * No spreadsheet import (owner, 2026-11-02): the standard set is seeded by
 * `CertificationSeeder`, the rest entered by hand.
 */
class CertificationController extends Controller
{
    public function __construct(protected CertificationService $certifications) {}

    public function index(CertificationIndexRequest $request): Response
    {
        $filters = $request->filters();

        $certifications = Certification::query()
            ->with('category:id,code,name')
            ->when($request->view() === 'trashed', fn ($query) => $query->onlyTrashed())
            ->filterColumns($filters['filter'])
            ->sortBy($filters['sort'], $filters['direction'])
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(fn (Certification $certification): array => [
                'id' => $certification->id,
                'certification_category_id' => $certification->certification_category_id,
                'category_name' => $certification->category->name,
                'code' => $certification->code,
                'name' => $certification->name,
                'issuing_body' => $certification->issuing_body,
                'version' => $certification->version,
                'description' => $certification->description,
                'status' => $certification->status->value,
                'created_at' => $certification->created_at?->toDateString(),
                'deleted_at' => $certification->deleted_at?->toDateString(),
            ]);

        return Inertia::render('projects/certifications/index', [
            'certifications' => $certifications,
            'statuses' => RecordStatus::options(),
            'categories' => $this->categoryOptions(),
            'sortable' => Certification::SORTABLE,
            'filterable' => Certification::FILTERABLE,
            'perPageOptions' => CertificationIndexRequest::PER_PAGE_OPTIONS,
            'filters' => $filters,
            'counts' => [
                'active' => Certification::query()->count(),
                'trashed' => Certification::onlyTrashed()->count(),
            ],
        ]);
    }

    public function store(CertificationRequest $request): RedirectResponse
    {
        $this->certifications->create($request->certificationAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification created.')]);

        return back();
    }

    public function update(CertificationRequest $request, Certification $certification): RedirectResponse
    {
        $this->certifications->update($certification, $request->certificationAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification updated.')]);

        return back();
    }

    public function destroy(Certification $certification): RedirectResponse
    {
        $this->certifications->delete($certification);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification moved to Deleted.')]);

        return back();
    }

    public function restore(Certification $certification): RedirectResponse
    {
        $this->certifications->restore($certification);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification restored.')]);

        return back();
    }

    public function forceDelete(Certification $certification): RedirectResponse
    {
        $this->certifications->forceDelete($certification);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Certification permanently deleted.')]);

        return back();
    }

    /**
     * Delete the ticked certifications (Active tab).
     */
    public function bulkDestroy(BulkRequest $request): RedirectResponse
    {
        $done = $this->certifications->bulkDelete(Certification::query()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count certification moved to Deleted.|:count certifications moved to Deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Permanently delete the ticked certifications (Deleted tab).
     */
    public function bulkForceDelete(BulkRequest $request): RedirectResponse
    {
        $done = $this->certifications->bulkForceDelete(Certification::onlyTrashed()->whereKey($request->ids())->get());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice(':count certification permanently deleted.|:count certifications permanently deleted.', $done, ['count' => $done]),
        ]);

        return back();
    }

    /**
     * Every live category, inactive ones marked: the form offers the active
     * ones plus the certification's own, the list filter all of them — as
     * `SupplierController::categoryOptions()`.
     *
     * @return list<array{value: int, label: string, hint: string, status: string}>
     */
    private function categoryOptions(): array
    {
        return array_values(CertificationCategory::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status'])
            ->map(fn (CertificationCategory $category): array => [
                'value' => $category->id,
                'label' => $category->name,
                'hint' => $category->code,
                'status' => $category->status->value,
            ])
            ->all());
    }
}
