<?php

namespace App\Http\Controllers;

use App\Services\QuarkusClient;

class RolController extends Controller
{
    public function index(QuarkusClient $quarkus)
    {
        $roles = $quarkus->get('/api/v1/roles');
        return view('roles.index', compact('roles'));
    }
}