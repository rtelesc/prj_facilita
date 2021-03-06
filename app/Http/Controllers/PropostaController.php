<?php

namespace App\Http\Controllers;

use App\DadosBancarios;
use App\Login;
use App\PreCadastro;
use Illuminate\Http\Request;
use Illuminate\Database;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\EmprestimoController;
//use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
//use App\Http\Controllers\EmprestimoController
use App\Http\Controllers\DadosBancariosController;

use Illuminate\Support\Facades\Mail;
use App\File;


class PropostaController extends Controller
{
    //


    /*
     *
     * Fluxo api banco
     *
     * ->
            /api/v1/ep/propostas

       ->

            /api/v1/ep/status
     *
     * Números propostas
     *
     *
     * REALIZANDO_ANALISE_PREVIA; (Demora alguns minutos para retornar)
     *
     *  CHAMAR API V2 PARA ANALISE CADASTRAL
     *
     * ANALISE_CADASTRAL_CONCLUIDA = 055090000040
     *
     *   -> Chamar api especificação financeira
     *      -> PUT METHOD
     *      /api/v1/ep/propostas/{numeroProposta}/especificacaofinanceira/055090000040
     *
     *    Chamar API para retorno da proposta completa e mostrar resumo
     *
     *
     *   -> Chamar api pendência documentos
     *
     *      /api/v1/ep/propostas/pendencias/
     *
     *   -> Chamar view pendências
     *
     *
     *
     * REPROVADA ->retorna view reprovada
     *
     * APROVADA -> 055090000002 (Formalizar Contrato)
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     * Proposta Aprovada
     *
     *  "codFaseAnaliseCredito": "FORMALIZACAO_PROPOSTA",
     *
     *
     *  -> Buscar documentos para formalização da proposta
     *
     * /api/v1/ep/propostas/{numeroProposta}/documentosformalizacao
     *
     *
     * Formaliza a proposta
     *
     * /api/v1/ep/propostas/{numeroProposta}/formalizacao
     *
     *
     * Após completar a proposta vem os contratos
     *
     * */


        public function __construct()
        {

            $get_Access_token = new EmprestimoController();
            $get_Access_token->ConfiguracoesAPI();

            $this->middleware('auth');

        }


        public function InserirProposta($id){


            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();


            $client =   new Client([
                'base_uri' => EmprestimoController::URL_TOKEN_API(),
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
            ]);


            $data = DB::table('cadastro')->where('id',  $id)->first();


            $pontos = array(',','.','-');
            $cpf = str_replace( $pontos,   "",  Auth::user()->cpf);


            $salario = str_replace('.', '', $data->salario);
            $salario = str_replace(',', '.', $salario);

            $retorno01  =  $client->request('POST', EmprestimoController::URL_ENDPOINT(). '/api/v1/ep/propostas',
                [
                    \GuzzleHttp\RequestOptions::JSON => ["nome" => $data->nome_completo,
    //                    "qteParcelas" => [
    //                        $request->qteParcelas,
    //                    ],
                        "cpf" => $cpf,
                        "dataNascimento" =>  date('Y-m-d', strtotime($data->dt_nasc)) ,
                        "naturezaOcupacao" => $data->ocupacao,
                        "genero" => strtoupper($data->sexo),
                        "rendaMensal" => $salario,
                        "uf" => strtoupper($data->uf_nasc)
                    ]
                ]);



//            dd($cpf, $data->dt_nasc, $data->ocupacao, $salario, $data->uf_nasc);

            $arr = json_decode($retorno01->getBody());

            /*tratar retorno $arr*/



            /*inserir em dados bancários*/



            try {
            $dados_bancarios                = new DadosBancarios();
            $dados_bancarios->nr_pedido     = $arr->retorno->numeroProposta;
            $dados_bancarios->nro_proc_bco  = $arr->retorno->identificadorOperacao;
            $dados_bancarios->id_cadastro   = $id;
            $dados_bancarios->save();

            }

            catch(\Exception $e){
                // do task when error
                echo $e->getMessage();   // insert query
            }


            $status_anliase                 = new Login();
            $status_anliase->exists         = true;
            $status_anliase->id             = Auth::user()->id;
            $status_anliase->status_analise = 2;
            $status_anliase->save();


            $data_email['nome'] = $data->nome_completo;


//            Mail::send('emails.pre_analise',  $data, function( $message ) use ($data_email)
//            {
//                $message->from('rtelesc@gmail.com', 'Proposta enviada', $data_email);
//                $message->to(Auth::user()->email);
//            });


//            $data = DB::table('pre_cadastro')->where('email',  Auth::user()->email)->first();
//
//
//            $user = PreCadastro::find($data->id)->toArray();
//
//
//
//            Mail::send('emails.pre_analise', $user, function($message) use ($user) {
//
//                $message->to(Auth::user()->email);
//
//                $message->subject('rtelesc@gmail.com');
//
//            });


            return $arr;
        }




