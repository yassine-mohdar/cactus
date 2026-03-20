<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\IAM\Services\DashboardMetricsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetricsService $dashboardMetrics)
    {
        $user = auth()->user();
        $dashboard = $dashboardMetrics->build($user);

        return view('admin.dashboard', compact('dashboard'));
    }
}
