<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecureWeb
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('production') && !$request->isSecure()) {
            // Never process credential submissions over HTTP or trust arbitrary forwarded headers.
            if (!$request->isMethodSafe()) abort(426, 'Gunakan HTTPS untuk mengirim data.');
            $host = parse_url(config('app.url'), PHP_URL_HOST);
            abort_unless($host && $host !== 'localhost', 503, 'Konfigurasi APP_URL HTTPS diperlukan.');
            return redirect()->away('https://'.$host.$request->getRequestUri())->header('Cache-Control', 'no-store');
        }

        $response = $next($request);
        if ($request->routeIs('exams.learn','exams.attempt','exams.answer','exams.result')) {
            $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
            $response->headers->set('X-Robots-Tag','noindex, nofollow');
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        if ($request->user()) $response->headers->set('Cache-Control', 'no-store, private');
        if (app()->environment('production') && $request->isSecure()) $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        if ($request->routeIs('onboarding.*', 'admin.elearning.*')) {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
            $response->headers->set('Cache-Control', 'no-store, private');
        }
        if ($request->routeIs('cuanin.*', 'admin.cuanin.*', 'marketing.register', 'marketing.login')) {
            $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; form-action 'self' https://wa.me https://api.whatsapp.com https://web.whatsapp.com https://www.whatsapp.com; frame-ancestors 'none'; base-uri 'none'");
            $response->headers->set('Cache-Control', 'no-store, private');
        }
        return $response;
    }
}
