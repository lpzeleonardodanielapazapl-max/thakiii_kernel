<?php

namespace App\Http\Controllers;

use App\Services\QuarkusClient;

class EmpleadoController extends Controller
{
    public function index(QuarkusClient $quarkus)
    {
        $empleados = $quarkus->get('/api/v1/empleados');
        return view('empleados.index', compact('empleados'));
    }
}