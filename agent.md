# VQR Agent Guide

## Propósito del producto

VQR es una aplicación web para mostrar los documentos legales asociados a un vehículo. La experiencia principal debe permitir que una persona consulte rápidamente el estado documental del vehículo, idealmente desde un código QR o enlace directo, sin fricción innecesaria.

El producto debe priorizar claridad, confianza y lectura rápida. La interfaz tiene que servir para verificar documentos, vigencias y datos relevantes del vehículo, no para vender ni para presentar una página promocional.

La aplicación debe tener una landing page inicial para explicar el servicio, mostrar el precio anual y dirigir a registro, pago o acceso según corresponda.

## Estado actual del proyecto

- Framework: Laravel 12.
- PHP requerido: `^8.2`.
- Frontend: Blade puro con CSS estático en `public/css/vqr.css`.
- Vista inicial actual: `resources/views/welcome.blade.php`, todavía corresponde al starter de Laravel.
- Ruta inicial actual: `/`, definida en `routes/web.php`.
- Base de datos inicial: migraciones base de usuarios, cache y jobs generadas por Laravel.
- No hay dominio funcional implementado todavía para vehículos, documentos, QR ni consultas públicas.
- El directorio actual no está inicializado como repositorio Git.

## Dominio esperado

Entidades probables:

- Usuario: puede tener uno o más vehículos asociados.
- Autenticación: el usuario puede registrarse e iniciar sesión con Google.
- Suscripción: cada usuario debe pagar una suscripción anual para habilitar su cuenta. Existen dos planes: normal de $5.000 anuales para 1 vehículo y premium de $10.000 anuales para hasta 3 vehículos.
- Pago: se procesa mediante Mercado Pago.
- Tarjeta: pertenece a un usuario, tiene un identificador único detectable por NFC y puede tener un QR impreso con una URL corta pública.
- Tipos de tarjeta: el sistema debe soportar en paralelo tarjetas QR con link único y tarjetas `NFC TAG 424 DNA`.
- Vehículo: puede estar asociado a uno o más usuarios mediante una relación histórica. Contiene patente, marca, modelo, año, VIN/chasis si aplica.
- Documento legal: tipo, número/folio, fecha de emisión, fecha de vencimiento, estado, archivo o URL, observaciones.
- Tipos de documento para el primer alcance: revisión técnica, SOAP y permiso de circulación.
- Consulta pública desde tarjeta: página legible desde NFC o QR para ver la lista de vehículos del usuario asociado a la tarjeta.
- Consulta pública de vehículo: al seleccionar un vehículo, muestra su resumen y los documentos asociados.
- Administración: carga, edición y actualización de documentos por usuarios autorizados.

Para el primer alcance, cada vehículo debe tener estos tres documentos: revisión técnica, SOAP y permiso de circulación. Modelar el sistema para que los tipos de documento puedan crecer más adelante sin rehacer la estructura principal.

Cada usuario puede tener una o más tarjetas. La tarjeta funciona como punto de entrada físico: una persona puede tocarla con un celular mediante NFC o escanear el QR impreso. Ambos caminos deben llevar a la URL pública corta de esa tarjeta.

Un cliente puede tener una o más tarjetas vinculadas a su cuenta. Las tarjetas pueden vincularse y desvincularse desde administración. La gestión técnica de tarjetas está deshabilitada para clientes comunes y solo queda disponible para cuentas con permiso de administrador.

Para el MVP, cada tarjeta pertenece al usuario y funciona como entrada a la lista de vehículos activos de ese usuario. La estructura conserva la relación N a N entre tarjetas y vehículos para permitir más adelante limitar una tarjeta a ciertos vehículos o habilitar reglas premium, pero no se debe crear una tarjeta por vehículo por defecto.

Cada usuario debe tener una cuenta habilitada para administrar vehículos, tarjetas y documentos. La habilitación depende de una suscripción anual pagada mediante Mercado Pago.

La relación entre usuarios y vehículos es N a N con historial. Si un vehículo se vende, se cierra la relación anterior con `ends_at` y se crea una nueva relación activa para el nuevo usuario, sin duplicar el vehículo ni perder documentos.

El registro con Google crea o vincula la cuenta del usuario, pero no reemplaza la suscripción. Después de autenticarse, si el usuario no tiene una suscripción activa, debe ser dirigido al flujo de pago.

## Principios funcionales

