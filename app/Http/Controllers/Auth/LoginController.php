<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedLoginResponse($request, 'Invalid input provided.');
        }

        $credentials = $this->parseCredentials($request);

        try {
            $user = $this->authService->authenticate($credentials);

            if ($user) {
                Auth::login($user, $request->filled('remember'));

                // Initialize session tracking
                $this->initializeSession($request);

                return $this->sendLoginResponse($request);
            }

            return $this->sendFailedLoginResponse($request, 'Login unsuccessful. Please check your details and try again.');

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ]);

            return $this->sendFailedLoginResponse($request, 'Authentication service unavailable.');
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
     * Parse and normalize credentials
     */
    protected function parseCredentials(Request $request): array
    {
        $email = trim($request->input('email'));

        if (strpos($email, '@') !== false) {
            $email = strtolower(explode('@', $email)[0]);
        } else {
            $email = strtolower($email);
        }

        return [
            'email' => $email,
            'password' => $request->input('password')
        ];
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
