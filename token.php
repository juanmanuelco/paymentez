<?php

     

  $transaction_id = "DF-30276";
  $appcode="RAQUETA-EC-SERVER";
  $appkey="GYsPQxmmYhWYcsHNaYRyOWgUyrMcxA";
  $fecha_actual = time();
  $variableTimestamp= (string)($fecha_actual);
    

    $uniq_token_string = $appkey.$variableTimestamp;
    $uniq_token_hash = hash('sha256', $uniq_token_string);
    $auth_token = base64_encode($appcode.';'.$variableTimestamp.';'.$uniq_token_hash);

    $urlrefund = 'https://ccapi-stg.paymentez.com/v2/transaction/refund/';

                      $data = array(
                            
                            'id' => $transaction_id

                            );


                        //url contra la que atacamos
                        $ch = curl_init($urlrefund);

                        //a true, obtendremos una respuesta de la url, en otro caso, 
                        //true si es correcto, false si no lo es
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        //establecemos el verbo http que queremos utilizar para la petición
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

                        //enviamos el array data
                        $payload = json_encode( array( "transaction"=> $data ) );

                        curl_setopt($ch, CURLOPT_POSTFIELDS,($payload));

                        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                          'Content-Type:application/json',
                          'Auth-Token:'.$auth_token));
                        

                        //obtenemos la respuesta
                        $response = curl_exec($ch);

                        //Descodificamos para leer
                        $getresponse = json_decode($response,true);

                        //Asociamos los campos del JSON a variables
                        $status = $getresponse['status'];

                        // Se cierra el recurso CURL
                        curl_close($ch);
                        
                        //echo $auth_token."<br/>";
                        echo $response;



?>