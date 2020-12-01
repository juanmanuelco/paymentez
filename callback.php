<?php

/*

require_once( '../../../wp-load.php' ); 



$requestBody = file_get_contents('php://input');
$requestBodyJs = json_decode($requestBody,true) ;
//$email = $data["registro"][0]['emailUser']


 $dev_reference        = $requestBodyJs["transaction"]['dev_reference'];
 global $woocommerce;
 $order = new WC_Order( $dev_reference );
 $order->update_status('completed');
 // Reduce stock levels
 $order->reduce_order_stock();
// Paso importantisímo ya que vacia el carrito
 $woocommerce->cart->empty_cart();
*/


require_once('../../../wp-load.php');
//require_once( '../../../wp-blog-header.php' );
//require_once 'paymentez.php';
add_filter('woocommerce_email_order_meta_fields', 'custom_woocommerce_email_order_meta_fields', 10, 3);

//if (isset($_POST["status"]) && isset($_POST["dev_reference"]) && isset($_POST["authorization_code"]) && isset($_POST["transaction_id"]) && isset($_POST["status_detail"]) && isset($_POST["payment_method"]) && isset($_POST["response_code"]) && isset($_POST["response_description"]) )

//{

$requestBody = file_get_contents('php://input');
$requestBodyJs = json_decode($requestBody, true);


$status = $requestBodyJs["transaction"]['status'];
$status_detail = $requestBodyJs["transaction"]['status_detail'];
$transaction_id = $requestBodyJs["transaction"]['id'];
$authorization_code = $requestBodyJs["transaction"]['authorization_code'];
$response_description = $requestBodyJs["transaction"]['order_description'];
$dev_reference = $requestBodyJs["transaction"]['dev_reference'];
$bin = $requestBodyJs["card"]['bin'];
$message = $requestBodyJs["transaction"]['message'];
$montototal = $requestBodyJs["transaction"]['amount'];
$cuota = $requestBodyJs["transaction"]['installments'];

// $buyer_email          = $requestBodyJs["user"]['email'];
// $montototal           =  $requestBodyJs["transaction"]['amount'];
//$taxable_amount       = $_POST["taxable_amount"]; $requestBodyJs["transaction"]['dev_reference'];
// $totalmount = number_format(($montototal),2,'.','');

global $woocommerce;
$order = new WC_Order($dev_reference);

$credito = get_post_meta($order->id, '_billing_customer_dni', true);
update_post_meta($order->id, '_transaction_id', $transaction_id);


if ($credito == 2) {
    $tipo = 'Diferido con Intereses';
} elseif ($credito == 3) {
    $tipo = 'Diferido sin Intereses';
} elseif ($credito == 7) {
    $tipo = 'Diferido con Intereses y Meses de Gracia';
} elseif ($credito == 9) {
    $tipo = 'Diferido sin Intereses y Meses de Gracia';
} else {
    $tipo = 'Corriente';
}

function custom_woocommerce_email_order_meta_fields($fields)
{

    $requestBody = file_get_contents('php://input');
    $requestBodyJs = json_decode($requestBody, true);

    echo '<h3>Datos de Transacción:</h3>';

    $variable = $requestBodyJs["transaction"]['id'];
    $variable1 = $requestBodyJs["transaction"]['authorization_code'];

    echo '<table>
                      <tr>
                        <td> Código de transacción: </td>
                        <td> ' . $variable . ' </td>
                      </tr>
                      <tr>
                        <td>N°. Autorización:  </td>
                        <td> ' . $variable1 . '</td>
                      </tr>

                   </table>

              ';
}


function insert_data($status, $comments, $description, $dev_reference, $transaction_id)
{

    $statusfinal = $status;
    $commentsfinal = $comments;
    $guardar = $description;
    $dev_reference = $dev_reference;
    $transaction_id = $transaction_id;


    global $wpdb;
    $table_name = $wpdb->prefix . 'paymentez';

    $wpdb->insert($table_name, array(
        'id' => $id,
        'Status' => $statusfinal,
        'Comments' => $commentsfinal,
        'description' => $guardar,
        'OrdenId' => $dev_reference,
        'Transaction_Code' => $transaction_id
    ), array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s')
    );

}


