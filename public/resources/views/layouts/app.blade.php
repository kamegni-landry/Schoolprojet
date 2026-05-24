<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DoualaClean') — Gestion des déchets</title>
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <header>
        <h1>🌿 DoualaClean</h1>
        <nav id="navAuth">
            @auth
                <span style="color:#fff;font-size:.85rem">👤 {{ auth()->user()->nom }}</span>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn-logout" onclick="return confirm('Déconnexion ?')">Déconnexion</button>
                </form>
            @else
                <a href="{{ route('login') }}">Connexion</a>
                <a href="{{ route('register') }}">Inscription</a>
            @endauth
        </nav>
    </header>

    <main>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    <footer>
        <p>© 2024 – Projet citoyen DoualaClean</p>
    </footer>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
    @stack('charts') {{-- Chart.js for dashboard --}}
</body>
</html>

