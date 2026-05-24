<?php

namespace App\Http\Controllers;

use App\Models\Ramassage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Illuminate\Routing\Controller as BaseController;

class RamassageController extends BaseController
{
    /**
     * POST /api/ramassage
     * Souscrire à un service de ramassage
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'adresse'              => 'required|string|max:255',
            'description_domicile' => 'nullable|string',
            'frequence'            => 'required|in:1_semaine,2_semaine',
            'phone_paiement'       => 'required|string|regex:/^6[0-9]{8}$/',
            'latitude'             => 'nullable|numeric',
            'longitude'            => 'nullable|numeric',
        ]);

        $user  = $request->user();
        $prix  = Ramassage::$tarifs[$validated['frequence']];

        // Vérifier si l'utilisateur a déjà un ramassage actif
        $existant = Ramassage::where('user_id', $user->id)
            ->where('statut', 'actif')
            ->first();

        if ($existant) {
            return response()->json([
                'message' => 'Vous avez déjà un service de ramassage actif',
                'ramassage' => $existant,
            ], 409);
        }

        // Simulation du paiement Orange Money
        $reference = 'OM-' . strtoupper(uniqid());

        $ramassage = Ramassage::create([
            'user_id'              => $user->id,
            'adresse'              => $validated['adresse'],
            'description_domicile' => $validated['description_domicile'] ?? null,
            'frequence'            => $validated['frequence'],
            'prix'                 => $prix,
            'phone_paiement'       => $validated['phone_paiement'],
            'statut_paiement'      => 'paye', // simulation
            'reference_paiement'   => $reference,
            'latitude'             => $validated['latitude'] ?? null,
            'longitude'            => $validated['longitude'] ?? null,
            'statut'               => 'actif',
        ]);

        return response()->json([
            'message'   => 'Service de ramassage souscrit avec succès',
            'reference' => $reference,
            'prix'      => $prix . ' FCFA',
            'ramassage' => $ramassage,
        ], 201);
    }

    /**
     * GET /api/ramassage/mon-service
     * Ramassage actif de l'utilisateur connecté
     */
    public function monService(Request $request): JsonResponse
    {
        $ramassage = Ramassage::where('user_id', $request->user()->id)
            ->where('statut', 'actif')
            ->latest()
            ->first();

        if (!$ramassage) {
            return response()->json(['message' => 'Aucun service actif'], 404);
        }

        return response()->json($ramassage);
    }

    /**
     * GET /api/ramassage (Admin/Agent uniquement)
     * Liste tous les services de ramassage
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isCitoyen()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $ramassages = Ramassage::with('user:id,nom,email,phone')
            ->latest()
            ->paginate(20);

        return response()->json($ramassages);
    }

    /**
     * DELETE /api/ramassage/{id}
     * Annuler un service de ramassage
     */
    public function annuler(Request $request, int $id): JsonResponse
    {
        $ramassage = Ramassage::findOrFail($id);
        $user      = $request->user();

        if (!$user->isAdmin() && $ramassage->user_id !== $user->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $ramassage->update(['statut' => 'annule']);

        return response()->json(['message' => 'Service de ramassage annulé']);
    }
}
