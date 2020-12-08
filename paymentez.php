<?php

/*
Plugin Name: Paymentez 
Plugin URI: http://www.paymentez.com
Description: Agrega Paymentez como método de pago.
Version: 2.0
Author: Paymentez
Author URI: http://www.paymentez.com
License: Apache License 2.0.  License URI: http://www.apache.org/licenses/LICENSE-2.0 */


add_action('plugins_loaded', 'woocommerce_paymentez_gateway', 0);

// Hook - Agrega un campo personalizado al formulario
//add_filter( 'woocommerce_checkout_fields' , 'custom_checkout_fields' );

/* Muestra el valor del campo en la página de edición del pedido*/
//add_action( 'woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order', 10, 1 );

//Agrega campos al mail
//add_filter( 'woocommerce_email_order_meta_fields', 'custom_woocommerce_email_order_meta_fields', 10, 3 );

/* Actualiza la orden-meta con el valor del campo  */
//add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order' );

add_action('add_meta_boxes', 'add_meta_boxes');

add_action('save_post', 'wpse_save_meta_fields');
add_action('new_to_publish', 'wpse_save_meta_fields');


//add_action('wp_enqueue_scripts', 'insertarJQuery');
//add_action('wp_enqueue_scripts', 'testing_jquery');
/*

function insertarJQuery() {
    if ( ! is_admin()) 
        wp_enqueue_script(
            'insert-jquery',
            plugins_url('js/jquery-1.11.1.min.js', __FILE__ ),
            ['jquery'],
            true
            );
}

function testing_jquery() {
    if ( ! is_admin()) 
        wp_enqueue_script(
            'testing-jquery',
            plugins_url('js/jquery.js', __FILE__ ),
            ['jquery'],   
            true
            );
}

*/


function add_meta_boxes()
{
    add_meta_box(
        'woocommerce-order-my-custom',
        __('Reverso Paymentez'),
        'order_my_custom',
        'shop_order',
        'side',
        'default'
    );
}


function order_my_custom()
{
    global $post;
    // Use nonce for verification to secure data sending
    wp_nonce_field(basename(__FILE__), 'wpse_our_nonce');
    ?>
    <!-- my custom value input -->
    <input type="text" name="wpse_value" value="">
    <input type="submit" value="Refund" class="button-primary">
    <a href="https://www.paymentez.com" style=" color: #5FA000" target="_blank">www.paymentez.com</a>
    </br>
    <?php
}


function my_admin_notice()
{
    //print the message
    echo '<div id="message">
              <p>MENSAJE DE ERROR!!!</p>
          </div>';
}


function select_order1($dev_reference)
{

    global $ID;
    $ID = $dev_reference;
    global $wpdb;
    $table_name = $wpdb->prefix . 'paymentez';
    $myrows = $wpdb->get_results("SELECT * FROM $table_name where Transaction_Code = '$ID' ", OBJECT);
    //echo $myrows;
    foreach ($myrows as $campos) {
        # code...
        // $var= $campos->post_title;
        $statusbd = $campos->OrdenId;

    }
    return $statusbd;
}


function insert_data1($status, $comments, $description, $dev_reference, $transaction_id)
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


function update_table1($dev_reference, $estado)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'posts';
    $ID = $dev_reference;
    $estado = $estado;
    $fecha_actual = date('Y-m-d H:i:s');
    $wpdb->update($table_name, array('post_status' => $estado, 'post_modified' => $fecha_actual, 'comment_status' => 'closed'), array('ID' => $ID));
}


function wpse_save_meta_fields($post_id)
{
    // verify nonce
    if (!isset($_POST['wpse_our_nonce']))
        return 'nonce not verified';

    global $transaction_id;
    $transaction_id = $_POST['wpse_value'];
    //$transaction_id=$_POST["transcode"];

    $clase = new Paymentezz();
    $code_server = $clase->code();
    $key_server = $clase->key();
    $appcode = $code_server;
    $appkey = $key_server;

    // $plaintext ='application_code='.$appcode.'&transaction_id='.$transaction_id.'&'.$variableTimestamp.'&'.$appkey;
    // $auth_token= hash('sha256', $plaintext);
    $environment = $clase->get_refund();
    $environment_url = ("TRUE" == $environment)
        ? 'https://ccapi-stg.paymentez.com/v2/transaction/refund/'
        : 'https://ccapi.paymentez.com/v2/transaction/refund/';

    $url = $environment_url . 'application_code=' . $appcode . '&transaction_id=' . $transaction_id . '&auth_timestamp=' . $variableTimestamp . '&auth_token=' . $auth_token;

    /************************* NUEVO REFUND - API_REST *********************************/

    $fecha_actual = time();
    $variableTimestamp = (string)($fecha_actual);

    $uniq_token_string = $appkey . $variableTimestamp;
    $uniq_token_hash = hash('sha256', $uniq_token_string);
    $auth_token = base64_encode($appcode . ';' . $variableTimestamp . ';' . $uniq_token_hash);

    $urlrefund = 'https://ccapi-stg.paymentez.com/v2/transaction/refund/';

    $data = array(
        'id' => $transaction_id
    );


    //url contra la que atacamos
    $ch = curl_init($environment_url);

    //a true, obtendremos una respuesta de la url, en otro caso,
    //true si es correcto, false si no lo es
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //establecemos el verbo http que queremos utilizar para la petición
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

    //enviamos el array data
    $payload = json_encode(array("transaction" => $data));

    curl_setopt($ch, CURLOPT_POSTFIELDS, ($payload));

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Auth-Token:' . $auth_token));

    //obtenemos la respuesta
    $response = curl_exec($ch);

    //Descodificamos para leer
    $getresponse = json_decode($response, true);

    //Asociamos los campos del JSON a variables
    $status = $getresponse['status'];

    // Se cierra el recurso CURL y se liberan los recursos del sistema
    curl_close($ch);

    /**********************************************************/

    $trancode = select_order1($transaction_id);
    $order = new WC_Order($trancode);
    $statusorder = $order->get_status();


    if ($status === 'success') {
        if (!is_null($trancode)) {
            if ($statusorder == 'completed') {
                insert_data1('1', 'Reverso', 'Reverso realizado correctamente', $trancode, $transaction_id);
                //$order->update_status('refunded');
                $order->add_order_note('Su pago se ha reversado Satisfactoriamente. Código Transacción: ' . $transaction_id);
                update_table1($trancode, 'wc-refunded');
                remove_action('save_post', 'wpse_save_meta_fields');
                remove_action('new_to_publish', 'wpse_save_meta_fields');
                $order->set_status('refunded');
                $order->save();
            } else {
                if ($statusorder == 'refunded') {
                    insert_data1('1', 'Error Reverso', 'El pago ha sido reversado previamente', $trancode, $transaction_id);
                } else {
                    insert_data1('1', 'Error Reverso', 'El pago no ha sido confirmado', $trancode, $transaction_id);
                }
            }
        } else {
            insert_data1('1', 'Error Reverso', 'El número de autorización es incorrecto, La Orden no existe', $trancode, $transaction_id);
            //echo "ERROR: EL # DE AUTORIZACION ES INCORRECTO, LA ORDEN NO EXISTE ";
        }
    } else {
        if ($status === 'Error') {
            //insert_data('1', 'Error-Refunded', 'Error al reversar', $trancode, $transaction_id);
            insert_data1('1', 'Error Reverso', 'Error al Reversar', $trancode, $transaction_id);
        } elseif ($status == 'failure') {
            insert_data1('1', 'Error Reverso', 'El pago ya ha sido reversado previamente', $trancode, $transaction_id);
            //update_table($trancode,'wc-refunded');
        }
    }
}


