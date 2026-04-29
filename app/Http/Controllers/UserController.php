<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Listar usuarios espejados localmente para selección.
     */
    public function index()
    {
        return response()->json(User::orderBy('name')->get(['id', 'name', 'username', 'avatar', 'puesto']));
    }
}
