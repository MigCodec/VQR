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
                <a href="{{ $fileUrl }}" class="btn btn-outline" target="_blank" rel="noopener">Abrir archivo</a>
            </div>
        </header>

        <section class="document-viewer-panel">
            <iframe src="{{ $fileUrl }}" title="Visualizador de documento {{ $document->type?->name ?? '' }}"></iframe>
        </section>
    </main>
</x-layouts.app>
