<?php

namespace Tests\Fixtures\Imports;

/**
 * {@see Widget}, unique per company rather than globally — the stand-in for a
 * company-scoped master table (ARCHITECTURE.md §9.10).
 */
class CompanyWidget extends Widget
{
    protected $table = 'test_company_widgets';
}
