<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageManagementController extends Controller
{
    public function index()
    {
        $packages = Package::where('slug','not like','uas-%')->latest()->get();

        return view('admin.packages.index', compact('packages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0|max:9999999999',
            'price_label' => 'nullable|string|max:100',
            'features' => 'nullable|string', // newline-separated
        ]);

        $featuresArray = array_filter(array_map('trim', explode("\n", $request->input('features', ''))));

        Package::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(4),
            'description' => $request->description,
            'price' => $request->price,
            'price_label' => $request->price_label,
            'features' => $featuresArray,
            'is_recommended' => $request->has('is_recommended'),
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Paket layanan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $package = Package::findOrFail($id);
        abort_if(str_starts_with($package->slug,'uas-'),409,'Kelola paket ini melalui menu Paket Simulasi UAS.');

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0|max:9999999999',
            'price_label' => 'nullable|string|max:100',
            'features' => 'nullable|string',
        ]);

        $featuresArray = array_filter(array_map('trim', explode("\n", $request->input('features', ''))));

        $package->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'price_label' => $request->price_label,
            'features' => $featuresArray,
            'is_recommended' => $request->has('is_recommended'),
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Paket layanan berhasil diupdate.');
    }

    public function destroy($id)
    {
        $package = Package::findOrFail($id);
        abort_if(str_starts_with($package->slug,'uas-'),409,'Nonaktifkan paket ini melalui menu Paket Simulasi UAS.');
        \Illuminate\Support\Facades\DB::transaction(function () use ($package) {
            if (\App\Support\CuaninSchema::ready()) \App\Support\Cuanin::lock();
            if (\App\Models\Order::where('package_id', $package->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['package' => 'Paket memiliki riwayat order. Nonaktifkan paket agar riwayat transaksi tetap tersimpan.']);
            }
            $package->delete();
        }, 3);

        return back()->with('success', 'Paket layanan berhasil dihapus.');
    }
}
