<?php

namespace App\Http\Controllers;

use App\Services\QuarkusClient;

class UsuarioController extends Controller
{
    public function index(QuarkusClient $quarkus)
    {
        $usuarios = $quarkus->get('/api/v1/usuarios');
        return view('usuarios.index', compact('usuarios'));
    }
}