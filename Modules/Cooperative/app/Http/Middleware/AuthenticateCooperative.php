<?php

namespace Modules\Cooperative\Http\Middleware;

use Closure;
use Modules\Cooperative\Repositories\Contracts\CooperativeApiKeyRepositoryInterface;
use Illuminate\Http\Request;

class AuthenticateCooperative
{

    public function __construct(
        protected CooperativeApiKeyRepositoryInterface $coopApiKeyRepo
    )
    {

    }
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$abilities)
    {
        $token = str_replace('Bearer ', '', $request->header('Authorization'));

        if (! $token) {
            return response()->json(['message' => 'API key missing'], 401);
        }

        $key = $this->coopApiKeyRepo->findByHash(
            hash('sha256', $token)
        );

        if (! $key || ! $key->isActive()) {
            return response()->json(['message' => 'Invalid API key'], 401);
        }

        if ($abilities && ! empty(array_diff($abilities, $key->abilities ?? []))) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }

        $key->update(['last_used_at' => now()]);

        // Attach cooperative context
        $request->attributes->set('cooperative', $key->cooperative);

        return $next($request);
    
    }
}
