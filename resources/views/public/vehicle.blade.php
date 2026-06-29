<x-layouts.app title="{{ $vehicle->plate }}">
    <main class="page-shell">
        <header class="panel public-header">
            <div class="header-logo-row">
                <img src="/brand/vqr-logo.png" alt="VQR" class="brand-logo">
                <span class="pill pill-blue">Documentos disponibles</span>
            </div>
            <p class="page-kicker">Vehículo</p>
            <div class="header-title-row">
                <div>
                    <h1 class="page-title">{{ $vehicle->plate }}</h1>
                    <p class="page-subtitle">{{ $vehicle->display_name }} @if($vehicle->year) · {{ $vehicle->year }} @endif</p>
                </div>
            </div>
        </header>

        <section class="stack">
            @forelse ($documents as $document)
                @php
                    $statusClass = match ($document->computed_status) {
                        'valid' => 'pill-blue',
                        'expiring_soon' => 'pill-warning',
                        'expired', 'rejected' => 'pill-danger',
                        default => 'pill-muted',
                    };
                @endphp
                <article class="document-card">
                    <div class="document-head">
                        <div>
                            <h2>{{ $document->type->name }}</h2>
                            <dl class="document-meta">
                                <div>
                                    <dt>Folio</dt>
                                    <dd>{{ $document->folio ?: 'No informado' }}</dd>
                                </div>
                                <div>
                                    <dt>Vencimiento</dt>
                                    <dd>{{ $document->expires_at?->format('d-m-Y') ?: 'No informado' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <span class="pill {{ $statusClass }}">{{ $document->status_label }}</span>
                    </div>

                    @if ($document->file_path)
                        <div class="document-action">
                            <a href="{{ route('public.vehicles.documents.show', [$vehicle->public_token, $document]) }}" class="btn btn-outline">
                                Ver documento
                            </a>
                        </div>
                    @endif
                </article>
            @empty
                <div class="panel empty-state">
                    Este vehículo no tiene documentos cargados.
                </div>
            @endforelse
        </section>
    </main>
</x-layouts.app>
