<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AfricasTalkingService {

    protected $apiKey;
    protected $username;
    protected $baseUrl = 'https://api.sandbox.africastalking.com';

    public function __construct() {
        $this->apiKey = config('services.africas_talking.api_key');
        $this->username = config('services.africas_talking.username');
    }

    /**
     * Envoyer un SMS
     */
    public function sendSMS($phone, $message) {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'apiKey' => $this->apiKey,
            ])->post($this->baseUrl . '/version1/messaging', [
                'username' => $this->username,
                'to' => $phone,
                'message' => $message,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SMS Send Error:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Envoyer une notification USSD
     */
    public function sendUSSDPush($phone, $message) {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'apiKey' => $this->apiKey,
            ])->post($this->baseUrl . '/version1/ussd/send', [
                'username' => $this->username,
                'to' => $phone,
                'message' => $message,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('USSD Push Error:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Initier un paiement C2B (Mobile Money)
     */
    public function initiateMobilePayment($phone, $amount, $reference) {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'apiKey' => $this->apiKey,
            ])->post($this->baseUrl . '/version3/mobile/checkout/request', [
                'username' => $this->username,
                'phone' => $phone,
                'amount' => $amount,
                'currencyCode' => 'XAF',
                'metadata' => [
                    'reference' => $reference,
                    'type' => 'subscription'
                ]
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Mobile Payment Error:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Vérifier le statut d'une transaction
     */
    public function getTransactionStatus($transactionId) {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'apiKey' => $this->apiKey,
            ])->get($this->baseUrl . '/version3/mobile/checkout/status', [
                'username' => $this->username,
                'transactionId' => $transactionId,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Transaction Status Error:', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Valider le numéro de téléphone
     */
    public function validatePhoneNumber($phone) {
        // Normaliser au format international Cameroun (+237)
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (!preg_match('/^\+?237\d{8,9}$/', $phone)) {
            return false;
        }

        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
