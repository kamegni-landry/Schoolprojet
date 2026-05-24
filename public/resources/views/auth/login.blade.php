@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
<div class="auth-wrap">
    <h2>🔐 Connexion</h2>
    <div class="alert alert-error" id="errMsg" style="display: none;"></div>
    <form id="loginForm">
        @csrf
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="votre@email.com" required autocomplete="email" value="{{ old('email') }}">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" placeholder="Mot de passe" required>
        <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
    </form>
    <div class="switch">Pas de compte ? <a href="{{ route('register') }}">S'inscrire</a></div>
    <div style="margin-top:20px;padding:14px;background:#f1f8e9;border-radius:8px;font-size:.82rem;color:#555">
        <strong>Comptes de test :</strong><br>
        🔴 Admin : admin@doualaclean.cm / Admin@1234<br>
        🔵 Agent : agent@doualaclean.cm / Agent@1234<br>
        🟢 Citoyen : jean@example.cm / Jean@1234
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', doLogin);
</script>
@endsection

