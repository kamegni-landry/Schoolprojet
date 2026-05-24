@extends('layouts.app')

@section('title', 'Inscription')

@section('content')
<div class="auth-wrap">
    <h2>📝 Inscription</h2>
    <div class="alert alert-error" id="errMsg" style="display: none;"></div>
    <form id="registerForm">
        @csrf
        <label for="nom">Nom complet</label>
        <input type="text" id="nom" name="nom" placeholder="Votre nom complet" required value="{{ old('nom') }}">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="votre@email.com" required value="{{ old('email') }}">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" placeholder="Minimum 6 caractères" required>
        <label for="password_confirmation">Confirmer mot de passe</label>
        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Répétez le mot de passe" required>
        <label for="phone">Téléphone (optionnel)</label>
        <input type="tel" id="phone" name="phone" placeholder="69 XX XX XXX" value="{{ old('phone') }}">
        <button type="submit" class="btn btn-primary btn-full">S'inscrire</button>
    </form>
    <div class="switch">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', doRegister);
</script>
@endsection

