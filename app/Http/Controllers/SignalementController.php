<?php

namespace App\Http\Controllers;

use App\Models\Signalement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;

class SignalementController extends BaseController
{
    /**
     * GET /api/signalements
     * Liste des signalements
     * - Admin/Agent : tous les signalements
     * - Citoyen : uniquement les siens
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Signalement::with('user:id,nom,email');

        // Filtres optionnels
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('quartier')) {
            $query->where('quartier', $request->quartier);
        }

        if ($request->filled('type_dechet')) {
            $query->where('type_dechet', $request->type_dechet);
        }

        // Citoyen = uniquement ses propres signalements
        if ($user->isCitoyen()) {
            $query->where('user_id', $user->id);
        }

        $signalements = $query->latest()->paginate(20);

        return response()->json($signalements);
    }

    /**
     * POST /api/signalements
     * Créer un signalement (avec photo optionnelle)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lieu'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'type_dechet' => 'required|in:Ménagers,Plastiques,Dangereux',
            'quartier'    => 'required|in:Akwa,Bonanjo,Deido,Bépanda,New-Bell,Ndogbong,Logbaba',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // max 5Mo
        ]);

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('signalements', 'public');
        }

        $signalement = Signalement::create([
            'user_id'     => $request->user()->id,
            'lieu'        => $validated['lieu'],
            'description' => $validated['description'] ?? null,
            'type_dechet' => $validated['type_dechet'],
            'quartier'    => $validated['quartier'],
            'latitude'    => $validated['latitude'] ?? null,
            'longitude'   => $validated['longitude'] ?? null,
            'photo'       => $photoPath,
            'statut'      => 'En attente',
        ]);

        return response()->json([
            'message'      => 'Signalement créé avec succès',
            'signalement'  => $this->formatSignalement($signalement),
        ], 201);
    }

    /**
     * GET /api/signalements/{id}
     * Détail d'un signalement
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $signalement = Signalement::with(['user:id,nom,email', 'agent:id,nom'])->findOrFail($id);

        // Citoyen ne peut voir que le sien
        if ($request->user()->isCitoyen() && $signalement->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        return response()->json($this->formatSignalement($signalement));
    }

    /**
     * PATCH /api/signalements/{id}/statut
     * Changer le statut d'un signalement (Agent ou Admin uniquement)
     */
    public function changerStatut(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if ($user->isCitoyen()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $request->validate([
            'statut' => 'required|in:En attente,En cours,Traité',
        ]);

        $signalement = Signalement::findOrFail($id);
        $signalement->update([
            'statut'    => $request->statut,
            'agent_id'  => $user->id,
            'traite_at' => $request->statut === 'Traité' ? now() : null,
        ]);

        return response()->json([
            'message'     => 'Statut mis à jour',
            'signalement' => $this->formatSignalement($signalement),
        ]);
    }

    /**
     * DELETE /api/signalements/{id}
     * Supprimer un signalement (Admin uniquement)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $signalement = Signalement::findOrFail($id);

        // Supprimer la photo si elle existe
        if ($signalement->photo) {
            Storage::disk('public')->delete($signalement->photo);
        }

        $signalement->delete();

        return response()->json(['message' => 'Signalement supprimé']);
    }

    /**
     * GET /api/signalements/stats
     * Statistiques globales (Admin / Agent)
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isCitoyen()) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $total    = Signalement::count();
        $attente  = Signalement::where('statut', 'En attente')->count();
        $en_cours = Signalement::where('statut', 'En cours')->count();
        $traites  = Signalement::where('statut', 'Traité')->count();

        $parQuartier = Signalement::selectRaw('quartier, count(*) as total')
            ->groupBy('quartier')
            ->get();

        $parType = Signalement::selectRaw('type_dechet, count(*) as total')
            ->groupBy('type_dechet')
            ->get();

        return response()->json([
            'total'       => $total,
            'en_attente'  => $attente,
            'en_cours'    => $en_cours,
            'traites'     => $traites,
            'par_quartier' => $parQuartier,
            'par_type'    => $parType,
        ]);
    }

    /**
     * GET /api/signalements/carte
     * Signalements géolocalisés pour la carte (public)
     */
    public function carte(): JsonResponse
    {
        $signalements = Signalement::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('id', 'quartier', 'lieu', 'type_dechet', 'statut', 'latitude', 'longitude', 'created_at')
            ->latest()
            ->get()
            ->map(fn($s) => [
                'id'         => $s->id,
                'quartier'   => $s->quartier,
                'lieu'       => $s->lieu,
                'type'       => $s->type_dechet,
                'statut'     => $s->statut,
                'latitude'   => $s->latitude,
                'longitude'  => $s->longitude,
                'date'       => $s->created_at->format('d/m/Y'),
            ]);

        return response()->json($signalements);
    }

    // Helper formatage
    private function formatSignalement(Signalement $s): array
    {
        return [
            'id'          => $s->id,
            'lieu'        => $s->lieu,
            'description' => $s->description,
            'type_dechet' => $s->type_dechet,
            'quartier'    => $s->quartier,
            'statut'      => $s->statut,
            'latitude'    => $s->latitude,
            'longitude'   => $s->longitude,
            'photo_url'   => $s->photo_url,
            'utilisateur' => $s->user ? $s->user->nom : null,
            'agent'       => $s->agent ? $s->agent->nom : null,
            'traite_at'   => $s->traite_at?->format('d/m/Y H:i'),
            'created_at'  => $s->created_at->format('d/m/Y H:i'),
        ];
    }
}
