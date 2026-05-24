#!/bin/bash

# Script de test des APIs USSD et Paiement
# Utilisation: bash test_api.sh

BASE_URL="http://localhost:8000/api"
NGROK_URL="${NGROK_URL:-$BASE_URL}"  # Remplacer par votre URL NGROK

echo "🧪 Tests DoualaClean USSD & Paiement"
echo "======================================="

# ──────────────────────────────────────────────
# TEST 1: Menu USSD Principal
# ──────────────────────────────────────────────
echo ""
echo "1️⃣ Test: Menu USSD Principal"
curl -X POST "$NGROK_URL/ussd" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=session-test-001&phoneNumber=+237670000000&text=&serviceCode=123"

# ──────────────────────────────────────────────
# TEST 2: Choisir option Signalement
# ──────────────────────────────────────────────
echo ""
echo ""
echo "2️⃣ Test: Sélectionner Signalement (option 1)"
curl -X POST "$NGROK_URL/ussd" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=session-test-001&phoneNumber=+237670000000&text=1&serviceCode=123"

# ──────────────────────────────────────────────
# TEST 3: Sélectionner Quartier
# ──────────────────────────────────────────────
echo ""
echo ""
echo "3️⃣ Test: Sélectionner Quartier (Akwa = 1)"
curl -X POST "$NGROK_URL/ussd" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=session-test-001&phoneNumber=+237670000000&text=1*1&serviceCode=123"

# ──────────────────────────────────────────────
# TEST 4: Sélectionner Type de Déchet
# ──────────────────────────────────────────────
echo ""
echo ""
echo "4️⃣ Test: Sélectionner Type (Ordures = 1)"
curl -X POST "$NGROK_URL/ussd" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=session-test-001&phoneNumber=+237670000000&text=1*1*1&serviceCode=123"

# ──────────────────────────────────────────────
# TEST 5: Obtenir les plans de paiement
# ──────────────────────────────────────────────
echo ""
echo ""
echo "5️⃣ Test: Obtenir les plans de paiement"
curl -X GET "$NGROK_URL/payments/plans" \
  -H "Content-Type: application/json"

# ──────────────────────────────────────────────
# TEST 6: Suivi d'un signalement
# ──────────────────────────────────────────────
echo ""
echo ""
echo "6️⃣ Test: Suivi (option 2)"
curl -X POST "$NGROK_URL/ussd" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=session-test-002&phoneNumber=+237670000000&text=2&serviceCode=123"

# ──────────────────────────────────────────────
# TEST 7: Suivi avec code
# ──────────────────────────────────────────────
echo ""
echo ""
echo "7️⃣ Test: Suivi avec code SIG-2026-0001"
curl -X POST "$NGROK_URL/ussd" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=session-test-002&phoneNumber=+237670000000&text=2*SIG-2026-0001&serviceCode=123"

# ──────────────────────────────────────────────
# TEST 8: Abonnements (option 4)
# ──────────────────────────────────────────────
echo ""
echo ""
echo "8️⃣ Test: Menu Abonnements (option 4)"
curl -X POST "$NGROK_URL/ussd" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=session-test-003&phoneNumber=+237670000000&text=4&serviceCode=123"

# ──────────────────────────────────────────────
# TEST 9: Sélectionner Plan Premium
# ──────────────────────────────────────────────
echo ""
echo ""
echo "9️⃣ Test: Sélectionner Premium (option 3)"
curl -X POST "$NGROK_URL/ussd" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "sessionId=session-test-003&phoneNumber=+237670000000&text=4*3&serviceCode=123"

echo ""
echo ""
echo "✅ Tests terminés!"