- La consulta pública de vehículo debe mostrar primero el estado general del vehículo y luego el detalle documental.
- La consulta pública de tarjeta debe mostrar primero la lista de vehículos del usuario asociado.
- Al hacer click en un vehículo desde la página de tarjeta, se debe abrir la vista pública de ese vehículo con sus documentos.
- Cada documento debe indicar claramente si está vigente, vencido, próximo a vencer, pendiente o no disponible.
- Las fechas deben mostrarse en formato local chileno cuando aplique.
- Los archivos legales deben abrirse o descargarse de forma explícita, sin ocultar el origen.
- La página pública debe funcionar bien en móvil, porque el flujo natural será escanear un QR.
- Si falta un documento, mostrar un estado claro en vez de romper la página.
- El administrador debe poder ver el identificador NFC, la URL corta y los datos necesarios para imprimir o grabar cada tarjeta.
- La parte técnica de tarjetas, QR, NFC, tokens, rutas internas, IDs, configuración o datos de impresión no se muestra nunca al cliente final. Solo debe aparecer en pantallas administrativas para el administrador del sistema.
- Una cuenta administradora debe tener acceso al panel admin y también al panel de usuario común. Desde el panel admin puede ver usuarios, tarjetas QR, tarjetas NFC TAG 424 DNA y sus vínculos.
- El usuario solo debe poder administrar su cuenta cuando su suscripción anual esté activa.
- El sistema debe registrar pagos, estado de suscripción, fecha de inicio y fecha de vencimiento.
- El plan normal permite agregar 1 vehículo.
- El plan premium permite agregar hasta 3 vehículos.
- El panel debe bloquear la creación de vehículos cuando el usuario alcanza el límite de su licencia.
- Al vencer la suscripción, se debe bloquear la administración del usuario hasta renovar el pago y las consultas públicas deben dejar de mostrar documentos.

## Privacidad y seguridad

- No exponer datos personales innecesarios en páginas públicas.
- No exponer información técnica al cliente final: identificadores NFC internos, tokens, IDs, rutas internas, nombres de storage, configuración de pagos, credenciales, webhooks, errores técnicos o mensajes pensados para soporte/impresión.
- No usar IDs autoincrementales como identificador público consultable si se implementan enlaces QR. Preferir UUID, ULID o token público estable.
- No exponer el identificador NFC interno como clave primaria de base de datos. Usarlo como identificador único de tarjeta, pero resolverlo hacia un token público o slug controlado.
- Validar autorización en cualquier pantalla administrativa.
- Validar estado de suscripción antes de permitir acciones administrativas del usuario.
- Validar el login con Google mediante OAuth oficial y no confiar en datos enviados manualmente desde el cliente.
- Guardar el identificador estable de Google del usuario para evitar cuentas duplicadas por cambios de correo.
- Verificar pagos de Mercado Pago mediante callbacks/webhooks firmados o mecanismos oficiales equivalentes.
- No confiar solamente en el retorno del navegador para activar una cuenta pagada.
- Validar carga de archivos por tipo, tamaño y permisos.
- Guardar documentos privados fuera de `public/` salvo que el negocio decida explícitamente que son públicos.
- Los archivos subidos no deben tener enlace público directo ni depender de `/storage`. Deben servirse desde una ruta controlada por Laravel usando token público del documento, validando vehículo y suscripción activa.
- No registrar en logs datos sensibles de propietarios, documentos o tokens.

## Convenciones de implementación

- Seguir patrones nativos de Laravel antes de crear abstracciones nuevas.
- Usar migraciones, modelos Eloquent, factories y seeders para el dominio.
- Mantener controladores delgados cuando empiece a crecer la lógica; mover reglas de negocio a servicios o acciones solo cuando haya complejidad real.
- Usar Form Requests para validaciones de formularios administrativos.
- Usar políticas o gates para permisos cuando exista autenticación.
- Evitar lógica de negocio en Blade.
- Mantener rutas públicas separadas conceptualmente de rutas administrativas.

## Estructura sugerida inicial

Rutas públicas:

- `GET /`: landing page inicial del servicio.
- `GET /t/{short_code}`: muestra la lista de vehículos del usuario asociado a la tarjeta.
- `GET /v/{public_token}`: muestra el vehículo seleccionado y sus documentos asociados.
- `GET /v/{public_token}/documents/{document}`: abre o descarga un documento permitido.

Rutas de cuenta y pago:

- `GET /register`: registro de usuario.
- `GET /auth/google/redirect`: inicia autenticación con Google.
- `GET /auth/google/callback`: recibe callback OAuth de Google y crea o vincula usuario.
- `GET /billing`: muestra estado de suscripción y opción de pago/renovación.
- `POST /billing/mercado-pago/checkout`: crea preferencia o sesión de pago en Mercado Pago.
- `GET /billing/mercado-pago/success`: retorno visible después de pago aprobado.
- `GET /billing/mercado-pago/failure`: retorno visible después de pago fallido.
- `GET /billing/mercado-pago/pending`: retorno visible después de pago pendiente.
- `POST /webhooks/mercado-pago`: webhook para confirmar pagos y actualizar suscripción.

