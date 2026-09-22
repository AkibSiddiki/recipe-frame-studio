<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    public function index(): View
    {
        return view('home', [
            'recentProjects' => $this->projectService->getRecent(),
        ]);
    }
}