        public function RetornarDocumentoFormalizacao(Request $reuest){
            $data_cadastro = DB::table('cadastro')->where('email',  Auth::user()->email)->first();
            $data_banco = DB::table('dados_bancarios')->where('id_cadastro',  $data_cadastro->id)->first();

            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();

            //        $token = session('token_key');


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v1/ep/propostas/".$data_banco->nr_pedido."/documentosformalizacao",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                //            CURLOPT_POSTFIELDS => "{\n    \"nomeMae\": \"".$data_cadastro->nome_mae."\",\n    \"email\": \"".Auth::user()->email."\",\n    \"estadoCivil\": \"SOLTEIRO\",\n    \"naturalidade\": \"São Paulo\",\n    \"valorPatrimonio\": \"5000\",\n    \"documentosPessoais\": [\n        {\n            \"numeroDocumento\": 125478991,\n            \"tipoDocumento\": \"RG\"\n        }\n    ],\n    \"endereco\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"complemento\": \"10o andar\"\n    },\n    \"enderecoComercial\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"uf\": \"SP\",\n        \"complemento\": \"10o andar\"\n    },\n    \"telefones\": [\n        {\n            \"ddd\": 11,\n            \"numero\": 985478547,\n            \"tipoTelefone\": \"CELULAR\",\n            \"ramal\": 444\n        }\n    ],\n    \"renda\": {\n        \"tipoComprovanteRenda\": \"EXTRATO_FGTS\"\n    }\n}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
                    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            $response = json_decode($response, true);

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename=\”" . $tempfile . “\”;');
            echo file_get_contents("data://application/pdf;base64,". $response['retorno']['listaDocumentosFormalizacao'][0]['arquivo']);
//            echo  ;
//            return  base64_decode($this->Get(self::Body));
        }

        /*Este metódo é chamado toda vez que o usuário acessa o sistemaa para verifiacar o status da proposta*/
        public function StatusPreAnalise($id){

            $data_cadastro = DB::table('cadastro')->where('id',  $id)->first();
            $data_banco = DB::table('dados_bancarios')->where('id_cadastro',  $id)->first();

            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();

    //        $token = session('token_key');


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v1/ep/propostas/status?numerosPropostas=".$data_banco->nr_pedido."",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
    //            CURLOPT_POSTFIELDS => "{\n    \"nomeMae\": \"".$data_cadastro->nome_mae."\",\n    \"email\": \"".Auth::user()->email."\",\n    \"estadoCivil\": \"SOLTEIRO\",\n    \"naturalidade\": \"São Paulo\",\n    \"valorPatrimonio\": \"5000\",\n    \"documentosPessoais\": [\n        {\n            \"numeroDocumento\": 125478991,\n            \"tipoDocumento\": \"RG\"\n        }\n    ],\n    \"endereco\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"complemento\": \"10o andar\"\n    },\n    \"enderecoComercial\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"uf\": \"SP\",\n        \"complemento\": \"10o andar\"\n    },\n    \"telefones\": [\n        {\n            \"ddd\": 11,\n            \"numero\": 985478547,\n            \"tipoTelefone\": \"CELULAR\",\n            \"ramal\": 444\n        }\n    ],\n    \"renda\": {\n        \"tipoComprovanteRenda\": \"EXTRATO_FGTS\"\n    }\n}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);


            if ($err) {
                echo "cURL Error #:" . $err;
            } else {


                $response = json_decode($response, true);


    //        print_r($response);

                return $response['retorno']['listaSituacaoPropostas'][0]['statusProposta'];

            }
        }


        public function AnaliseCadastral(){


            /*CONSULTAR DADOS*/


            $data_cadastro = DB::table('cadastro')->where('email',  Auth::user()->email)->first();
            $data_banco = DB::table('dados_bancarios')->where('id_cadastro',  $data_cadastro->id)->first();


            /*REALIZAR ANALISE CADASTRAL*/

            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();

    //        $token = session('token_key');

            $val_patrimonio = str_replace('.', '', $data_cadastro->val_patriominio);
            $val_patrimonio = str_replace(',', '.', $val_patrimonio);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v2/ep/propostas/".$data_banco->nr_pedido."/analisecadastral",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_POSTFIELDS => "{\n    \"nomeMae\": \"".$data_cadastro->nome_mae."\",\n    \"email\": \"".Auth::user()->email."\",\n    \"estadoCivil\": \"".$data_cadastro->estado_civil."\",\n    \"naturalidade\": \"$data_cadastro->nacionalidade\",\n    \"valorPatrimonio\": \"".$val_patrimonio."\",\n    \"documentosPessoais\": [\n        {\n            \"numeroDocumento\": ".$data_cadastro->nr_doc.",\n            \"tipoDocumento\": \"".$data_cadastro->tp_doc."\"\n        }\n    ],\n    \"endereco\": {\n        \"cep\": ".str_replace('-', '', $data_cadastro->cep_res).",\n        \"logradouro\": \"".$data_cadastro->end_res."\",\n        \"numero\": ".$data_cadastro->num_res.",\n        \"bairro\": \"".$data_cadastro->bairro_res."\",\n        \"cidade\": \"".$data_cadastro->cidade_res."\",\n        \"complemento\": \"".$data_cadastro->compl_res."\"\n    },\n    \"enderecoComercial\": {\n        \"cep\": ".str_replace('-', '', $data_cadastro->end_comercial_cep).",\n        \"logradouro\": \"".$data_cadastro->end_comercial."\",\n        \"numero\": ".$data_cadastro->end_comercial_nro.",\n        \"bairro\": \"".$data_cadastro->bairro_comerc."\",\n        \"cidade\": \"".$data_cadastro->cidade_comerc."\",\n        \"uf\": \"".strtoupper($data_cadastro->uf_comerc)."\",\n        \"complemento\": \"".$data_cadastro->compl_comerc."\"\n    },\n    \"telefones\": [\n        {\n            \"ddd\": 11,\n            \"numero\": 985478547,\n            \"tipoTelefone\": \"CELULAR\",\n            \"ramal\": 444\n        }\n    ],\n    \"renda\": {\n        \"tipoComprovanteRenda\": \"EXTRATO_FGTS\"\n    }\n}",                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {




                return response($response, 200)
                    ->header('Content-Type', 'text/plain');
    //            echo $response;



            }
            /*CRIAR VÁRIAVEL NO SISTEMA PARA DEFINIR O ACESSO DIRETO PARA A PÁGINA DE STATUS*/

            /*ENNVIAR EMAIL PARA CLIENTE INFORMANDO QUE ESTA EM ANÁLISE*/
        }


        /*Metódo responsável por controlar  o status das propostas, ele vai definir para qual fase o usuário vai ser direcionado*/
        public function  ConsultarStatusProposta(){

            /*Consultar api e direcionar para metódo*/


//            return 'teste';
            $data_cadastro = DB::table('cadastro')->where('email',  Auth::user()->email)->first();

//            return $data_cadastro->id;
            $data_banco = DB::table('dados_bancarios')->where('id_cadastro',  $data_cadastro->id)->first();

            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();

            //        $token = session('token_key');


//            return $data_banco->nr_pedido;


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v1/ep/propostas/status?numerosPropostas=".$data_banco->nr_pedido."",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                //            CURLOPT_POSTFIELDS => "{\n    \"nomeMae\": \"".$data_cadastro->nome_mae."\",\n    \"email\": \"".Auth::user()->email."\",\n    \"estadoCivil\": \"SOLTEIRO\",\n    \"naturalidade\": \"São Paulo\",\n    \"valorPatrimonio\": \"5000\",\n    \"documentosPessoais\": [\n        {\n            \"numeroDocumento\": 125478991,\n            \"tipoDocumento\": \"RG\"\n        }\n    ],\n    \"endereco\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"complemento\": \"10o andar\"\n    },\n    \"enderecoComercial\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"uf\": \"SP\",\n        \"complemento\": \"10o andar\"\n    },\n    \"telefones\": [\n        {\n            \"ddd\": 11,\n            \"numero\": 985478547,\n            \"tipoTelefone\": \"CELULAR\",\n            \"ramal\": 444\n        }\n    ],\n    \"renda\": {\n        \"tipoComprovanteRenda\": \"EXTRATO_FGTS\"\n    }\n}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
                    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);


            if ($err) {
                echo "cURL Error #:" . $err;
            } else {


                $response = json_decode($response, true);




                //        print_r($response);

                $retorno =  $response['retorno']['listaSituacaoPropostas'][0]['statusProposta'];


//                return $retorno;

                if($retorno == "REALIZANDO_ANALISE_PREVIA"){

                   return  $this->REALIZANDO_ANALISE_PREVIA();
                }

                if($retorno == "ANALISE_PREVIA_CONCLUIDA"){


                   return $this->ANALISE_PREVIA_CONCLUIDA();
                }


                if($retorno == "REALIZANDO_ANALISE_CADASTRAL"){


                   return  $this->REALIZANDO_ANALISE_CADASTRAL();
                }

                if($retorno == "ANALISE_CADASTRAL_CONCLUIDA"){

                   return  $this->ANALISE_CADASTRAL_CONCLUIDA();
                }

                if($retorno == "REPROVADA"){
                    return  $this->REPROVADA();
                }


                if($retorno == "REALIZANDO_ANALISE_DOCUMENTAL"){

                   return $retorno;
                }
                /*

                REALIZANDO_ANALISE_DOCUMENTAL

                ANALISE_DOCUMENTAL_CONCLUIDA

                ->

                */



            }
        }

        /*Metódo para REALIZANDO_ANALISE_PREVIA*/
        public function REALIZANDO_ANALISE_PREVIA(){


            return view(    'emprestimo.status_analise');

        }

        /*Metódo para ANALISE_PREVIA_CONCLUID*/

        public function ANALISE_PREVIA_CONCLUIDA(){

            /*Chamar API Para realizar analise cadastral*/


            $data_erro = "";
            $retorno  =  $this->AnaliseCadastral();
//            $retorno = json_decode($retorno, true);

//            if (array_key_exists("erros", $retorno)){
//
//                $data_erro = ['tipo_erro' => $retorno['erros']['tipo'], 'mensagem_erro' => $retorno['erros']['mensagem']];
//            }



            return view('emprestimo.status_analise',  ['status' => $retorno]);
        }


        /*mETÓDO PARA REALIZANDO_ANALISE_CADASTRAL*/
        public function REALIZANDO_ANALISE_CADASTRAL(){

            return view('emprestimo.status_analise');
        }


        /*Metódo para ANALISE_CADASTRAL_CONCLUIDA*/
        public function ANALISE_CADASTRAL_CONCLUIDA(){



//                return view('emprestimo.status_analise');
//                redirect()->route('/resumo');

//                $data = DB::table('cadastro')->where('email',  Auth::user()->email)->first();

//                return view('emprestimo.resumo');

            // chamar api de especificação financeira e retornar o resumo

            // chamar api de validação dos dados bancários
            // após continuar chamar as pendências e incluir o kitprobatóro
            /*Buscar proposta completa*/


            /*Verificar o status_analise, se for == a 2 continua se for == 3 ele entra para outro metódo*/

            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();


            if(Auth::user()->status_analise == 3){

                return $this->ANALISE_CADASTRAL_CONCLUIDA_STEP_DOCUMENTOS();
            }


            $this->InserirEspecificacaoFinanceira();




            $data = DB::table('dados_bancarios')->where('cpf',  Auth::user()->cpf)->first();

            $data_pre_cadastro = DB::table('pre_cadastro')->where('cpf', Auth::user()->cpf)->first();


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v1/ep/propostas/".$data->nr_pedido."",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
    //            CURLOPT_POSTFIELDS => "{\n    \"nomeMae\": \"".$data_cadastro->nome_mae."\",\n    \"email\": \"".Auth::user()->email."\",\n    \"estadoCivil\": \"SOLTEIRO\",\n    \"naturalidade\": \"São Paulo\",\n    \"valorPatrimonio\": \"5000\",\n    \"documentosPessoais\": [\n        {\n            \"numeroDocumento\": 125478991,\n            \"tipoDocumento\": \"RG\"\n        }\n    ],\n    \"endereco\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"complemento\": \"10o andar\"\n    },\n    \"enderecoComercial\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"uf\": \"SP\",\n        \"complemento\": \"10o andar\"\n    },\n    \"telefones\": [\n        {\n            \"ddd\": 11,\n            \"numero\": 985478547,\n            \"tipoTelefone\": \"CELULAR\",\n            \"ramal\": 444\n        }\n    ],\n    \"renda\": {\n        \"tipoComprovanteRenda\": \"EXTRATO_FGTS\"\n    }\n}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);


            if ($err) {
                echo "cURL Error #:" . $err;
            } else {

                $response = json_decode($response, true);


    //        print_r($response);

//                return $response;


                return view('emprestimo.resumo',
                    [

                        'valorPrincipal' => $response['retorno']['especificacaoFinanceira']['valorPrincipal'],
                        'iof' => $response['retorno']['especificacaoFinanceira']['iof'],
                        'cet'=> $response['retorno']['especificacaoFinanceira']['cet'],
                        'taxaJuros' => $response['retorno']['especificacaoFinanceira']['taxaJuros'],
                        'taxaJurosAno' => $response['retorno']['especificacaoFinanceira']['taxaJurosAno'],
                        'valorFinanciado' => $response['retorno']['especificacaoFinanceira']['valorFinanciado'],
                        'valorParcela' => $response['retorno']['especificacaoFinanceira']['valorParcela'],
                        'dataPrimeiraParcela' => $response['retorno']['especificacaoFinanceira']['dataPrimeiraParcela'],
                        'quantidadeParcelas' => $response['retorno']['especificacaoFinanceira']['quantidadeParcelas'],
                        'valorTC' => $response['retorno']['especificacaoFinanceira']['valorTC'],
                        'motivo_solicitacao' => $data_pre_cadastro->finalidade,
                        'dataSolicitacao' => $response['retorno']['dataStatusProposta']
                    ]
                );

            }

        }


        /*Metódo InserirEspecificacaoFinanceira*/

        public function InserirEspecificacaoFinanceira(){

            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();
            $curl = curl_init();

            $data = DB::table('dados_bancarios')->where('cpf',  Auth::user()->cpf)->first();

            $data_pre_cadastro = DB::table('pre_cadastro')->where('cpf', Auth::user()->cpf)->first();
            $data_simulacao     = DB::table('simulacao')->where('user_id', Auth::user()->id)->first();
            $data_bancarios     = DB::table('dados_bancarios')->where('cpf', Auth::user()->cpf)->first();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v1/ep/propostas/".$data->nr_pedido."/especificacaofinanceira",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "PUT",
                            CURLOPT_POSTFIELDS => "{
  \"dataPrimeiraParcela\": \"$data_simulacao->dataPrimeiraParcela\",
  \"valorPrincipal\": \"$data_simulacao->valorSolicitado\",
  \"quantidadeParcelas\": \"$data_simulacao->qteParcelas\",
  \"dadosBancarios\": {
    \"tipoConta\": \"$data_bancarios->tipo\",
    \"codigoBanco\": \"$data_bancarios->banco\",
    \"numeroAgencia\": \"$data_bancarios->agencia\",
    \"numeroConta\": \"$data_bancarios->conta\",
    \"digitoConta\": \"$data_bancarios->dig_conta\"
  }
}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
                    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);


