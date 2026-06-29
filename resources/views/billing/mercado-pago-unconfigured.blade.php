<x-layouts.app title="Pago pendiente">
    <main class="center-shell">
        <section class="panel">
            <img src="/brand/vqr-logo.png" alt="VQR" class="panel-logo">
            <p class="eyebrow">Integración pendiente</p>
            <h1 class="page-title">El pago todavía no está conectado.</h1>
            <p class="section-text">La ruta de checkout ya existe. Falta crear la preferencia de pago y confirmar la suscripción mediante webhook.</p>
            <div class="action-row">
                <a href="{{ route('billing.show') }}" class="btn btn-navy">Volver a pago</a>
            </div>
        </section>
    </main>
</x-layouts.app>
