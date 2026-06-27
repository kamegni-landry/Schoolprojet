<?php
namespace App\Http\Controllers;

use App\Models\USSDSession;
use App\Models\User;
use App\Models\Signalement;
use App\Models\Abonnement;
use App\Services\AfricasTalkingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class USSDController extends Controller {

    protected $africasTalking;
    const SESSION_TIMEOUT = 600; // 10 minutes

    public function __construct() {
        $this->africasTalking = new AfricasTalkingService();
    }

    /**
     * Gérer les requêtes USSD
     * POST /api/ussd
     * Body: { sessionId, phoneNumber, text, serviceCode }
     */
    public function menu(Request $request) {
        $sessionId = $request->input('sessionId');
        $phoneNumber = $request->input('phoneNumber');
        $text = $request->input('text', '');
        $serviceCode = $request->input('serviceCode');

        // Valider le numéro
        $phoneNumber = $this->africasTalking->validatePhoneNumber($phoneNumber);
        if (!$phoneNumber) {
            return response('END Numéro invalide', 200)
                ->header('Content-Type', 'text/plain');
        }

        // Récupérer ou créer la session
        $session = USSDSession::where('session_id', $sessionId)->first();

        if (!$session || $session->isExpired()) {
            $session = USSDSession::create([
                'session_id' => $sessionId,
                'phone_number' => $phoneNumber,
                'current_step' => 'menu',
                'status' => 'active',
                'expires_at' => now()->addSeconds(self::SESSION_TIMEOUT)
            ]);
        } else {
            $session->update(['expires_at' => now()->addSeconds(self::SESSION_TIMEOUT)]);
        }

        // Traiter la saisie
        $response = $this->processInput($session, $text);

        // Enregistrer l'étape
        $session->update(['current_step' => $text]);
        $session->save();

        return response($response, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Traiter l'input USSD
     */
    protected function processInput(USSDSession $session, string $text): string {
        $data = $session->data ?? [];

        // Menu Principal
        if ($text === '') {
            return 'CON Bienvenue sur DoualaClean

1. Signaler un problème
2. Suivre mon signalement
3. Demander un ramassage
4. Abonnements
5. Paiements
6. Quitter';
        }

        // ──────────────────────────────────────────
        // SIGNALEMENT
        // ──────────────────────────────────────────
        if ($text === '1') {
            return 'CON Choisissez votre quartier:

1. Akwa
2. Bessengue
3. Bonamoussadi
4. Douala 1
5. Douala 2
6. Douala 3
7. Douala 4
8. Douala 5
9. Douala 6
10. Autre';
        }

        // Enregistrement du quartier
        if (preg_match('/^1\*(\d+)$/', $text, $matches)) {
            $quartiers = [
                1 => 'Akwa', 2 => 'Bessengue', 3 => 'Bonamoussadi',
                4 => 'Douala 1', 5 => 'Douala 2', 6 => 'Douala 3',
                7 => 'Douala 4', 8 => 'Douala 5', 9 => 'Douala 6', 10 => 'Autre'
            ];

            $quartier = $quartiers[$matches[1]] ?? 'Autre';
            $session->data = ['quartier' => $quartier];
            $session->save();

            return 'CON Description du problème:

1. Ordures
2. Eaux stagnantes
3. Dégâts
4. Autre';
        }

        // Type de déchet
        if (preg_match('/^1\*\d+\*(\d+)$/', $text, $matches)) {
            $types = [
                1 => 'Ordures', 2 => 'Eaux stagnantes',
                3 => 'Dégâts', 4 => 'Autre'
            ];

            $data['type_dechet'] = $types[$matches[1]] ?? 'Autre';
            $session->data = $data;
            $session->save();

            // Créer le signalement
            $codeUnique = 'SIG-' . date('Y') . '-' . str_pad(Signalement::count() + 1, 4, '0', STR_PAD_LEFT);
            
            Signalement::create([
                'code_unique' => $codeUnique,
                'ussd_session_id' => $session->session_id,
                'phone_number' => $session->phone_number,
                'quartier' => $data['quartier'] ?? 'Autre',
                'type_dechet' => $data['type_dechet'] ?? 'Autre',
                'description' => 'Signalement USSD',
                'lieu' => $data['quartier'] ?? 'Douala',
                'statut' => 'en_attente',
                'origine' => 'ussd'
            ]);

            // Envoyer SMS de confirmation
            $this->africasTalking->sendSMS(
                $session->phone_number,
                "Votre signalement a été reçu. Code: {$codeUnique} ✅"
            );

            return "END Signalement enregistré ✅
Code: {$codeUnique}";
        }

        // ──────────────────────────────────────────
        // SUIVI
        // ──────────────────────────────────────────
        if ($text === '2') {
            return 'CON Entrez votre code de signalement (SIG-YYYY-0001):';
        }

        if (preg_match('/^2\*(.+)$/', $text, $matches)) {
            $codeUnique = strtoupper($matches[1]);
            $signalement = Signalement::where('code_unique', $codeUnique)->first();

            if (!$signalement) {
                return 'END Signalement non trouvé ❌';
            }

            return "END Suivi - {$signalement->code_unique}
Quartier: {$signalement->quartier}
Statut: {$signalement->statut}
Créé: {$signalement->created_at->format('d/m/Y')}";
        }

        // ──────────────────────────────────────────
        // RAMASSAGE (USSD complet simulé: adresse, description, fréquence, phone_paiement)
        // ──────────────────────────────────────────
        if ($text === '3') {
            $session->data = ['ramassage_step' => 'frequence'];
            $session->save();

            return 'CON Choisissez la fréquence:\r\n\r\n1. 1 fois / semaine (2000 FCFA)\r\n2. 2 fois / semaine (3000 FCFA)';
        }

        // 3*1 ou 3*2 => fréquence
        if (preg_match('/^3\*(\d+)$/', $text, $matches)) {
            $freqMap = [1 => '1_semaine', 2 => '2_semaine'];
            $frequence = $freqMap[(int)$matches[1]] ?? null;

            if (!$frequence) {
                return 'END Option de fréquence invalide ❌';
            }

            $session->data = array_merge($session->data ?? [], [
                'frequence' => $frequence,
                'ramassage_step' => 'adresse',
            ]);
            $session->save();

            return 'CON Entrez votre adresse complète:';
        }

        // adresse (texte libre): ramassage_step=adresse
        if (($session->data['ramassage_step'] ?? null) === 'adresse' && preg_match('/^[0-9A-Za-z].*/', $text)) {
            $session->data = array_merge($session->data ?? [], ['adresse' => $text, 'ramassage_step' => 'desc']);
            $session->save();
            return 'CON Description du domicile (optionnel). Si rien, tape 0:';
        }

        // description: si 0 => null
        if (($session->data['ramassage_step'] ?? null) === 'desc') {
            $desc = ($text === '0') ? null : $text;
            $session->data = array_merge($session->data ?? [], ['description_domicile' => $desc, 'ramassage_step' => 'phone']);
            $session->save();
            return 'CON Numéro Orange Money (ex: 670000000) :';
        }

        // phone validation (cameroon starting with 6, 9 digits)
        if (($session->data['ramassage_step'] ?? null) === 'phone') {
            // attendu: digits 6xxxxxxxx (9 chiffres)
            if (!preg_match('/^6[0-9]{8}$/', $text)) {
                return 'CON Numéro invalide. Réessayez (ex: 670000000) :';
            }

            $freq = $session->data['frequence'] ?? null;
            $adresse = $session->data['adresse'] ?? null;
            $desc = $session->data['description_domicile'] ?? null;

            if (!$freq || !$adresse) {
                return 'END Session ramassage invalide ❌';
            }

            $tarifs = \App\Models\Ramassage::$tarifs;
            $prix = $tarifs[$freq] ?? 0;
            if ($prix <= 0) {
                return 'END Fréquence invalide ❌';
            }

            // Vérifier si l'utilisateur a déjà un service ramassage actif (aligné avec RamassageController)
            $existant = \App\Models\Ramassage::where('user_id', $session->user_id)
                ->where('statut', 'actif')
                ->first();

            if ($existant) {
                return 'END Vous avez déjà un service de ramassage actif ❗';
            }

            // simulation paiement + création ramassage
            $reference = 'OM-' . strtoupper(uniqid());

            \App\Models\Ramassage::create([
                'user_id' => $session->user_id,
                'adresse' => $adresse,
                'description_domicile' => $desc,
                'frequence' => $freq,
                'prix' => $prix,
                'phone_paiement' => $text,
                'statut_paiement' => 'paye',
                'reference_paiement' => $reference,
                'latitude' => null,
                'longitude' => null,
                'statut' => 'actif',
            ]);


            // reset step
            $session->data = [];
            $session->save();

            return "END Ramassage activé ✅\r\nREF Paiement: {$reference}\r\nPrix: {$prix} FCFA";
        }


        // ──────────────────────────────────────────
        // ABONNEMENTS
        // ──────────────────────────────────────────
        if ($text === '4') {
            return 'CON Choisissez votre plan:

1. Basique (Gratuit)
2. Standard (2000 XAF)
3. Premium (5000 XAF)';
        }

        if (preg_match('/^4\*(\d+)$/', $text, $matches)) {
            $plans = [
                1 => ['name' => 'Basique', 'prix' => 0],
                2 => ['name' => 'Standard', 'prix' => 2000],
                3 => ['name' => 'Premium', 'prix' => 5000]
            ];

            $plan = $plans[$matches[1]] ?? $plans[1];

            if ($plan['prix'] > 0) {
                $session->data = ['plan' => $plan['name'], 'prix' => $plan['prix']];
                $session->save();
                return "CON Confirmer paiement: {$plan['name']} ({$plan['prix']} XAF)?

1. Oui
2. Non";
            } else {
                // Abonnement gratuit
                return "END Abonnement {$plan['name']} activé ✅";
            }
        }

        // Confirmation paiement abonnement
        if (preg_match('/^4\*\d+\*(\d+)$/', $text, $matches)) {
            if ($matches[1] === '1') {
                $data = $session->data ?? [];
                $plan = $data['plan'] ?? 'Standard';
                $prix = $data['prix'] ?? 2000;

                // Rediriger vers paiement
                return "CON Initier le paiement pour {$plan}?

1. MTN
2. Orange";
            } else {
                return 'END Abonnement annulé ❌';
            }
        }

        // ──────────────────────────────────────────
        // PAIEMENTS
        // ──────────────────────────────────────────
        if ($text === '5') {
            return 'CON Choisissez une action:

1. Payer mon abonnement
2. Historique des paiements
3. Retour';
        }

        // Quitter
        if ($text === '6') {
            return 'END Merci d\'avoir utilisé DoualaClean! 👋';
        }

        return 'END Option invalide ❌';
    }

    /**
     * Webhook pour les notifications de paiement USSD
     * POST /api/ussd/payment/callback
     */
    public function paymentCallback(Request $request) {
        Log::info('USSD Payment Callback:', $request->all());

        $status = $request->input('status');
        $transactionId = $request->input('transactionId');
        $amount = $request->input('amount');
        $phoneNumber = $request->input('phoneNumber');

        if ($status === 'Success') {
            // Créer ou mettre à jour la transaction
            $this->createTransaction($phoneNumber, $amount, $transactionId, 'completed');
        } else {
            $this->createTransaction($phoneNumber, $amount, $transactionId, 'failed');
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Créer une transaction
     */
    protected function createTransaction($phone, $amount, $reference, $status) {
        \App\Models\Transaction::create([
            'phone_number' => $phone,
            'amount' => $amount,
            'reference' => $reference,
            'type' => 'payment',
            'status' => $status,
            'provider' => 'africas-talking',
            'payment_method' => $this->detectOperator($phone),
            'completed_at' => now()
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
}
