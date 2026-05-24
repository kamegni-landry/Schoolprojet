# ❓ FAQ & Bonnes Pratiques - USSD & Paiement

## ❓ Questions Fréquemment Posées

### 🔴 Erreurs Courantes

#### 1. "Invalid API Key"
**Problème:** Le API Key est incorrect ou vide
**Solution:**
```bash
# Vérifier le .env
cat .env | grep AFRICAS_TALKING

# Ajouter la clé correctement
AFRICAS_TALKING_API_KEY=your_key_from_dashboard
```

#### 2. "Callback URL is not reachable"
**Problème:** Africa's Talking ne peut pas atteindre votre URL
**Solutions:**
- Vérifier que NGROK est lancé: `ngrok http 8000`
- Vérifier l'URL dans Africa's Talking Dashboard
- Vérifier les pare-feu
```bash
# Tester la connexion
curl -X POST "https://your-ngrok.com/api/ussd" -d "test=1"
```

#### 3. "Session ID invalid"
**Problème:** Le session_id reçu de Africa's Talking est vide
**Solution:**
```php
// Vérifier dans USSDController
$sessionId = $request->input('sessionId') ?? Str::uuid();
```

#### 4. "Phone number validation failed"
**Problème:** Le numéro n'est pas au bon format
**Format valide:**
```
+237XXXXXXXXX où X = 8-9 chiffres
+237650000000 ✅ (MTN)
+237690000000 ✅ (Orange)
237650000000 ❌ (manque le +)
06500000000 ❌ (mauvais format)
```

---

### 💳 Questions Paiement

#### Q: Combien de temps pour confirmer un paiement?
**R:** Généralement 2-5 secondes. L'API vérifie toutes les 2s. Timeout: 5 minutes.

#### Q: Comment tester le paiement en Sandbox?
**R:**
```
Utilisateur: sandbox
Montant: Quelconque
Réponse: Toujours réussie en Sandbox
```

#### Q: Le remboursement automatique est-il possible?
**R:** Oui, via l'API:
```php
POST /api/payments/{id}/refund
Authorization: Bearer YOUR_TOKEN
```

#### Q: Quels sont les frais de transaction?
**R:** À définir avec votre agreement Africa's Talking.
Typiquement: 1-3% + frais fixes

---

### 📱 Questions USSD

#### Q: Comment tester le USSD sans vrai téléphone?
**R:**
```bash
# Avec cURL
curl -X POST "http://localhost:8000/api/ussd" \
  -d "sessionId=test&phoneNumber=+237670000000&text=1"

# Ou importer la collection Postman
```

#### Q: Le timeout de session est 10 min, c'est modifiable?
**R:** Oui, dans `app/Http/Controllers/USSDController.php`:
```php
const SESSION_TIMEOUT = 600; // Changer à votre valeur (en secondes)
```

#### Q: Les signalements USSD sont-ils anonymes?
**R:** Oui par défaut. Associer à un utilisateur si connexion SMS préalable.

---

### 🛡️ Questions Sécurité

#### Q: Comment valider les webhooks?
**R:** Vérifier la signature:
```php
// Dans votre Middleware
public function verifyWebhookSignature(Request $request)
{
    $signature = $request->header('X-Signature');
    $payload = $request->getContent();
    $key = config('services.africas_talking.api_key');
    
    return hash_hmac('sha256', $payload, $key) === $signature;
}
```

#### Q: Comment protéger les données sensibles?
**R:**
- Utiliser HTTPS/TLS en production ✅
- Ne jamais logguer les numéros de téléphone complets ✅
- Chiffrer les données sensibles ✅
- Utiliser Sanctum pour les tokens ✅

---

## ✅ Bonnes Pratiques

### 1. Logging

```php
// ✅ BON
Log::info('USSD Signal received', [
    'quarter' => $quartier,
    'type' => $type_dechet,
    'reference' => $codeUnique
]);

// ❌ MAUVAIS
Log::info('User phone: ' . $phoneNumber . ' paid: ' . $amount);
```

### 2. Gestion d'Erreurs

```php
// ✅ BON
try {
    $result = $this->africasTalking->initiateMobilePayment(...);
    if (!$result) {
        return response()->json(['error' => 'Payment initiation failed'], 400);
    }
} catch (\Exception $e) {
    Log::error('Payment error: ' . $e->getMessage());
    return response()->json(['error' => 'Server error'], 500);
}

// ❌ MAUVAIS
$result = $this->africasTalking->initiateMobilePayment(...);
```

### 3. Validation

```php
// ✅ BON
$phone = $this->africasTalking->validatePhoneNumber($phoneNumber);
if (!$phone) {
    return 'END Numéro invalide ❌';
}

// ❌ MAUVAIS
$phone = $phoneNumber; // Sans validation
```

### 4. Rate Limiting

```php
// Ajouter dans routes/api.php
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/ussd', [USSDController::class, 'menu']);
    Route::post('/payments/webhook', [PaymentController::class, 'webhook']);
});
```

### 5. Tests

```php
// ✅ BON
public function test_ussd_menu_return_valid_response()
{
    $response = $this->post('/api/ussd', [
        'sessionId' => 'test',
        'phoneNumber' => '+237670000000',
        'text' => ''
    ]);
    
    $this->assertStringContainsString('Bienvenue', $response->getContent());
}

// ❌ MAUVAIS
// Pas de test du tout
```

