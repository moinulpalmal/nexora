<?php

namespace Tests\Fixtures\Imports;

use App\Services\Imports\ImportRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Installs the fixture importers for one test: their tables, their registry
 * entries and their routes — everything a real importer gets from a migration,
 * `ImportRegistry::BLUEPRINTS` and its module's route file.
 *
 * The routes follow the convention every real importer uses
 * (ARCHITECTURE.md §9.13): `{resource}.import.{store,review,confirm,rejects,
 * cancel,template}`, all behind `permission:{module}.{resource}.import`.
 */
class ImportFixtures
{
    /** @var array<string, class-string> resource => controller */
    public const array CONTROLLERS = [
        'widgets' => WidgetImportController::class,
        'gadgets' => GadgetImportController::class,
        'company-widgets' => CompanyWidgetImportController::class,
    ];

    public static function install(): void
    {
        self::createTables();
        self::bindRegistry();
        self::registerRoutes();
    }

    private static function createTables(): void
    {
        Schema::create('test_widgets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->char('status', 1)->default('A');
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('test_company_widgets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('code', 20);
            $table->string('name', 100);
            $table->char('status', 1)->default('A');
            $table->foreignId('inserted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });
    }

    private static function bindRegistry(): void
    {
        app()->instance(ImportRegistry::class, new class extends ImportRegistry
        {
            protected const array BLUEPRINTS = [
                'tests.widgets' => WidgetImportBlueprint::class,
                'tests.gadgets' => GadgetImportBlueprint::class,
                'tests.company-widgets' => CompanyWidgetImportBlueprint::class,
            ];
        });
    }

    private static function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])->prefix('tests')->name('tests.')->group(function (): void {
            foreach (self::CONTROLLERS as $resource => $controller) {
                $gate = "permission:tests.{$resource}.import";

                Route::get($resource, fn (): string => "{$resource} list")->name("{$resource}.index");
                Route::get("{$resource}/import/template", [$controller, 'template'])->middleware($gate)->name("{$resource}.import.template");
                Route::post("{$resource}/import", [$controller, 'store'])->middleware($gate)->name("{$resource}.import.store");
                Route::post("{$resource}/imports/{sheetImport}/review", [$controller, 'review'])->middleware($gate)->name("{$resource}.import.review");
                Route::post("{$resource}/imports/{sheetImport}/confirm", [$controller, 'confirm'])->middleware($gate)->name("{$resource}.import.confirm");
                Route::get("{$resource}/imports/{sheetImport}/rejects", [$controller, 'rejects'])->middleware($gate)->name("{$resource}.import.rejects");
                Route::delete("{$resource}/imports/{sheetImport}", [$controller, 'cancel'])->middleware($gate)->name("{$resource}.import.cancel");
            }
        });

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();
    }
}
