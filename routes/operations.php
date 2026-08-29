<?php

declare(strict_types=1);

use App\Http\Controllers\ReadinessController;
use Illuminate\Support\Facades\Route;

// Cette route reste hors du groupe web : une panne du Redis de session doit produire un 503
// structuré, pas interrompre la sonde dans le middleware avant le contrôle des dépendances.
Route::get('/ready', ReadinessController::class)->name('readiness');
