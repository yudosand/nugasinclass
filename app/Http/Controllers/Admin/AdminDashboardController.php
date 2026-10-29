<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Http\Request;
use App\Support\AcademicProgressSummary;
use App\Support\ClientHandlerSchema;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        ClientHandlerSchema::ensure();
        $totalClients = User::where('role', 'client')->count();
        $pendingPayments = Order::where('payment_status', 'paid')->count();
        $approvedOrders = Order::where('payment_status', 'approved')->count();
        $totalPackages = Package::where('is_active', true)->count();

        $recentOrders = Order::with(['user', 'package'])->latest()->take(10)->get();
        $search = $request->validate(['client_search' => 'nullable|string|max:100'])['client_search'] ?? '';
        $progressClients = User::where('role', 'client')->with(['courses.progresses', 'handlerAdmin:id,name'])
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', '%'.$search.'%')->orWhere('nomor_hp', 'like', '%'.$search.'%')))
            ->orderBy('name')->paginate(15, ['*'], 'clients_page')->withQueryString();
        $progressClients->each(fn ($client) => $client->setAttribute('session_summary', AcademicProgressSummary::forCourses($client->courses)));

        return view('admin.dashboard', compact(
            'totalClients',
            'pendingPayments',
            'approvedOrders',
            'totalPackages',
            'recentOrders', 'progressClients', 'search'
        ));
    }
}
