<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
     * Inscription d'un nouveau citoyen
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'      => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(6)],
            'phone'    => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'nom'      => $validated['nom'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone'    => $validated['phone'] ?? null,
            'role'     => 'citoyen',
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
