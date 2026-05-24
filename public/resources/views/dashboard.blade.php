@extends('layouts.app')

@section('title', 'Dashboard')

@section('styles')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="dash-layout">
    <aside class="sidebar">
        <div class="logo">🌿 DoualaClean</div>
        <a href="{{ route('dashboard') }}" class="active">📊 Dashboard</a>
        <a href="{{ route('signalement.create') }}">📝 Nouveau signalement</a>
        <a href="{{ route('carte') }}">📍 Carte</a>
        <a href="{{ route('statistic') }}">📈 Statistiques</a>
        <a href="{{ route('abonnement') }}">💳 Abonnement</a>
        <a href="{{ route('ramassage') }}">🚛 Ramassage</a>
        <a href="{{ route('param') }}">⚙️ Paramètres</a>
        @if (auth()->user()->role === 'admin')
            <a href="{{ route('admin.users') }}">👥 Utilisateurs</a>
        @endif
        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">🚪 Déconnexion</a>
        <form id="logout-form" method="POST" action="{{ route('auth.logout') }}" style="display: none;">
            @csrf
        </form>
    </aside>
    
    <main class="dash-main">
        <div class="dash-topbar">
            <h1>Dashboard</h1>
            <span class="user-pill" id="userPill">👤 {{ auth()->user()->nom }} — {{ ucfirst(auth()->user()->role) }}</span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total</h3>
                <div class="val" id="statTotal">{{ $stats['signalements']['total'] ?? 0 }}</div>
            </div>
            <div class="stat-card orange">
                <h3>En attente</h3>
                <div class="val" id="statAttente">{{ $stats['signalements']['en_attente'] ?? 0 }}</div>
            </div>
            <div class="stat-card blue">
                <h3>En cours</h3>
                <div class="val" id="statCours">{{ $stats['signalements']['en_cours'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <h3>Traités</h3>
                <div class="val" id="statTraite">{{ $stats['signalements']['traites'] ?? 0 }}</div>
            </div>
        </div>

        <div class="table-section">
            <h2>Liste des signalements</h2>
            <div class="filter-bar">
                <select id="filtreStatut">
                    <option value="">Tous les statuts</option>
                    <option value="En attente">En attente</option>
                    <option value="En cours">En cours</option>
                    <option value="Traité">Traités</option>
                </select>
                <select id="filtreType">
                    <option value="">Tous les types</option>
                    <option value="Ménagers">Ménagers</option>
                    <option value="Plastiques">Plastiques</option>
                    <option value="Dangereux">Dangereux</option>
                </select>
                <input type="text" id="filtreQuartier" placeholder="Quartier…">
                <button class="btn btn-primary" onclick="appliquerFiltres()">Filtrer</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Lieu</th>
                        <th>Quartier</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Utilisateur</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @forelse($signalements as $s)
                        <tr>
                            <td><strong>{{ $s->lieu }}</strong></td>
                            <td>{{ $s->quartier }}</td>
                            <td>{{ $s->type_dechet }}</td>
                            <td>@badge($s->statut)</td>
                            <td>{{ $s->user->nom ?? '—' }}</td>
                            <td>{{ $s->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if (auth()->user()->role !== 'citoyen')
                                    <button class="act-btn act-blue" onclick="changerStatut({{ $s->id }},'En cours')">En cours</button>
                                    <button class="act-btn act-green" onclick="changerStatut({{ $s->id }},'Traité')">✔ Traité</button>
                                    @if (auth()->user()->role === 'admin')
                                        <button class="act-btn act-red" onclick="supprimerSignalement({{ $s->id }})">🗑</button>
                                    @endif
                                @endif
                                @if ($s->latitude)
                                    <button class="act-btn act-gray" onclick="voirCarte({{ $s->latitude }},{{ $s->longitude }})">📍</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:28px;color:#aaa">Aucun signalement</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="charts-grid">
            <div class="chart-box">
                <h3>Répartition par statut</h3>
                <canvas id="statusChart"></canvas>
            </div>
            <div class="chart-box">
                <h3>Signalements par quartier</h3>
                <canvas id="zoneChart"></canvas>
            </div>
        </div>
    </main>
</div>

<script>
initDashboard();
</script>
@endsection

@push('scripts')
<script>
// Badge helper JS
function badge(statut) {
  const map = {
    'En attente': 'badge-attente',
    'En cours': 'badge-cours',
    'Traité': 'badge-traite',
  };
  return `<span class="badge ${map[statut] || ''}">${statut}</span>`;
}
</script>
@endpush

