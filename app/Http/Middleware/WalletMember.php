<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class WalletMember
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && in_array($request->user()->role, ['admin', 'marketing'], true) && $request->user()->isApproved(), 403);
        return $next($request);
    }
}