Rutas administrativas, cuando exista autenticación:

- `GET /admin/cards`
- `POST /admin/cards`
- `GET /admin/cards/{card}`: muestra identificador NFC, URL corta, destino público y datos para impresión.
- `PUT /admin/cards/{card}`
- `GET /admin/vehicles`
- `POST /admin/vehicles`
- `GET /admin/vehicles/{vehicle}`
- `PUT /admin/vehicles/{vehicle}`
- `POST /admin/vehicles/{vehicle}/documents`
- `PUT /admin/documents/{document}`
- `DELETE /admin/documents/{document}`

Tablas probables:

- `cards`
- `subscriptions`
- `payments`
- `vehicles`
- `user_vehicle`
- `card_vehicle`
- `document_types`
- `vehicle_documents`

Campos sugeridos para `cards`:

- `id`
- `user_id` nullable para permitir tarjetas sin vincular temporalmente
- `type` (`qr_link` o `nfc_tag_424_dna`)
- `nfc_identifier` único
- `short_code` único
- `public_url`
- `label`
- `status`
- timestamps

Campos sugeridos para `subscriptions`:

- `id`
- `user_id`
- `status`
- `plan`
- `vehicle_limit`
- `amount`
- `currency`
- `starts_at`
- `expires_at`
- `last_payment_id`
- timestamps

Campos sugeridos adicionales para `users`:

- `google_id`
- `avatar_url`
- `email_verified_at`

Campos sugeridos para `payments`:

- `id`
- `user_id`
- `subscription_id`
- `provider`
- `provider_payment_id`
- `provider_preference_id`
- `status`
- `plan`
- `amount`
- `currency`
- `paid_at`
- `raw_payload`
- timestamps

Campos sugeridos para `vehicles`:

- `id`
- `public_token`
- `plate`
- `brand`
- `model`
- `year`
- `vin`
- `status`
- timestamps

Campos sugeridos para `user_vehicle`:

- `id`
- `user_id`
- `vehicle_id`
- `role`
- `starts_at`
- `ends_at`
- `is_primary`
- timestamps

Campos sugeridos para `card_vehicle`:

- `id`
- `card_id`
- `vehicle_id`
- `starts_at`
- `ends_at`
- timestamps

Campos sugeridos para `document_types`:

- `id`
- `name`
- `slug`
- `description`
- `is_required`
- `sort_order`
- timestamps

Campos sugeridos para `vehicle_documents`:

- `id`
- `vehicle_id`
- `document_type_id`
- `folio`
- `issued_at`
- `expires_at`
- `status`
- `file_path`
- `source_url`
- `notes`
- timestamps

## Estados documentales

Usar estados explícitos y consistentes:

- `valid`: documento vigente.
- `expired`: documento vencido.
- `expiring_soon`: documento próximo a vencer.
- `pending`: documento pendiente de carga o revisión.
- `missing`: documento no disponible.
- `rejected`: documento cargado pero inválido o rechazado.

El estado puede guardarse o calcularse; si se calcula desde fechas, mantener la regla en un solo lugar.

## Frontend y UX

- La ruta `/` debe ser una landing page inicial del servicio.
- La landing debe explicar de forma directa el servicio, el precio anual de $5.000, el uso de tarjetas NFC/QR y el acceso a documentos vehiculares.
- La landing debe tener llamadas claras a registro con Google, inicio de sesión y pago/activación cuando aplique.
- Priorizar diseño móvil, lectura rápida y jerarquía clara.
- La vista pública de tarjeta debe ser una lista simple de vehículos, optimizada para abrir rápido desde NFC o QR.
- En MVP, esa lista normalmente tendrá un solo vehículo por tarjeta. El diseño debe soportar más de uno para la futura modalidad premium.
- Cada vehículo en la lista debe mostrar patente, marca/modelo y un resumen breve del estado documental.
- Mostrar una cabecera compacta con patente y estado general.
- Usar tarjetas o filas para documentos individuales, sin anidar tarjetas.
- Evitar textos largos de explicación dentro de la app.
- Usar colores de estado con texto visible: vigente, vencido, por vencer, pendiente.
- Asegurar que botones y textos no se desborden en pantallas pequeñas.

## Flujo NFC y QR

