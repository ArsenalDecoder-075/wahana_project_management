<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Handle unauthenticated users
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Clear session data to prevent conflicts
        $request->session()->flush();

        return redirect()->guest(route('login'))->with('error', 'Silakan login terlebih dahulu untuk mengakses halaman ini.');
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle Route Not Found Exception (untuk route yang tidak terdefinisi)
        if ($exception instanceof RouteNotFoundException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Route not found'], 404);
            }

            // Jika user masih login tapi route tidak ada, arahkan ke dashboard yang sesuai
            if (Auth::check()) {
                $user = Auth::user();

                switch ($user->type) {
                    case 0: // User
                        return redirect()->route('user.dashboard')->with('error', 'Halaman tidak ditemukan.');
                    case 1: // Admin
                        return redirect()->route('admin.home')->with('error', 'Halaman tidak ditemukan.');
                    case 2: // Manager
                        return redirect()->route('manager.dashboard')->with('error', 'Halaman tidak ditemukan.');
                    default:
                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                        return redirect()->route('login')->with('error', 'Terjadi kesalahan. Silakan login kembali.');
                }
            }

            return redirect()->route('login')->with('error', 'Halaman tidak ditemukan.');
        }

        // Handle Token Mismatch (CSRF) Exception
        if ($exception instanceof TokenMismatchException) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'CSRF token mismatch'], 419);
            }

            // Logout user dan clear session
            if (Auth::check()) {
                Auth::logout();
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->guest(route('login'))->with('error', 'Session Anda telah berakhir. Silakan login kembali.');
        }

        // Handle Authentication Exception
        if ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        // Only handle custom error pages for web requests (not AJAX/API)
        if ($this->shouldShowCustomErrorPage($request)) {

            // Handle 403 Forbidden
            if ($exception instanceof AccessDeniedHttpException) {
                // Jika user login tapi tidak punya akses, arahkan ke dashboard yang sesuai
                if (Auth::check()) {
                    $user = Auth::user();

                    switch ($user->type) {
                        case 0: // User
                            return redirect()->route('user.dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
                        case 1: // Admin
                            return redirect()->route('admin.home')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
                        case 2: // Manager
                            return redirect()->route('manager.dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
                    }
                }

                return response()->view('errors.403', [
                    'exception' => $exception
                ], 403);
            }

            // Handle 404 Not Found
            if ($exception instanceof NotFoundHttpException) {
                return response()->view('errors.404', [
                    'exception' => $exception
                ], 404);
            }

            // Handle 503 Service Unavailable
            if ($exception instanceof ServiceUnavailableHttpException) {
                return response()->view('errors.503', [
                    'exception' => $exception
                ], 503);
            }

            // Handle HttpException
            if ($exception instanceof HttpException) {
                $statusCode = $exception->getStatusCode();

                switch ($statusCode) {
                    case 401:
                        if (Auth::check()) {
                            Auth::logout();
                            $request->session()->invalidate();
                            $request->session()->regenerateToken();
                        }
                        return redirect()->guest(route('login'))->with('error', 'Silakan login terlebih dahulu.');

                    case 403:
                        // Similar handling as AccessDeniedHttpException
                        if (Auth::check()) {
                            $user = Auth::user();

                            switch ($user->type) {
                                case 0: // User
                                    return redirect()->route('user.dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
                                case 1: // Admin
                                    return redirect()->route('admin.home')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
                                case 2: // Manager
                                    return redirect()->route('manager.dashboard')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
                            }
                        }

                        return response()->view('errors.403', [
                            'exception' => $exception
                        ], 403);

                    case 404:
                        return response()->view('errors.404', [
                            'exception' => $exception
                        ], 404);

                    case 500:
                        // Don't show error details in production
                        if (!app()->environment('local')) {
                            return response()->view('errors.500', [
                                'exception' => null
                            ], 500);
                        }
                        break;

                    case 503:
                        return response()->view('errors.503', [
                            'exception' => $exception
                        ], 503);
                }
            }

            // Handle general server errors as 500 (only in non-local environment)
            if (!app()->environment('local')) {
                // Log the error for debugging
                Log::error('Application Error: ' . $exception->getMessage(), [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ]);

                return response()->view('errors.500', [
                    'exception' => null
                ], 500);
            }
        }

        return parent::render($request, $exception);
    }

    /**
     * Check if we should show custom error page.
     */
    protected function shouldShowCustomErrorPage(Request $request): bool
    {
        return !$request->expectsJson() &&
            !$request->ajax() &&
            !$request->is('api/*') &&
            !$request->wantsJson();
    }
}
