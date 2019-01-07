<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use GuzzleHttp\Client;
//use \kamermans\OAuth2\GrantType\ClientCredentials;
//use \kamermans\OAuth2\OAuth2Subscriber;

use kamermans\OAuth2\GrantType\ClientCredentials;
use kamermans\OAuth2\OAuth2Middleware;
use GuzzleHttp\HandlerStack;
use kamermans\OAuth2\GrantType\NullGrantType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Emprestimo;


/*
 *
 * http://www.befirstcode.com/2017/03/integrate-swagger-in-laravel-project-l5.html
 * */

class EmprestimoController extends Controller
{

    public static function URL_ENDPOINT(){
        return 'https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/';
    }

    public static function URL_TOKEN_API(){
        return 'https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/oauth2/token';
    }

    public function __construct()
    {
        $this->middleware('auth');

//        $this->ChecarAnalise();
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //

        /*Fazer consulta na api do banco cbss para retornar os dados do contrato e também limites*/
//        $this->ConfiguracoesAPI();

//      else{


        return view('emprestimo.pedido');

//        }
    }


    public function ConfiguracoesAPI(){

        // CHAMADA API FUNCIONANDO
//
//
//        $client = new Client();
//        $response = $client->get('https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v1/ep/dominios/bancos', [
//            'form_params' =>
//                [
////                    'code'          => $code,
//                    'client_id'     => '721c4075-2c6f-41dd-b609-3d7c5aabb7fd',
//                    'client_secret' => 'uvMl9-rRr_Nexx4LuZYsgaRedsS7iLVEl5j2aBqO',
//                    'redirect_uri'  => 'https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg',
//                    'grant_type'    => 'authorization_code'
//                ]
//        ]);
        $uri = $this->URL_TOKEN_API();
        $clientId = '721c4075-2c6f-41dd-b609-3d7c5aabb7fd';
        $secret = 'uvMl9-rRr_Nexx4LuZYsgaRedsS7iLVEl5j2aBqO';

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $uri, [
                'headers' =>
                    [
                        'Accept' => 'application/json',
                        'Accept-Language' => 'en_US',
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                'body' => 'grant_type=client_credentials',

                'auth' => [$clientId, $secret, 'basic']
            ]
        );

        $data = json_decode($response->getBody(), true);

        $token = (string) $data['access_token'];


        session()->put('token_key',  $token);

        return $token;



    }

    public function EmprestimoDadosPessoais(Request $request){

        /*Verificar se cliente já fez alguma proposta e excluir do cadastro e se possuir excluir*/
//        return $request;



        $validator = Validator::make($request->all(), [
            'nome_solicitante' => 'required|max:30',
//            'name' => 'required|string|max:50',
//            'password' => 'required'
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->messages()->first());
            return redirect()->back()->withInput();
        }

        $dados_pessoais = new Emprestimo();
        $dados_pessoais->nome_completo      =   $request->nome_solicitante;
        $dados_pessoais->email              =   Auth::user()->email;
        $dados_pessoais->dt_nasc            =   $request->dtn_solicitante;
        $dados_pessoais->nr_doc             =   $request->nro_documento;
        $dados_pessoais->tp_doc             =   $request->tp_documento;
        $dados_pessoais->emissor            =   $request->solicitation_organ;
        $dados_pessoais->sexo               =   $request->sexo;
        $dados_pessoais->nacionalidade      =   $request->nacionalidade;
        $dados_pessoais->uf_nasc            =   $request->uf_nascimento;
        $dados_pessoais->tel_fixo           =   $request->telefone;
        $dados_pessoais->tel_cel            =   $request->celular;
        $dados_pessoais->nome_conj          =   $request->nome_conjuge;
        $dados_pessoais->cpf_conj           =   $request->cpf_conjuge;
        $dados_pessoais->dt_nasc_conj       =   $request->nasto_conjuge;
        $dados_pessoais->sexo_conj          =   $request->sexo_conjuge;
        $dados_pessoais->pb_exposta         =   $request->pb_exposta;
        $dados_pessoais->nome_mae           =   $request->nome_mae;
        $dados_pessoais->emissor            =   $request->solicitation_organ;
        $dados_pessoais->status_cadastro          =   1; // status do passo a passo cadastro;
        $dados_pessoais->estado_civil       =   $request->estado_civil;



//        $dados_pessoais->emissor            =   '1';
        $dados_pessoais->save();

//        Session::put('dados_emprestimo',['id_cadastro',$dados_pessoais->id]);
        // return Session::get('dados_emprestimo.id_cadastro')

        $request->session()->put('id_cadastro', $dados_pessoais->id);

        return response('Dados inseridos com sucesso'. $request->session()->get('id_cadastro').'', 200)
            ->header('Content-Type', 'text/plain');
        /*inputar no banco de dados*/
//        return $request->nome_solicitanteg;
    }