            if ($err) {
                echo "cURL Error #:" . $err;
            } else {

                return $response;
            }
        }

        /*Metódo para proposta REPROVADA*/

        public function REPROVADA(){


            /*Enviar email*/

            return view('emprestimo.status_reprovada');
        }


        /*Metódo para chamada da api para validação dos dados bancários*/

        public function ValidarDadosBancarios(){


            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v1/ep/validadores/dadosbancarios",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => "{
  \"codigoBanco\": 341,
  \"numeroAgencia\": 4508,
  \"numeroConta\": 29825,
  \"digitoConta\": \"6\",
  \"tipoConta\": \"CONTA_CORRENTE_INDIVIDUAL\"
}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
                    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);


            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
//                echo json_

                $retorno = json_decode($response, true);


                return $retorno['retorno'];
            }
        }


        public function DocumentosPendentes(){

            $data_cadastro                  =       DB::table('cadastro')->where('email',  Auth::user()->email)->first();

            $data_documentos                =       DB::table('documentos')->where('id_cadastro',  $data_cadastro->id)->first();
            $data_bancarios                 =       DB::table('dados_bancarios')->where('id_cadastro',  $data_cadastro->id)->first();



            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg//api/v1/ep/propostas/pendencias?numerosPropostas=".$data_bancarios->nr_pedido."",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
//                CURLOPT_POSTFIELDS => "{
//                  \"arquivo\": \"".$image."\",
//                  \"extensaoArquivo\": \"".strtoupper($request->file('image')->extension())."\",
//                  \"nomeArquivo\": \"".$request->file('image')->getClientOriginalName()."\",
//                  \"tipoDocumento\": \"".$request->tipodoc."\"
//                }",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
                    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            $response = json_decode($response, true);

            $array = (array) $response['retorno']['listaPendenciasPropostas'][0]['listaPendenciasDocumentos'];


//            return $response['retorno']['listaPendenciasPropostas'][0];
            if(empty($array)){

                return '';
            }else{

                return 'collapsed';
            }



        }

        public function INSERIR_DOCUMENTO($request){



            $data_cadastro                  =       DB::table('cadastro')->where('email',  Auth::user()->email)->first();

            $data_documentos                =       DB::table('documentos')->where('id_cadastro',  $data_cadastro->id)->first();
            $data_bancarios                 =       DB::table('dados_bancarios')->where('id_cadastro',  $data_cadastro->id)->first();

            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();

            $image                          =       base64_encode(file_get_contents($request->file('image')));


//            $image = base64_encode(file_get_contents($request->file('image')));


//            return $request->file('image')->extension();
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg//api/v1/ep/propostas/".$data_bancarios->nr_pedido."/documentos",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => "{
  \"arquivo\": \"".$image."\",
  \"extensaoArquivo\": \"".strtoupper($request->file('image')->extension())."\",
  \"nomeArquivo\": \"".$request->file('image')->getClientOriginalName()."\",
  \"tipoDocumento\": \"".$request->tipodoc."\"
}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
                    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));


            /*
             * [ FOTO, RG, CARTEIRA_CONSELHO_ORDEM, PASSAPORTE, CNH, RNE, EXTRATO_BANCARIO, HOLERITE_GRANDE_PORTE, EXTRATO_FGTS, EXTRATO_INSS, COMPROVANTE_SAQUE_INSS, EXTRATO_CONTA_COM_INSS, RECIBO_DECLARACAO_IR, PROLABORE, DECORE_ANUAL, CONTA_LUZ, CONTRATO, KIT_PROBATORIO ]
]*/
            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            $response = json_decode($response, true);
             
             
