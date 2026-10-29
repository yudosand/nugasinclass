<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $admins = User::where('role', 'admin')->when($search !== '', function ($query) use ($search) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('nomor_hp', 'like', '%'.$search.'%'));
        })->orderBy('name')->paginate(15)->withQueryString();
        return view('admin.admins.index', compact('admins', 'search'));
    }

    public function create()
    {
        return view('admin.admins.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'nomor_hp' => ['required', 'regex:/^[0-9]{9,15}$/D', 'unique:users,nomor_hp'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'current_password' => ['required', 'current_password'],
        ], ['current_password.current_password' => 'Password admin Anda tidak sesuai.']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            if (\App\Support\CuaninSchema::ready()) \App\Support\Cuanin::lock();
            User::create(['name' => $data['name'], 'nomor_hp' => $data['nomor_hp'], 'password' => $data['password'], 'role' => 'admin', 'status' => 'approved']);
        }, 3);
        return redirect()->route('admin.admins.index')->with('success', 'Admin baru berhasil ditambahkan dan dapat login menggunakan nomor HP.');
    }
}
