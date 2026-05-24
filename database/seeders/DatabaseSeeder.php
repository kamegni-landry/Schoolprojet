<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Signalement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ──────────────────────────────────────────────
        // COMPTES PRÉDÉFINIS
        // ──────────────────────────────────────────────

        // Administrateur
        $admin = User::create([
            'nom'        => 'Administrateur',
            'email'      => 'admin@doualaclean.cm',
            'password'   => Hash::make('Admin@1234'),
            'role'       => 'admin',
            'abonnement' => 'premium',
            'phone'      => '699000001',
            'is_active'  => true,
        ]);

        // Agent de terrain
        $agent = User::create([
            'nom'        => 'Agent Douala',
            'email'      => 'agent@doualaclean.cm',
            'password'   => Hash::make('Agent@1234'),
            'role'       => 'agent',
            'abonnement' => 'premium',
            'phone'      => '699000002',
            'is_active'  => true,
        ]);

        // Citoyen de test
        $citoyen = User::create([
            'nom'        => 'Jean Mbarga',
            'email'      => 'jean@example.cm',
            'password'   => Hash::make('Jean@1234'),
            'role'       => 'citoyen',
            'abonnement' => 'standard',
            'phone'      => '655123456',
            'is_active'  => true,
        ]);

        // ──────────────────────────────────────────────
        // SIGNALEMENTS DE TEST (Douala)
        // ──────────────────────────────────────────────

        $signalements = [
            [
                'user_id'     => $citoyen->id,
                'lieu'        => 'Marché Central de Douala',
                'description' => 'Grand dépôt d\'ordures ménagères devant l\'entrée principale',
                'type_dechet' => 'Ménagers',
                'quartier'    => 'Akwa',
                'latitude'    => 4.0511,
                'longitude'   => 9.7042,
                'statut'      => 'En attente',
            ],
            [
                'user_id'     => $citoyen->id,
                'lieu'        => 'Carrefour Ndokoti',
                'description' => 'Déchets plastiques abandonnés sur le trottoir',
                'type_dechet' => 'Plastiques',
                'quartier'    => 'Bépanda',
                'latitude'    => 4.0712,
                'longitude'   => 9.7348,
                'statut'      => 'En cours',
                'agent_id'    => $agent->id,
            ],
            [
                'user_id'     => $citoyen->id,
                'lieu'        => 'Rue de la Paix, Bonanjo',
                'description' => 'Déchets chimiques déversés près de l\'immeuble',
                'type_dechet' => 'Dangereux',
                'quartier'    => 'Bonanjo',
                'latitude'    => 4.0438,
                'longitude'   => 9.6979,
                'statut'      => 'Traité',
                'agent_id'    => $agent->id,
                'traite_at'   => now()->subDays(2),
            ],
            [
                'user_id'     => $citoyen->id,
                'lieu'        => 'Bord de mer Deido',
                'description' => 'Déversement d\'ordures sur la plage',
                'type_dechet' => 'Ménagers',
                'quartier'    => 'Deido',
                'latitude'    => 4.0634,
                'longitude'   => 9.7213,
                'statut'      => 'En attente',
            ],
            [
                'user_id'     => $citoyen->id,
                'lieu'        => 'École publique New-Bell',
                'description' => 'Tas d\'ordures devant l\'école, risque sanitaire',
                'type_dechet' => 'Ménagers',
                'quartier'    => 'New-Bell',
                'latitude'    => 4.0589,
                'longitude'   => 9.7156,
                'statut'      => 'En attente',
            ],
        ];

        foreach ($signalements as $data) {
            Signalement::create($data);
        }

        $this->command->info('✅ Base de données initialisée avec succès !');
        $this->command->newLine();
        $this->command->info('📋 Comptes créés :');
        $this->command->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Admin',   'admin@doualaclean.cm', 'Admin@1234'],
                ['Agent',   'agent@doualaclean.cm', 'Agent@1234'],
                ['Citoyen', 'jean@example.cm',      'Jean@1234'],
            ]
        );
    }
}
