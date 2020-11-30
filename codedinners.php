<?php


    if (isset($_POST['codedinners'])) {

       $transaction_id = $_POST['codedinners']; 
       $iduser = $_POST['iduser']; 
       $codf = $_POST['codf'];      
     
      $appcode=$_POST['appcode'];
      $appkey=$_POST['appkey']; //PRODUCC

      $dev_reference=$_POST['dev_reference'];
    //$appkey="dEw2u0QScVZkNKsfBMZ8Sw23PDQLvX"; //desarrollo
       // $fecha_actual = time();
        $variableTimestamp= (string)(time());
        

        $uniq_token_string = $appkey.$variableTimestamp;
        $uniq_token_hash = hash('sha256', $uniq_token_string);
        $auth_token = base64_encode($appcode.';'.$variableTimestamp.';'.$uniq_token_hash);

   // $urlrefund = 'https://ccapi.paymentez.com/v2/transaction/refund/';
     // $urlrefund ='https://ccapi-stg.paymentez.com/v2/transaction/verify';
		$urlrefund ='https://ccapi.paymentez.com/v2/transaction/verify';

                      
                        $user = array(                          
                                   "id"=>  $iduser
                              );

                      $data = array(
                            
                            'id' => $codf

                            );

                
                        //url contra la que atacamos
                        $ch = curl_init($urlrefund);

                        //a true, obtendremos una respuesta de la url, en otro caso, 
                        //true si es correcto, false si no lo es
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        //establecemos el verbo http que queremos utilizar para la petición
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

                        //enviamos el array data
                        $payload = json_encode( array( "user"=> $user, "transaction"=> $data, "type" => "BY_OTP", "value"=>$transaction_id, "more_info" => true) );

                        curl_setopt($ch, CURLOPT_POSTFIELDS,($payload));

                        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                          'Content-Type:application/json',
                          'Auth-Token:'.$auth_token));
                        

                        //obtenemos la respuesta
                        $response = curl_exec($ch);

                        //Descodificamos para leer
                        $getresponse = json_decode($response,true);
                        //$status= $getresponse['transaction']['status'];
                        //$Status_detail= $getresponse['transaction']['status_detail'];
		                //$Auto_code= $getresponse['transaction']['authorization_code'];
                        //$transaction_id = $getresponse['transaction_id'];
                                           

                        $return_arr = array();

                        $return_arr[] = array("resp" => $getresponse
                                            );

                        // Encoding array in JSON format
                        echo json_encode($return_arr);



                        curl_close($ch);
                        //echo $response;
                        //echo $status ;
                  


                      /**************** FUNCIONES DEL CALLBACK *************/

   
                      
    }




?>
