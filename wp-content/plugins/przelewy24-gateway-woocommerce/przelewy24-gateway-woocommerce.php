<?php
/**
 * Plugin Name: WooCommerce Przelewy24 Payment Gateway
 * Plugin URI: http://www.przelewy24.pl/cms,22,pliki_do_pobrania.htm
 * Description: Przelewy24 Payment gateway for woocommerce.
 * Version: 3.2.1a
 * Author: DialCom24 Sp. z o.o.
 * Author URI: http://www.przelewy24.pl/
 */
session_start();
add_action('plugins_loaded', 'woocommerce_gateway_przelewy24_init', 0);
function woocommerce_gateway_przelewy24_init(){
    if(!class_exists('WC_Payment_Gateway')) return;
    require_once 'class_przelewy24.php';
    class WC_Gateway_Przelewy24 extends WC_Payment_Gateway{
        public function __construct(){
            global $woocommerce;
	
            $this -> id = 'przelewy';
            $this->icon = home_url( '/' ).'wp-content/plugins/przelewy24-gateway-woocommerce/przelewy24_logo.png';
            $this -> medthod_title = 'Przelewy24';
            $this -> has_fields = false;

            $this -> init_form_fields();
            $this -> init_settings();

            $this -> title = $this -> settings['title'];
            $this -> description = (isset($this -> settings['description']))? $this -> settings['description']:'';
            $this -> instructions = $this->get_option( 'instructions', $this->description );
            $this -> merchant_id = (isset($this -> settings['merchant_id']))? $this -> settings['merchant_id']:0;
            $this -> shop_id = (isset($this -> settings['shop_id']))? $this -> settings['shop_id']:0;
            $this -> salt = (isset($this -> settings['CRC_key']))?$this -> settings['CRC_key']:'';
            $this -> p24_testmod = $this -> settings['p24_testmod'];
            $this -> p24_debug = $this -> settings['p24_debug'];
	 
	  
            if(version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
            } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
            }
            add_action('woocommerce_receipt_przelewy', array(&$this, 'receipt_page'));
			add_action('woocommerce_thankyou_przelewy', array($this, 'thankyou_page' ) );
			add_action('woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	  
            //Listener API
            add_action('woocommerce_api_wc_gateway_przelewy24', array($this,'przelewy24_response'));
            if (isset($_SESSION['P24'])) $this->sanitized_fields=$_SESSION['P24'];
        }
        function init_form_fields(){
            $this -> form_fields = array(
                'enabled' => array(
                    'title' => __('Włącz/Wyłącz'),
                    'type' => 'checkbox',
                    'label' => __('Aktywuj moduł płatności Przelewy24.'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:'),
                    'type'=> 'text',
                    'description' => __('Tekst który zobaczą klienci podczas dokonywania zakupu'),
                    'default' => __('Przelewy24')),
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
                    'description' => __('Tekst który zobaczą klienci przy wyborze metody płatności'),
					'default'     => __( 'Płać z Przelewy24')),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
                    'description' => __('Tekst który zobaczą klienci po dokonaniu zakupu oraz w mailu z potwierdzeniem'),
					'default'     => __( 'Dziękujemy za skorzystanie z płatności Przelewy24.' )),
                'merchant_id' => array(
                    'title' => __('ID Sprzedawcy'),
                    'type' => 'text',
                    'description' => __('Identyfikator sprzedawcy nadany w systemie Przelewy24.'),
                    'default' => 0),
                'shop_id' => array(
                    'title' => __('ID Sklepu'),
                    'type' => 'text',
                    'description' => __('Identyfikator sklepu nadany w systemie Przelewy24.'),
                    'default' => 0),
                'CRC_key' => array(
                    'title' => __('Klucz CRC'),
                    'type' => 'text',
                    'description' => __('Klucz do CRC nadany w systemie Przelewy24.')),
                'p24_testmod' => array(
                    'title' => __('Tryb testowy'),
                    'type' => 'select',
                    'options' => $this -> get_options(),
                    'description' => __('Tryb przeprowadzania testowych transakcji')),
                'p24_debug' => array(
                    'title' => __('Tryb debugowania'),
                    'type' => 'checkbox',
                    'description' => __('Tryb do testowania poprawności działania modułu')),
            );
        }
        
        public function validate_id($key, $error) {
            $ret=$this->get_option($key);
            $valid=false;
            if (isset($_POST[$this->plugin_id.$this->id.'_'.$key])) {
                $ret=$_POST[$this->plugin_id.$this->id.'_'.$key];
                if (is_numeric($ret) && $ret>=1000) $valid=true;
            }
            if (!$valid) $this->errors[$key]=$error;
            return $ret;
        }
        
        public function validate_crc($key) {
            $ret=$this->get_option($key);
            $valid=false;
            if (isset($_POST[$this->plugin_id.$this->id.'_'.$key])) {
                $ret=$_POST[$this->plugin_id.$this->id.'_'.$key];
                if (strlen($ret)==16 && ctype_xdigit($ret)) $valid=true;
            }
            if (!$valid) $this->errors[$key]='Klucz do CRC powinien mieć 16 znaków.';
            return $ret;
        }
        
        public function get_option($key, $empty_value = null) {
            if (isset($this->sanitized_fields[$key])) {
                return $this->sanitized_fields[$key];
            }
            return parent::get_option($key, $empty_value);
        }
        public function display_errors() {
            foreach ($this->errors as $v) {
                echo '<div class="error">Błąd: '.$v.'</div>';
            }
            echo '<script type="text/javascript">jQuery(document).ready(function () {jQuery(".updated").remove();});</script>';
        }
        
        public function validate_settings_fields($form_fields = false) {
            if (!$form_fields) $form_fields = $this->get_form_fields();

            $this->sanitized_fields['merchant_id']=$this->validate_id('merchant_id', 'Błędny ID Sprzedawcy.');
            $this->sanitized_fields['shop_id']=$this->validate_id('shop_id', 'Błędny ID Sklepu.');
            $this->sanitized_fields['CRC_key']=$this->validate_crc('CRC_key');
            parent::validate_settings_fields($form_fields);
            $P24=new Przelewy24Class($this->sanitized_fields['merchant_id'], $this->sanitized_fields['shop_id'], $this->sanitized_fields['CRC_key'], ($this->sanitized_fields['p24_testmod']=='sandbox'));
            $ret=$P24->testConnection();
            if ($ret['error']!=0) 
                $this->errors['p24_testmod']='Błędny ID Sklepu, Sprzedawcy lub Klucz do CRC dla tego trybu pracy wtyczki.';
            
            $_SESSION['P24']=$this->sanitized_fields;
        }
        
        public function admin_options(){
            echo '<h3>'.__('Bramka płatności Przelewy24').'</h3>';
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this -> generate_settings_html();
            echo '</table>';
        }
        
        function payment_fields(){
            if($this -> description) echo wpautop(wptexturize($this -> description));
        }
        /**
         * Receipt Page
         **/ 
        function receipt_page($order){
            echo $this -> generate_przelewy24_form($order);
        }
        /**
         * Generate przelewy24 button link
         **/
        public function generate_przelewy24_form($order_id){
            global $woocommerce;
            $order = new WC_Order( $order_id );
            $transaction_id =  $order_id."_".uniqid(md5($order_id.'_'.date("ymds")),true);
            $redirect_url = get_site_url();
		
            //p24_opis depend of test mode
            if($this -> p24_testmod=="sandbox") $opis="Transakcja testowa";
            else $opis='Zamówienie nr: '.$order_id.', '.$order -> billing_first_name.' '.$order -> billing_last_name.', '.date('Ymdhi');

            //return address URL		
            $payment_page = add_query_arg(array('wc-api'=>'WC_Gateway_Przelewy24','order_id'=>$order_id), home_url( '/' )) ;
            $status_page = add_query_arg(array('wc-api'=>'WC_Gateway_Przelewy24'), home_url( '/' )) ;

		
            /*Form send to przelewy24*/
            $amount=round($order->order_total*100,0);
            $currency=strtoupper($order->get_order_currency());
            $przelewy24_arg = array(
       			  	'p24_session_id'	=>	$transaction_id,
       			  	'p24_merchant_id'	=>	$this->merchant_id,
                                'p24_pos_id'    	=>	$this->shop_id,
       			  	'p24_email'		=>	$order->billing_email,
       			  	'p24_amount'		=>	$amount,
                                'p24_currency'          =>      $currency,
       			  	'p24_description'	=>	$opis,
       			  	'p24_language'		=>	'pl',
       			  	'p24_client'		=>	$order->billing_first_name.' '.$order->billing_last_name,
       			  	'p24_address'		=>	$order->billing_address_1,
       			  	'p24_city'		=>	$order->billing_city,
       			  	'p24_zip'		=>	$order->billing_postcode,
       			  	'p24_country'		=>	$order->billing_country,
                                'p24_encoding'          =>      'UTF-8',
       			  	'p24_url_status'	=>	$status_page,
                                'p24_url_return'	=>	$payment_page,
       			  	'p24_url_cancel'        =>	$payment_page,
                                'p24_api_version'       =>      P24_VERSION
            );
            $P24=new Przelewy24Class($this->merchant_id, $this->shop_id, $this->salt, ($this->p24_testmod=='sandbox'));
            $przelewy24_arg['p24_sign']=$P24->trnDirectSign($przelewy24_arg);
            $P24->checkMandatoryFieldsForAction($przelewy24_arg,'trnDirect');
            $przelewy_form='';
            foreach($przelewy24_arg as $key => $value) 
                $przelewy_form .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
            
            return '<form action="'.$P24->trnDirectUrl().'" method="post" id="przelewy_payment_form">'.$przelewy_form.'<input type="submit" class="button-alt" id="submit_przelewy_payment_form" value="Zapłać" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">Anuluj zamówienie</a><script type="text/javascript">jQuery(function(){jQuery("body").block({message: "'.__('Dziękujemy za złożenie zamówienia. Za chwilę nastąpi przekierowanie na stronę przelewy24.pl').'",overlayCSS: {background: "#fff",opacity: 0.6},css: {padding:20,textAlign:"center",color:"#555",border:"2px solid #AF2325",backgroundColor:"#fff",cursor:"wait",lineHeight:"32px"}});jQuery("#submit_przelewy_payment_form").click();});</script></form>';
	}
        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id){
            global $woocommerce;
            $order = new WC_Order( $order_id );
                return array('result' => 'success', 'redirect' => add_query_arg('order',
                $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
            );
        }
	/**
	/*Check przelewy24 response
	**/
	function przelewy24_response(){
        global $woocommerce;
        $P24=new Przelewy24Class($this->merchant_id, $this->shop_id, $this->salt, ($this->p24_testmod=='sandbox'));

        $debug=false;
        if (($this->p24_debug=='yes') and isset($_GET['test']) and $_GET['test']==md5('p24'.$this->salt.'debug')) $debug=true;
        if ($debug) {
            echo 'v. 3.2.1<br /><pre>';
            var_dump($P24->testConnection());
            echo "\n\nPOST:\n";
            var_dump($_POST);
            echo "\n\n";
            var_dump($this->merchant_id);
            var_dump($this->shop_id);
            echo "\n";
        }
        if (isset($_POST['p24_session_id'])) {
            $p24_session_id=$_POST['p24_session_id'];
            $reg_session="/^[0-9a-zA-Z_\.]+$/D";
            if(!preg_match($reg_session,$p24_session_id)) exit;
            $session_id = explode('_',$p24_session_id);
            $order_id = $session_id[0];
            $order = new WC_Order($order_id);	
                
            $validation=array('p24_amount'=>round($order->order_total*100,0));
            $WYNIK=$P24->trnVerifyEx($validation);
            if ($WYNIK===null) {
                exit("\n".'MALFORMED POST');
            } elseif ($WYNIK===true) {
                $order->add_order_note(__('IPN payment completed', 'woocommerce'));
                $order->payment_complete();
                if($debug) exit("\n".'OK');
            } else {
                $order->update_status('failed');
                $order->cancel_order();
                if($debug) {
                    var_dump($validation);
                    exit("\n".'ERROR');
                }
            }
            if (!isset($_GET['order_id'])) exit;
        }
        if ($debug) exit;
        if (isset($_GET['order_id']) && !$debug) {
            $order = new WC_Order($_GET['order_id']);
            if ($order->status=='failed') {
                $woocommerce->add_error(__('Payment error: ', 'woothemes') . 'Sorry your transaction did not go through successfully, please try again.');
                wp_redirect( $order->get_cancel_order_url() );
            } else {
                $woocommerce->cart->empty_cart();
                wp_redirect($this->get_return_url($order));
            }
        }
	}

	function get_options() {
            $option_list=array();
            $option_list['secure']= 'Wyłączony';
            $option_list['sandbox']= 'Test transakcji';
		
            return $option_list;
	}


    /**
     * Output for the order received page.
     */
    
	function thankyou_page() {
		if ( $this->instructions ) {
        	echo wpautop( wptexturize( $this->instructions ) );
		}
	}

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    
	function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'przelewy' === $order->payment_method ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

    }
    function woocommerce_przelewy24_gateway($methods) {
        $methods[] = 'WC_Gateway_Przelewy24';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_przelewy24_gateway' );
}
