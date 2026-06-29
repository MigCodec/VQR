<x-layouts.app title="Vehículos disponibles">
    <main class="page-shell">
        <header class="panel public-header">
            <div class="header-logo-row">
                <img src="/brand/vqr-logo.png" alt="VQR" class="brand-logo">
                <span class="pill pill-blue">Cuenta activa</span>
            </div>
            <p class="page-kicker">Tarjeta VQR</p>
            <div class="header-title-row">
                <div>
                    <h1 class="page-title">Vehículos disponibles</h1>
                    <p class="page-subtitle">Selecciona un vehículo para ver sus documentos.</p>
                </div>
            </div>
        </header>

        <section class="stack">
            @forelse ($vehicles as $vehicle)
                @php($summary = $vehicle->documentSummary())
                <a href="{{ route('public.vehicles.show', $vehicle->public_token) }}" class="list-card">
                    <div class="list-card-content">
                        <div>
                            <p class="vehicle-plate">{{ $vehicle->plate }}</p>
                            <p class="vehicle-meta">{{ $vehicle->display_name }} @if($vehicle->year) · {{ $vehicle->year }} @endif</p>
                        </div>
                        <div class="pill-row">
                            <span class="pill pill-muted">{{ $summary['total'] }} docs</span>
                            <span class="pill pill-blue">{{ $summary['valid'] }} vigentes</span>
                            @if ($summary['attention'] > 0)
                                <span class="pill pill-warning">{{ $summary['attention'] }} revisar</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="panel empty-state">
                    Esta tarjeta no tiene vehículos activos asociados.
                </div>
            @endforelse
        </section>
    </main>
</x-layouts.app>
