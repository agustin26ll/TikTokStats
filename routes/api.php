<?php

use App\Http\Controllers\ImportarPublicacionesController;
use Illuminate\Support\Facades\Route;

Route::post('/publicaciones/importar', [ImportarPublicacionesController::class, 'importar']);