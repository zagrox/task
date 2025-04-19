<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyGitHubWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip verification in local development if configured to do so
        if (app()->environment('local') && env('SKIP_GITHUB_WEBHOOK_VERIFICATION', false)) {
            return $next($request);
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            Log::warning('GitHub webhook request missing signature');
            return response()->json(['error' => 'Invalid request signature'], 403);
        }

        $payload = $request->getContent();
        $secret = env('GITHUB_WEBHOOK_SECRET');
        
        if (!$secret) {
            Log::error('GITHUB_WEBHOOK_SECRET not configured in .env');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Invalid GitHub webhook signature');
            return response()->json(['error' => 'Invalid request signature'], 403);
        }

        return $next($request);
    }
}
