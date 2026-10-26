<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (! Auth::user()->active || ! array_key_exists(Auth::user()->role, config('construction.roles')))) {
            Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email'=>'Your account is inactive. Please contact your administrator.']);
        }
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options','nosniff');
        $response->headers->set('X-Frame-Options','DENY');
        $response->headers->set('Referrer-Policy','strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy','camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
        $response->headers->set('Cache-Control','no-store, private');
        if ($request->isSecure()) $response->headers->set('Strict-Transport-Security','max-age=31536000');
        return $response;
    }
}
