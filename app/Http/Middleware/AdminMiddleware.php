<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Akses khusus Admin Backoffice.');
        }
        abort_unless(Auth::user()->role === 'admin' && Auth::user()->isApproved(), 403, 'Akses khusus Admin Backoffice.');

        return $next($request);
    }
}
