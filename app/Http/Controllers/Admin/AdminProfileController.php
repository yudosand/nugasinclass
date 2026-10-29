<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile');
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $request->user()->update(['name' => $data['name']]);

        return redirect()->route('admin.profile.edit')->with('success', 'Nama Anda berhasil diperbarui.');
    }
}
