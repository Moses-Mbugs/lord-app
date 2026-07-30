<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class AuthenticationService
{
    protected string $apiUrl;
    protected string $apiUrl2;
    protected int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('auth.api_url', 'https://merchantchargeback.ecobank.com/api/loginx');
        $this->apiUrl2 = config('auth.api_url_fallback', 'http://10.8.32.3:400/api/send-login');
        $this->timeout = config('auth.api_timeout', 120);
    }

    /**
     * Authenticate user via the external API.
     */
    public function authenticate(array $credentials): ?User
    {
        $user = $this->authenticateViaApi($credentials);

        if ($user) {
            Log::info('API authentication successful', ['email' => $credentials['email']]);
            return $user;
        }

        Log::warning('Authentication failed', ['email' => $credentials['email']]);
        return null;
    }

    /**
     * Authenticate via external API.
     *
     * Credentials are sent as a POST JSON body (not a GET query string) so
     * they don't end up in access/proxy logs, and TLS certificate
     * verification is left enabled for the HTTPS endpoint.
     */
    protected function authenticateViaApi(array $credentials): ?User
    {
        $apiUrls = [
            $this->apiUrl,      // Primary API URL
            $this->apiUrl2      // Secondary API URL (fallback)
        ];

        foreach ($apiUrls as $index => $url) {
            try {
                $response = Http::timeout($this->timeout)
                    ->retry(1, 500) // Single retry with 500ms delay per URL
                    ->post($url, [
                        'email' => $credentials['email'],
                        'password' => $credentials['password'],
                    ]);

                if ($response->successful() && $response->json('success')) {
                    $userData = $response->json('data');

                    Log::info('API authentication successful', [
                        'email' => $credentials['email'],
                        'api_url_index' => $index + 1,
                    ]);

                    return $this->createOrUpdateUser($this->formatApiUser($userData));
                }

            } catch (\Exception $e) {
                Log::warning('API authentication failed for URL ' . ($index + 1), [
                    'error' => $e->getMessage(),
                    'email' => $credentials['email'],
                    'is_fallback' => $index > 0
                ]);

                // Continue to next URL if available
                continue;
            }
        }

        Log::error('API authentication failed on all URLs', [
            'email' => $credentials['email'],
            'urls_attempted' => count($apiUrls)
        ]);

        return null;
    }

    /**
     * Create or update user in database.
     *
     * The local password hash is never checked on login (authentication is
     * always delegated to the external API above), so it's set to a random,
     * unguessable value per user rather than a fixed default.
     */
    protected function createOrUpdateUser(array $userData): User
    {
        return User::updateOrCreate(
            ['email' => strtolower($userData['email'])],
            [
                'name' => $userData['name'],
                'email' => strtolower($userData['email']),
                'department' => $userData['department'] ?? null,
                'title' => $userData['title'] ?? null,
                'password' => Hash::make(Str::random(40)),
            ]
        );
    }

    /**
     * Format API user data to standard format
     */
    protected function formatApiUser(array $apiUser): array
    {
        return [
            'name' => $apiUser['fullname'] ?? $apiUser['name'] ?? 'Unknown User',
            'email' => $apiUser['email'],
            'department' => $apiUser['department'] ?? null,
            'title' => $apiUser['title'] ?? null,
        ];
    }
}
