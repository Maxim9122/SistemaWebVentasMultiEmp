<?php

use App\Http\Controllers\Webhooks\FacturacionComprobantesWebhookController;
use App\Http\Controllers\Webhooks\FacturacionOnboardingWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/facturacion/onboarding', FacturacionOnboardingWebhookController::class)
    ->name('webhooks.facturacion.onboarding');

Route::post('/webhooks/facturacion/comprobantes', FacturacionComprobantesWebhookController::class)
    ->name('webhooks.facturacion.comprobantes');
