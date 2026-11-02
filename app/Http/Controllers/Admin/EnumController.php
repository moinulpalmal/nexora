<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\EnumService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin → Enums: the read-only catalogue of every enum — its purpose, its
 * cases, and the value stored for each. Enums are defined in code; this page
 * only explains them.
 */
class EnumController extends Controller
{
    public function __construct(protected EnumService $enums) {}

    public function index(): Response
    {
        return Inertia::render('admin/enums/index', [
            'enums' => $this->enums->catalogue(),
            'modules' => $this->enums->moduleOptions(),
        ]);
    }
}