function SendMail($authorization_code, $transaction_id, $mensaje, $dev_reference, $buyer_email, $totalmount, $taxable_amount)
{

    global $transaction_id, $authorization_code, $mensaje, $dev_reference;
    $transaction_id = $transaction_id;
    $authorization_code = $authorization_code;
    $mensaje = $mensaje;
    $dev_reference = $dev_reference;
    $buyer_email = $buyer_email;
    $totalmount = $totalmount;


    $to = $buyer_email;
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $subject = 'Se ha efectuado un nuevo Pago';
    $message = '<html>' .
        '<head></head>' .
        '<body>' .
        '<table width="520" align="center" cellpadding="3" cellspacing="0" style="padding: 25px">
                <tr >
                  <td colspan="2" valign="top"  style="font-family: Arial;  color: #333; font-size:17px;  line-height: 1.5em; " ><p><img src="https://image.ibb.co/kizh0b/paymentez_logo_mail.png" width="221" height="70" style="margin-bottom: 15px"></p>
                    
                    <h1 style="font-family: Arial;  color: #5FA000; font-size:22px; font-weight: bold;">¡Gracias por tu pedido con Paymentez! </h1>
                    <p style="line-height: 1.4em;"><strong>Detalles de la transacción:</strong></p>
                    <p style="line-height: 1.4em;">No. de orden: <strong>' . $dev_reference . '</strong><br>
                      Medio de pago: <strong> Paymentez </strong><br>
                      Código de transacción: <strong>' . $transaction_id . '</strong><br>
                      No. Autorización: <strong>' . $authorization_code . '</strong><br>
                      Valor de Compra: <strong> $' . $totalmount . '</strong></p>
                    <p style="line-height: 1.4em;">Si usted tiene alguna duda, siéntase libre en contactarnos en: <a href="http://soporte.paymentez.com" target="_blank" style="color: #5FA000;">http://soporte.paymentez.com</a></p>
                    <p style="line-height: 1.4em; color: #666; font-style: italic"><strong>Equipo Paymentez</strong><br>
                      Teléfono: <a href="tel:+59346021900" style="color: #5FA000;">046021900</a><br>
                      Av. 9 de Octubre #109 y Malecón<br>
                      Edificio Santistevan, Piso 3, Of. 1<br>
                      Guayaquil – Ecuador<br>
                      <a href="http://www.paymentez.com.ec"  style=" color: #5FA000">www.paymentez.com.ec</a></p></td>
                </tr>
              </table>' .
        '</body>' .
        '</html>';

    wp_mail($to, $subject, $message, $headers);


}


switch ($status_detail) {
    case 2:
        {
            $detailPayment = "Paid partially";
            break;
        }
    case 3:
        {
            $detailPayment = "Paid";
            break;
        }
    case 6:
        {
            $detailPayment = "Fraud";
            break;
        }
    case 7:
        {
            $detailPayment = "Refund";
            break;
        }
    case 8:
        {
            $detailPayment = "Chargeback";
            break;
        }
    case 9:
        {
            $detailPayment = "Rejected by carrier";
            break;
        }
    case 10:
        {
            $detailPayment = "System error";
            break;
        }
    case 11:
        {
            $detailPayment = "Paymentez fraud";
            break;
        }
    case 12:
        {
            $detailPayment = "Paymentez blacklist";
            break;
        }
    case 16:
        {
            $detailPayment = "Rejected by our fraud control system";
            break;
        }
    case 19:
        {
            $detailPayment = "Rejected by invalid data";
            break;
        }
    case 20:
        {
            $detailPayment = "Rejected by bank";
            break;
        }
}


//header("HTTP/1.0 200 success");


$statusOrder = $order->get_status();