    public function EmprestimoRenda(Request $request){
        $validator = Validator::make($request->all(), [
            'salario' => 'required|max:30',
//            'name' => 'required|string|max:50',
//            'password' => 'required'
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->messages()->first());
            return redirect()->back()->withInput();
        }


        /*buscar no sistema se existe a falta da segunda parte no cadastro*/


        $data = DB::table('cadastro')->where('email',  Auth::user()->email)->first();

        if($data->status_cadastro == 1){



            $renda_ocupacao = new Emprestimo();

//        $value = $request->session()->get('id_cadastro');
            $renda_ocupacao->exists = true;
            $renda_ocupacao->id = $data->id; //already exists in database.
            $renda_ocupacao->salario = $request->salario;
            $renda_ocupacao->ocupacao = $request->ocupacao;
            $renda_ocupacao->escolaridade = $request->escolaridade;
            $renda_ocupacao->profissao = $request->profissao;
            $renda_ocupacao->cargo = $request->cargo;
            $renda_ocupacao->empresa = $request->empresa;
            $renda_ocupacao->data_admissao = $request->data_admissao;
            $renda_ocupacao->end_comercial = $request->end_comercial;
            $renda_ocupacao->end_comercial_nro = $request->end_com_nro;
            $renda_ocupacao->end_comercial_cep = $request->endereco_comercial_cep;
            $renda_ocupacao->bairro_comerc = $request->endereco_comercial_bairro;
            $renda_ocupacao->cidade_comerc = $request->endereco_comercial_cidade;
            $renda_ocupacao->uf_comerc = $request->endereco_comercial_uf;
            $renda_ocupacao->compl_comerc = $request->complemento_endereco_comercial;
            $renda_ocupacao->tel_comerc = $request->telefone_comercial;
            $renda_ocupacao->ramal = $request->ramal;
            $renda_ocupacao->status_cadastro = 2;

            $renda_ocupacao->save();


            $request->session()->put('id_cadastro', $request->session()->get('id_cadastro'));
            return response('Dados inseridos com sucesso', 200)
                ->header('Content-Type', 'text/plain');



        }






        /* salario: salario,
                ocupacao: ocupacao,
                escolaridade: escolaridade,
                profissao: profissao,
                cargo: cargo,
                empresa: empresa,
                data_admissao: data_admissao,
                end_comercial: end_comercial,
                end_com_nro: end_com_nro,
                endereco_comercial_cep: endereco_comercial_cep,
                endereco_comercial_bairro: endereco_comercial_bairro,
                endereco_comercial_cidade: endereco_comercial_cidade,
                endereco_comercial_uf: endereco_comercial_uf,
                complemento_endereco_comercial: complemento_endereco_comercial,
                telefone_comercial: telefone_comercial,
                ramal: ramal*/
    }

    public function EmprestimoEndereco(Request $request){



        $validator = Validator::make($request->all(), [
            'cep' => 'required|max:30',
//            'name' => 'required|string|max:50',
//            'password' => 'required'
        ]);

        if ($validator->fails()) {
            Session::flash('error', $validator->messages()->first());
            return redirect()->back()->withInput();
        }

        /*
        var cep     =   $("#form-2 input[name=cep]").val();
        var endereco     =   $("#form-2 input[name=endereco]").val();
        var nro     =   $("#form-2 input[name=nro]").val();
        var complemento     =   $("#form-2 input[name=complemento]").val();
        var bairro     =   $("#form-2 input[name=bairro]").val();
        var cidade     =   $("#form-2 input[name=cidade]").val();
        var valor_patrimonio     =   $("#form-2 input[name=valor-patrimonio-name]").val();
        // var cep     =   $("#form-2 input[name=cep]").val();
        var residencia    =   $('#tipo-residencia-id').find(":selected").text();
        var escolaridade    =   $('#uf').find(":selected").text();*/


        $data = DB::table('cadastro')->where('email',  Auth::user()->email)->first();

        if($data->status_cadastro == 2) {
            $endereco = new Emprestimo();
            $endereco->exists = true;
            $endereco->id = $data->id; //already exists in database.
            $endereco->cep_res = $request->cep;
            $endereco->end_res = $request->endereco;
            $endereco->num_res = $request->nro;
            $endereco->compl_res = $request->complemento;
            $endereco->bairro_res = $request->bairro;
            $endereco->cidade_res = $request->cidade;
            $endereco->val_patriominio = $request->valor_patrimonio;
            $endereco->tipo_res = $request->residencia;
            $endereco->uf_res = $request->uf_id;
            $endereco->status_cadastro = 3;
            $endereco->save();

            $teste = new PropostaController();
            $retorno_dados = $teste->InserirProposta($data->id);
            /*CHAMAR CONTROLADOR PROPOSTA*/


            return response('concluido com sucesso', 200)
                ->header('Content-Type', 'text/plain');

        }


    }


    /*Quando o usuário acessa o sistema e entra na página de pedido, é chamado esta função para definir o status da proposta e direcionar o usuário para o local correto*/
    public function PedirEmprestimo(Request $request){

        /*Consultar API*/

        $this->ConfiguracoesAPI();

        /*BUSCAR DADOS DA ULTIMA SIMULAÇÃO*/


        $userId = Auth::id();
        $user = DB::table('simulacao')->where('user_id',  $userId)->orderBy('created_at', 'DESC')->first();


        $data_status_pre_analise = DB::table('cadastro')->where('email',  Auth::user()->email)->first();

        /**/

        if(Auth::user()->status_analise == 2) {



//            return 'teste';

            $call = new PropostaController();
            $call->teste();

        }else{

            $get_finalidade = DB::table('pre_cadastro')
                ->where('email', '=',  Auth::user()->email)
////            ->where('cpf', '=',  $request->simulation_cpf)
////                ->orderBy('quantity', 'asc')
                ->first();

            return view('emprestimo.pedido',
                ['valor_solicitacao'        => $user->valorSolicitado ,
                    'data_solicitacao'          =>  $user->created_at,
                    'qtde_parcelas'           =>  $user->qteParcelas,
                    'finalidade'    =>   $get_finalidade->finalidade,
                    'simulacao_id' => $user->id

                ]);
        }


    }




}
