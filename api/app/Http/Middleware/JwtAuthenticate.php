<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenAbsentException;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (!JWTAuth::parseToken()->check()) {
                return response()->json(['message' => 'Non autorisé.'], 401);
            }
        } catch (TokenBlacklistedException $e) {
            return response()->json(['message' => 'Token révoqué.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide.'], 401);
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré.'], 401);
        } catch (TokenAbsentException $e) {
            return response()->json(['message' => 'Token manquant.'], 401);
        }

        return $next($request);
    }
}