if ($statusOrder != 'completed' && $statusOrder != 'cancelled' && $statusOrder != 'refunded') {

    $description = "Respuesta Paymentez: Status: " . $status_detail . " | Status_detail: " . $detailPayment . " | Dev_Reference: " . $dev_reference . " | Authorization_Code: " . $authorization_code . " | Response_Description: " . $response_description . " | Transaction_Code: " . $transaction_id;


    if ($status == 'success' || $status_detail == 3) { 
	$comments= "Pago Aprobado";
     insert_data($status, $comments,$description,$dev_reference,$transaction_id);                                      	
	$order->add_order_note( __("Su pago se ha realizo con exito.  ", "pg_woocommerce") . $status .
					   __(" |Bin de la tarjeta:  ", "pg_woocommerce"). $bin .
					   __(" |Cuota usada:  ", "pg_woocommerce"). $cuota .
					   __(" |Monto a pagar:  ", "pg_woocommerce"). $montototal .
					   __(" |Transaction_Code:  ", "pg_woocommerce"). $transaction_id .
					   __(" |Authorization_Code:  ", "pg_woocommerce"). $authorization_code .
					   __(" |Pedido:  ", "pg_woocommerce"). $dev_reference .
					   __(" |Mensaje:  ", "pg_woocommerce"). $message);
			   
        $order->update_status('processing');
        $order->add_meta_data('transaction', $transaction_id);
        $order->reduce_order_stock();
        $woocommerce->cart->empty_cart(); 
        $statusOrder = $order->get_status();
        $order->save();
        if (!headers_sent()) {
            header("HTTP/1.0 200 confirmado");
        }


    } elseif($status == 'failure' || $status_detail == 9){
            $comments= "Pago fallido";
            insert_data($status, $comments,$description,$dev_reference,$transaction_id);
             $order->add_order_note( __("Su pago no se realizo con exito.  ", "pg_woocommerce") . $status .
					   __(" |Bin de la tarjeta:  ", "pg_woocommerce"). $bin .
					   __(" |Cuota usada:  ", "pg_woocommerce"). $cuota .
					   __(" |Monto a pagar:  ", "pg_woocommerce"). $montototal .
					   __(" |Transaction_Code:  ", "pg_woocommerce"). $transaction_id .
					   __(" |Authorization_Code:  ", "pg_woocommerce"). $authorization_code .
					   __(" |Pedido:  ", "pg_woocommerce"). $dev_reference .
					   __(" |Mensaje:  ", "pg_woocommerce"). $message);

              $order->update_status('failed');
              $woocommerce->cart->empty_cart();
              $statusOrder = $order->get_status();
                $order->save();
            if (!headers_sent()) {
                header("HTTP/1.0 204 confirmado");
            }


        } else {


            $comments = "Pedido pendiente";
            insert_data($status, $comments, $description, $dev_reference, $transaction_id);
            if (!headers_sent()) {
                header("HTTP/1.0 204 confirmado");
            }

        }
} else {
	if($statusOrder == 'failed' || $statusOrder == 'cancelled' ){
    if ($status == 'success' || $status_detail == 3) { 
	$comments= "Pago Aprobado";
     insert_data($status, $comments,$description,$dev_reference,$transaction_id);                                      	
	$order->add_order_note( __("Su pago se ha realizo con exito.  ", "pg_woocommerce") . $status .
					   __(" |Bin de la tarjeta:  ", "pg_woocommerce"). $bin .
					   __(" |Cuota usada:  ", "pg_woocommerce"). $cuota .
					   __(" |Monto a pagar:  ", "pg_woocommerce"). $montototal .
					   __(" |Transaction_Code:  ", "pg_woocommerce"). $transaction_id .
					   __(" |Authorization_Code:  ", "pg_woocommerce"). $authorization_code .
					   __(" |Pedido:  ", "pg_woocommerce"). $dev_reference .
					   __(" |Mensaje:  ", "pg_woocommerce"). $message);
			   
        $order->update_status('processing');
        $order->reduce_order_stock();
        $woocommerce->cart->empty_cart(); 
        $statusOrder = $order->get_status();
        $order->save();
    if (!headers_sent()) {
        header("HTTP/1.0 204 confirmado");
    }
	}
	
}
}

//}


//else{

//header("HTTP/1.0 201 error");
//header($_SERVER['SERVER_PROTOCOL'] . ' ERROR PRODUCT', true, 201);
//htt_response_code(201);

//}


?>