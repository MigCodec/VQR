<?php

use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('vqr:grant-license {user : User ID or email} {--plan=normal : License plan: normal or premium} {--fresh : Start the year from today even if the user already has an active subscription}', function (string $user) {
    $account = User::query()
        ->where('email', $user)
        ->orWhere('id', $user)
        ->first();

    if (! $account) {
        $this->error("No user found for [{$user}].");

        return self::FAILURE;
    }

    $currentSubscription = Subscription::query()
        ->where('user_id', $account->id)
        ->latest('expires_at')
        ->first();

    $startsAt = now();
    $plan = array_key_exists($this->option('plan'), Subscription::PLANS)
        ? $this->option('plan')
        : 'normal';
    $planConfig = Subscription::plan($plan);
    $baseDate = $this->option('fresh') || ! $currentSubscription?->isActive()
        ? $startsAt
        : $currentSubscription->expires_at;

    $subscription = Subscription::updateOrCreate([
        'user_id' => $account->id,
    ], [
        'status' => 'active',
        'plan' => $plan,
        'vehicle_limit' => $planConfig['vehicle_limit'],
        'amount' => $planConfig['amount'],
        'currency' => 'CLP',
        'starts_at' => $startsAt,
        'expires_at' => $baseDate->copy()->addYear(),
        'last_payment_id' => $currentSubscription?->last_payment_id,
    ]);

    $this->info("{$planConfig['label']} license granted to {$account->email} until {$subscription->expires_at->format('Y-m-d')}.");

    return self::SUCCESS;
})->purpose('Grant or renew a one-year VQR license for a user account');

Artisan::command('vqr:grant-admin {email : User email} {--revoke : Remove admin permission}', function (string $email) {
    $account = User::query()
        ->where('email', $email)
        ->first();

    if (! $account) {
        $this->error("No user found for [{$email}].");

        return self::FAILURE;
    }

    $account->forceFill([
        'is_admin' => ! $this->option('revoke'),
    ])->save();

    $status = $account->is_admin ? 'granted' : 'revoked';

    $this->info("Admin permission {$status} for {$account->email}.");

    return self::SUCCESS;
})->purpose('Grant or revoke VQR admin permission for a user by email');

Artisan::command('vqr:install-defaults', function () {
    DocumentType::ensureRequiredTypes();

    $this->info('VQR default document types are ready.');

    return self::SUCCESS;
})->purpose('Install required VQR default data');

Artisan::command('vqr:diagnose-document-url {vehicleToken} {documentToken}', function (string $vehicleToken, string $documentToken) {
    $vehicle = Vehicle::query()
        ->where('public_token', $vehicleToken)
        ->with('activeUsers.activeSubscription')
        ->first();

    $document = VehicleDocument::query()
        ->where('public_token', $documentToken)
        ->first();

    $this->line('Vehicle: '.($vehicle ? "FOUND id={$vehicle->id} status={$vehicle->status}" : 'MISSING'));
    $this->line('Document: '.($document ? "FOUND id={$document->id} vehicle_id={$document->vehicle_id}" : 'MISSING'));

    if (! $vehicle || ! $document) {
        return self::FAILURE;
    }

    $this->line('Relation: '.($document->vehicle_id === $vehicle->id ? 'OK' : 'MISMATCH'));
    $this->line('Subscribed owner: '.($vehicle->activeUsers->contains(fn ($user) => $user->hasActiveSubscription()) ? 'YES' : 'NO'));
    $this->line('file_path: '.($document->file_path ?: 'NULL'));

    $relativePath = ltrim(str_replace('\\', '/', (string) $document->file_path), '/');
    $paths = [
        storage_path('app/private/'.$relativePath),
        storage_path('app/'.$relativePath),
        storage_path('app/public/'.$relativePath),
        public_path('storage/'.$relativePath),
    ];

    foreach ($paths as $path) {
        $this->line((is_file($path) ? 'FOUND ' : 'MISS  ').$path);
    }

    return self::SUCCESS;
})->purpose('Diagnose why a public VQR document URL is not resolving');

Artisan::command('vqr:sync-pending-payments {--limit=50 : Maximum payments to sync in one run}', function (MercadoPagoService $mercadoPago) {
    $limit = max(1, (int) $this->option('limit'));

    $payments = Payment::query()
        ->where('provider', 'mercado_pago')
        ->whereNull('subscription_id')
        ->where(function ($query): void {
            $query->whereNotNull('provider_payment_id')
                ->orWhereNotNull('provider_preference_id');
        })
        ->whereIn('status', ['pending', 'in_process', 'authorized', 'unknown'])
        ->oldest()
        ->limit($limit)
        ->get();

    $activated = 0;

    foreach ($payments as $payment) {
        $synced = $payment->provider_payment_id
            ? $mercadoPago->syncPayment($payment->provider_payment_id)
            : null;

        if (! $synced?->subscription_id && $payment->provider_preference_id) {
            $mercadoPago->syncPreferencePayments($payment->provider_preference_id);
            $synced = $payment->fresh();
        }

        if ($synced?->subscription_id) {
            $activated++;
        }
    }

    $this->info("Synced {$payments->count()} Mercado Pago payment(s). Activated {$activated} license(s).");

    return self::SUCCESS;
})->purpose('Sync pending Mercado Pago payments and activate approved licenses');

Schedule::command('vqr:sync-pending-payments')
    ->everyFiveMinutes()
    ->withoutOverlapping();
