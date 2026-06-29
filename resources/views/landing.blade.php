@php
    $structuredData = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'Que documentos del vehiculo puedo guardar en VQR?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'VQR esta pensado para mantener a mano revision tecnica, SOAP y permiso de circulacion desde una tarjeta que se escanea con el celular.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'VQR avisa cuando mis documentos estan por vencer?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Si. VQR te ayuda a visualizar vencimientos y avisos para anticiparte a la renovacion de documentos importantes del vehiculo.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => 'Necesito instalar una app para ver mis documentos?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'No. La informacion se abre desde el navegador del celular al escanear la tarjeta VQR.',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

<x-layouts.app
    title="VQR | Documentos del vehiculo, SOAP, revision tecnica y permiso"
    meta-description="Ordena revision tecnica, SOAP y permiso de circulacion en una tarjeta VQR. Muestra tus documentos desde el celular y recibe avisos antes del vencimiento."
    :structured-data="$structuredData"
>
    <main class="site-main">
        <section class="hero-section">
            <div class="container hero-container">
                <header class="site-header">
                    <a href="{{ route('landing') }}">
                        <img src="/brand/vqr-logo.png" alt="VQR" class="brand-logo">
                    </a>
                    <nav class="nav-actions">
                        <a href="#planes" class="btn btn-outline">Ver planes</a>
                        <a href="/auth/google/redirect" class="btn btn-navy">Crear cuenta</a>
                    </nav>
                </header>

                <div class="hero-grid">
                    <div class="hero-copy">
                        <p class="eyebrow">SOAP, revision tecnica y permiso siempre a mano</p>
                        <h1 class="hero-title">Muestra los documentos de tu vehiculo en segundos.</h1>
                        <p class="hero-text">
                            VQR ordena los papeles obligatorios del auto en una tarjeta lista para usar cuando te los pidan.
                        </p>
                        <div class="hero-points">
                            <span>Sin buscar fotos</span>
                            <span>Sin instalar apps</span>
                            <span>Con avisos de vencimiento</span>
                        </div>
                        <div class="action-row">
                            <a href="/auth/google/redirect" class="btn btn-primary">Crear mi cuenta</a>
                            <a href="#beneficios" class="btn btn-outline">Conocer VQR</a>
                        </div>
                    </div>

                    <div class="hero-card-stage">
                        <div class="device-card">
                            <div class="vqr-card-real">
                                <div class="card-pixel-strip" aria-hidden="true">
                                    @foreach (range(1, 18) as $index)
                                        <span></span>
                                    @endforeach
                                </div>

                                <div class="card-topline">
                                    <div>
                                        <p class="card-brand">VQR</p>
                                        <p class="card-tagline">Tus documentos siempre contigo.</p>
                                    </div>
                                    <div class="nfc-mark" aria-label="NFC">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </div>

                                <div class="card-main-row">
                                    <div class="card-copy">
                                        <p class="card-label">Tarjeta vehicular</p>
                                        <p class="card-plate">ABCD-12</p>
                                        <p class="card-helper">Escanea y muestra tus papeles</p>
                                    </div>
                                    <div class="card-qr-block" aria-label="QR VQR">
                                        <div class="qr-mark qr-mark-large" aria-hidden="true">
                                            @foreach (range(1, 25) as $index)
                                                <span></span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <span>REVISION TECNICA</span>
                                    <span>SOAP</span>
                                    <span>PERMISO</span>
                                </div>
                            </div>
                        </div>

                        <div class="hero-status-panel">
                            <div>
                                <p class="small-label">Estado documental</p>
                                <strong>Todo al dia</strong>
                            </div>
                            <span class="pill pill-blue">Listo para mostrar</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="beneficios" class="section">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow">Beneficios</p>
                    <h2 class="section-title">Mas orden, menos preocupacion</h2>
                    <p class="section-text">
                        Ten a mano SOAP, revision tecnica y permiso de circulacion, y anticipate a vencimientos antes de que se transformen en un problema.
                    </p>
                </div>

                <div class="feature-grid">
                    <article class="feature-card">
                        <img src="/brand/benefits/card.svg" alt="" class="feature-image" loading="lazy">
                        <h3>Todo desde una tarjeta</h3>
                        <p>Escanea y accede a los papeles del vehiculo sin buscar carpetas, fotos o archivos sueltos.</p>
                    </article>
                    <article class="feature-card">
                        <img src="/brand/benefits/checkpoint.svg" alt="" class="feature-image" loading="lazy">
                        <h3>Listo cuando te lo pidan</h3>
                        <p>Para controles, emergencias o tramites, muestra lo importante en pocos segundos.</p>
                    </article>
                    <article class="feature-card">
                        <img src="/brand/benefits/documents.svg" alt="" class="feature-image" loading="lazy">
                        <h3>Documentos en orden</h3>
                        <p>Revision tecnica, SOAP y permiso de circulacion reunidos en un solo lugar.</p>
                    </article>
                    <article class="feature-card">
                        <img src="/brand/benefits/mobile.svg" alt="" class="feature-image" loading="lazy">
                        <h3>Sin instalar aplicaciones</h3>
                        <p>Abre la informacion desde el celular y comparte lo necesario sin complicaciones.</p>
                    </article>
                    <article class="feature-card">
                        <img src="/brand/benefits/status.svg" alt="" class="feature-image" loading="lazy">
                        <h3>Avisos antes del vencimiento</h3>
                        <p>Te avisa cuando tus documentos estan por vencer para que puedas renovarlos a tiempo.</p>
                    </article>
                    <article class="feature-card">
                        <img src="/brand/benefits/daily.svg" alt="" class="feature-image" loading="lazy">
                        <h3>Para autos personales o flotas</h3>
                        <p>Sirve para conductores, familias y empresas que necesitan tener sus vehiculos bajo control.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section-alt">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow">Como funciona</p>
                    <h2 class="section-title">De tus documentos al celular en tres pasos</h2>
                </div>

                <div class="steps-grid">
                    <article class="step-item">
                        <span>1</span>
                        <h3>Crea tu cuenta</h3>
                        <p>Activa tu licencia anual y agrega los datos de tu vehiculo.</p>
                    </article>
                    <article class="step-item">
                        <span>2</span>
                        <h3>Sube tus documentos</h3>
                        <p>Guarda revision tecnica, SOAP y permiso de circulacion en tu panel.</p>
                    </article>
                    <article class="step-item">
                        <span>3</span>
                        <h3>Usa tu tarjeta VQR</h3>
                        <p>Escaneala desde el celular y muestra los papeles cuando los necesites.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow">Documentos del vehiculo</p>
                    <h2 class="section-title">Lo importante, reunido en un solo lugar</h2>
                    <p class="section-text">
                        En Chile, conductores y empresas suelen necesitar tener claros sus documentos vigentes para controles, tramites y renovaciones.
                    </p>
                </div>

                <div class="document-focus-grid">
                    <article class="document-focus-item">
                        <span>SOAP</span>
                        <h3>Seguro obligatorio</h3>
                        <p>Guarda tu SOAP y tenlo disponible junto al resto de los papeles del vehiculo.</p>
                    </article>
                    <article class="document-focus-item">
                        <span>Revision tecnica</span>
                        <h3>Vigencia visible</h3>
                        <p>Revisa el estado y la fecha de vencimiento para no enterarte tarde.</p>
                    </article>
                    <article class="document-focus-item">
                        <span>Permiso de circulacion</span>
                        <h3>Listo para marzo</h3>
                        <p>Manten el permiso ordenado y accesible cuando lo necesites consultar.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="planes" class="section">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow">Planes</p>
                    <h2 class="section-title">Elige segun la cantidad de vehiculos</h2>
                    <p class="section-text">Pago anual simple para mantener tu cuenta activa y tus documentos disponibles.</p>
                </div>

                <div class="landing-plan-grid">
                    <article class="landing-plan">
                        <p class="small-label">Normal</p>
                        <h3>$5.000 <span>CLP / a&ntilde;o</span></h3>
                        <p>Para mantener los documentos de 1 vehiculo siempre a mano.</p>
                        <a href="/auth/google/redirect" class="btn btn-outline">Empezar con normal</a>
                    </article>
                    <article class="landing-plan landing-plan-featured">
                        <p class="small-label">Premium</p>
                        <h3>$10.000 <span>CLP / a&ntilde;o</span></h3>
                        <p>Para administrar hasta 3 vehiculos desde una misma cuenta.</p>
                        <a href="/auth/google/redirect" class="btn btn-primary">Empezar con premium</a>
                    </article>
                    <article class="landing-plan landing-plan-enterprise">
                        <p class="small-label">Empresa</p>
                        <h3>Hablemos <span>Solucion a medida</span></h3>
                        <p>Para equipos, flotas o empresas que necesitan gestionar mas vehiculos y tarjetas.</p>
                        <a href="mailto:contacto@vqr.cl?subject=Plan%20Empresa%20VQR" class="btn btn-outline">Hablemos</a>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section-alt">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow">Preguntas frecuentes</p>
                    <h2 class="section-title">Dudas comunes sobre documentos del auto</h2>
                </div>

                <div class="faq-list">
                    <details>
                        <summary>Que documentos puedo ordenar en VQR?</summary>
                        <p>Revision tecnica, SOAP y permiso de circulacion. La idea es que los papeles mas pedidos del vehiculo esten siempre disponibles desde el celular.</p>
                    </details>
                    <details>
                        <summary>VQR reemplaza los tramites oficiales?</summary>
                        <p>No. VQR ayuda a ordenar, mostrar y recordar tus documentos. Las compras, pagos o renovaciones oficiales se hacen en los canales correspondientes.</p>
                    </details>
                    <details>
                        <summary>Me avisa si un documento esta por vencer?</summary>
                        <p>Si. VQR muestra vencimientos y avisos para que puedas renovar con tiempo tu SOAP, revision tecnica o permiso de circulacion.</p>
                    </details>
                    <details>
                        <summary>Necesito instalar una aplicacion?</summary>
                        <p>No. Al escanear la tarjeta VQR, la informacion se abre desde el navegador del celular.</p>
                    </details>
                </div>
            </div>
        </section>

        <section class="section final-cta-section">
            <div class="container">
                <div class="final-cta">
                    <div>
                        <p class="eyebrow">Empieza hoy</p>
                        <h2 class="section-title">Ten tus papeles listos antes de que te los pidan.</h2>
                        <p class="section-text">Crea tu cuenta, sube tus documentos y usa VQR desde tu celular.</p>
                    </div>
                    <a href="/auth/google/redirect" class="btn btn-primary">Crear mi cuenta</a>
                </div>
            </div>
        </section>
    </main>
</x-layouts.app>
