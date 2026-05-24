# 📱 Intégration Frontend - USSD & Paiement

## 🌐 Frontend Web (HTML/JavaScript)

### Payer un Abonnement

```html
<!DOCTYPE html>
<html>
<head>
    <title>Payer Abonnement - DoualaClean</title>
</head>
<body>
    <h1>Choisir un Abonnement</h1>
    
    <div id="plans"></div>
    <div id="payment-form" style="display:none;">
        <input type="hidden" id="plan" />
        <input type="tel" id="phone" placeholder="+237670000000" />
        <button onclick="initiatepayment()">Payer</button>
    </div>

    <script>
        const API_URL = 'http://localhost:8000/api';
        let selectedPlan = null;

        // Charger les plans
        async function loadPlans() {
            const response = await fetch(`${API_URL}/payments/plans`);
            const data = await response.json();

            let html = '';
            data.plans.forEach(plan => {
                html += `
                    <div style="border: 1px solid #ccc; padding: 15px; margin: 10px; cursor: pointer;"
                         onclick="selectPlan('${plan.key}', ${plan.price})">
                        <h3>${plan.name}</h3>
                        <p>Durée: ${plan.duration_days} jours</p>
                        <p><strong>${plan.price} ${plan.currency}</strong></p>
                        <ul>
                            ${plan.features.map(f => `<li>${f}</li>`).join('')}
                        </ul>
                    </div>
                `;
            });

            document.getElementById('plans').innerHTML = html;
        }

        function selectPlan(key, price) {
            selectedPlan = key;
            document.getElementById('plan').value = key;
            document.getElementById('payment-form').style.display = 'block';
            alert(`Plan sélectionné: ${key} (${price} XAF)`);
        }

        async function initiatepayment() {
            const phone = document.getElementById('phone').value;
            const token = localStorage.getItem('auth_token');

            const response = await fetch(`${API_URL}/payments/subscribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    plan: selectedPlan,
                    phone_number: phone
                })
            });

            const data = await response.json();
            
            if (data.success) {
                alert('Veuillez confirmer le paiement sur votre téléphone!');
                checkPaymentStatus(data.reference);
            } else {
                alert('Erreur: ' + data.error);
            }
        }

        async function checkPaymentStatus(reference) {
            for (let i = 0; i < 60; i++) {
                const response = await fetch(`${API_URL}/payments/${reference}/status`);
                const data = await response.json();

                if (data.status === 'completed') {
                    alert('✅ Paiement effectué avec succès!');
                    location.reload();
                    return;
                }

                await new Promise(r => setTimeout(r, 2000));
            }
            alert('Paiement non confirmé');
        }

        // Charger les plans au démarrage
        loadPlans();
    </script>
</body>
</html>
```

---

## 📱 React Component

### Hook pour les Paiements

```jsx
import { useState, useEffect } from 'react';
import axios from 'axios';

const API_URL = 'http://localhost:8000/api';

