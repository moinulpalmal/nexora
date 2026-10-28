<?php

namespace App\Http\Controllers\ProjectManagement;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Projects module's overview.
 *
 * A proof of concept (ARCHITECTURE.md §5, 🧪 POC): the page shows sample data
 * held in the frontend and is replaced when the first real Projects slice lands.
 * The route, its permission and this controller stay.
 */
class DashboardController extends Controller
{
    /**
     * Render the overview.
     */
    public function __invoke(): Response
    {
        return Inertia::render('projects/dashboard');
    }
}
