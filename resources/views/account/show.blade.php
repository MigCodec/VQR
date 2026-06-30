<x-layouts.app title="Mi cuenta VQR">
    <main class="account-shell">
        <header class="panel public-header">
            <div class="header-logo-row">
                <img src="/brand/vqr-logo.png" alt="VQR" class="brand-logo">
                <span class="pill {{ $subscription ? 'pill-blue' : 'pill-muted' }}">
                    {{ $subscription ? 'Licencia activa' : 'Vista admin' }}
                </span>
            </div>
            <p class="page-kicker">Mi cuenta</p>
            <div class="header-title-row">
                <div>
                    <h1 class="page-title">{{ $user->name }}</h1>
                    <p class="page-subtitle">{{ $user->email }}</p>
                </div>
                <div>
                    @if ($subscription)
                        <p class="page-subtitle">Activa hasta {{ $subscription->expires_at?->format('d-m-Y') }}</p>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="inline-action-form">
                        @csrf
                        <button type="submit" class="btn btn-outline">Cerrar sesión</button>
                    </form>
                    @if ($user->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Panel admin</a>
                    @endif
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="notice notice-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice notice-error">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="panel account-summary">
            <div>
                <h2 class="section-title">Vehiculos</h2>
                <p class="section-text">
                    Usas {{ $vehicleCount }} de {{ $vehicleLimit }} cupos de tu licencia {{ $subscription?->plan === 'premium' ? 'Premium' : 'Normal' }}.
                </p>
                @if ($vehicleCount > 0)
                    <div class="mobile-action-row">
                        <a href="#documentos" class="btn btn-primary">Subir documentos</a>
                    </div>
                @endif
                <div class="card-link-box account-card-box">
                    <div class="account-vqr-card-wrap">
                        <div class="vqr-card-real account-vqr-card">
                            <div class="card-pixel-strip" aria-hidden="true">
                                @foreach (range(1, 18) as $index)
                                    <span></span>
                                @endforeach
                            </div>

                            <div class="card-topline">
                                <div>
                                    <p class="card-brand">VQR</p>
                                    <p class="card-tagline">Tu vehículo. Tu información.</p>
                                </div>
                                <div class="nfc-mark" aria-label="NFC">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>

                            <div class="card-main-row">
                                <div class="card-copy">
                                    <p class="card-label">Tarjeta de la cuenta</p>
                                    <p class="card-plate">VQR</p>
                                    <p class="card-helper">Escanea para ver vehículos</p>
                                </div>
                                <div class="card-qr-block account-card-real-qr">
                                    <img src="{{ route('public.cards.qr', $accountCard->short_code) }}" alt="QR de tarjeta VQR">
                                </div>
                            </div>

                            <div class="card-footer">
                                <span>REVISION TECNICA</span>
                                <span>SOAP</span>
                                <span>PERMISO</span>
                            </div>
                        </div>
                        <a href="{{ route('public.cards.show', $accountCard->short_code) }}" class="account-card-url">{{ $accountCard->public_url }}</a>
                    </div>
                </div>
            </div>

            @if ($canAddVehicle)
                <form method="POST" action="{{ route('account.vehicles.store') }}" class="vehicle-form">
                    @csrf
                    <div class="form-grid">
                        <label>
                            <span>Patente</span>
                            <input name="plate" value="{{ old('plate') }}" placeholder="ABCD-12" required>
                        </label>
                        <label>
                            <span>Anno</span>
                            <input type="number" name="year" value="{{ old('year') }}" min="1900" max="{{ now()->year + 1 }}">
                        </label>
                    </div>
                    <div class="form-grid">
                        <label>
                            <span>Marca</span>
                            <input name="brand" value="{{ old('brand') }}" placeholder="Toyota">
                        </label>
                        <label>
                            <span>Modelo</span>
                            <input name="model" value="{{ old('model') }}" placeholder="Corolla">
                        </label>
                    </div>
                    <label>
                        <span>VIN / Chasis</span>
                        <input name="vin" value="{{ old('vin') }}" placeholder="Opcional">
                    </label>
                    <button class="btn btn-primary" type="submit">Agregar vehículo</button>
                </form>
            @else
                <p class="section-text">Tu licencia actual no permite agregar más vehículos.</p>
            @endif
        </section>

        <section class="account-grid" id="documentos">
            @forelse ($vehicles as $vehicle)
                @php
                    $documentsByType = $vehicle->documents->keyBy('document_type_id');
                @endphp

                <article class="vehicle-panel">
                    <div class="vehicle-panel-head">
                        <div>
                            <p class="page-kicker">Vehiculo</p>
                            <h2 class="vehicle-plate">{{ $vehicle->plate }}</h2>
                            <p class="vehicle-meta">{{ $vehicle->display_name }} @if($vehicle->year) - {{ $vehicle->year }} @endif</p>
                        </div>
                        <a class="btn btn-outline" href="{{ route('public.vehicles.show', $vehicle->public_token) }}">Vista publica</a>
                    </div>

                    <div class="document-section-head">
                        <div>
                            <h3>Documentos requeridos</h3>
                            <p class="muted-small">Sube o reemplaza revisión técnica, SOAP y permiso de circulación.</p>
                        </div>
                    </div>

                    <div class="document-upload-grid">
                        @foreach ($documentTypes as $documentType)
                            @php
                                $document = $documentsByType->get($documentType->id);
                                $statusClass = match ($document?->computed_status) {
                                    'valid' => 'pill-blue',
                                    'expiring_soon' => 'pill-warning',
                                    'expired', 'rejected' => 'pill-danger',
                                    default => 'pill-muted',
                                };
                            @endphp

                            <section class="document-upload-card">
                                <div class="document-upload-head">
                                    <div>
                                        <h3>{{ $documentType->name }}</h3>
                                        <p class="muted-small">
                                            @if ($document)
                                                Vence {{ $document->expires_at?->format('d-m-Y') ?: 'sin fecha' }}
                                            @else
                                                Sube el archivo y VQR intentará detectar los datos automáticamente
                                            @endif
                                            @if ($document?->ai_extracted)
                                                · Datos detectados automáticamente
                                            @endif
                                        </p>
                                    </div>
                                    <span class="pill {{ $statusClass }}">{{ $document?->status_label ?? 'Pendiente' }}</span>
                                </div>

                                <form method="POST" action="{{ route('account.vehicles.documents.store', [$vehicle, $documentType]) }}" enctype="multipart/form-data" class="upload-form">
                                    @csrf
                                    @if ($document)
                                        <dl class="document-auto-meta">
                                            <div>
                                                <dt>Folio</dt>
                                                <dd>{{ $document->folio ?: 'No detectado' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Emisión</dt>
                                                <dd>{{ $document->issued_at?->format('d-m-Y') ?: 'No detectada' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Vencimiento</dt>
                                                <dd>{{ $document->expires_at?->format('d-m-Y') ?: 'No detectado' }}</dd>
                                            </div>
                                        </dl>
                                    @endif
                                    <label>
                                        <span>Archivo</span>
                                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                    </label>
                                    <button class="btn btn-primary" type="submit">Subir y detectar datos</button>
                                </form>
                            </section>
                        @endforeach
                    </div>
                </article>
            @empty
                <article class="panel">
                    <h2 class="section-title">Vehiculos</h2>
                    <p class="section-text">Aún no tienes vehículos asociados.</p>
                </article>
            @endforelse
        </section>
    </main>
</x-layouts.app>
