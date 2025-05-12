<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Sistema de Censo Anual da Engenharia - Polícia Civil">
        <meta name="theme-color" content="#bea55a">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
                
        <!-- Fonts - Optimized loading -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

        <!-- Leaflet CSS with integrity check -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
              crossorigin="" 
              referrerpolicy="no-referrer" />

        <!-- Leaflet JS with integrity check and defer -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
                crossorigin="" 
                referrerpolicy="no-referrer"
                defer></script>
        
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-100">
        @inertia
        <noscript>
            <div class="fixed inset-0 flex items-center justify-center bg-white z-50">
                <div class="text-center p-6 max-w-lg">
                    <img src="/images/logo-pc.png" alt="Logo Polícia Civil" class="h-16 mx-auto mb-4">
                    <h1 class="text-xl font-bold mb-2">JavaScript Necessário</h1>
                    <p>Este sistema requer JavaScript para funcionar corretamente. Por favor, habilite JavaScript no seu navegador e recarregue a página.</p>
                </div>
            </div>
        </noscript>
    </body>
</html>