### 6. Performance

```php
// ✅ BON - Indexer les colonnes fréquemment filtrées
Schema::create('transactions', function (Blueprint $table) {
    $table->index('reference');
    $table->index('phone_number');
    $table->index('status');
});

// ✅ BON - Utiliser les relations
$transactions = Transaction::with('user')->get();

// ❌ MAUVAIS - N+1 queries
foreach ($transactions as $t) {
    echo $t->user->name; // Nouvelle requête pour chaque transaction
}
```

### 7. Documentation

```php
// ✅ BON - Ajouter des commentaires
/**
 * Traiter une requête USSD
 * @param Request $request Session, téléphone, texte
 * @return string Réponse USSD (CON ou END)
 */
public function menu(Request $request): string

// ❌ MAUVAIS
public function menu(Request $request)
```

---

## 🔍 Dépannage Avancé

### Debug Mode

```php
// Dans .env
APP_DEBUG=true

// Dans USSDController
Log::debug('USSD Debug', [
    'input' => $text,
    'session_data' => $session->data,
    'response' => $response
]);

tail -f storage/logs/laravel.log
```

### Vérifier les Transactions

```bash
# Dans Tinker
php artisan tinker

# Lister les transactions
Transaction::latest()->limit(10)->get()

# Chercher une transaction
Transaction::where('reference', 'TXN-2026-00001')->first()

# Transactions par statut
Transaction::where('status', 'pending')->get()
```

### Vérifier les Sessions USSD

```bash
php artisan tinker

# Sessions actives
USSDSession::where('status', 'active')->get()

# Sessions expirées
USSDSession::where('status', 'expired')->count()

# Nettoyer les sessions expirées
USSDSession::where('expires_at', '<', now())->delete()
```

---

## 📊 Statistiques

### Requêtes Typiques

```sql
-- Nombre de signalements USSD
SELECT COUNT(*) FROM signalements WHERE origine = 'ussd';

-- Revenus totaux
SELECT SUM(amount) FROM transactions WHERE status = 'completed';

-- Paiements par plan
SELECT plan, COUNT(*) as count, SUM(prix) as total 
FROM abonnements 
GROUP BY plan;

-- Opérateurs utilisés
SELECT 
  payment_method, 
  COUNT(*) as count, 
  SUM(amount) as total 
FROM transactions 
GROUP BY payment_method;
```

### Métriques Clés

```
- Taux de conversion: Sessions USSD -> Paiements
- Temps moyen de confirmation: Initiation -> Paiement
- Signalements par jour
- Revenus par jour/mois
- Taux d'erreur par endpoint
```

---

## 🚀 Optimisations

### 1. Caching

```php
// Cacher les plans (durée de vie: 1 jour)
$plans = Cache::remember('payment_plans', 86400, function () {
    return Abonnement::$tarifs;
});
```

### 2. Queue

```php
// Envoyer les SMS en arrière-plan
class SendPaymentConfirmationSMS implements ShouldQueue {
    public function handle()
    {
        $this->africasTalking->sendSMS(...);
    }
}
```

### 3. CDN

```php
// Servir les images statiques depuis CDN
$url = config('filesystems.disks.s3.url') . $path;
```

---

## 📞 Support & Ressources

### Documentation Officielle

- **Africa's Talking USSD**: https://africastalking.com/ussd/api
- **Africa's Talking Mobile Money**: https://africastalking.com/mobile-checkout
- **Laravel Documentation**: https://laravel.com/docs/11.x
- **API Documentation**: [Votre docs complète]

### Contactez le Support

- **Email**: support@africas-talking.com
- **Slack**: [Si disponible]
- **Forum**: https://africastalking.com/community

### Outils Utiles

- **Postman**: Collection fournie
- **NGROK**: Pour les tests locaux
- **Laravel Tinker**: Debugger
- **Telescope**: Monitoring Laravel

---

## 📝 Changelog

### v1.0.0 (2026-05-15)
- ✅ API USSD complète
- ✅ Système de paiement
- ✅ Documentation complète
- ✅ Collection Postman
- ✅ Scripts de test

### v1.1.0 (Prochainement)
- 🔄 Géolocalisation USSD
- 🔄 Multi-langue support
- 🔄 Notifications push
- 🔄 Dashboard admin

---

## 🎓 Formation & Support

### Formation Interne

1. Lire [API_USSD_PAIEMENT.md](API_USSD_PAIEMENT.md)
2. Lire [INSTALLATION_USSD_PAYMENT.md](INSTALLATION_USSD_PAYMENT.md)
3. Lire [INTEGRATION_FRONTEND.md](INTEGRATION_FRONTEND.md)
4. Tester avec Postman
5. Tester avec NGROK
6. Déployer en staging

### Support Technique

- **Slack Channel**: #ussd-payment-support
- **Documentation**: Wiki du projet
- **Issues**: GitHub Issues
- **Meetings**: Jeudi 10h (Chat d'équipe)

---

## ✨ Conclusion

Vous avez maintenant une solution complète USSD + Paiement!

**Prochaines étapes:**

1. ✅ Configurer Africa's Talking
2. ✅ Lancer les migrations
3. ✅ Tester avec Postman
4. ✅ Tester avec NGROK
5. ✅ Déployer en production
6. ✅ Monitorer et optimiser

**Besoin d'aide?** Consultez la FAQ ou contactez le support! 🚀