1. El administrador crea una tarjeta para un usuario.
2. El sistema genera o registra un `nfc_identifier` único y un `short_code` público.
3. El administrador ve la URL corta resultante, por ejemplo `/t/{short_code}`, y la usa para imprimir el QR o grabar la tarjeta NFC.
4. Una persona escanea el QR o toca la tarjeta NFC con el celular.
5. El navegador abre la vista pública de tarjeta.
6. La vista muestra todos los vehículos del usuario asociado a esa tarjeta.
7. Al seleccionar un vehículo, se abre la vista de documentos de ese vehículo.

El QR y el NFC deben resolver al mismo destino público siempre que sea posible. Esto simplifica impresión, soporte y trazabilidad.

## Panel de usuario

- El panel debe ser responsive y usable desde celular.
- El panel del cliente debe mostrar la tarjeta de cuenta con QR real y una experiencia limpia, sin identificadores NFC internos ni instrucciones técnicas de impresión/grabación.
- En MVP, el panel debe mantener una tarjeta de cuenta asociada al usuario.
- La gestión de vincular, desvincular, crear o configurar tarjetas no debe aparecer para clientes comunes. Solo puede estar disponible para cuentas administradoras.
- El panel debe permitir subir o reemplazar revisión técnica, SOAP y permiso de circulación.
- Cada carga debe permitir archivo, folio opcional, fecha de emisión opcional y fecha de vencimiento requerida.
- Los documentos cargados deben guardarse en storage privado por defecto.
- Los documentos cargados solo deben abrirse desde el enlace controlado de VQR. No usar URLs públicas directas para archivos subidos.

## Suscripción y pagos

- El plan normal cuesta $5.000 anuales y permite 1 vehículo.
- El plan premium cuesta $10.000 anuales y permite hasta 3 vehículos.
- La suscripción habilita al usuario para administrar sus vehículos, tarjetas y documentos.
- Mercado Pago es el proveedor de pago del sistema.
- El sistema debe crear el flujo de pago con APIs oficiales de Mercado Pago.
- La activación o renovación de cuenta debe ocurrir solo después de confirmar el pago con Mercado Pago.
- Registrar cada intento o pago relevante en `payments`.
- Mantener el estado anual en `subscriptions`.
- Al renovar, extender la suscripción desde la fecha de vencimiento vigente si aún está activa, o desde la fecha de pago si ya venció.
- Usar moneda local según operación del negocio; por defecto considerar CLP mientras no se defina otro país.

## Autenticación con Google

- Google debe estar disponible para registro e inicio de sesión.
- Si el correo de Google coincide con un usuario existente, vincular la cuenta con cuidado y solo si la identidad está verificada.
- Guardar `google_id` como identificador principal de la vinculación OAuth.
- Después del login, dirigir al usuario según estado:
  - Sin suscripción activa: pantalla de pago/activación.
  - Con suscripción activa: panel administrativo.
- El acceso público por tarjeta o vehículo no debe requerir login salvo que se defina lo contrario.

## Comandos útiles

Instalación inicial:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Desarrollo:

```bash
composer run dev
```

Servidor Laravel solamente:

```bash
php artisan serve
```

Tests:

```bash
composer test
php artisan test
```

Administración:

```bash
php artisan vqr:grant-admin correo@dominio.cl
php artisan vqr:grant-admin correo@dominio.cl --revoke
```

Formato PHP:

```bash
./vendor/bin/pint
```

En Windows PowerShell, si el binario shell no responde:

```powershell
vendor\bin\pint.bat
vendor\bin\phpunit.bat
```

## Criterios antes de cerrar cambios

- Ejecutar tests relevantes con `php artisan test` o `composer test`.
- No usar npm, Vite ni Tailwind en este proyecto. Mantener estilos en CSS estático servido desde `public/css/vqr.css`.
- Revisar migraciones antes de aplicarlas a datos reales.
- No commitear `.env`, archivos generados por storage, ni documentos reales de vehículos.
- Confirmar que la consulta pública no filtra datos privados.

## Preguntas abiertas

- Si la aplicación será solo Chile o debe soportar otros países.
- Quién administra los documentos y qué roles existirán.
- Si los documentos cargados serán públicos, privados con acceso por token, o solo visibles a usuarios autenticados.
- Qué regla exacta define "próximo a vencer".
- Si se requiere historial de documentos anteriores o solo el documento vigente.
- Si una tarjeta puede cambiar de usuario después de impresa o debe quedar vinculada permanentemente.
- Si se requiere desactivar una tarjeta perdida sin borrar sus datos.
- Si el precio anual de $5.000 incluye impuestos, comisiones de Mercado Pago o ambos se absorben por separado.
- Si también se permitirá registro con correo/contraseña o solo Google.