export function usePayment() {
    const [plans, setPlans] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchPlans();
    }, []);

    const fetchPlans = async () => {
        try {
            const { data } = await axios.get(`${API_URL}/payments/plans`);
            setPlans(data.plans);
        } catch (err) {
            setError(err.message);
        }
    };

    const initiatePayment = async (plan, phone, token) => {
        setLoading(true);
        try {
            const { data } = await axios.post(
                `${API_URL}/payments/subscribe`,
                { plan, phone_number: phone },
                { headers: { Authorization: `Bearer ${token}` } }
            );

            if (data.success) {
                // Attendre la confirmation
                const result = await checkPaymentStatus(data.reference);
                return result;
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const checkPaymentStatus = async (reference) => {
        return new Promise((resolve) => {
            const interval = setInterval(async () => {
                try {
                    const { data } = await axios.get(
                        `${API_URL}/payments/${reference}/status`
                    );

                    if (data.status === 'completed') {
                        clearInterval(interval);
                        resolve(data);
                    }
                } catch (err) {
                    console.error(err);
                }
            }, 2000);
        });
    };

    return {
        plans,
        loading,
        error,
        initiatePayment,
        checkPaymentStatus
    };
}

// Utilisation
export function PaymentComponent() {
    const { plans, initiatePayment } = usePayment();
    const [selectedPlan, setSelectedPlan] = useState(null);
    const token = localStorage.getItem('auth_token');

    const handlePayment = async (plan) => {
        const phone = prompt('Entrez votre numéro de téléphone:');
        if (phone) {
            await initiatePayment(plan, phone, token);
        }
    };

    return (
        <div>
            <h1>Abonnements</h1>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '20px' }}>
                {plans.map(plan => (
                    <div key={plan.key} style={{ border: '1px solid #ccc', padding: '20px' }}>
                        <h2>{plan.name}</h2>
                        <p style={{ fontSize: '24px', fontWeight: 'bold' }}>
                            {plan.price} {plan.currency}
                        </p>
                        <p>{plan.duration_days} jours</p>
                        <ul>
                            {plan.features.map((f, i) => (
                                <li key={i}>{f}</li>
                            ))}
                        </ul>
                        <button onClick={() => handlePayment(plan.key)}>
                            Souscrire
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
}
```

---

## 📱 React Native / Expo

```jsx
import { useState, useEffect } from 'react';
import { View, Text, Button, Alert, StyleSheet } from 'react-native';
import axios from 'axios';

const API_URL = 'http://your-domain.com/api';

export function PaymentScreen() {
    const [plans, setPlans] = useState([]);

    useEffect(() => {
        loadPlans();
    }, []);

    const loadPlans = async () => {
        try {
            const { data } = await axios.get(`${API_URL}/payments/plans`);
            setPlans(data.plans);
        } catch (error) {
            Alert.alert('Erreur', 'Impossible de charger les plans');
        }
    };

    const handlePayment = async (planKey) => {
        Alert.prompt('Numéro de téléphone', '', 
            async (phone) => {
                const token = await getAuthToken(); // Votre méthode d'auth
                
                try {
                    const { data } = await axios.post(
                        `${API_URL}/payments/subscribe`,
                        { plan: planKey, phone_number: phone },
                        { headers: { Authorization: `Bearer ${token}` } }
                    );

                    if (data.success) {
                        Alert.alert(
                            'Paiement en attente',
                            'Confirmez le paiement sur votre téléphone'
                        );
                    }
                } catch (error) {
                    Alert.alert('Erreur', error.message);
                }
            },
            'plain-text',
            '+237'
        );
    };

    return (
        <View style={styles.container}>
            <Text style={styles.title}>Abonnements DoualaClean</Text>
            
            {plans.map(plan => (
                <View key={plan.key} style={styles.planCard}>
                    <Text style={styles.planName}>{plan.name}</Text>
                    <Text style={styles.planPrice}>
                        {plan.price} {plan.currency}
                    </Text>
                    <Text style={styles.planDuration}>
                        Durée: {plan.duration_days} jours
                    </Text>
                    <Button
                        title="Souscrire"
                        onPress={() => handlePayment(plan.key)}
                    />
                </View>
            ))}
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, padding: 20 },
    title: { fontSize: 24, fontWeight: 'bold', marginBottom: 20 },
    planCard: { 
        borderWidth: 1, 
        borderColor: '#ccc', 
        padding: 15, 
        marginBottom: 15,
        borderRadius: 8
    },
    planName: { fontSize: 18, fontWeight: 'bold' },
    planPrice: { fontSize: 24, color: '#27ae60', marginVertical: 10 },
    planDuration: { fontSize: 14, color: '#666' }
});
```

---

## 🔗 Intégration Backend Node.js/Express

```javascript
// Exemple d'intégration dans votre frontend Node

const express = require('express');
const axios = require('axios');
const router = express.Router();

const LARAVEL_API = 'http://your-domain.com/api';

// Route pour initier un paiement
router.post('/pay', async (req, res) => {
    const { plan, phone, token } = req.body;

    try {
        const response = await axios.post(
            `${LARAVEL_API}/payments/subscribe`,
            { plan, phone_number: phone },
            { headers: { Authorization: `Bearer ${token}` } }
        );

        res.json({
            success: true,
            data: response.data,
            message: 'Paiement initialisé'
        });
    } catch (error) {
        res.status(400).json({
            success: false,
            error: error.message
        });
    }
});

// Route pour vérifier le statut
router.get('/status/:reference', async (req, res) => {
    try {
        const response = await axios.get(
            `${LARAVEL_API}/payments/${req.params.reference}/status`
        );

        res.json(response.data);
    } catch (error) {
        res.status(400).json({ error: error.message });
    }
});

module.exports = router;
```

---

## 🧾 Intégration USSD avec Flutter

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class USSDService {
    final String apiUrl = 'http://your-domain.com/api';

    Future<String> sendUSSDRequest({
        required String sessionId,
        required String phoneNumber,
        required String text,
    }) async {
        try {
            final response = await http.post(
                Uri.parse('$apiUrl/ussd'),
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: {
                    'sessionId': sessionId,
                    'phoneNumber': phoneNumber,
                    'text': text,
                    'serviceCode': '123',
                }
            );

            if (response.statusCode == 200) {
                return response.body;
            }
            throw Exception('Erreur USSD');
        } catch (e) {
            throw Exception('Erreur: $e');
        }
    }

    Future<Map<String, dynamic>> getPaymentPlans() async {
        try {
            final response = await http.get(
                Uri.parse('$apiUrl/payments/plans'),
                headers: {'Content-Type': 'application/json'},
            );

            if (response.statusCode == 200) {
                return jsonDecode(response.body);
            }
            throw Exception('Erreur lors de la récupération des plans');
        } catch (e) {
            throw Exception('Erreur: $e');
        }
    }

    Future<Map<String, dynamic>> initiatePayment({
        required String plan,
        required String phone,
        required String token,
    }) async {
        try {
            final response = await http.post(
                Uri.parse('$apiUrl/payments/subscribe'),
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer $token',
                },
                body: jsonEncode({
                    'plan': plan,
                    'phone_number': phone,
                }),
            );

            if (response.statusCode == 200) {
                return jsonDecode(response.body);
            }
            throw Exception('Erreur lors du paiement');
        } catch (e) {
            throw Exception('Erreur: $e');
        }
    }
}
```

---

## 🌐 Intégration API - Client HTTP Générique

```javascript
// client.js - Classe utilitaire
class DoualaCleanAPI {
    constructor(baseUrl, token) {
        this.baseUrl = baseUrl;
        this.token = token;
    }

    async request(method, endpoint, data = null) {
        const url = `${this.baseUrl}${endpoint}`;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.token}`
            }
        };

        if (data) options.body = JSON.stringify(data);

        const response = await fetch(url, options);
        return response.json();
    }

    // Méthodes de paiement
    async getPlans() {
        return this.request('GET', '/payments/plans');
    }

    async initiatepayment(plan, phone) {
        return this.request('POST', '/payments/subscribe', { plan, phone_number: phone });
    }

    async checkPaymentStatus(reference) {
        return this.request('GET', `/payments/${reference}/status`);
    }

    async getPaymentHistory() {
        return this.request('GET', '/payments/history');
    }

    // Méthodes USSD
    async sendUSSD(sessionId, phoneNumber, text) {
        return fetch(`${this.baseUrl}/ussd`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                sessionId,
                phoneNumber,
                text,
                serviceCode: '123'
            })
        }).then(r => r.text());
    }
}

