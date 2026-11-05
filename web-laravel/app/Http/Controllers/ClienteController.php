<?php

namespace App\Http\Controllers;

use App\Services\QuarkusClient;

class ClienteController extends Controller
{
    public function index(QuarkusClient $quarkus)
    {
        $clientes = $quarkus->get('/api/v1/clientes');
        return view('clientes.index', compact('clientes'));
    }
}