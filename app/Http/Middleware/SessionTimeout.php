<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip untuk route login dan logout
        if ($request->routeIs('login') || $request->routeIs('logout')) {
            return $next($request);
        }

        // Cek apakah user masih login
        if (Auth::check()) {
            $user = Auth::user();

            // Cek apakah user masih ada di database (mungkin sudah didelete)
            if (!$user || !$user->exists) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('error', 'Session tidak valid. Silakan login kembali.');
            }

            // Update last activity
            $request->session()->put('last_activity', time());
        }

        return $next($request);
    }
}
