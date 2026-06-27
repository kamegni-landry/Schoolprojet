<?php

namespace App\Services;

use App\Models\Abonnement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MobileMoneySimulator

{
    public function simulateCheckout(string $phone, float $amount, string $reference, string $providerOutcome = 'success'): array
    {
        $status = strtolower($providerOutcome) === 'success' ? 'Success' : 'Failed';

        // Retourne la structure attendue (code "completed" simulé via webhook)
        return [
            'data' => [
                'status' => 'pending',
                'transactionId' => $reference,
                'amount' => $amount,
                'phoneNumber' => $phone,
                'reference' => $reference,
                'simulated' => true,
            ],
            'simulated_outcome' => [
                'status' => $status,
            ],
        ];
    }

    public function finalizeReference(string $reference, string $phone, float $amount, string $outcome, array $transactionData = []): void
    {
        $transaction = Transaction::where('reference', $reference)->first();
        if (!$transaction) {
            Log::warning('MM Simulator: transaction not found', ['reference' => $reference]);
            return;
        }

        if (strtolower($outcome) === 'success') {
            $transaction->update([
                'status' => 'completed',
                'transaction_data' => array_merge($transactionData, [
                    'status' => 'Success',
                    'transactionId' => $reference,
                    'amount' => $amount,
                    'phoneNumber' => $phone,
                ]),
                'completed_at' => now(),
            ]);

            // Activer abonnement si possible
            $user = $transaction->user_id ? User::find($transaction->user_id) : null;
            if ($user) {
                $plan = $transactionData['plan'] ?? 'standard';
                $this->activateSubscription($user, $plan, $amount);
            }
        } else {
            $transaction->update([
                'status' => 'failed',
                'error_message' => 'Simulated failure',
                'transaction_data' => array_merge($transactionData, [
                    'status' => 'Failed',
                    'transactionId' => $reference,
                    'amount' => $amount,
                    'phoneNumber' => $phone,
                ]),
            ]);
        }
    }

    private function activateSubscription(User $user, string $plan, float $amount): void
    {
        $durations = [
            'basique' => 30,
            'standard' => 90,
            'premium' => 365,
        ];

        $duration = $durations[$plan] ?? 30;

        // Eviter de créer des doublons si déjà actif (simple stratégie)
        $existingActive = Abonnement::where('user_id', $user->id)
            ->where('statut', 'actif')
            ->where('plan', $plan)
            ->first();

        if ($existingActive) {
            return;
        }

        Abonnement::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'prix' => $amount,
            'statut' => 'actif',
            'date_debut' => now(),
            'date_fin' => now()->addDays($duration),
        ]);
    }
}

