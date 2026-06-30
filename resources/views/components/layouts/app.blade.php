<!DOCTYPE html>
<html lang="es-CL">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'VQR') }}</title>
        <meta name="description" content="{{ $metaDescription ?? 'VQR mantiene ordenados los documentos de tu vehículo: revisión técnica, SOAP y permiso de circulación siempre disponibles desde el celular.' }}">
        <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
        <meta property="og:title" content="{{ $title ?? config('app.name', 'VQR') }}">
        <meta property="og:description" content="{{ $metaDescription ?? 'Documentos del vehículo siempre a mano con VQR.' }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
        <meta property="og:image" content="{{ url('/brand/vqr-logo.png') }}">
        <meta property="og:locale" content="es_CL">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title ?? config('app.name', 'VQR') }}">
        <meta name="twitter:description" content="{{ $metaDescription ?? 'Documentos del vehículo siempre a mano con VQR.' }}">
        <meta name="twitter:image" content="{{ url('/brand/vqr-logo.png') }}">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/brand/favicon-32.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/brand/favicon-192.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/brand/favicon-180.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#0b2749">
        <link rel="stylesheet" href="{{ asset('css/vqr.css') }}?v={{ filemtime(public_path('css/vqr.css')) }}">
        @isset($structuredData)
            <script type="application/ld+json">{!! $structuredData !!}</script>
        @endisset
    </head>
    <body>
        {{ $slot }}
    </body>
</html>