//             $response;
            $store_image = new File();
            $store_image->exists    = true;
            $store_image->id        = $data_documentos->id;
            $store_image->nr_doc    = $response["retorno"]['idDocumento'];
            $store_image->save();


            /*consultar pendências e enviar para analise cadastral e salvar   "identificadorOperacao": "9c1583b9-ecac-424b-a6c9-30c2f714f810"*/

            return $response;




        }

        /*Metódo para retornar a view de documentos*/

        public function ANALISE_CADASTRAL_CONCLUIDA_STEP_DOCUMENTOS(){


            /*Validar dados bancários*/
//            /**/

            $retorno =   $this->ValidarDadosBancarios();

            $data = DB::table('dados_bancarios')->where('cpf',  Auth::user()->cpf)->first();

            $data_pre_cadastro = DB::table('pre_cadastro')->where('cpf', Auth::user()->cpf)->first();


            $curl = curl_init();

            $simulacao = new EmprestimoController();
            $token = $simulacao->ConfiguracoesAPI();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://c2gvw4lxh9.execute-api.sa-east-1.amazonaws.com/hmg/api/v1/ep/propostas/".$data->nr_pedido."",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                //            CURLOPT_POSTFIELDS => "{\n    \"nomeMae\": \"".$data_cadastro->nome_mae."\",\n    \"email\": \"".Auth::user()->email."\",\n    \"estadoCivil\": \"SOLTEIRO\",\n    \"naturalidade\": \"São Paulo\",\n    \"valorPatrimonio\": \"5000\",\n    \"documentosPessoais\": [\n        {\n            \"numeroDocumento\": 125478991,\n            \"tipoDocumento\": \"RG\"\n        }\n    ],\n    \"endereco\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"complemento\": \"10o andar\"\n    },\n    \"enderecoComercial\": {\n        \"cep\": 11740000,\n        \"logradouro\": \"Rua Butantã\",\n        \"numero\": 123,\n        \"bairro\": \"Pinheiros\",\n        \"cidade\": \"Sao Paulo\",\n        \"uf\": \"SP\",\n        \"complemento\": \"10o andar\"\n    },\n    \"telefones\": [\n        {\n            \"ddd\": 11,\n            \"numero\": 985478547,\n            \"tipoTelefone\": \"CELULAR\",\n            \"ramal\": 444\n        }\n    ],\n    \"renda\": {\n        \"tipoComprovanteRenda\": \"EXTRATO_FGTS\"\n    }\n}",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$token."",
                    "Content-Type: application/json",
                    //                "Postman-Token: 06ec2ce6-7d28-4a29-9f61-957d375a0f04",
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);


            $response = json_decode($response, true);

