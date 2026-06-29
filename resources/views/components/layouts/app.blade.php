<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'VQR') }}</title>
        <meta name="description" content="{{ $metaDescription ?? 'VQR mantiene ordenados los documentos de tu vehiculo: revision tecnica, SOAP y permiso de circulacion siempre disponibles desde el celular.' }}">
        <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
        <meta property="og:title" content="{{ $title ?? config('app.name', 'VQR') }}">
        <meta property="og:description" content="{{ $metaDescription ?? 'Documentos del vehiculo siempre a mano con VQR.' }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
        <meta property="og:image" content="{{ url('/brand/vqr-logo.png') }}">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/brand/favicon-32.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/brand/favicon-192.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/brand/favicon-180.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#0b2749">
        <link rel="stylesheet" href="/css/vqr.css">
        @isset($structuredData)
            <script type="application/ld+json">{!! $structuredData !!}</script>
        @endisset
    </head>
    <body>
        {{ $slot }}
    </body>
</html>
