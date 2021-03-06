            /*SCRIPT RESPONSÁVEL PELA SIMULAÇÃO DO EMPRESTIMO*/


            'use stritc';

            /*variaveis publicas*/
            var value = 0;
            var qtdParcelas = 0;



            ///validar cpf
            $('#simulation-cpf').mask('000.000.000-00', {reverse: true});

            $("#simulation-other-value").maskMoney({prefix:'R$ ', allowNegative: true, thousands:'.', decimal:',', affixesStay: false});


            /*


             formatar no formato real

            var valor = 16.00;
            var texto = valor.toLocaleString("pt-BR", { style: "currency" , currency:"BRL"});

            console.log(texto);
             Executar*/

            $( "#simulation-other-value" ).change(function(e) {
                // alert( "Handler for .change() called." );
                e.preventDefault();


                if (document.querySelector('input[name="simulation-other-value"]').value) {

                    value = document.querySelector('input[name="simulation-other-value"]').value;

                }else{

                    return false;
                }



                console.log(value);
                value_correct = document.querySelector('input[name="simulation-other-value"]').value.replace(".", "");
                valueCorreto = value_correct.replace(",", ".");



                // console.log()



                /*salva em váriavel global o valor*/
                $("body").data('simulacao', valueCorreto);


                valor_formatado = value.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});

                $(".simulation-box form.pt1").css('display', 'none'); // ocultar formulario 1
                $(".simulation-box h2.pt1").css('display', 'none'); // ocultar h2
                $(".simulation-box form.pt2").css('display', 'block'); // ocultar formulario
                $(".simulation-box p.pt2").css('display', 'block'); // ocultar h2
                $(".simulation-box h2.pt2").css('display', 'block'); // ocultar h2

                $(".simulation-box p.pt2").html('R$ ' + valor_formatado);

                $('.banner__simulation').addClass('simulation-value-selected');
                $('.banner__simulation').removeClass('simulation-value');



            });




            $(document).on('click','.simulation-value', function(e){

                e.preventDefault();



                /*Verificar se houve o click*/

                    if ( ! $("input[name=\"simulation-value\"]:checked").is(':checked') ){

                        return false;
                    }


                    if (document.querySelector('input[name="simulation-value"]:checked').value) {

                        value = document.querySelector('input[name="simulation-value"]:checked').value;
                        // console.log(value);
                    }else{

                        return false;
                    }


                    valueCorreto = document.querySelector('input[name="simulation-value"]:checked').value;

                    $("body").data('simulacao', valueCorreto);


                    valor_formatado = value.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});

                    $(".simulation-box form.pt1").css('display', 'none'); // ocultar formulario 1
                    $(".simulation-box h2.pt1").css('display', 'none'); // ocultar h2
                    $(".simulation-box form.pt2").css('display', 'block'); // ocultar formulario
                    $(".simulation-box p.pt2").css('display', 'block'); // ocultar h2
                    $(".simulation-box h2.pt2").css('display', 'block'); // ocultar h2

                    $(".simulation-box p.pt2").html('R$ ' + valor_formatado);

                    $('.banner__simulation').addClass('simulation-value-selected');
                    $('.banner__simulation').removeClass('simulation-value');







            });



        // console.log(value);
            $(document).on('click','.simulation-item', function(e) {

                e.preventDefault();

                if ( ! $("input[name=\"simulation-plots\"]:checked").is(':checked') ){

                    return false;
                }


                console.log();




                $(".simulation-box form.pt2").css('display','none'); // ocultar formulario
                $(".simulation-box h2.pt2").css('display','none'); // ocultar h2
                $(".simulation-box p.pt2").css('display','none');

                /*Call modal*/


                // chamada da api

                CalcularParcelas();
                $('#exampleModal').modal('toggle');

                $("form.pt3").css('display','block'); // ocultar formulario
                $("p.pt3").css('display','block'); // ocultar h2
                $("h2.pt3").css('display','block'); // ocultar h2


                // var value = $("body").data('simulacao')
                $("p.pt3").html('R$' + formatReal( $("body").data('simulacao') ));


                qtdParcelas = document.querySelector('#pt2 input[name="simulation-plots"]:checked').value;

                $("input[name='simulation-plots2'][value='"+qtdParcelas+"']").prop('checked', true);

            });


            /*FUNÇÃO PAARA FORMATAR O VALOR*/
            function formatReal( int ) {
                var tmp = int+'';
                tmp = tmp.replace(/([0-9]{2})$/g, ",$1");
                if( tmp.length > 6 )
                    tmp = tmp.replace(/([0-9]{3}),([0-9]{2}$)/g, ".$1,$2");
                return tmp;
            }






            // $(document).ready(function(){
            //     $("#simulation-other-value").inputmask('decimal', {
            //         'alias': 'numeric',
            //         'groupSeparator': '',
            //         'autoGroup': true,
            //         'digits': 2,
            //         'radixPoint': ".",
            //         'digitsOptional': false,
            //         'allowMinus': false,
            //         // 'prefix': 'R$ ',
            //         'placeholder': ''
            //     })});




                function CalcularParcelas(){


                var valorSolicitado = $("body").data('simulacao');
                var qteParcelas = document.querySelector('#pt2 input[name="simulation-plots"]:checked').value;


                    $.ajax({
                        type: "POST",

                        url:  APP_URL + '/api/simulador',
                        data: {valorSolicitado: valorSolicitado, qteParcelas: qteParcelas},
                    success: function( data, msg ) {

                        console.log(msg);
                        // <span class="plots-value">*</span>

                        $(".plots-value").html('Sua parcela mensal será a partir de  R$ '+ data["teste"]+' ');

                        // alert(data['lastInserId']);
                        $("input[name=simulacao_id]").val(data['lastInserId']);

                    }
                    // });
                    });

                }




                $('#exampleModal').on('hidden.bs.modal', function () {
                    location.reload();
                });



                function numberToReal(numero) {
                    var numero = numero.toFixed(2).split('.');
                    numero[0] = "R$ " + numero[0].split(/(?=(?:...)*$)/).join('.');
                    return numero.join(',');
                }

