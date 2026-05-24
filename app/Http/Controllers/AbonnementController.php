<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class AbonnementController extends BaseController
{
    /**
     * GET /api/abonnements/mon-abonnement
     * Retourne l'abonnement actif de l'utilisateur connecté
     */
    public function monAbonnement(Request $request): JsonResponse
    {
        $user = $request->user()->load('abonnementActif');

        return response()->json([
            'plan_actuel'  => $user->abonnement,
            'abonnement'   => $user->abonnementActif,
        ]);
    }

    /**
     * POST /api/abonnements
     * Souscrire ou changer de plan
     */
    public function choisirPlan(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:basique,standard,premium',
        ]);

        $user = $request->user();
        $plan = $request->plan;
        $prix = Abonnement::$tarifs[$plan];

        // Désactiver l'ancien abonnement actif
        Abonnement::where('user_id', $user->id)
            ->where('statut', 'actif')
            ->update(['statut' => 'annule']);

        // Créer le nouvel abonnement
        $abonnement = Abonnement::create([
            'user_id'    => $user->id,
            'plan'       => $plan,
            'prix'       => $prix,
            'statut'     => 'actif',
            'date_debut' => now(),
            'date_fin'   => $plan === 'basique' ? null : now()->addMonth(),
        ]);

        // Mettre à jour le champ abonnement de l'utilisateur
        $user->update(['abonnement' => $plan]);

        return response()->json([
            'message'    => "Abonnement \"$plan\" activé avec succès",
            'abonnement' => $abonnement,
        ], 201);
    }

    /**
     * GET /api/abonnements (Admin uniquement)
     * Liste de tous les abonnements
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $abonnements = Abonnement::with('user:id,nom,email')
            ->latest()
            ->paginate(20);

        return response()->json($abonnements);
    }

    /**
     * DELETE /api/abonnements/{id}
     * Annuler un abonnement (Admin ou propriétaire)
     */
    public function annuler(Request $request, int $id): JsonResponse
    {
        $abonnement = Abonnement::findOrFail($id);
        $user       = $request->user();

        if (!$user->isAdmin() && $abonnement->user_id !== $user->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $abonnement->update(['statut' => 'annule']);

        // Repasser au plan basique
        $abonnement->user->update(['abonnement' => 'basique']);

        return response()->json(['message' => 'Abonnement annulé']);
    }
}
