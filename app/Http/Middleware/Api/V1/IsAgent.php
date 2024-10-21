<?php

namespace App\Http\Middleware\Api\V1;

use App\Http\Controllers\Helper\V1\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if($user->role !== 'agent' && $user->role !== 'admin'){
            return ApiResponse::errorResponse('Unauthorized access ', Response::HTTP_FORBIDDEN);
        } else {

            return $next($request);
        }
    }
}
