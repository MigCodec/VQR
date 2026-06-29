<x-layouts.app title="{{ $title }}">
    <main class="center-shell">
        <section class="panel">
            <img src="/brand/vqr-logo.png" alt="VQR" class="panel-logo">
            <p class="eyebrow">Cuenta VQR</p>
            <h1 class="page-title">{{ $title }}</h1>
            <p class="section-text">{{ $message }}</p>
            <div class="action-row">
                <a href="{{ $actionRoute ?? route('account.show') }}" class="btn btn-navy">{{ $actionLabel ?? 'Ir a mi cuenta' }}</a>
            </div>
        </section>
    </main>
</x-layouts.app>
