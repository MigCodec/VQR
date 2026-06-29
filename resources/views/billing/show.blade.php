<x-layouts.app title="Activar cuenta">
    <main class="center-shell">
        <section class="panel">
            <img src="/brand/vqr-logo.png" alt="VQR" class="panel-logo">
            <p class="eyebrow">Suscripción anual</p>
            <h1 class="page-title">Activa tu cuenta VQR</h1>
            <p class="section-text">Elige la licencia según la cantidad de vehículos que necesitas administrar.</p>

            <div class="plan-grid">
                <article class="plan-card">
                    <div>
                        <p class="small-label">Normal</p>
                        <p class="billing-price">$5.000 <span>CLP / año</span></p>
                        <p class="section-text">Permite administrar 1 vehículo.</p>
                    </div>
                    <form method="POST" action="{{ route('billing.checkout') }}" class="action-row">
                        @csrf
                        <input type="hidden" name="plan" value="normal">
                        <button class="btn btn-primary">Elegir normal</button>
                    </form>
                </article>

                <article class="plan-card plan-card-featured">
                    <div>
                        <p class="small-label">Premium</p>
                        <p class="billing-price">$10.000 <span>CLP / año</span></p>
                        <p class="section-text">Permite administrar hasta 3 vehículos.</p>
                    </div>
                    <form method="POST" action="{{ route('billing.checkout') }}" class="action-row">
                        @csrf
                        <input type="hidden" name="plan" value="premium">
                        <button class="btn btn-navy">Elegir premium</button>
                    </form>
                </article>
            </div>
        </section>
    </main>
</x-layouts.app>
