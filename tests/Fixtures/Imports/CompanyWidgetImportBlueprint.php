<?php

namespace Tests\Fixtures\Imports;

/**
 * {@see WidgetImportBlueprint} with the pre-wired company scope switched on, so
 * the engine's company handling is proved before the Company master exists.
 */
class CompanyWidgetImportBlueprint extends WidgetImportBlueprint
{
    public function key(): string
    {
        return 'tests.company-widgets';
    }

    public function modelClass(): string
    {
        return CompanyWidget::class;
    }

    public function isCompanyScoped(): bool
    {
        return true;
    }
}