if (!function_exists('bdtable_paymentez')) {
    //crear la tabla para registro del callback
    function bdtable_paymentez()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'paymentez';

        if ($wpdb->get_var('SHOW TABLES LIKES ' . $table_name) != $table_name) {
            $sql = 'CREATE TABLE ' . $table_name . ' (
                   id integer(9) unsigned NOT NULL AUTO_INCREMENT,
                   Status varchar(50) NOT NULL,
                   Comments varchar(50) NOT NULL,
                   description text(500) NOT NULL,
                   OrdenId int(9) NOT NULL,
                   Transaction_Code varchar(50) NOT NULL,
                   PRIMARY KEY  (id)
                   );';
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}

register_activation_hook(__FILE__, 'bdtable_paymentez');


if (!function_exists('custom_checkout_fields')) {
// Our hooked in function - $fields is passed via the filter
    function custom_checkout_fields($fields)
    {

        $clase = new Paymentezz();

        $cred_rot = $clase->cred_rot();
        $dif_con = $clase->cred_dif_con();
        $dif_sin = $clase->cred_dif_sin();

        if ($cred_rot == "yes") {
        }
        if ($dif_con == "yes") {
            $fields['billing']['billing_customer_dni'] = array(
                'type' => 'select',
                'label' => __('Tipo de Crédito', 'woocommerce'),
                'clear' => true,
                'options' => array(
                    '2' => __('Diferido con Intereses', 'woocommerce')
                ),
                'placeholder' => _x('Tipo de Crédito', 'placeholder', 'woocommerce'),
                'required' => true
            );
        }
        if ($dif_sin == "yes") {
            $fields['billing']['billing_customer_dni'] = array(
                'type' => 'select',
                'label' => __('Tipo de Crédito', 'woocommerce'),
                'clear' => true,
                'options' => array(
                    '3' => __('Diferido sin Intereses', 'woocommerce')
                ),
                'placeholder' => _x('Tipo de Crédito', 'placeholder', 'woocommerce'),
                'required' => true
            );

        }
        if ($dif_con == "yes" && $cred_rot == "yes") {
            $fields['billing']['billing_customer_dni'] = array(
                'type' => 'select',
                'label' => __('Tipo de Crédito', 'woocommerce'),
                'clear' => true,
                'options' => array(
                    '0' => __('Corriente', 'woocommerce'),
                    '2' => __('Diferido con Intereses', 'woocommerce')
                ),
                'placeholder' => _x('Tipo de Crédito', 'placeholder', 'woocommerce'),
                'required' => true
            );
        }

        if ($dif_sin == "yes" && $cred_rot == "yes") {
            $fields['billing']['billing_customer_dni'] = array(
                'type' => 'select',
                'label' => __('Tipo de Crédito', 'woocommerce'),
                'clear' => true,
                'options' => array(
                    '0' => __('Corriente', 'woocommerce'),
                    '3' => __('Diferido sin Intereses', 'woocommerce')
                ),
                'placeholder' => _x('Tipo de Crédito', 'placeholder', 'woocommerce'),
                'required' => true
            );
        }
        if ($dif_con == "yes" && $dif_sin == "yes") {

            $fields['billing']['billing_customer_dni'] = array(
                'type' => 'select',
                'label' => __('Tipo de Crédito', 'woocommerce'),
                'clear' => true,
                'options' => array(
                    '2' => __('Diferido con Intereses', 'woocommerce'),
                    '3' => __('Diferido sin Intereses', 'woocommerce')
                ),
                'placeholder' => _x('Tipo de Crédito', 'placeholder', 'woocommerce'),
                'required' => true
            );
        }
        if ($dif_sin == "yes" && $cred_rot == "yes" && $dif_con == "yes") {

            $fields['billing']['billing_customer_dni'] = array(
                'type' => 'select',
                'label' => __('Tipo de Crédito', 'woocommerce'),
                'clear' => true,
                'options' => array(
                    '0' => __('Corriente', 'woocommerce'),
                    '2' => __('Diferido con Intereses', 'woocommerce'),
                    '3' => __('Diferido sin Intereses', 'woocommerce')
                ),
                'placeholder' => _x('Tipo de Crédito', 'placeholder', 'woocommerce'),
                'required' => true
            );
        }
        return $fields;
    }
}


if (!function_exists('my_custom_checkout_field_display_admin_order')) {
    function my_custom_checkout_field_display_admin_order($order)
    {
        $var = get_post_meta($order->id, '_billing_customer_dni', true);
        if ($var == 0) {
            $tipo = 'Corriente';
        }
        if ($var == 2) {
            $tipo = 'Diferido con Intereses';
        }
        if ($var == 3) {
            $tipo = 'Diferido sin Intereses';
        }
        echo '<p><strong>' . __('Tipo de Crédito') . ':</strong> ' . $tipo . '</p>';
    }
}


if (!function_exists('my_custom_checkout_field_update_order')) {
    function my_custom_checkout_field_update_order($order_id)
    {
        if (!empty($_POST['customer_dni'])) {
            update_post_meta($order_id, '_customer_dni', sanitize_text_field($_POST['customer_dni']));
        }
    }
}


