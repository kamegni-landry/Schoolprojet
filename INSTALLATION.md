# DoualaClean — Installation complète

## Prérequis
- XAMPP (Apache + MySQL actifs)
- PHP 8.2+ (inclus dans XAMPP)
- Composer installé

---

## ÉTAPES (dans l'ordre, CMD dans le dossier du projet)

### 1. Créer la base de données
→ http://localhost/phpmyadmin → Nouvelle BDD → **`doualaclean`**

### 2. Installer les dépendances
```cmd
composer install
```

### 3. Configurer l'environnement
```cmd
copy .env.example .env
php artisan key:generate
```

### 4. Migrations + données de test
```cmd
php artisan migrate --seed
```

### 5. Lien de stockage (pour les photos)
```cmd
php artisan storage:link
```

### 6. Démarrer le serveur
```cmd
php artisan serve
```
✅ API disponible sur : **http://localhost:8000/api**

### 7. Ouvrir le frontend
Ouvrir `frontend/index.html` dans votre navigateur

---

## Comptes de test

| Rôle    | Email                  | Mot de passe |
|---------|------------------------|-------------|
| Admin   | admin@doualaclean.cm   | Admin@1234  |
| Agent   | agent@doualaclean.cm   | Agent@1234  |
| Citoyen | jean@example.cm        | Jean@1234   |

---

## Routes API complètes

### Publiques
| Méthode | Route | Description |
|---------|-------|-------------|
| POST | /api/auth/register | Inscription |
| POST | /api/auth/login | Connexion |
| GET  | /api/signalements/carte | Carte publique |

### Utilisateur connecté (token Sanctum)
| Méthode | Route | Description |
|---------|-------|-------------|
| POST   | /api/auth/logout | Déconnexion |
| GET    | /api/auth/me | Mon profil |
| PUT    | /api/auth/profile | Modifier profil |
| PUT    | /api/auth/password | Changer mot de passe |
| GET    | /api/signalements | Mes signalements |
| POST   | /api/signalements | Créer signalement |
| GET    | /api/signalements/{id} | Détail |
| PATCH  | /api/signalements/{id}/statut | Changer statut (agent/admin) |
| DELETE | /api/signalements/{id} | Supprimer (admin) |
| GET    | /api/signalements/stats/globales | Stats (agent/admin) |
| GET    | /api/abonnements/mon-abonnement | Mon abonnement |
| POST   | /api/abonnements | Souscrire un plan |
| POST   | /api/ramassage | Souscrire ramassage |
| GET    | /api/ramassage/mon-service | Mon service actif |

### Admin uniquement
| Méthode | Route | Description |
|---------|-------|-------------|
| GET    | /api/admin/dashboard | Stats globales |
| GET    | /api/admin/users | Liste utilisateurs |
| POST   | /api/admin/users | Créer agent/admin |
| PATCH  | /api/admin/users/{id}/toggle | Activer/Désactiver |
| DELETE | /api/admin/users/{id} | Supprimer |
| GET    | /api/admin/abonnements | Tous abonnements |
| GET    | /api/admin/ramassage | Tous ramassages |
