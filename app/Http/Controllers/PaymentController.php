<?php
namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Abonnement;
use App\Services\AfricasTalkingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller {

    protected $africasTalking;

    public function __construct() {
        $this->africasTalking = new AfricasTalkingService();
    }

    /**
     * Initier un paiement pour un abonnement
     * POST /api/payments/initiate
     * Body: { plan: 'basique|standard|premium', phone_number }
     */
    public function initiateSubscription(Request $request) {
        $request->validate([
            'plan' => 'required|in:basique,standard,premium',
            'phone_number' => 'required|string'
        ]);

        $user = Auth::user();
        $plan = $request->input('plan');
        $phone = $this->africasTalking->validatePhoneNumber($request->input('phone_number'));

        if (!$phone) {
            return response()->json(['error' => 'Numéro invalide'], 400);
        }

        // Définir le montant
        $amounts = [
            'basique' => 0,
            'standard' => 2000,
            'premium' => 5000
        ];

        $amount = $amounts[$plan] ?? 0;

        if ($amount === 0) {
            // Abonnement gratuit
            return $this->activateSubscription($user, $plan, 0);
        }

        // Créer une transaction
        $reference = 'TXN-' . date('Y') . '-' . str_pad(Transaction::count() + 1, 5, '0', STR_PAD_LEFT);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'payment',
            'amount' => $amount,
            'currency' => 'XAF',
            'reference' => $reference,
            'phone_number' => $phone,
            'status' => 'pending',
            'payment_method' => $this->detectOperator($phone),
            'provider' => 'africas-talking',
            'transaction_data' => ['plan' => $plan]
        ]);

        // Initier le paiement
        $result = $this->africasTalking->initiateMobilePayment(
            $phone,
            $amount,
            $reference
        );

        if (!$result || !isset($result['data'])) {
            return response()->json([
                'error' => 'Erreur lors de l\'initiation du paiement',
                'details' => $result
            ], 400);
        }

        // Envoyer SMS
        $this->africasTalking->sendSMS(
            $phone,
            "DoualaClean: Confirmez le paiement de {$amount} XAF pour l'abonnement {$plan}. REF: {$reference}"
        );

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->id,
            'reference' => $reference,
            'amount' => $amount,
            'message' => 'Veuillez confirmer le paiement sur votre téléphone'
        ]);
    }

    /**
     * Vérifier le statut d'une transaction
     * GET /api/payments/{reference}/status
     */
    public function checkStatus($reference) {
        $transaction = Transaction::where('reference', $reference)->firstOrFail();

        if ($transaction->status === 'pending') {
            // Vérifier auprès d'Africa's Talking
            $result = $this->africasTalking->getTransactionStatus($reference);

            if ($result && isset($result['data'])) {
                $transaction->update([
                    'status' => $result['data']['status'] ?? 'pending',
                    'transaction_data' => $result['data'] ?? [],
                    'completed_at' => $result['data']['status'] === 'Success' ? now() : null
                ]);
            }
        }

        return response()->json([
            'reference' => $transaction->reference,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'created_at' => $transaction->created_at
        ]);
    }

    /**
     * Webhook pour la confirmation de paiement
     * POST /api/payments/webhook
     */
    public function webhook(Request $request) {
        Log::info('Payment Webhook:', $request->all());

        $data = $request->all();
        $reference = $data['reference'] ?? null;

        $transaction = Transaction::where('reference', $reference)->first();

        if (!$transaction) {
            Log::warning('Transaction not found:', ['reference' => $reference]);
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $status = $data['status'] ?? 'failed';

        if ($status === 'completed' || $status === 'success') {
            $transaction->update([
                'status' => 'completed',
                'transaction_data' => $data,
                'completed_at' => now()
            ]);

            // Activer l'abonnement
            if ($transaction->user_id) {
                $planData = $transaction->transaction_data;
                $plan = $planData['plan'] ?? 'standard';

                $this->activateSubscription(
                    User::find($transaction->user_id),
                    $plan,
                    $transaction->amount
                );
            }
        } else {
            $transaction->update([
                'status' => 'failed',
                'transaction_data' => $data,
                'error_message' => $data['error'] ?? 'Payment failed'
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Activer un abonnement
     */
    protected function activateSubscription(User $user, string $plan, float $amount) {
        $durations = [
            'basique' => 30,
            'standard' => 90,
            'premium' => 365
        ];

        $duration = $durations[$plan] ?? 30;

        $subscription = Abonnement::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'prix' => $amount,
            'statut' => 'actif',
            'date_debut' => now(),
            'date_fin' => now()->addDays($duration)
        ]);

        // Envoyer SMS de confirmation
        $this->africasTalking->sendSMS(
            $user->phone ?? '',
            "Votre abonnement {$plan} est maintenant actif jusqu'au {$subscription->date_fin->format('d/m/Y')} ✅"
        );

        return response()->json([
            'success' => true,
            'plan' => $plan,
            'expires_at' => $subscription->date_fin
        ]);
    }

    /**
     * Obtenir l'historique des paiements
     * GET /api/payments/history
     */
    public function history() {
        $user = Auth::user();

        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'total' => $transactions->total(),
            'data' => $transactions->items()
        ]);
    }

    /**
     * Obtenir le détail d'un paiement
     * GET /api/payments/{id}
     */
    public function show($id) {
        $transaction = Transaction::findOrFail($id);

        // Vérifier que c'est le propriétaire
        if ($transaction->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($transaction);
    }

    /**
     * Rembourser une transaction
     * POST /api/payments/{id}/refund
     */
    public function refund($id) {
        $transaction = Transaction::findOrFail($id);

        if ($transaction->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($transaction->status !== 'completed') {
            return response()->json([
                'error' => 'Can only refund completed transactions'
            ], 400);
        }

        $refund = Transaction::create([
            'user_id' => $transaction->user_id,
            'type' => 'refund',
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'reference' => 'REF-' . $transaction->reference,
            'phone_number' => $transaction->phone_number,
            'status' => 'pending',
            'payment_method' => $transaction->payment_method,
            'provider' => $transaction->provider,
            'transaction_data' => ['original_transaction' => $transaction->id]
        ]);

        return response()->json([
            'success' => true,
            'refund_id' => $refund->id,
            'reference' => $refund->reference
        ]);
    }

    /**
     * Détecter l'opérateur (MTN ou Orange)
     */
    protected function detectOperator($phone) {
        // Extraire les 3 premiers chiffres après +237
        $code = substr($phone, 4, 3);

        if (in_array($code, ['650', '651', '652', '653', '654', '655', '656', '657', '658', '659'])) {
            return 'mtn';
        } elseif (in_array($code, ['690', '691', '692', '693', '694', '695', '696', '697'])) {
            return 'orange';
        }

        return 'unknown';
    }

    /**
     * Obtenir les plans disponibles
     * GET /api/payments/plans
     */
    public function plans() {
        return response()->json([
            'plans' => [
                [
                    'name' => 'Basique',
                    'key' => 'basique',
                    'price' => 0,
                    'currency' => 'XAF',
                    'duration_days' => 30,
                    'features' => [
                        'Signalements illimités',
                        'Support par SMS',
                        'Historique 30 jours'
                    ]
                ],
                [
                    'name' => 'Standard',
                    'key' => 'standard',
                    'price' => 2000,
                    'currency' => 'XAF',
                    'duration_days' => 90,
                    'features' => [
                        'Signalements illimités',
                        'Support prioritaire',
                        'Suivi en temps réel',
                        'Historique 90 jours'
                    ]
                ],
                [
                    'name' => 'Premium',
                    'key' => 'premium',
                    'price' => 5000,
                    'currency' => 'XAF',
                    'duration_days' => 365,
                    'features' => [
                        'Signalements illimités',
                        'Support prioritaire 24/7',
                        'Suivi en temps réel',
                        'Historique 365 jours',
                        'Géolocalisation automatique',
                        'Notifications SMS',
                        'Rapports personnalisés'
                    ]
                ]
            ]
        ]);
    }
}