if (!function_exists('woocommerce_paymentez_gateway')) {
    function woocommerce_paymentez_gateway()
    {
        // Verifica que woocomerce este instalado
        if (!class_exists('WC_Payment_Gateway')) return;

        // Clase Paymentez que hereda funcionalidad de Woocommerce
        class Paymentezz extends WC_Payment_Gateway
        {
            // Configuracion del botón de pago
            public function __construct()
            {
                // ID global para este metodo de pago
                $this->id = 'paymentez';

                // Icono que será mostrado al momento de escoger medio de pagos
                $this->icon = apply_filters('woocomerce_paymentez_icon', plugins_url('/imgs/paymentezcheck.png', __FILE__));

                // Bool. Puede ser configurada con true si se esta haciendo una integración directa.
                // este no es nuestro caso, ya el proceso se terminará por medio de un tercero $this->has_fields     = false;
                $this->method_title = 'Paymentez';
                $this->method_description = 'Integración de Woocommerce con Paymentez';

                // Define la configuracion que luego serán cargadas con init_settings()
                $this->init_settings();
                $this->init_form_fields();

                // Luego que init_settings() es llamado, es posible guardar la configuracion en variables
                // e.j: $this->get_option( 'title' );
                //$this->init_settings();

                // Convertimos settings en variables que podemos utilizar
                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');
                $this->currency = $this->get_option('currency');

                $this->app_code = $this->get_option('app_code');
                $this->environment_url = '';
                $this->app_key = $this->get_option('app_key');
                $this->test = $this->get_option('test');
                $this->autocomplete = $this->get_option('autocomplete');

                // Only Ecuador, when currency == 'USD'
                $this->impuesto_pay = $this->get_option('impuesto_pay');
                $this->cred_rot = $this->get_option('credito');
                $this->dif_con = $this->get_option('dif_con');
                $this->dif_sin = $this->get_option('dif_sin');
                $this->gracia_con = $this->get_option('gracia_con');
                $this->gracia_sin = $this->get_option('gracia_sin');

                // Installments when not is USD
                $this->installments = $this->get_option('installments');
                $this->webhook_paymentez = $this->get_option('webhook_paymentez');

                //SERVER
                $this->app_key_server = $this->get_option('app_key_server');
                $this->app_code_server = $this->get_option('app_code_server');
                //$this->supports = array('products', 'refunds');

                $this->beta_functions = $this->get_option('beta_functions');
                $this->exclusive_types = $this->get_option('exclusive_types');
                $this->invalid_card_type_message = $this->get_option('invalid_card_type_message');

                // Guarda las opciones administrativas de acuerdo a la version de WC
                // 'process_admin_options' es un metodo de WC
                if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
                } else {
                    add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
                }

                add_action('woocommerce_receipt_paymentez', array(&$this, 'receipt_page'));
            }


            // Formulario de configuración Paymentez WebCheckout
            function init_form_fields()
            {
                $basics = array(
                    'enabled' => array(
                        'title' => __('Habilitar/Deshabilitar', 'paymentez'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar método de pago Paymentez', 'paymentez'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __('Título', 'paymentez'),
                        'type' => 'text',
                        'description' => __('Título que el usuario verá durante checkout.', 'paymentez'),
                        'default' => __('Paymentez', 'paymentez')
                    ),
                    'description' => array(
                        'title' => __('Mensaje Personalizado', 'paymentez'),
                        'type' => 'textarea',
                        'css' => 'width:500px;',
                        'default' => 'Paymentez es la solución completa para pagos en línea. Segura, fácil y rápida.',
                        'description' => __('Descripción que aparecerá en las opciones del CheckOut', 'paymentez')
                    ),
                    'currency' => array(
                        'title' => __('Moneda', 'paymentez'),
                        'type' => 'select',
                        'default' => 'COP',
                        'options' => array(
                            'COP' => 'COP',
                            'USD' => 'USD',
                            'MXN' => 'MXN',
                            'BRL' => 'BRL',
                            'CLP' => 'CLP',
                            'ARS' => 'ARS',
                            'VEF' => 'VEF',
                        ),
                        'description' => __('Moneda con la que transacciona con Paymentez. <br/>
                        Debe coincidir con la moneda configurada a sus credenciales. <br/>
                        Guarda cambios para USD y mostrar tipos de créditos', 'paymentez')
                    ),
                    'app_code' => array(
                        'title' => __('App Code Client', 'paymentez'),
                        'type' => 'text',
                        'description' => __('Identificador único en Paymentez.', 'paymentez')
                    ),
                    'app_key' => array(
                        'title' => __('App Key Client', 'paymentez'),
                        'type' => 'text',
                        'description' => __('Llave que sirve para encriptar la comunicación con Paymentez.', 'paymentez')
                    ),
                    'app_code_server' => array(
                        'title' => __('App Code Server', 'paymentez'),
                        'type' => 'text',
                        'description' => __('Identificador único en Paymentez Server.', 'paymentez')
                    ),
                    'app_key_server' => array(
                        'title' => __('App Key Server', 'paymentez'),
                        'type' => 'text',
                        'description' => __('Llave que sirve para la comunicación de reverso con Paymentez Server.', 'paymentez')
                    ),
                    'webhook_paymentez' => array(
                        'title' => __('Webhook para transacciones.', 'paymentez'),
                        'type' => 'text',
                        'description' => __('Ingresa la URL de tu webhook ya configurado.', 'paymentez')
                    ),
                    'test' => array(
                        'title' => __('Transacciones en modo de prueba', 'paymentez'),
                        'type' => 'checkbox',
                        'label' => __('Habilita las transacciones en modo de prueba.', 'paymentez'),
                        'default' => 'no',
                        'description' => 'Inhabilita para pasar al ambiente productivo.'
                    ),
                    'beta_functions' => array(
                        'title' => __('Habilitar funcionalidad beta', 'paymentez'),
                        'type' => 'checkbox',
                        'label' => __('Habilitar funcionalidad beta.', 'paymentez'),
                        'default' => 'no',
                        'description' => __('Guarda cambios para mostrar / ocultar funciones en beta.', 'paymentez')
                    )
                );

                $installments = [];
                if ($this->get_option('currency') == 'USD') {
                    $installments = array(
                        'impuesto_pay' => array(
                            'title' => __('IVA %', 'paymentez'),
                            'type' => 'number',
                            'default' => 0,
                            'description' => __('Impuesto que utiliza en su comercio. Solo ingrese su valor entero, Ej: 12 ', 'paymentez')
                        ),
                        'credito' => array(
                            'title' => __('Tipo de Crédito a Utilizar', 'paymentez'),
                            'type' => 'checkbox',
                            'label' => __('Corriente', 'paymentez'),
                            'default' => 'yes',
                        ),
                        'dif_sin' => array(
                            'type' => 'checkbox',
                            'label' => __('Diferido sin Intereses', 'paymentez'),
                            'default' => 'no'
                        ),
                        'dif_con' => array(
                            'type' => 'checkbox',
                            'label' => __('Diferido con Intereses', 'paymentez'),
                            'default' => 'no'
                        ),
                        'gracia_sin' => array(
                        'type' => 'checkbox',
                        'label' => __('Diferido sin Intereses y Meses de Gracia', 'paymentez'),
                        'default' => 'no'
                    ),
                        'gracia_con' => array(
                        'type' => 'checkbox',
                        'label' => __('Diferido con Intereses y Meses de Gracia', 'paymentez'),
                        'default' => 'no'
                    )
                    );
                } elseif ($this->get_option('currency') != 'MXN') {
                    $installments = array(
                        'installments' => array(
                            'title' => __('Uso de cuotas', 'paymentez'),
                            'type' => 'checkbox',
                            'default' => 'no',
                            'description' => __('Activar para mostrar las cuotas definidas con Paymentez en el checkout de pago.', 'paymentez')
                        )
                    );
                }
                $beta_functions = [];
                if ($this->get_option('beta_functions') == 'yes') {
                    $beta_functions = array(
                        'exclusive_types' => array(
                            'title' => __('Tipos de tarjetas permitidas', 'paymentez'),
                            'type' => 'text',
                            'default' => 0,
                            'description' => sprintf(__('Defina los tipos de tarjetas permitidos. <br/>
                                                        Dejar en vacio para inhabilitar. <br/>
                                                        <a href="%s" target="_blank">Tipos de tarjetas permitidas
                                                         por Paymentez</a>.', 'text_domain'), 'https://paymentez.github.io/api-doc/#card-brands')
                        ),
                        'invalid_card_type_message' => array(
                            'title' => __('Mensaje de alerta para tipos de tarjeta invalida.', 'paymentez'),
                            'type' => 'text',
                            'label' => __('Corriente', 'paymentez'),
                            'description' => __('Defina el mensaje de error para los tipos de tarjeta no permitidos. <br/>
                                                 Dejar en vacio para inhabilitar.', 'paymentez')
                        )
                    );
                }
                $this->form_fields = array_merge($basics, $installments, $beta_functions);
            }

            // Crea el formulario de administrador
            public function admin_options()
            {
                ?>
                <p>
                    <img style='width: 30%;position: relative;display: inherit;'
                         src='<?php echo plugins_url('/imgs/paymentez.png', __FILE__); ?>'>
                </p>
                <?php
                echo '<h3>' . __('Solución completa de pagos en línea para tu negocio', 'paymentez') . '</h3>';
                ?>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <?php
                        echo '<div id="post-body-content">';
                        echo '<table class="form-table">';
                        $this->generate_settings_html();
                        echo '</table>';
                        echo '</div>';
                        ?>
                        <div id="postbox-container-1" class="postbox-container">
                            <div id="side-sortables" class="meta-box-sortables ui-sortable">
                                <div class="postbox ">
                                    <div class="handlediv" title="Click to toggle"><br></div>
                                    <h3 class="hndle"><span><i class="dashicons dashicons-format-chat"></i> - Soporte Paymentez</span>
                                    </h3>
                                    <div class="inside">
                                        <div class="support-widget">
                                            <p>
                                                <img style="width: 70%;margin: 0 auto;position: relative;display: inherit;"
                                                     src='<?php echo plugins_url('/imgs/paymentez.png', __FILE__); ?>'>
                                                <br/>
                                                ¿Tienes una pregunta, idea, problema ?</p>
                                            <ul>
                                                <li>» <a href="https://paymentez.com" target="_blank">Solicitud de
                                                        Soporte</a></li>
                                                <li>» <a href="https://paymentez.com" target="_blank">Documentación
                                                        y
                                                        problemas comunes.</a></li>
                                                <li>» <a href="https://paymentez.com" target="_blank">Nuestro Sitio
                                                        Web</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clear"></div>
                <style type="text/css">
                    .wpruby_button {
                        background-color: #4CAF50 !important;
                        border-color: #4CAF50 !important;
                        color: #ffffff !important;
                        width: 100%;
                        padding: 5px !important;
                        text-align: center;
                        height: 35px !important;
                        font-size: 12pt !important;
                    }
                </style>
                <?php
            }


            function code()
            {
                $appcodepasar = $this->app_code_server;
                return $appcodepasar;
            }

            function key()
            {
                $appkeypasar = $this->app_key_server;
                return $appkeypasar;
            }


            function credi_rot()
            {
                if ($this->currency == 'USD' && $this->cred_rot == "yes") {
                    $cred_rot = '<div class="BtnV Rot">Pago Corriente<br>
                                <span class="txtB">Todas las tarjetas VISA y MASTERCARD</span>
                                </div>';
                } else {
                    $cred_rot = '<div class=" Rot"></div>';
                }
                return $cred_rot;
            }

            function cred_dif_con()
            {
                if ($this->currency == 'USD' && $this->dif_con == "yes") {
                    $dif_con = '<div class="BtnV DifCon">Pago Diferido <br>
                                <span class="txtB">Con Intereses </span>
                                </div>';
                } else {
                    $dif_con = '<div class="DifCon"></div>';
                }
                return $dif_con;
            }

            public function cred_dif_sin()
            {
                if ($this->currency == 'USD' && $this->dif_sin == "yes") {
                    $dif_sin = '<div class="BtnV DifSin">Pago Diferido <br>
                                <span class="txtB">Sin Intereses</span>
                                </div>';
                } else {
                    $dif_sin = '<div class="DifSin"></div>';
                }
                return $dif_sin;
            }

            function gracia_con_int()
            {
                if ($this->currency == 'USD' && $this->gracia_con == "yes") {
                    $gracia_con = '<div class="BtnV GraciaCon">Pago Diferido <br>
                                <span class="txtB">Con Intereses Y Meses de Gracia</span>
                                </div>';
                } else {
                    $gracia_con = '<div class="GraciaCon"></div>';
                }
                return $gracia_con;
            }

            public function gracia_sin_int()
            {
                if ($this->currency == 'USD' && $this->gracia_sin == "yes") {
                    $gracia_sin = '<div class="BtnV GraciaSin">Pago Diferido <br>
                                <span class="txtB">Sin Intereses Y Meses de Gracia</span>
                                </div>';
                } else {
                    $gracia_sin = '<div class="GraciaSin"></div>';
                }
                return $gracia_sin;
            }

            public function cred_normal()
            {
                if ($this->currency != 'USD') {
                    $cred_normal = '<div class="BtnV CredNormal">Pagar<br>
                                <span class="txtB">Sin Cuotas</span>
                                </div>';
                } else {
                    $cred_normal = '<div class="CredNormal"></div>';
                }
                return $cred_normal;
            }

            public function installments()
            {
                if ($this->currency != 'USD' && $this->currency != 'MXN' && $this->installments == 'yes') {
                    $installments = '<div class="BtnV Installments">Pagar<br>
                                <span class="txtB">Con cuotas</span>
                                </div>';
                } else {
                    $installments = '<div class="Installments"></div>';
                }
                return $installments;
            }

            function get_refund()
            {
                $environment = ($this->test == "yes") ? 'TRUE' : 'FALSE';
                return $environment;
            }

            // Muestra el iframe
            function receipt_page($order)
            {
                // echo '<p>'.__('Gracias por su pedido, Hagá click en PAGAR para proceder .', 'paymentez').'</p>';
                echo $this->generate_paymentez_form($order);
            }

            // Configura los datos que serán luego renderizados como un formulario
            public function get_params_post($orderId)
            {
                $order = new WC_Order($orderId);
                $order_data = $order->get_data();

                $currency = get_woocommerce_currency();
                $amount = $order_data['total'];

                $credito = get_post_meta($orderId, '_billing_customer_dni', true);
                // Obtiene los items del carrito de compras
                $products = $order->get_items();

                $description = '';

                $taxable_amount = 0.00;
                //$taxable_amount = number_format(($order_data['total']),2,'.','');
                foreach ($products as $product) {
                    $description .= $product['name'] . ',';
                    if ($product['subtotal_tax'] != 0 && $product['subtotal_tax'] != '') {
                        $taxable_amount = number_format(($product['subtotal']), 2, '.', '');
                    }
                }

                foreach ($order->get_items() as $item_key => $item) {
                    $prod = $order->get_product_from_item($item);
                    $sku = $prod->get_id();
                    /*$prod = $item->get_product();
                    $sku = $prod['product_id'];*/
                }

                //variable timestamp
                $fecha_actual = date('Y-m-d');
                $variableTimestamp = strtotime($fecha_actual);
                $subtotal = number_format(($order->get_subtotal()), 2, '.', '');

                //calcular el IVA
                $vat = number_format(($order->get_total_tax()), 2, '.', '');
                //$tax = $order->get_total_tax();
                if ($vat != 0){
                $taxReturnBase = number_format(($amount - $vat), 2, '.', '');
                $tax_percentage = $this->impuesto_pay; //number_format((($vat * 100) / $taxReturnBase),0,'.','');
            }
                if ($vat == 0) $taxReturnBase = 0;
                if ($vat == 0) $tax_percentage = 0;

                //$vat = 2.85;

                //$uid = $order->user_id;

                if (is_null($order_data['customer_id']) or empty($order_data['customer_id'])) {
                    $uid = $orderId;
                } else {
                    $uid = $order_data['customer_id'];
                }
                // Calcula la firma digital hash('sha256',
                // $token = 'application_code='.$this->app_code .'&dev_reference='. $orderId .'&product_amount='. $amount .'&product_code='. $sku .'&product_description='. urlencode($description).'&taxable_amount='.$taxable_amount.'&uid='. $uid .'&vat='. $vat .'&'. $variableTimestamp .'&'. $this->app_key;
                $token = 'application_code=' . $this->app_code . '&dev_reference=' . $orderId . '&product_amount=' . $amount . '&product_code=' . $sku . '&product_description=' . urlencode($description) . '&uid=' . $uid . '&vat=' . $vat . '&' . $variableTimestamp . '&' . $this->app_key;
                $signature = hash('sha256', $token);
                $div1 = $this->credi_rot();
                $div2 = $this->cred_dif_con();
                $div3 = $this->cred_dif_sin();
                $div4 = $this->cred_normal();
                $div5 = $this->installments();
                $div6 = $this->gracia_con_int();
                $div7 = $this->gracia_sin_int();


                // Campos que convertirán los datos
                $parametersArgs = array(
                    'app_code' => $this->app_code,
                    'credito' => $credito,
                    'purchase_order_id' => $orderId,
                    'purchase_description' => $description,
                    'purchase_amount' => $amount,
                    'iva' => $this->impuesto_pay,
                    'subtotal' => $subtotal,
                    'purchase_tax' => $vat,
                    'purchase_returnbase' => $taxReturnBase,
                    'purchase_tax_percentage' => $tax_percentage,
                    'purchase_signature' => $signature,
                    'token' => $token,
                    'purchase_currency' => $currency,
                    'customer_firstname' => $order_data['billing']['first_name'],
                    'customer_lastname' => $order_data['billing']['last_name'],
                    'customer_phone' => $order_data['billing']['phone'],
                    'customer_email' => $order_data['billing']['email'],
                    'address_street' => $order_data['billing']['address_1'],
                    'address_city' => $order_data['billing']['city'],
                    'address_country' => $order_data['billing']['country'],
                    'address_state' => $order_data['billing']['state'],
                    'user_id' => $uid,
                    'div1' => $div1,
                    'div2' => $div2,
                    'div3' => $div3,
                    'div4' => $div4,
                    'div5' => $div5,
                    'div6' => $div6,
                    'div7' => $div7,
                    'cod_prod' => $sku,
                    'timestamp' => $variableTimestamp,
                    'productos' => $prod,
                    'taxable_amount' => $taxable_amount,
                    'exclusive_types' => ($this->beta_functions == 'yes') ? $this->exclusive_types : '',
                    'invalid_card_type_message' => ($this->beta_functions == 'yes') ? $this->invalid_card_type_message : '',
                    'webhook_paymentez' => $this->webhook_paymentez,
                );

                return $parametersArgs;
            }


            public function eliminar_simbolos($string)
            {

                $string = trim($string);

                $string = str_replace(
                    array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
                    array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
                    $string
                );

                $string = str_replace(
                    array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
                    array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
                    $string
                );

                $string = str_replace(
                    array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
                    array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
                    $string
                );

                $string = str_replace(
                    array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
                    array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
                    $string
                );

                $string = str_replace(
                    array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
                    array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
                    $string
                );

                $string = str_replace(
                    array('ñ', 'Ñ', 'ç', 'Ç'),
                    array('n', 'N', 'c', 'C',),
                    $string
                );

                $string = str_replace(
                    array("\\", "¨", "º", "-", "~",
                        "#", "@", "|", "!", "\"",
                        "·", "$", "%", "&", "/",
                        "(", ")", "?", "'", "¡",
                        "¿", "[", "^", "<code>", "]",
                        "+", "}", "{", "¨", "´",
                        ">", "< ", ";", ",", ":",
                        ".", " "),
                    ' ',
                    $string
                );
                return $string;
            }


            // Genera el formulario iframe

            public function generate_paymentez_form($orderId)
            {
                $parametersArgs = $this->get_params_post($orderId);

                // Url de Paymentez dependiendo del ambiente en el que nos encontremos
                $environment = ($this->test == "yes") ? 'TRUE' : 'FALSE';
                $this->environment_url = ("TRUE" == $environment)
                    ? 'stg'
                    : 'prod';

                //url
                $url_actual = "https://" . $_SERVER["SERVER_NAME"];
                //directorio
                $dir = plugins_url('/includes/confirmation.php', __FILE__);

                $order = new WC_Order($orderId);
                $order_data = $order->get_data();
                $amount = $order_data['total'];

                $amountchoco= $amount;
                $tax_amount_choco= round(($amountchoco / 1.12),2);
                $vat_choco= round(($tax_amount_choco * 0.12),2);

                // Obtiene los items del carrito de compras
                $products = $order->get_items();
                $taxable_amount = 0.00;
                $taxmedia = $amount / 1.12;
                $description = '';
                //$taxable_amount += number_format(($product['subtotal']),2,'.','');
                foreach ($products as $product) {
                    $description .= $product['name'] . ',';
                    if ($product['total_tax'] > 0) {
                        $taxable_amount = $taxable_amount + $product['total'];
                    }
                }

                $description = $this->eliminar_simbolos($description);

                // $url2 = $this->environment_url.'application_code='.$this->app_code.'&uid='.$parametersArgs['user_id'].'&auth_timestamp='.$parametersArgs['timestamp'] .'&auth_token='. $parametersArgs['purchase_signature'] .'&dev_reference='.$parametersArgs['purchase_order_id'].'&product_description='.$parametersArgs['purchase_description'].'&product_code='.$parametersArgs['cod_prod'].'&product_amount='.$parametersArgs['purchase_amount'].'&success_url='.$dir.'?status=1&failure_url='.$dir.'?status=2&review_url='.$dir.'&installments_type=0&vat='.$parametersArgs['purchase_tax'].'&tax_percentage='.$this->impuesto_pay;
                // $url3 = $this->environment_url.'application_code='.$this->app_code.'&uid='.$parametersArgs['user_id'].'&auth_timestamp='.$parametersArgs['timestamp'] .'&auth_token='. $parametersArgs['purchase_signature'] .'&dev_reference='.$parametersArgs['purchase_order_id'].'&product_description='.$parametersArgs['purchase_description'].'&product_code='.$parametersArgs['cod_prod'].'&product_amount='.$parametersArgs['purchase_amount'].'&success_url='.$dir.'?status=1&failure_url='.$dir.'?status=2&review_url='.$dir.'&installments_type=3&vat='.$parametersArgs['purchase_tax'].'&tax_percentage='.$this->impuesto_pay;
                // $url4= $this->environment_url.'application_code='.$this->app_code.'&uid='.$parametersArgs['user_id'].'&auth_timestamp='.$parametersArgs['timestamp'] .'&auth_token='. $parametersArgs['purchase_signature'] .'&dev_reference='.$parametersArgs['purchase_order_id'].'&product_description='.$parametersArgs['purchase_description'].'&product_code='.$parametersArgs['cod_prod'].'&product_amount='.$parametersArgs['purchase_amount'].'&success_url='.$dir.'?status=1&failure_url='.$dir.'?status=2&review_url='.$dir.'&installments_type=2&vat='.$parametersArgs['purchase_tax'].'&tax_percentage='.$this->impuesto_pay;

                return '
        <link rel="stylesheet" type="text/css" href="https://cdn.paymentez.com/checkout/1.0.1/paymentez-checkout.min.css" media="all">
        <script src="https://cdn.paymentez.com/checkout/1.0.1/paymentez-checkout.min.js" charset="UTF-8"></script>
      <style>
        .BtnV{ 
          border: 1px solid #9c9c9c;
          width: 30%;
          margin-right: 10px;
          margin-bottom: 20px;
          line-height:1;
          height:53px;
          padding: 8px;
          text-align: center;
          background: #0fbd71;
          color: #FFF;
          font-size: 17px;
          text-transform: uppercase;
          letter-spacing: 2px;
          font-weight: 500;
          border-radius: 4px;
          cursor: pointer;
         float: left;
        }

        .txtB{
          font-size: 9px;
          letter-spacing: 0px;}

        .crdT{width: 100%;
          height: 685px;
          border: 0px;
        }

        .paymentez-checkout-modal{
            z-index: 2000;
        }

        .order_details{
        width: 100%;
        list-style: none;
        margin: 0px 0px 20px;
        padding: 0px;
        background: rgb(250, 250, 250);
        border-width: 1px;
        border-style: solid;
        border-color: rgb(238, 238, 238);
        border-image: initial;
        margin-top: 15px;
        }

       .order_details li {
        display: table-cell; 
        vertical-align: middle; 
        width: 1%; 
        text-align: center; 
        height: 150px; 
        text-transform: uppercase;
        float: none !important;
    }

      .order_details li+li {
     border-left: 1px solid #EEE; 
      }

      .order_details li strong{
         display: block; 
        font-size: 15px;
        font-weight: 550;
     
    }

    .alert {
        padding: 15px;        
        border: 1px solid transparent;
        border-radius: 4px
        background-clip: padding-box;
        border-radius: 0;
        border: 5px solid;
        -webkit-box-shadow: inset 0 0 0 1px rgba(255,255,255,.4);
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.4);
        color: #FFF;
        font-weight: 300;
    }

    .alert-success {
          background: #0fbd71;
          border-color: #0fbd71;
          
      }

      .alert-warning {
          background: #F5A9A9;
          border-color: #FA5858;
          color: #FA5858 !important;
          
      }

      .hide{

          display: none!important;
      }

      .btn-tienda{
        background-color: #0fbd71;
        text-decoration: none;
        color: #fff;
        display: inline-block;
        text-align: center;
        font-weight: normal;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 14px;
      }
       #formDinners{
         margin-bottom: 15px;
        }

    #formDinners form{
        text-align: center
    }

      #formDinners #resp{
        text-align: center;
        margin-top: 10px;
    }

    #formDinners form input[type=button]{
            padding: 5px 15px;
            border: 1px solid #000;
            border-radius: 4px;
            background-color: lightblue;
            color: #000;
            font-size: 15px;
            width: 250px;
    }

    #formDinners form input[type=text]{ 
       
            padding: 5px;
            border-radius: 4px;
            border: 1px solid;
            font-size: 15px;
            width: 250px;
            display: block;
            margin: 0 auto;
            margin-bottom: 15px;
            text-align: center;
    }

    #formDinners #MsgforDinners {
            text-align: center;
            background: #ccc;
            color: #000;
            font-size: 15px;
            width: 650px;
            margin: 0 auto;
            padding: 10px;
            margin-bottom: 15px;
    }
    

    #errordinners p {
            padding: 5px;
            background-color: #F83B54;
            color: #fff;
            width: 500px;
            text-align: center;
            margin: 0 auto;
            margin-top: 10px;
    }

      #respdinners p {
            padding: 5px;
            background-color: #77C256;
            color: #fff;
            width: 500px;
            text-align: center;
            margin: 0 auto;
            margin-top: 10px;
    }

    #btnbackdinners {
            width: 650px;
            margin: 0 auto;
    }

     #btnbackdinners p{
          text-align: center;
        }

   #btnbackdinners p a{
           background: #F83B54;
        }

   #btnbackdinners p a:hover{
           color: #FFF;
        }
       

       </style>
       <div id="messagetwo" class="hide"> <p class="alert alert-success" > Su pago se ha realizado con éxito. Muchas gracias por su compra </p> </div>

       <div id="messagetres" class="hide"> <p class="alert alert-warning"> Ocurrió un error al comprar y su pago no se pudo realizar. Intente con otra Tarjeta de Crédito </p> </div>
      
      <div id="buttonreturn" class="hide">
        <p><a class="btn-tienda" target="_parent" href="' . get_permalink(get_option('woocommerce_shop_page_id')) . '">' . __('&larr; Volver a la Tienda', 'woothemes') . ' </a></p>
       </div> 

       <div id="message"> <p class="alert alert-success" > Elija su forma de pago y haga click para proceder </p> </div>


        <div id="formDinners" class="hide">    

                <div id="MsgforDinners" > 
                    <p> Favor ingresar el <strong> Código </strong> que le llegó a su email, o SMS del Banco emisor de su tarjeta </p>
                </div>

                <form method="post" id="formulario">
                    <input type="text" maxlength=6 name="codedinners" id="codedinners" class="codedinners" placeholder="Ingrese Código" autofocus/>           
                    <input type="button" id="btn-ingresar" value="ENVIAR" />
                </form>   

                <div id="resp"></div>
                <div id="respdinners" > </div>
                <div id="errordinners" >  </div>
                <div id="btnbackdinners">
                     <p><a class="btn-tienda" style="padding: 15px; margin-top: 15px; font-size: 14px;" target="_parent" href="' . get_permalink(get_option('woocommerce_shop_page_id')) . '">' . __('Cancelar Transancción', 'woothemes') . ' </a></p>   
                </div>
                <div id="buttonreturn2" class="hide">
        <p><a class="btn-tienda" style="padding: 15px; margin-top: 15px; font-size: 14px;" target="_parent" href="' . get_permalink(get_option('woocommerce_shop_page_id')) . '">' . __('&larr; Volver a la Tienda', 'woothemes') . ' </a></p>
       </div> 
               
       </div>

        <div id="buttonspay" style="width:100%; text-align:center; margin-left: 50px; margin-bottom: 20px; ">   
            ' . $parametersArgs['div1'] . '
            ' . $parametersArgs['div2'] . '
            ' . $parametersArgs['div3'] . '
            ' . $parametersArgs['div4'] . '
            ' . $parametersArgs['div5'] . '
            ' . $parametersArgs['div6'] . '
            ' . $parametersArgs['div7'] . '
        </div>

         <script>    
            jQuery(document).ready(function($) {

                $(".codedinners").keydown(function(event) {
         
                       if(event.shiftKey)
                        event.preventDefault();                    
                                
                    if (event.keyCode != 46 && event.keyCode != 8 && event.keyCode != 37 && event.keyCode != 39) 
                        if($(this).val().length >= 11)
                            event.preventDefault();
             
                   
                    if (event.keyCode < 48 || event.keyCode > 57)                        
                        if (event.keyCode < 96 || event.keyCode > 105)                       
                            if(event.keyCode != 46 && event.keyCode != 8 && event.keyCode != 37 && event.keyCode != 39)
                                event.preventDefault();                      
                 });

            var paymentezCheckout = new PaymentezCheckout.modal({
            client_app_code: "' . $this->app_code . '", 
            client_app_key: "' . $this->app_key . '", 
            locale: "es", 
            env_mode: "' . $this->environment_url . '", 
            onOpen: function() {
            //console.log("modal open");
            },
            onClose: function() {
            //console.log("modal closed");
            },
            onResponse: function(response) { 
                //announceTransaction(response);
               if (response.transaction["status_detail"] === 3) {
                    
                   showMessageSuccess(); 
                   announceTransaction(response);
                } 
                
                else if(response.transaction["status_detail"] === 31){                 
                    showFormDinners();  

                      $("#btn-ingresar").click(function(){

                         $("#respdinners").addClass("hide");
                         $("#errordinners").addClass("hide");
                         $("#resp").removeClass("hide");
                      
                        var url = "'.plugins_url('codedinners.php', __FILE__).'";
                        codedinners =$("#codedinners").val(); 
                        var iduser= "' . $parametersArgs["user_id"] . '";
                        var codf = response.transaction["id"];   
                        var appcode=  "' . $this->app_code_server . '";
                        var appkey= "' . $this->app_key_server . '";  
                        var dev_reference= "' . $parametersArgs["purchase_order_id"] . '";   
                        
                       $.ajax({                        
                           type: "POST",  
                           dataType: "json",                
                           url: url,                    
                            data: {
                                codedinners: codedinners,
                                iduser:iduser,
                                codf: codf,
                                appcode:appcode,
                                appkey: appkey,
                                dev_reference: dev_reference,
                            },
                          beforeSend: function () {
                                $("#resp").html("Validando, espere por favor...");
                                $("#btn-ingresar").addClass("hide");
                            },
                           success: function(data)            
                           {
                              var dinnerstatus = data[0]["resp"]["transaction"]["status"];
                              var dinnerdetails = data[0]["resp"]["transaction"]["status_detail"];
                              
                              if (dinnerdetails === 3) {
                              
                             response.transaction=data[0]["resp"]["transaction"];
                             response.card=data[0]["resp"]["card"];
                              announceTransaction(response);
                                $("#resp").addClass("hide");
                                 $("#btnbackdinners").addClass("hide");
                                 $("#formulario").addClass("hide");
                                 $("#MsgforDinners").addClass("hide");
                                 $("#buttonreturn2").removeClass("hide");
                                 $("#respdinners").removeClass("hide");                                
                                $("#respdinners").html(" <p >  Su pago con <strong>PAYMENTEZ</strong> se ha realizado exitosamente. Muchas gracias por su compra </p> ");  
                                
                              }
                              else if(dinnerstatus === "pending" && dinnerdetails===31){
                              
                                $("#resp").addClass("hide");
                                $("#errordinners").removeClass("hide");
                                $("#btn-ingresar").removeClass("hide");
                                 
                                $("#errordinners").html(" <p > Código Inválido, Vuelve a Intentar</p> ");  
                              }
                              else {
                              response.transaction=data[0]["resp"]["transaction"];
                               response.card=data[0]["resp"]["card"];
                              announceTransaction(response);
                              $("#btnbackdinners").addClass("hide");
                                $("#resp").addClass("hide");
                                $("#formulario").addClass("hide");
                                $("#errordinners").removeClass("hide");
                                 console.log(data);
                                $("#errordinners").html(" <p > Transacción Inválida, Has excedido el numero de intentos</p> ");  
                                $("#buttonreturn2").removeClass("hide"); 
                              }
                                                  

                           }
                         });
                         
                      });
                    
            
                }
                else {
                  showMessageError();
                  announceTransaction(response);
                }
            }
        });
      
        var btnOpenCheckout = document.querySelector(".Rot");
        var btnOpenCheckoutDifCon = document.querySelector(".DifCon");
        var btnOpenCheckoutDifSin = document.querySelector(".DifSin");
        var btnOpenCheckoutCredNormal = document.querySelector(".CredNormal");
        var btnOpenCheckoutInstallments = document.querySelector(".Installments");
        var btnOpenCheckoutGraciaCon = document.querySelector(".GraciaCon");
        var btnOpenCheckoutGraciaSin = document.querySelector(".GraciaSin");

        btnOpenCheckout.addEventListener("click", function(){
            paymentezCheckout.open({
                user_id: "' . $parametersArgs["user_id"] . '",
                user_email: "' . $parametersArgs["customer_email"] . '",       
                user_phone: "' . $parametersArgs["customer_phone"] . '",
                order_description: "' . $parametersArgs["purchase_order_id"] . '",
                order_amount: ' . $amountchoco . ',
                order_vat: ' . $vat_choco. ',
                order_reference: "' . $parametersArgs["purchase_order_id"] . '",            
                order_tax_percentage: 12,
                order_taxable_amount: ' . $tax_amount_choco . ',
                conf_exclusive_types: "' . $parametersArgs["exclusive_types"] . '",
                conf_invalid_card_type_message: "' . $parametersArgs["invalid_card_type_message"] . '"
            });
        });

        btnOpenCheckoutDifCon.addEventListener("click", function(){
            paymentezCheckout.open({
                user_id: "' . $parametersArgs["user_id"] . '",
                user_email: "' . $parametersArgs["customer_email"] . '",       
                user_phone: "' . $parametersArgs["customer_phone"] . '",
                order_description: "' . $parametersArgs["purchase_order_id"] . '",
                order_amount: ' . $amountchoco . ',
                order_vat: ' . $vat_choco. ',
                order_reference: "' . $parametersArgs["purchase_order_id"] . '",
                order_installments_type: 2,
                order_tax_percentage: 12,
                order_taxable_amount: ' . $tax_amount_choco . ',
                conf_exclusive_types: "' . $parametersArgs["exclusive_types"] . '",
                conf_invalid_card_type_message: "' . $parametersArgs["invalid_card_type_message"] . '"
            });
        });

        btnOpenCheckoutDifSin.addEventListener("click", function(){
            paymentezCheckout.open({
                user_id: "' . $parametersArgs["user_id"] . '",
                user_email: "' . $parametersArgs["customer_email"] . '",       
                user_phone: "' . $parametersArgs["customer_phone"] . '",
                order_description: "' . $parametersArgs["purchase_order_id"] . '",
                order_amount: ' . $amountchoco . ',
                order_vat: ' . $vat_choco. ',
                order_reference: "' . $parametersArgs["purchase_order_id"] . '",
                order_installments_type: 3,
                order_tax_percentage: 12,
                order_taxable_amount: ' . $tax_amount_choco . ',
                conf_exclusive_types: "' . $parametersArgs["exclusive_types"] . '",
                conf_invalid_card_type_message: "' . $parametersArgs["invalid_card_type_message"] . '"
            });
        });
        
        btnOpenCheckoutGraciaCon.addEventListener("click", function(){
            paymentezCheckout.open({
                user_id: "' . $parametersArgs["user_id"] . '",
                user_email: "' . $parametersArgs["customer_email"] . '",       
                user_phone: "' . $parametersArgs["customer_phone"] . '",
                order_description: "' . $parametersArgs["purchase_order_id"] . '",
                order_amount: ' . $amountchoco . ',
                order_vat: ' . $vat_choco. ',
                order_reference: "' . $parametersArgs["purchase_order_id"] . '",
                order_installments_type: 7,
                order_tax_percentage: ' . $parametersArgs["purchase_tax_percentage"] . ',
                order_taxable_amount: ' . $tax_amount_choco . ',
                conf_exclusive_types: "' . $parametersArgs["exclusive_types"] . '",
                conf_invalid_card_type_message: "' . $parametersArgs["invalid_card_type_message"] . '"
            });
        });
        
        btnOpenCheckoutGraciaSin.addEventListener("click", function(){
            paymentezCheckout.open({
                user_id: "' . $parametersArgs["user_id"] . '",
                user_email: "' . $parametersArgs["customer_email"] . '",       
                user_phone: "' . $parametersArgs["customer_phone"] . '",
                order_description: "' . $parametersArgs["purchase_order_id"] . '",
                order_amount: ' . $amountchoco . ',
                order_vat: ' . $vat_choco. ',
                order_reference: "' . $parametersArgs["purchase_order_id"] . '",
                order_installments_type: 9,
                order_tax_percentage: ' . $parametersArgs["purchase_tax_percentage"] . ',
                order_taxable_amount: ' . $tax_amount_choco . ',
                conf_exclusive_types: "' . $parametersArgs["exclusive_types"] . '",
                conf_invalid_card_type_message: "' . $parametersArgs["invalid_card_type_message"] . '"
            });
        });

        btnOpenCheckoutCredNormal.addEventListener("click", function(){
            paymentezCheckout.open({
                user_id: "' . $parametersArgs["user_id"] . '",
                user_email: "' . $parametersArgs["customer_email"] . '",       
                user_phone: "' . $parametersArgs["customer_phone"] . '",
                order_description: "' . $parametersArgs["purchase_order_id"] . '",
                order_amount: ' . $amountchoco . ',
                order_vat: ' . $vat_choco. ',
                order_reference: "' . $parametersArgs["purchase_order_id"] . '",
                order_installments_type: -1,
                conf_exclusive_types: "' . $parametersArgs["exclusive_types"] . '",
                conf_invalid_card_type_message: "' . $parametersArgs["invalid_card_type_message"] . '"
            });
        });
        
        btnOpenCheckoutInstallments.addEventListener("click", function(){
            paymentezCheckout.open({
                user_id: "' . $parametersArgs["user_id"] . '",
                user_email: "' . $parametersArgs["customer_email"] . '",       
                user_phone: "' . $parametersArgs["customer_phone"] . '",
                order_description: "' . $parametersArgs["purchase_order_id"] . '",
                order_amount: ' . $amountchoco . ',
                order_vat: ' . $vat_choco. ',
                order_reference: "' . $parametersArgs["purchase_order_id"] . '",
                order_installments_type: 0,
                conf_exclusive_types: "' . $parametersArgs["exclusive_types"] . '",
                conf_invalid_card_type_message: "' . $parametersArgs["invalid_card_type_message"] . '"
            });
        });

        window.addEventListener("popstate", function() {
          paymentezCheckout.close();
        });
          
      
        function showMessageSuccess() {
          $("#message").addClass("hide");
          $("#buttonspay").addClass("hide");
          $("#messagetwo").removeClass("hide");
          $("#buttonreturn").removeClass("hide");
        }
         function showFormDinners(){
          $("#message").addClass("hide");
          $("#buttonspay").addClass("hide");
          $("#formDinners").removeClass("hide");
        
        }
        
        function announceTransaction(data) {
            fetch("' . $parametersArgs['webhook_paymentez'] . '", {
            method: "POST",
            body: JSON.stringify(data)
            }).then(function(response) {
            console.log(response);
            }).catch(function(myJson) {
            console.log(myJson);
            });
        }

        function showMessageError() {
          $("#message").addClass("hide");
          $("#buttonspay").addClass("hide");
          $("#messagetres").removeClass("hide");
          $("#buttonreturn").removeClass("hide");
        }

      });
      </script>
      ';
            }

            public function get_token($orderId)
            {
                $parametersArgs = $this->get_params_post($orderId);
                $tokenfinal = $parametersArgs['purchase_signature'];
                return $tokenfinal;
            }

            // Procesa el pago e informa a WC del mismo.
            function process_payment($orderId)
            {
                global $woocommerce;
                $order = new WC_Order($orderId);
                //$order->update_status('on-hold', __( 'Esperando Pago', 'woocommerce' ));
                // Reduce stock levels
                //  $order->reduce_order_stock();
                // Paso importantisímo ya que vacia el carrito
                $woocommerce->cart->empty_cart();
                //  $parametersArgs = $this->get_params_post( $orderId );
                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_payment_url(true)
                    //'redirect' => $this->get_return_url( $order )
                );
            }

            // Autocompleta la orden si el admin seleccionó dicha opción
            function order_completed($order)
            {
                $order->update_status('completed');
            }

            function order_failed($order)
            {
                $order->update_status('failed');
            }
        }

        // Notifica a WC la integración con paymentez
        function add_paymentez($methods)
        {
            $methods[] = 'Paymentezz';
            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'add_paymentez');
    }
}