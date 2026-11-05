<?php

namespace App\Http\Controllers;

use App\Services\QuarkusClient;

class PersonaController extends Controller
{
    public function index(QuarkusClient $quarkus)
    {
        $personas = $quarkus->get('/api/v1/personas');
        return view('personas.index', compact('personas'));
    }
}