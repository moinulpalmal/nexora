<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\ListRequest;
use App\Models\Admin\SentMail;

class SentMailIndexRequest extends ListRequest
{
    protected function sortable(): array
    {
        return SentMail::SORTABLE;
    }

    protected function filterable(): array
    {
        return SentMail::FILTERABLE;
    }

    /**
     * A log reads newest first.
     */
    protected function defaultDirection(): string
    {
        return 'desc';
    }
}
