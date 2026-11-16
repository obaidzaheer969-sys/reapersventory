<?php

namespace App\Http\Controllers\Earning;

use App\Http\Controllers\Controller;
use App\Services\DepositService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CashmaalIpnController extends Controller
{
    public function __invoke(Request $request, DepositService $deposits): Response
    {
        $result = $deposits->handleIpn($request->all());

        return response($result, 200)->header('Content-Type', 'text/plain');
    }
}
