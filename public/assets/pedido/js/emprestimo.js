/*JS EMPRESTIMO*/




        var step = 0;


        $(document).on('click','.step_01', function(){

            /**PRIMEIRO FORMULARIO*/

            console.log('Primeiro passo' + APP_URL);


            var nome_solicitante = $("#form-1 input[name=solicitation-name]").val();
            var dtn_solicitante = $("#form-1 input[name=solicitation-birth]").val();
            var nro_documento = $("#form-1 input[name=solicitation-doc]").val();
            var tp_documento = $("#form-1 input[name=tipo-documento]").val();
            var solicitation_emission_id = $("#form-1 input[name=solicitation-emission-id]").val();
            var solicitation_organ = $("#form-1 input[name=solicitation-organ]").val();
            var sexo = $("#form-1 input[name=sexo]").val();
            var estado_civil = $("#form-1 input[name=estado-civil]").val();
            var nacionalidade = $("#form-1 input[name=nacionalidade]").val();
            var naturalidade = $("#form-1 input[name=naturalidade]").val();
            var uf_nascimento = $("#form-1 input[name=uf-nascimento]").val();
            var telefone = $("#form-1 input[name=telefone]").val();
            var celular = $("#form-1 input[name=celular]").val();
            var tel_recado = $("#form-1 input[name=telefone-recado]").val();
            var nome_mae = $("#form-1 input[name=nome-mae]").val();
            var nome_conjuge = $("#form-1 input[name=nome-conjuge]").val();
            var cpf_conjuge = $("#form-1 input[name=cpf-conjuge]").val();
            var nasto_conjuge = $("#form-1 input[name=nascto-conjuge]").val();
            var sexo_conjuge = $("#form-1 input[name=sexo-conjuge]").val();
            var pb_exposta = $("#form-1 input[name=publicamente-exposta]").val();



            axios.post(APP_URL + '/pedido_emprestimo_parte01', {
                firstName: 'Fred',
                lastName: 'Flintstone'
            })
                .then(function (response) {
                    console.log(response);
                })
                .catch(function (error) {
                    console.log(error);
                });
            // uf-nacimento


            // estado_civil



            /*PEGAR DADOS E ENVIAR PARA O BACK-END LARAVEL*/


            // $.ajax({
            //     type: "POST",
            //
            //     url:  'http://ec2-18-212-126-252.compute-1.amazonaws.com/prj_facilita/public/api/simulador',
            //     data: {valorSolicitado: valorSolicitado, qteParcelas: qteParcelas, cpf:cpf, email:email, name:name},
            //     success: function( data, msg ) {
            //
            //         console.log(msg);
            //
            //         $(".plots-value").html('Sua parcela mensal será a partir de  R$ '+ data["teste"]+' ');
            //         $("input[name=simulacao_id]").val(data['lastInserId']);
            //     }
            //     // });
            // });

        });



        // $(document).on('click','.step_01', function(){
        //
        //     if(step != 2){
        //
        //         return false;
        //     }
        //     /**PRIMEIRO FORMULARIO*/
        //
        // });
        //
        //
        // $(document).on('click','.step_01', function(){
        //
        //     /**PRIMEIRO FORMULARIO*/
        //
        // });
        //
        // $(document).on('click','.step_01', function(){
        //
        //     /**PRIMEIRO FORMULARIO*/
        //
        // });