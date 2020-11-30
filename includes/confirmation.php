<?php
	
require_once( '../../../../wp-load.php' );   
	if (isset( $_GET['status'] )){
		$status = $_GET['status'];
		//$orderid= 132;
		//$paymentez = new Paymentezz; 
		//$order = new WC_Order($orderid);
?>
<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../css/styles.css">
		<title> Confirmación Pago</title>
	</head>
	<body>
		<div class="container">
			<?php
				if ($status==1) {

						
				 ?>
					
			
			<div >
				<img src="../imgs/icon-check.png" alt="gift" class="center-block img-circle">
			</div>

			<div class="center-block">
				<p> <spam class="textconfirmation"> Su pago se ha realizado con éxito </spam>  </p>
				<p> Muchas gracias por su compra </p>
				<p> <?php 
					
					echo '<p><a class="btn-tienda" target="_parent" href="'.get_permalink(get_option('woocommerce_shop_page_id')).'">'.__('&larr; Volver a la Tienda', 'woothemes').' </a></p>';
					//$url_actual = "https://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

					//echo "<b>$url_actual</b>";

					//PROCESO COMPLETADO
					// $order->add_order_note( __( 'Pago satisfactorio por medio de Paymentez.', 'paymentez' ) );
   					// $order->payment_complete();
    
   					 // Marca la orden como completa automáticamente
   					 //$paymentez->autocomplete($order);
    				 

				?></p>
			</div>
		
	<?php }	
	else{ 

		?>

			<div >
				<img src="../imgs/icon-error.png" alt="gift" class="center-block img-circle">
			</div>

			<div class="center-block">
				<p> <span class="textconfirmation"> Ocurrió un Error </span></p>
				<p>Ups, parece que algo salió mal y su pago no se pudo realizar.</p>
				<p>Intente nuevamente utilizando otra tarjeta de crédito</p>
				<p>	<?php
					echo '<p><a class="btn-tienda" target="_parent" href="'.get_permalink(get_option('woocommerce_shop_page_id')).'">'.__('&larr; Volver a la Tienda', 'woothemes').' </a></p>';
						// PROCESO FALLIDO
						//wc_add_notice( $responseMessage, 'error');
    					//$order->add_order_note('Error mientras se procesaba el pago, con estado: Payment ' . $responseMessage);
    					//$order->update_status('failed', __('Transaccion fallida. ', 'woothemes'));
    					//echo $responseMessage;
    					
				?> </p>
				
			</div>
		
			<?php } ?>

		</div>		
	</body>


</html>

<?php }
else{ ?>
<html>
	<head>
		
	</head>
	<body>
		
	</body>
</html>

<?php } 
?>