<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Signalement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

use Illuminate\Routing\Controller as BaseController;

use App\Models\Signalement;

class AdminController extends BaseController
{
    /**
     * GET /dashboard
     */
    public function dashboardView(Request $request)
    {
        $user = $request->user();
        
        $response = $this->dashboard($request);
        $stats = $response->getData(true);
        
        $signalements = Signalement::with('user:id,nom')
            ->latest()
            ->take(10)
            ->get();
        
        return view('dashboard', compact('stats', 'signalements', 'user'));
    }
    /**
     * GET /api/admin/users
     * Liste tous les utilisateurs
     */
    public function listeUsers(Request $request): JsonResponse
    {
        $users = User::withCount(['signalements', 'ramassages'])
            ->when($request->filled('role'), fn($q) => $q->where('role', $request->role))
            ->when($request->filled('search'), fn($q) => $q->where('nom', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    /**
     * POST /api/admin/users
     * Créer un agent ou admin
     */
    public function creerUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'      => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(6)],
            'role'     => 'required|in:agent,admin',
            'phone'    => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'nom'        => $validated['nom'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'role'       => $validated['role'],
            'phone'      => $validated['phone'] ?? null,
            'abonnement' => 'premium', // les agents/admins ont accès premium
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user'    => $user,
        ], 201);
    }

    /**
     * PATCH /api/admin/users/{id}/toggle
     * Activer / désactiver un compte utilisateur
     */
    public function toggleUser(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'message'   => $user->is_active ? 'Compte activé' : 'Compte désactivé',
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * DELETE /api/admin/users/{id}
     * Supprimer un utilisateur
     */
    public function supprimerUser(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    /**
     * GET /api/admin/dashboard
     * Données globales du dashboard admin
     */
    public function dashboard(): JsonResponse
    {
        $totalUsers      = User::count();
        $totalCitoyens   = User::where('role', 'citoyen')->count();
        $totalAgents     = User::where('role', 'agent')->count();
        $totalSignaux    = Signalement::count();
        $enAttente       = Signalement::where('statut', 'En attente')->count();
        $enCours         = Signalement::where('statut', 'En cours')->count();
        $traites         = Signalement::where('statut', 'Traité')->count();

        $parQuartier = Signalement::selectRaw('quartier, count(*) as total')
            ->groupBy('quartier')
            ->orderByDesc('total')
            ->get();

        $dernierSignalements = Signalement::with('user:id,nom')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($s) => [
                'id'       => $s->id,
                'lieu'     => $s->lieu,
                'quartier' => $s->quartier,
                'type'     => $s->type_dechet,
                'statut'   => $s->statut,
                'user'     => $s->user?->nom,
                'date'     => $s->created_at->format('d/m/Y H:i'),
            ]);

        return response()->json([
            'users' => [
                'total'    => $totalUsers,
                'citoyens' => $totalCitoyens,
                'agents'   => $totalAgents,
            ],
            'signalements' => [
                'total'      => $totalSignaux,
                'en_attente' => $enAttente,
                'en_cours'   => $enCours,
                'traites'    => $traites,
                'par_quartier' => $parQuartier,
            ],
            'derniers_signalements' => $dernierSignalements,
        ]);
    }
}
