<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Routing\Controller as BaseController;

class AuthController extends BaseController
{
    /**
     * GET /login
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * GET /register
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * POST /api/auth/register
     * Inscription d'un nouveau citoyen, agent ou admin
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'      => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(6)],
            'phone'    => 'nullable|string|max:20',
            'role'     => 'required|in:citoyen,agent,admin',
        ]);

        $user = User::create([
            'nom'      => $validated['nom'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone'    => $validated['phone'] ?? null,
            'role'     => $validated['role'],
            'abonnement' => 'basique',
        ]);

        $token = $user->createToken('doualaclean')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ], 201);
    }

    /**
     * POST /api/auth/login
     * Connexion (citoyen ou admin/agent)
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Compte désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // Révoquer anciens tokens
        $user->tokens()->delete();

        $token = $user->createToken('doualaclean')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ]);
    }

    /**
     * POST /api/auth/logout
     * Déconnexion
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * GET /api/auth/me
     * Récupérer le profil de l'utilisateur connecté
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('abonnementActif');

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * PUT /api/auth/profile
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nom'   => 'sometimes|string|max:100',
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour',
            'user'    => $this->formatUser($user),
        ]);
    }

    /**
     * PUT /api/auth/password
     * Changer le mot de passe
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Mot de passe actuel incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès',
        ]);
    }

    /**
     * POST /api/auth/forgot-password
     * Génère un token de réinitialisation et l'envoie par email (log en dev)
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
            ]);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $mailer = config('mail.default', 'log');

        if ($mailer !== 'log' && $mailer !== 'array') {
            // Real email configured — send and do NOT expose token in API
            try {
                Mail::to($request->email)->send(new ResetPasswordMail($request->email, $token));

                return response()->json([
                    'message' => 'Un email de réinitialisation a été envoyé à ' . $request->email . '. Vérifiez votre boîte de réception.',
                ]);
            } catch (\Exception $e) {
                Log::error('DoualaClean — Mail send failed', [
                    'email' => $request->email,
                    'error' => $e->getMessage(),
                ]);
                // Fall through to token-in-response fallback
            }
        }

        // No email configured (or send failed) — return token directly
        Log::info('DoualaClean — Reset password token (no email)', ['email' => $request->email]);

        return response()->json([
            'message'     => 'Lien de réinitialisation généré avec succès.',
            'reset_token' => $token,
            'notice'      => 'Token inclus directement (configurez MAIL_MAILER=smtp pour envoyer par email).',
        ]);
    }

    /**
     * POST /api/auth/reset-password
     * Réinitialise le mot de passe avec le token
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => ['required', 'confirmed', Password::min(6)],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 422);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Token expiré. Demandez un nouveau lien.'], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Token invalide.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.']);
    }

    // Helper formatage user
    private function formatUser(User $user): array
    {
        return [
            'id'          => $user->id,
            'nom'         => $user->nom,
            'email'       => $user->email,
            'role'        => $user->role,
            'abonnement'  => $user->abonnement,
            'phone'       => $user->phone,
            'is_active'   => $user->is_active,
            'created_at'  => $user->created_at,
        ];
    }
}
