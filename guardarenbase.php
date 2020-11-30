<?php

require_once( '../../../wp-load.php' );    

  /*         function insert_data($status, $comments, $description, $dev_reference, $transaction_id) 
        {
            
            $statusfinal = $status;
            $commentsfinal = $comments;
            $guardar=  $description;
            $dev_reference= $dev_reference;
            $transaction_id = $transaction_id;
          

            global $wpdb; 
           $table_name = 'wp_hhpjmyh1da_paymentez';  
           // $table_name = $wpdb->'wp_paymentez';
            $wpdb->insert($table_name, array(
                                      'id' => $id,
                                      'Status' => $statusfinal,
                                      'Comments' => $commentsfinal,
                                      'description' => $guardar,
                                      'OrdenId' => $dev_reference,
                                      'Transaction_Code' => $transaction_id
                                      ),array(
                                      '%s',
                                      '%s',
                                      '%s',
                                      '%s',
                                      '%s',
                                      '%s') 
              );

          }

       insert_data('status', 'comments','description dos',12345,'transaction_id');   */

          global $woocommerce;      
          $woocommerce->cart->empty_cart();
?>