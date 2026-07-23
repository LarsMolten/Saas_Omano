<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => 'required|in:client,prestataire',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'email_verification_token' => Str::random(64),
        ]);

        $token = JWTAuth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès.',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        $user = JWTAuth::user();

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $token = JWTAuth::parseToken()->refresh();
        } catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e) {
            return response()->json(['message' => 'Token révoqué.'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'slug' => $user->slug,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'phone' => $user->phone,
            'phone_verified' => $user->phone_verified,
            'email_verified_at' => $user->email_verified_at,
            'bio' => $user->bio,
            'category' => $user->category,
            'city' => $user->city,
            'average_rating' => $user->average_rating,
            'rating_count' => $user->rating_count,
            'created_at' => $user->created_at,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        JWTAuth::parseToken()->invalidate();

        return response()->json([
            'message' => 'Déconnexion réussie.',
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $user = User::where('email_verification_token', $validated['token'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Token invalide.',
            ], 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ]);

        return response()->json([
            'message' => 'Email vérifié avec succès.',
        ]);
    }

    public function sendEmailVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non autorisé.'], 401);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email déjà vérifié.',
            ]);
        }

        $user->update([
            'email_verification_token' => Str::random(64),
        ]);

        return response()->json([
            'message' => 'Lien de vérification envoyé.',
        ]);
    }

    public function verifyPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non autorisé.'], 401);
        }

        if ($user->phone_verification_code !== $validated['code']) {
            return response()->json([
                'message' => 'Code invalide.',
            ], 422);
        }

        $user->update([
            'phone_verified' => true,
            'phone_verification_code' => null,
        ]);

        return response()->json([
            'message' => 'Téléphone vérifié avec succès.',
        ]);
    }

    public function sendPhoneVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Non autorisé.'], 401);
        }

        if (!$user->phone) {
            return response()->json([
                'message' => 'Aucun numéro de téléphone configuré.',
            ], 422);
        }

        if ($user->phone_verified) {
            return response()->json([
                'message' => 'Téléphone déjà vérifié.',
            ]);
        }

        $code = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'phone_verification_code' => $code,
        ]);

        $smsService = app(\App\Modules\Auth\Services\SmsServiceInterface::class);
        $smsService->send($user->phone, "Votre code de vérification Omano : {$code}");

        return response()->json([
            'message' => 'Code de vérification envoyé.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
            ]);
        }

        $token = Str::random(64);

        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
            'debug_token' => env('APP_DEBUG') ? $token : null,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $resetRecord = \DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (!$resetRecord || !Hash::check($validated['token'], $resetRecord->token)) {
            return response()->json([
                'message' => 'Token invalide ou expiré.',
            ], 422);
        }

        if (Carbon::parse($resetRecord->created_at)->addMinutes(60)->isPast()) {
            \DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->delete();

            return response()->json([
                'message' => 'Token expiré.',
            ], 422);
        }

        $user = User::where('email', $validated['email'])->first();
        $user->update([
            'password' => $validated['password'],
        ]);

        \DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès.',
        ]);
    }
}
