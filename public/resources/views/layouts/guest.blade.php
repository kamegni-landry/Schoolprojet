<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DoualaClean') — Gestion des déchets</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    @yield('styles')
</head>
<body>
    <div id="app">
        @yield('content')
    </div>
    
    <footer>
        <p style="text-align:center;padding:28px 20px 12px;color:#999;font-size:.84rem;margin:0;">© 2024 – Projet citoyen DoualaClean</p>
    </footer>

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')

