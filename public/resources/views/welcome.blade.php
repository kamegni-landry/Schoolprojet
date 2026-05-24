@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
<div class="hero">
    <h2>Ensemble, rendons Douala plus propre 🌍</h2>
    <p>Signalez les dépôts d'ordures sauvages dans votre quartier et participez à l'amélioration de l'environnement urbain de Douala.</p>
    <div class="hero-btns">
        <a href="{{ url('/login') }}" class="btn btn-primary">🚮 Signaler un dépôt</a>
        <a href="{{ url('/login') }}" class="btn btn-outline">🗺️ Voir la carte</a>
    </div>
</div>
@endsection
