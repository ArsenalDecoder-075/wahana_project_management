<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $userType
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $userType)
    {
        $userTypeMapping = [
            'user' => 0,
            'admin' => 1,
            'manager' => 2
        ];

        if (!isset($userTypeMapping[$userType])) {
            return redirect('/login');
        }

        if (!Auth::check()) {
            return redirect('/login');
        }

        if (Auth::user()->type == $userTypeMapping[$userType]) {
            return $next($request);
        }

        // Redirect berdasarkan tipe user jika tidak sesuai
        $user = Auth::user();

        switch ($user->type) {
            case 0: // User
                return redirect()->route('user.dashboard');

            case 1: // Admin
                return redirect()->route('admin.home');

            case 2: // Manager
                return redirect()->route('manager.dashboard');

            default:
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/login');
        }
    }
}
