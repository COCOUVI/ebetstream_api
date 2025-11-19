<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RechargeAgent;
use Illuminate\Http\Request;

class RechargeAgentController extends Controller
{
    /**
     * Récupérer tous les agents rechargeurs actifs
     */
    public function index()
    {
        $agents = RechargeAgent::active()->get();

        return response()->json([
            'success' => true,
            'data' => $agents
        ]);
    }
}


