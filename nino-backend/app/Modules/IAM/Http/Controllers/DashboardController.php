<?php

namespace App\Modules\IAM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('admin.dashboard');
    }
}
