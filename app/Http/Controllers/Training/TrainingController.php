<?php

namespace App\Http\Controllers\Training;

use App\Http\Controllers\Controller;
use App\Services\Training\TrainingRegistry;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The training manual — see documentation/training.md.
 *
 * Open to every signed-in user, with no permission: the person who cannot yet
 * open a screen is the person who needs to read what it does. A guide holds no
 * application data, only prose and screenshots of fictional records.
 */
class TrainingController extends Controller
{
    /**
     * The manual's table of contents.
     */
    public function index(): Response
    {
        return Inertia::render('training/index', [
            'sections' => TrainingRegistry::grouped(),
        ]);
    }

    /**
     * One guide, linkable and printable.
     */
    public function show(string $key): Response
    {
        $page = TrainingRegistry::present($key);

        abort_if($page === null, 404);

        return Inertia::render('training/show', [
            'page' => $page,
            'sections' => TrainingRegistry::grouped(),
        ]);
    }

    /**
     * The same guide for the header's help dialog, fetched when it opens, so
     * no screen pays for screenshot lookups until someone asks for help.
     */
    public function panel(string $key): JsonResponse
    {
        $page = TrainingRegistry::present($key);

        abort_if($page === null, 404);

        return response()->json([
            'page' => $page,
            'sections' => TrainingRegistry::grouped(),
        ]);
    }
}
