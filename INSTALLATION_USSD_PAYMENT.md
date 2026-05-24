# 🚀 Guide d'Installation et Déploiement - USSD & Paiement

## 📋 Prérequis

- Laravel 11
- PHP 8.2+
- MySQL 8.0+
- Composer
- NGROK (pour les tests locaux)
- Compte Africa's Talking (https://africastalking.com)

---

## ⚡ Installation Rapide

### 1. Ajouter les fichiers au projet

```bash
# Modèles
cp app/Models/USSDSession.php app/Models/
cp app/Models/Transaction.php app/Models/

# Services
cp app/Services/AfricasTalkingService.php app/Services/

# Contrôleurs
cp app/Http/Controllers/USSDController.php app/Http/Controllers/
cp app/Http/Controllers/PaymentController.php app/Http/Controllers/

# Migrations
cp database/migrations/2026_05_15_*.php database/migrations/

# Configuration
cp config/services.php config/
```

### 2. Installer les dépendances

```bash
composer require guzzlehttp/guzzle:^7.8
```

### 3. Configurer les variables d'environnement

Créer/éditer `.env`:

```env
# Africa's Talking
AFRICAS_TALKING_API_KEY=your_api_key_here
AFRICAS_TALKING_USERNAME=sandbox
AFRICAS_TALKING_ENV=sandbox

# URLs
USSD_CALLBACK_URL=https://your-ngrok.com/api/ussd
PAYMENT_WEBHOOK_URL=https://your-ngrok.com/api/payments/webhook
```

### 4. Lancer les migrations

```bash
php artisan migrate
```

### 5. Ajouter les routes

Les routes USSD et Paiement sont déjà dans `routes/api.php`.

---

## 🔧 Configuration Africa's Talking

### 1. Créer un compte Sandbox

- Aller sur https://africastalking.com
- Cliquer sur "Sign Up"
- Créer un compte développeur

### 2. Obtenir les clés API

1. Dashboard → Settings
2. Copier votre **API Key**
3. Coller dans `.env` sous `AFRICAS_TALKING_API_KEY`

### 3. Configurer USSD

1. Dashboard → USSD
2. Dans la section "Sandbox":
   - **Service Code**: 123
   - **Callback URL**: https://your-ngrok.com/api/ussd
3. Cliquer sur "Update"

### 4. Configurer les Webhooks de Paiement

1. Dashboard → Mobile Checkout
2. **Webhook URL**: https://your-ngrok.com/api/payments/webhook
3. Activer les notifications

---

## 🧪 Tests Locaux avec NGROK

### 1. Installer NGROK

```bash
npm install -g ngrok
# ou
brew install ngrok  # macOS
```

### 2. Lancer NGROK

```bash
ngrok http 8000
```

Vous verrez:
```
Forwarding  https://abc123.ngrok.io -> http://localhost:8000
```

### 3. Ajouter l'URL à `.env`

```env
USSD_CALLBACK_URL=https://abc123.ngrok.io/api/ussd
PAYMENT_WEBHOOK_URL=https://abc123.ngrok.io/api/payments/webhook
```

### 4. Lancer le serveur Laravel

```bash
php artisan serve
```

### 5. Importer dans Postman

1. Ouvrir Postman
2. File → Import
3. Sélectionner `DoualaClean_USSD_Payment.postman_collection.json`
4. Configurer les variables:
   - `base_url`: http://localhost:8000/api
   - `ngrok_url`: https://abc123.ngrok.io/api
   - `phone`: +237670000000

### 6. Tester avec USSD

**Pour les développeurs Africa's Talking (Sandbox):**

```
*384*123#
```

---

## 📊 Tests Complets

### Test 1: Menu USSD
```bash
curl -X POST "http://localhost:8000/api/ussd" \
  -d "sessionId=test-001&phoneNumber=+237670000000&text=&serviceCode=123"
```

### Test 2: Signalement
```bash
curl -X POST "http://localhost:8000/api/ussd" \
  -d "sessionId=test-001&phoneNumber=+237670000000&text=1&serviceCode=123"
```

