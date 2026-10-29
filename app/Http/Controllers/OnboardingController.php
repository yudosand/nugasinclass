<?php

namespace App\Http\Controllers;

use App\Http\Requests\OnboardingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ElearningCredential;
use App\Support\ElearningSchema;

class OnboardingController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        abort_unless($user->role === 'client' && $user->isApproved(), 403);

        $profile = $user->profile;

        $accessReady = ElearningSchema::ready();
        $hasAccess = $accessReady && ElearningCredential::where('user_id', $user->id)->exists();
        return view('client.onboarding', compact('user', 'profile', 'accessReady', 'hasAccess'));
    }

    public function store(OnboardingRequest $request)
    {
        $user = Auth::user();
        abort_unless(ElearningSchema::ready(), 503, 'Admin perlu mengaktifkan penyimpanan akses e-learning terlebih dahulu.');
        DB::transaction(function () use ($user, $request) {
        // Serialize profile/credential changes for the same client.
        \App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
        $profile = $user->profile()->firstOrCreate([]);
        $profile->update([
            'universitas' => $request->universitas,
            'nim' => $request->nim,
            'program_studi' => $request->program_studi,
            'semester' => $request->semester,
            'detail_akademik' => $request->detail_akademik,
            'agreed_terms_at' => now(),
            'is_onboarding_completed' => true,
        ]);
        if ($request->filled('elearning_login') && $request->filled('elearning_password')) {
            ElearningCredential::updateOrCreate(['user_id' => $user->id], [
                'login' => $request->input('elearning_login'),
                'password' => $request->input('elearning_password'),
                'consented_at' => now(),
            ]);
        }
        });

        return redirect()->route('client.dashboard')->with('success', 'Data akademik berhasil dilengkapi! Selamat datang di Dashboard Nugasin Class.');
    }
}
