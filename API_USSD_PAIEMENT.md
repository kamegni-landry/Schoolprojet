# 📱 API USSD & Paiement - DoualaClean

## 🏗️ Architecture

```
Téléphone Utilisateur
    ⬇
Opérateur (MTN/Orange)
    ⬇
Africa's Talking (Passerelle USSD)
    ⬇
Backend Laravel (Routes USSD)
    ⬇
MySQL (Stockage)
```

---

## ⚙️ Configuration

### 1. Installer les dépendances
```bash
composer require guzzlehttp/guzzle
```

### 2. Configurer les variables d'environnement

Ajouter à votre `.env`:
```env
AFRICAS_TALKING_API_KEY=your_api_key_here
AFRICAS_TALKING_USERNAME=sandbox
AFRICAS_TALKING_ENV=sandbox
USSD_CALLBACK_URL=https://your-ngrok-url.com/api/ussd
PAYMENT_WEBHOOK_URL=https://your-ngrok-url.com/api/payments/webhook
```

### 3. Lancer les migrations
```bash
php artisan migrate
```

---

## 📡 API USSD

### Endpoint Principal
**POST** `/api/ussd`

**Headers:**
```
Content-Type: application/x-www-form-urlencoded
```

**Body:**
```
sessionId=UNIQUE_SESSION_ID
phoneNumber=+237670000000
text=1
serviceCode=123
```

### Réponse Format

**Affichage du menu:**
```
CON Bienvenue...
1. Option 1
2. Option 2
```

**Fin de session:**
```
END Merci d'avoir utilisé DoualaClean
```

---

## 🎯 Flux USSD Complet

### Flux de Signalement
```
Utilisateur → *123#
    ↓
Menu Principal
    ↓
Choisir Option 1 (Signaler)
    ↓
Sélectionner Quartier (Akwa, Bessengue, etc)
    ↓
Choisir Type de Déchet
    ↓
✅ Signalement enregistré avec code unique: SIG-2026-0001
📨 SMS reçu avec confirmation
```

### Flux de Suivi
```
Utilisateur → 2
    ↓
Entrer le code (SIG-2026-0001)
    ↓
Voir le statut du signalement
```

### Flux d'Abonnement
```
Utilisateur → 4
    ↓
Choisir Plan (Basique/Standard/Premium)
    ↓
Si payant → Initier paiement
    ↓
✅ Abonnement activé
```

---

## 💳 API Paiement

### 1. Obtenir les plans disponibles
**GET** `/api/payments/plans`

**Réponse:**
```json
{
  "plans": [
    {
      "name": "Basique",
      "key": "basique",
      "price": 0,
      "duration_days": 30,
      "features": ["Signalements illimités", "Support par SMS"]
    },
    {
      "name": "Standard",
      "key": "standard",
      "price": 2000,
      "duration_days": 90
    },
    {
      "name": "Premium",
      "key": "premium",
      "price": 5000,
      "duration_days": 365
    }
  ]
}
```

### 2. Initier un paiement d'abonnement
**POST** `/api/payments/subscribe`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Body:**
```json
{
  "plan": "standard",
  "phone_number": "+237670000000"
}
```

**Réponse Succès:**
```json
{
  "success": true,
  "transaction_id": 1,
  "reference": "TXN-2026-00001",
  "amount": 2000,
  "message": "Veuillez confirmer le paiement sur votre téléphone"
}
```

### 3. Vérifier le statut d'une transaction
**GET** `/api/payments/{reference}/status`

**Réponse:**
```json
{
  "reference": "TXN-2026-00001",
  "status": "completed|pending|failed",
  "amount": 2000,
  "created_at": "2026-05-15T10:00:00Z"
}
```

### 4. Obtenir l'historique des paiements
**GET** `/api/payments/history`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Réponse:**
```json
{
  "total": 5,
  "data": [
    {
      "id": 1,
      "reference": "TXN-2026-00001",
      "amount": 2000,
      "status": "completed",
      "created_at": "2026-05-15T10:00:00Z"
    }
  ]
}
```

### 5. Rembourser une transaction
**POST** `/api/payments/{id}/refund`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Réponse:**
```json
{
  "success": true,
  "refund_id": 5,
  "reference": "REF-TXN-2026-00001"
}
```

---

## 🔗 Webhooks

### Webhook Paiement
**POST** `/api/payments/webhook`

**Body reçu d'Africa's Talking:**
```json
{
  "reference": "TXN-2026-00001",
  "status": "completed",
  "amount": 2000,
  "transactionId": "ABC123456"
}
```

### Webhook Callback USSD
**POST** `/api/ussd/payment/callback`

**Body:**
```json
{
  "status": "Success",
  "transactionId": "ABC123456",
  "amount": 2000,
  "phoneNumber": "+237670000000"
}
```

---

## 🧪 Tests avec NGROK

### 1. Installer NGROK
```bash
npm install -g ngrok
```

### 2. Lancer NGROK
```bash
ngrok http 8000
```

Vous obtiendrez:
```
https://abc123.ngrok.io -> localhost:8000
```

### 3. Configurer Africa's Talking
- Aller sur https://africastalking.com
- Accéder au Sandbox
- Configurer **USSD Callback URL**: `https://abc123.ngrok.io/api/ussd`
- Configurer **Payment Webhook**: `https://abc123.ngrok.io/api/payments/webhook`

### 4. Tester avec le code USSD
```
*384*123#
```

---

## 🧪 Tests Postman

### Collection USSD
```bash
POST /api/ussd
{
  "sessionId": "session-12345",
  "phoneNumber": "+237670000000",
  "text": "1",
  "serviceCode": "123"
}
```

### Collection Paiement
```bash
POST /api/payments/subscribe
Header: Authorization: Bearer YOUR_TOKEN
{
  "plan": "standard",
  "phone_number": "+237670000000"
}
```

---

## 📊 Base de données

### Table: ussd_sessions
```sql
SELECT * FROM ussd_sessions;
```

### Table: transactions
```sql
SELECT * FROM transactions WHERE status = 'completed';
```

### Signalements USSD
```sql
SELECT * FROM signalements WHERE origine = 'ussd';
```

---

## 🔐 Sécurité

1. **Validation des numéros**
   - Format: +237XXXXXXXXX
   - Opérateurs: MTN (650-659), Orange (690-697)

2. **Rate Limiting**
   - 5 tentatives par numéro par minute
   - Timeout de session: 10 minutes

3. **Tokens**
   - Utiliser Sanctum pour les routes protégées
   - Les webhooks ne nécessitent pas de token

---

## 📝 Modèles Disponibles

### USSDSession
```php
$session = USSDSession::find($id);
$session->user;
$session->isExpired();
```

### Transaction
```php
$transaction = Transaction::find($id);
$transaction->scopeSuccessful();
$transaction->scopePending();
$transaction->user;
```

### Signalement (avec USSD)
```php
$signalement = Signalement::where('origine', 'ussd')->get();
$signalement->code_unique; // SIG-2026-0001
```

---

## 🚀 Déploiement

### Environnement Production
```env
AFRICAS_TALKING_ENV=production
APP_ENV=production
APP_DEBUG=false
```

### Commandes
```bash
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
```

---

## 📞 Support Africa's Talking

- Site: https://africastalking.com
- Documentation: https://africastalking.com/sms
- USSD: https://africastalking.com/ussd
- Support: support@africastalking.com
