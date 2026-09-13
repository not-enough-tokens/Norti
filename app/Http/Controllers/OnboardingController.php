<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Punto de entrada post-login/registro. Hoy es un placeholder -- el flujo
 * real de onboarding (preguntas para detectar la intención del usuario:
 * aprender, invertir, dar seguimiento a una meta, etc.) se construye
 * después. La arquitectura ya reserva este paso: login/registro siempre
 * redirigen aquí primero en vez de ir directo a /education, para que la
 * lógica de intención se pueda insertar sin tocar el flujo de auth.
 */
class OnboardingController extends Controller
{
    public function index(Request $request): View
    {
        return view('onboarding.index', [
            'user' => $request->user(),
        ]);
    }
}
