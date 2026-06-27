<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\AfricasTalkingService;
use App\Services\MobileMoneySimulator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SimulationController extends \Illuminate\Routing\Controller
{
    public function simulatePayment(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:basique,standard,premium',
            'phone_number' => 'required|string',
            'outcome' => 'required|in:success,failed',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $plan = $request->input('plan');
        $phone = (new AfricasTalkingService())->validatePhoneNumber($request->input('phone_number'));
        if (!$phone) {
            return response()->json(['error' => 'Numéro invalide'], 400);
        }

        $amounts = [
            'basique' => 0,
            'standard' => 2000,
            'premium' => 5000,
        ];
        $amount = $amounts[$plan] ?? 0;

        $reference = 'TXN-' . date('Y') . '-' . Str::padLeft((string)(Transaction::count() + 1), 5, '0');

        if ($amount === 0) {
            // Basique gratuit : transaction directement complétée
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'payment',
                'amount' => $amount,
                'currency' => 'XAF',
                'reference' => $reference,
                'phone_number' => $phone,
                'status' => 'completed',
                'payment_method' => (new AfricasTalkingService())->detectOperator($phone),
                'provider' => 'simulator',
                'transaction_data' => ['plan' => $plan, 'simulated' => true],
                'completed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'reference' => $transaction->reference,
                'amount' => $transaction->amount,
                'message' => 'Abonnement gratuit activé (simulation)',
            ]);
        }

        // Création pending
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'type' => 'payment',
            'amount' => $amount,
            'currency' => 'XAF',
            'reference' => $reference,
            'phone_number' => $phone,
            'status' => 'pending',
            'payment_method' => (new AfricasTalkingService())->detectOperator($phone),
            'provider' => 'simulator',
            'transaction_data' => ['plan' => $plan, 'simulated' => true],
        ]);

        // Finalisation simulation
        (new MobileMoneySimulator())->finalizeReference(
            $reference,
            $phone,
            $amount,
            $request->input('outcome'),
            ['plan' => $plan]
        );

        return response()->json([
            'success' => true,
            'reference' => $reference,
            'transaction_id' => $transaction->id,
            'amount' => $amount,
            'status' => $request->input('outcome') === 'success' ? 'completed' : 'failed',
        ]);
    }
}

