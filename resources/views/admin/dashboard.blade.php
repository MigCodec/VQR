<x-layouts.app title="Admin VQR">
    <main class="account-shell">
        <header class="panel public-header">
            <div class="header-logo-row">
                <img src="/brand/vqr-logo.png" alt="VQR" class="brand-logo">
                <span class="pill pill-blue">Administrador</span>
            </div>
            <p class="page-kicker">Panel admin</p>
            <div class="header-title-row">
                <div>
                    <h1 class="page-title">Tarjetas y usuarios</h1>
                    <p class="page-subtitle">Gestiona tarjetas QR y NFC TAG 424 DNA vinculadas a cuentas VQR.</p>
                </div>
                <a href="{{ route('account.show') }}" class="btn btn-outline">Panel usuario</a>
            </div>
        </header>

        @if (session('status'))
            <div class="notice notice-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="notice notice-error">{{ $errors->first() }}</div>
        @endif

        <section class="panel admin-section">
            <h2 class="section-title">Crear tarjeta</h2>
            <form method="POST" action="{{ route('admin.cards.store') }}" class="admin-form">
                @csrf
                <div class="form-grid">
                    <label>
                        <span>Tipo</span>
                        <select name="type" required>
                            @foreach ($cardTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Usuario vinculado</span>
                        <select name="user_id">
                            <option value="">Sin vincular</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="form-grid">
                    <label>
                        <span>Etiqueta</span>
                        <input name="label" placeholder="Opcional">
                    </label>
                    <label>
                        <span>Identificador NFC / TAG</span>
                        <input name="nfc_identifier" placeholder="Opcional si aun no esta grabada">
                    </label>
                </div>
                <button class="btn btn-primary" type="submit">Crear tarjeta</button>
            </form>
        </section>

        <section class="admin-grid">
            <article class="panel admin-section">
                <h2 class="section-title">Usuarios</h2>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Cuenta</th>
                                <th>Rol</th>
                                <th>Vehiculos</th>
                                <th>Tarjetas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                        <span>{{ $user->email }}</span>
                                    </td>
                                    <td>{{ $user->is_admin ? 'Admin' : 'Cliente' }}</td>
                                    <td>{{ $user->active_vehicles_count }}</td>
                                    <td>{{ $user->cards_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="panel admin-section">
                <h2 class="section-title">Tarjetas</h2>
                <div class="admin-card-list">
                    @forelse ($cards as $card)
                        <section class="admin-card-row">
                            <div>
                                <p class="small-label">{{ $card->type_label }}</p>
                                <h3>{{ $card->label ?: 'Sin etiqueta' }}</h3>
                                <p class="muted-small">{{ $card->public_url }}</p>
                                <p class="muted-small">NFC/TAG: {{ $card->nfc_identifier }}</p>
                                <p class="muted-small">
                                    Vinculada:
                                    @if ($card->user)
                                        {{ $card->user->name }} - {{ $card->user->email }}
                                    @else
                                        Sin usuario
                                    @endif
                                </p>
                            </div>

                            <div class="admin-card-actions">
                                <form method="POST" action="{{ route('admin.cards.attach', $card) }}">
                                    @csrf
                                    <select name="user_id" required>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected($card->user_id === $user->id)>
                                                {{ $user->email }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-outline" type="submit">Vincular</button>
                                </form>

                                @if ($card->user_id)
                                    <form method="POST" action="{{ route('admin.cards.detach', $card) }}">
                                        @csrf
                                        <button class="btn btn-outline" type="submit">Desvincular</button>
                                    </form>
                                @endif
                            </div>
                        </section>
                    @empty
                        <p class="section-text">Aun no hay tarjetas creadas.</p>
                    @endforelse
                </div>
            </article>
        </section>
    </main>
</x-layouts.app>
