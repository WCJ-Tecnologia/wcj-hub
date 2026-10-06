<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('admin/dashboard', [
            'summary' => $this->dashboard->summary($user),
            'activity' => $user->isAdmin() ? $this->dashboard->recentActivity() : [],
        ]);
    }
}