### Test 3: Plans de paiement
```bash
curl -X GET "http://localhost:8000/api/payments/plans" \
  -H "Content-Type: application/json"
```

### Test complet avec script
```bash
bash test_ussd_api.sh
# ou
php test_api.php
```

---

## 🌐 Déploiement en Production

### 1. Préparer le serveur

```bash
# Cloner le repo
git clone your-repo.git
cd your-repo

# Installer les dépendances
composer install --no-dev --optimize-autoloader

# Générer la clé
php artisan key:generate

# Lancer les migrations
php artisan migrate --force

# Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Configurer les variables production

Éditer `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Africa's Talking (Production)
AFRICAS_TALKING_API_KEY=your_production_key
AFRICAS_TALKING_USERNAME=your_username
AFRICAS_TALKING_ENV=production

# URLs Production
USSD_CALLBACK_URL=https://your-domain.com/api/ussd
PAYMENT_WEBHOOK_URL=https://your-domain.com/api/payments/webhook
```

### 3. Configurer SSL/TLS

```bash
# Let's Encrypt
certbot certonly --webroot -w /path/to/public -d your-domain.com
```

### 4. Configurer Nginx/Apache

**Nginx:**
```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    root /path/to/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Configurer Africa's Talking pour Production

1. Passer du Sandbox à Production
2. Ajouter les URLs:
   - USSD Callback: https://your-domain.com/api/ussd
   - Payment Webhook: https://your-domain.com/api/payments/webhook

### 6. Vérifier les Logs

```bash
tail -f storage/logs/laravel.log
```

---

## 🔐 Sécurité

### Rate Limiting

Ajouter à `app/Http/Middleware/ThrottleRequests.php`:

```php
'ussd' => '10,1', // 10 requêtes par minute
'payment' => '5,1', // 5 requêtes par minute
```

### Validation

```php
// Valider les webhooks
if (!verifyWebhookSignature($request)) {
    return response()->json(['error' => 'Invalid signature'], 403);
}
```

### CORS

Configuré dans `config/cors.php`:

```php
'allowed_origins' => [
    'http://localhost:3000',
    'https://your-domain.com',
],
```

---

## 🐛 Dépannage

### Problème: "Invalid API Key"
```
Solution: Vérifier AFRICAS_TALKING_API_KEY dans .env
```

### Problème: "Callback URL not reachable"
```
Solution: Vérifier que NGROK est actif et l'URL est correcte
```

### Problème: "Session expired"
```
Solution: Le timeout USSD est 10 minutes. Augmenter si nécessaire.
```

### Problème: "Phone number invalid"
```
Solution: Utilisez le format +237XXXXXXXXX
Exemples valides:
- +237670000000 (MTN)
- +237690000000 (Orange)
```

---

## 📈 Monitoring

### Dashboard Laravel
```bash
php artisan tinker

# Vérifier les transactions
Transaction::where('status', 'completed')->count()

# Vérifier les sessions USSD
USSDSession::where('status', 'active')->count()

# Vérifier les signalements USSD
Signalement::where('origine', 'ussd')->count()
```

### Logs
```bash
tail -f storage/logs/laravel.log

# Filtrer les erreurs USSD
grep "USSD" storage/logs/laravel.log

# Filtrer les erreurs paiement
grep "Payment" storage/logs/laravel.log
```

---

## 📞 Support

- **Africa's Talking**: https://africastalking.com/support
- **Laravel**: https://laravel.com/docs
- **GitHub Issues**: Ouvrir une issue dans le repo

---

## ✅ Checklist de Déploiement

- [ ] Cloner le repo
- [ ] Installer les dépendances (`composer install`)
- [ ] Configurer `.env` (clés API, URLs, BD)
- [ ] Lancer les migrations (`php artisan migrate`)
- [ ] Tester localement avec NGROK
- [ ] Configurer Africa's Talking (Sandbox)
- [ ] Configurer le serveur production
- [ ] Configurer SSL/TLS
- [ ] Configurer Africa's Talking (Production)
- [ ] Vérifier les logs
- [ ] Tester les webhooks en production
- [ ] Vérifier les SMS et notifications
