<x-layouts.app title="{{ $document->type?->name ?? 'Documento' }} - {{ $vehicle->plate }}">
    <main class="document-viewer-shell">
        <header class="panel public-header">
            <div class="header-logo-row">
                <img src="/brand/vqr-logo.png" alt="VQR" class="brand-logo">
                @php
                    $statusClass = match ($document->computed_status) {
                        'valid' => 'pill-blue',
                        'expiring_soon' => 'pill-warning',
                        'expired', 'rejected' => 'pill-danger',
                        default => 'pill-muted',
                    };
                @endphp
                <span class="pill {{ $statusClass }}">{{ $document->status_label }}</span>
            </div>
            <p class="page-kicker">Documento</p>
            <div class="header-title-row">
                <div>
                    <h1 class="page-title">{{ $document->type?->name ?? 'Documento vehicular' }}</h1>
                    <p class="page-subtitle">
                        {{ $vehicle->plate }}
                        @if ($document->expires_at)
                            · Vence {{ $document->expires_at->format('d-m-Y') }}
                        @endif
                    </p>
                </div>
                @if ($fileExists)
                    <div class="document-viewer-actions">
                        <a href="{{ route('public.vehicles.show', $vehicle->public_token) }}" class="btn btn-outline">Volver</a>
                        <a href="{{ $fileUrl }}" class="btn btn-primary" target="_blank" rel="noopener">Abrir archivo</a>
                    </div>
                @endif
            </div>
        </header>

        <section class="document-viewer-panel">
            @if ($fileExists)
                <div class="document-viewer-mobile-note">
                    Si tu navegador no muestra el PDF completo, abre el archivo en una pestaña nueva.
                </div>
                <iframe src="{{ $fileUrl }}" title="Visualizador de documento {{ $document->type?->name ?? '' }}"></iframe>
            @else
                <div class="document-viewer-empty">
                    <h2>Archivo no disponible</h2>
                    <p>El documento existe en VQR, pero el archivo no fue encontrado en el almacenamiento del servidor.</p>
                </div>
            @endif
        </section>
    </main>
</x-layouts.app>
