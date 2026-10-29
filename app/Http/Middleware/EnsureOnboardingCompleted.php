<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'client', 403);

        if ($user && $user->role === 'client') {
            if (!$user->isApproved()) {
                return redirect()->route('login')->with('warning', 'Akun Anda belum di-approve oleh Admin.');
            }

            if (!$user->hasCompletedOnboarding()) {
                return redirect()->route('onboarding.show');
            }
        }

        return $next($request);
    }
}
