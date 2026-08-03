<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Attributes\Middleware;

class LoginController extends Controller
{
    protected $redirectTo = '/home';
    protected $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Show the login form
     */
    #[Middleware('guest')]
    public function showLoginForm(Request $request)
    {
        if ($request->has('returnUrl')) {
            $returnUrl = $this->validateRedirectUrl($request->input('returnUrl'));
            session(['url.intended' => $returnUrl]);
        }

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    #[Middleware('guest')]
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:3'],
        ]);

        if ($validator->fails()) {
            Log::warning('Login validation failed', [
                'identifier' => $request->input('email'),
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
            ]);

            return $this->sendFailedLoginResponse(
                $request,
                'Please enter a valid username and password.'
            );
        }

        $identifier = strtolower(trim($request->input('email')));
        $username = str_contains($identifier, '@')
            ? explode('@', $identifier, 2)[0]
            : $identifier;

        Log::info('Login attempt started', [
            'identifier' => $identifier,
            'normalized_username' => $username,
            'ip' => $request->ip(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. Local authentication
        |--------------------------------------------------------------------------
        */

        $user = User::query()
            ->where(function ($query) use ($identifier, $username) {
                $query
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$identifier])
                    ->orWhereRaw('LOWER(TRIM(email)) = ?', [$username])
                    ->orWhereRaw(
                        "LOWER(SUBSTRING_INDEX(TRIM(email), '@', 1)) = ?",
                        [$username]
                    );
            })
            ->first();

        if ($user) {
            Log::info('Local user record found', [
                'user_id' => $user->id,
                'email' => $user->email,
                'has_local_password' => !empty($user->password),
                'ip' => $request->ip(),
            ]);

            if (
                !empty($user->password) &&
                Hash::check($request->input('password'), $user->password)
            ) {
                Auth::login($user, $request->boolean('remember'));

                try {
                    $user->updateLastLogin();
                } catch (\Throwable $e) {
                    Log::warning('Unable to update last login', [
                        'user_id' => $user->id,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);
                }

                $this->initializeSession($request);

                Log::info('Local authentication successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                return $this->sendLoginResponse($request);
            }

            Log::warning('Local password verification failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);
        } else {
            Log::info('No matching local user found', [
                'identifier' => $identifier,
                'normalized_username' => $username,
                'ip' => $request->ip(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. External authentication
        |--------------------------------------------------------------------------
        */

        $credentials = [
            'email' => $username,
            'password' => $request->input('password'),
        ];

        try {
            Log::info('External authentication started', [
                'username' => $username,
                'ip' => $request->ip(),
            ]);

            $user = $this->authService->authenticate($credentials);

            if (!$user) {
                Log::warning('External authentication returned no user', [
                    'username' => $username,
                    'ip' => $request->ip(),
                ]);

                return $this->sendFailedLoginResponse(
                    $request,
                    'Login unsuccessful. Please check your username and password.'
                );
            }

            Auth::login($user, $request->boolean('remember'));

            $this->initializeSession($request);

            Log::info('External authentication successful', [
                'user_id' => $user->id,
                'email' => $user->email ?? null,
                'ip' => $request->ip(),
            ]);

            return $this->sendLoginResponse($request);

        } catch (\Throwable $e) {
            Log::error('External authentication exception', [
                'username' => $username,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $request->ip(),
            ]);

            return $this->sendFailedLoginResponse(
                $request,
                'Authentication service is currently unavailable.'
            );
        }
    }

    /**
     * Initialize session tracking after successful login
     */
    protected function initializeSession(Request $request)
    {
        session([
            'last_activity_time' => time(),
            'session_start_time' => time(),
            'user_ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }

    /**
     * Check if session is still active (API endpoint)
     */
    #[Middleware('auth')]
    public function checkSession(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'active' => false,
                'message' => 'Session expired',
                'redirect' => route('login')
            ], 401);
        }

        $lastActivity = session('last_activity_time', time());
        $timeout = config('session.lifetime') * 60; // Convert to seconds
        $currentTime = time();
        $timePassed = $currentTime - $lastActivity;
        $timeRemaining = max(0, $timeout - $timePassed);

        // Check if session has expired
        if ($timePassed > $timeout) {
            $this->handleSessionTimeout($request);

            return response()->json([
                'active' => false,
                'message' => 'Session expired',
                'redirect' => route('login')
            ], 401);
        }

        return response()->json([
            'active' => true,
            'time_remaining' => $timeRemaining,
            'timeout_duration' => $timeout,
            'user_id' => Auth::id(),
            'last_activity' => $lastActivity
        ]);
    }

    /**
     * Keep session alive by updating last activity time (heartbeat)
     */
    #[Middleware('auth')]
    public function heartbeat(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        session(['last_activity_time' => time()]);

        Log::debug('Session heartbeat', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'timestamp' => time()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session refreshed',
            'timestamp' => time()
        ]);
    }

    /**
     * Handle session timeout
     */
    protected function handleSessionTimeout(Request $request)
    {
        $userId = Auth::id();

        Log::info('Session timeout detected', [
            'user_id' => $userId,
            'last_activity' => session('last_activity_time'),
            'ip' => $request->ip()
        ]);

        // Logout user
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Set timeout flag
        session()->flash('session_timeout', true);
    }

    /**
     * Save draft data (API endpoint)
     */
    #[Middleware('auth')]
    public function saveDraft(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'data' => 'required',
            'page' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userId = Auth::id();
            $draftKey = "draft_{$userId}_{$request->input('key')}";

            $draftData = [
                'data' => $request->input('data'),
                'page' => $request->input('page'),
                'saved_at' => now()->toDateTimeString(),
                'user_id' => $userId,
                'ip' => $request->ip()
            ];

            // Store draft for 24 hours
            Cache::put($draftKey, $draftData, now()->addHours(24));

            Log::info('Draft saved', [
                'user_id' => $userId,
                'key' => $request->input('key'),
                'page' => $request->input('page'),
                'data_size' => strlen(json_encode($request->input('data')))
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Draft saved successfully',
                'saved_at' => $draftData['saved_at']
            ]);

        } catch (\Exception $e) {
            Log::error('Draft save error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save draft'
            ], 500);
        }
    }

    /**
     * Get saved draft data (API endpoint)
     */
    #[Middleware('auth')]
    public function getDraft(Request $request, string $key)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $userId = Auth::id();
        $draftKey = "draft_{$userId}_{$key}";

        $draft = Cache::get($draftKey);

        if (!$draft) {
            return response()->json([
                'success' => false,
                'message' => 'No draft found'
            ], 404);
        }

        // Verify the draft belongs to the current user
        if (isset($draft['user_id']) && $draft['user_id'] !== $userId) {
            Log::warning('Unauthorized draft access attempt', [
                'user_id' => $userId,
                'draft_user_id' => $draft['user_id'],
                'key' => $key
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        Log::info('Draft retrieved', [
            'user_id' => $userId,
            'key' => $key
        ]);

        return response()->json([
            'success' => true,
            'draft' => $draft
        ]);
    }

    /**
     * Delete saved draft (API endpoint)
     */
    #[Middleware('auth')]
    public function deleteDraft(Request $request, string $key)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $userId = Auth::id();
        $draftKey = "draft_{$userId}_{$key}";

        Cache::forget($draftKey);

        Log::info('Draft deleted', [
            'user_id' => $userId,
            'key' => $key
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Draft deleted successfully'
        ]);
    }

    /**
     * Send successful login response
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        // Pull and validate the intended URL before regenerating the session,
        // then forget it so it cannot be replayed on a subsequent request.
        $intendedUrl = $this->validateRedirectUrl(
            session()->pull('url.intended', $this->redirectTo)
        );

        Log::info('Login successful', [
            'user_id' => Auth::id(),
            'redirect_url' => $intendedUrl,
            'ip' => $request->ip()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => $intendedUrl,
                'session_timeout' => config('session.lifetime') * 60
            ]);
        }

        // Use redirect() directly — NOT redirect()->intended() — because we have
        // already resolved and cleared the intended URL ourselves above.
        return redirect($intendedUrl);
    }

    /**
     * Validate redirect URL for security
     */
    protected function validateRedirectUrl(string $url): string
    {
        $url = trim($url);

        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        $allowedRoutes = ['/home', '/finance', '/loans'];

        // Exact match
        if (in_array($url, $allowedRoutes)) {
            return $url;
        }

        // Pattern match for sub-routes
        foreach ($allowedRoutes as $allowedRoute) {
            if (str_starts_with($url, $allowedRoute)) {
                return $url;
            }
        }

        return $this->redirectTo;
    }

    /**
     * Send failed login response
     */
    protected function sendFailedLoginResponse(Request $request, string $message = 'Invalid credentials')
    {
        Log::warning('Failed login attempt', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 401);
        }

        return back()->withErrors(['email' => $message])->withInput($request->except('password'));
    }

    /**
     * Handle logout
     */
    #[Middleware('auth')]
    public function logout(Request $request)
    {
        $userId = Auth::id();

        Log::info('User logout', [
            'user_id' => $userId,
            'ip' => $request->ip()
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
                'redirect' => route('login')
            ]);
        }

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