// Utilisation
const api = new DoualaCleanAPI('http://localhost:8000/api', 'your_token');

// Obtenir les plans
const plans = await api.getPlans();

// Initier un paiement
const payment = await api.initiatepayment('standard', '+237670000000');

// Vérifier le statut
const status = await api.checkPaymentStatus(payment.reference);
```

---

## 📊 Webhooks Frontend

```javascript
// Recevoir les notifications de paiement
const webhook = express();

webhook.post('/payment-notification', (req, res) => {
    const { reference, status, amount } = req.body;

    // Notifier l'utilisateur
    if (status === 'completed') {
        console.log(`Paiement confirmé: ${reference}`);
        // Mettre à jour l'interface
        updateUI({ paymentConfirmed: true });
    }

    res.json({ success: true });
});

webhook.listen(3000);
```

---

## ✅ Checklist d'Intégration

- [ ] Configurer les URLs correctes (localhost vs production)
- [ ] Ajouter la gestion des tokens auth
- [ ] Implémenter la validation des formulaires
- [ ] Ajouter les messages d'erreur
- [ ] Tester sur mobile (Android/iOS)
- [ ] Tester avec les deux opérateurs (MTN, Orange)
- [ ] Configurer les redirects après paiement
- [ ] Ajouter les logs de paiement