//            return view('emprestimo.pedido',
//                ['valor_solicitacao'        => $user->valorSolicitado ,
//                    'data_solicitacao'          =>  $user->created_at,
//                    'qtde_parcelas'           =>  $user->qteParcelas,
//                    'finalidade'    =>   $get_finalidade->finalidade,
//                    'simulacao_id' => $user->id,
//                    'data_cadastro' => $data_cadastro
//
//                ]);
            $userId = Auth::id();
            $user = DB::table('simulacao')->where('user_id',  $userId)->orderBy('created_at', 'DESC')->first();

            $get_finalidade = DB::table('pre_cadastro')
                ->where('email', '=',  Auth::user()->email)
////            ->where('cpf', '=',  $request->simulation_cpf)
////                ->orderBy('quantity', 'asc')
                ->first();
            return view('emprestimo.pendencias', ['valor_solicitacao'        => $user->valorSolicitado ,
                        'data_solicitacao'          =>  $user->created_at,
                        'qtde_parcelas'           =>  $user->qteParcelas,
                        'finalidade'    =>   $get_finalidade->finalidade,
                        'simulacao_id' => $user->id,
//                        'data_cadastro' => $data_cadastro,
                        'conta' => $retorno['contaValida'], 'agencia' => $retorno['agenciaValida'], 'bancoValido' => $retorno['bancoValido'], 'valorPrincipal' => $response['retorno']['especificacaoFinanceira']['valorPrincipal'],
                        'iof' => $response['retorno']['especificacaoFinanceira']['iof'],
                        'cet'=> $response['retorno']['especificacaoFinanceira']['cet'],
                        'taxaJuros' => $response['retorno']['especificacaoFinanceira']['taxaJuros'],
                        'taxaJurosAno' => $response['retorno']['especificacaoFinanceira']['taxaJurosAno'],
                        'valorFinanciado' => $response['retorno']['especificacaoFinanceira']['valorFinanciado'],
                        'valorParcela' => $response['retorno']['especificacaoFinanceira']['valorParcela'],
                        'dataPrimeiraParcela' => $response['retorno']['especificacaoFinanceira']['dataPrimeiraParcela'],
                        'quantidadeParcelas' => $response['retorno']['especificacaoFinanceira']['quantidadeParcelas'],
                        'valorTC' => $response['retorno']['especificacaoFinanceira']['valorTC'],
                        'motivo_solicitacao' => $data_pre_cadastro->finalidade,
                        'dataSolicitacao' => $response['retorno']['dataStatusProposta'],
                        "pendencias" => $this->DocumentosPendentes()]);

        }


        public function PENDENCIAS(){


            if(Auth::user()->status_analise == 2){

                $status_anliase = new Login();
                $status_anliase->exists = true;
                $status_anliase->id = Auth::user()->id;
                $status_anliase->status_analise = 3;
                $status_anliase->save();


                return $this->ANALISE_CADASTRAL_CONCLUIDA_STEP_DOCUMENTOS();
            }

            if(Auth::user()->status_analise == 3){

//                $user = PreCadastro::find(Auth::user()->email)->toArray();
//
//
//
//                Mail::send('emails.pendencia_documentos', $user, function($message) use ($user) {
//
//                    $message->to(Auth::user()->email);
//
//                    $message->subject('rtelesc@gmail.com');
//
//                });

                if($this->ConsultarStatusProposta() == "REALIZANDO_ANALISE_DOCUMENTAL"){

                    return view('emprestimo.status_analise');
                }
                return $this->ANALISE_CADASTRAL_CONCLUIDA_STEP_DOCUMENTOS();

            }




        }


}
