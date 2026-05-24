<?php

/**
 * Script de test pour les APIs USSD et Paiement
 * Utilisation: php test_api.php
 */

$baseUrl = 'http://localhost:8000/api';

echo "🧪 Tests DoualaClean USSD & Paiement\n";
echo "=====================================\n\n";

// Test 1: Menu USSD
echo "1️⃣ Test: Menu USSD Principal\n";
testUSSD('', 'session-001', '+237670000000');

// Test 2: Signalement
echo "\n2️⃣ Test: Sélectionner Signalement (option 1)\n";
testUSSD('1', 'session-001', '+237670000000');

// Test 3: Quartier
echo "\n3️⃣ Test: Sélectionner Quartier (Akwa)\n";
testUSSD('1*1', 'session-001', '+237670000000');

// Test 4: Type
echo "\n4️⃣ Test: Sélectionner Type (Ordures)\n";
testUSSD('1*1*1', 'session-001', '+237670000000');

// Test 5: Plans de paiement
echo "\n5️⃣ Test: Obtenir les plans\n";
testPaymentPlans();

// Test 6: Suivi
echo "\n6️⃣ Test: Suivi (option 2)\n";
testUSSD('2', 'session-002', '+237670000000');

// Test 7: Suivi avec code
echo "\n7️⃣ Test: Suivi avec code\n";
testUSSD('2*SIG-2026-0001', 'session-002', '+237670000000');

// Test 8: Abonnements
echo "\n8️⃣ Test: Menu Abonnements\n";
testUSSD('4', 'session-003', '+237670000000');

echo "\n✅ Tests terminés!\n";

// ──────────────────────────────────────────────
// Fonctions Helper
// ──────────────────────────────────────────────

function testUSSD($text, $sessionId, $phoneNumber) {
    global $baseUrl;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/ussd',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'sessionId' => $sessionId,
            'phoneNumber' => $phoneNumber,
            'text' => $text,
            'serviceCode' => '123'
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo "Input: {$text}\n";
    echo "Response:\n{$response}\n";
}

function testPaymentPlans() {
    global $baseUrl;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/payments/plans',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $json = json_decode($response, true);
    echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
}
