<?php

namespace App\Http\Middleware;

use Closure;
use App\ApiKey;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiKeyValidate
{
    /**
     * Handle an incoming request and check for the API key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        // Check for API key
        if (!$request->has('api_key')) {
            return response()->json([
                'errors' => ['Unauthorized']
            ], Response::HTTP_UNAUTHORIZED);

        }
        else {

            // Look for API key
            try {
                ApiKey::where('key', $request->api_key)
                    ->where('active', 1)->firstOrFail();
            } catch (ModelNotFoundException $mnfe) {
                return response()->json([
                    'errors' => ['Invalid key']
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        return $next($request);
    }
}
