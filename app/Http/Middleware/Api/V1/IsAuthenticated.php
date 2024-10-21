<?php

namespace App\Http\Middleware\Api\V1;

use App\Http\Controllers\Helper\V1\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if($user->user_verified_at == null){
            return ApiResponse::errorResponse('Verify your account', Response::HTTP_FORBIDDEN);
         } else {
            return $next($request);
        }
    }
}
