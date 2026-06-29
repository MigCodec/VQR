<?php

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
