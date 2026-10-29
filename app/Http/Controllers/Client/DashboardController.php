<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->load(['profile', 'courses.progresses', 'orders.package']);

        $latestOrder = $user->orders->where('payment_status', 'approved')->first()
            ?: $user->orders->first();

        return view('client.dashboard', compact('user', 'latestOrder'));
    }
}
