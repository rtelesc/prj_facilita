<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use GuzzleHttp\Client;
use App\DadosBancarios;


class DadosBancariosController extends Controller
{
    //


    public function  InserirDadosBacnarios(Request $request){

        /*CONTA

        NR_PEDIDO
        CPF
        BANCO
        AGENCIA
        DIG_AG
        CONTA
        DIG_CONTA
        TIPO
        CONTA_DESDE
*/

        return $request;

    }
}