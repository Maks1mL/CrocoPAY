<?php

namespace Modules\ExpressRestApi\Http\Middleware;

use Closure;
use App\Models\AppToken;
use Illuminate\Http\Request;

class MerchantTokenAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $actualToken = $request->bearerToken();

        if (!$actualToken) {
            return response()->json(['error' => 'Unauthorized - Token not provided'], 401);
        }

        $appToken = AppToken::where('token', $actualToken)
            ->where('expires_in', '>=', time())
            ->first();

        if (!$appToken) {
            return response()->json(['error' => 'Unauthorized - Invalid token or expired'], 401);
        }

        $request->merge(['appToken' => $appToken]);

        return $next($request);
    }
}
