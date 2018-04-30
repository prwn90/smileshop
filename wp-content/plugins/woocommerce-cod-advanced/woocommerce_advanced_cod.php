<?php
/*
Plugin Name: WooCommerce COD Advanced
Plugin URI: http://aheadzen.com/
Description: Cash On Delivery Advanced - Added advanced options like hide COD payment while checkout if minimum amount, enable extra charges if minimum amount.
Author: AheadZen
Version: 1.3.0
Author URI: http://aheadzen.com/
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

class WooCommerceCODAdvanced{
    public function __construct(){
		$this->current_extra_charge_min_amount = 0;
        $this->current_gateway_title = '';
        $this->current_gateway_extra_charges = 0;
		$this->current_gateway_extra_charges_type_value = '';
		add_action('woocommerce_settings_api_form_fields_cod',array($this,'adv_cod_woocommerce_update_options_payment_gateways_cod_fun'));
		add_filter('woocommerce_available_payment_gateways',array($this,'adv_cod_filter_gateways'));
		//add_action( 'woocommerce_calculate_totals', array($this,'adv_cod_calculate_totals'), 9, 1 );
		add_action('wp_head',array($this,'adv_cod_wp_header'), 99 );
		add_filter('woocommerce_gateway_icon',array($this,'adv_cod_gateway_icon'),9,2);

		add_action('woocommerce_cart_calculate_fees',array( &$this, 'woo_add_extra_fee'));

		add_action('init',array($this,'az_woocod_init'));

		global $woocommerce;
		if(isset($_POST['action']) && $_POST['action'] == 'woocommerce_update_order_review'){
			add_filter('woocommerce_available_payment_gateways',array($this,'adv_cod_filter_gateways'));
		}
    }


	function az_woocod_init(){
		load_plugin_textdomain('askoracle', false, basename( dirname( __FILE__ ) ) . '/languages');
	}

	/****************************
	COD header
	****************************/
	function adv_cod_wp_header()
	{
	?>
	<script>
	 jQuery(document).ready(function($){
		 jQuery(document.body).on('change', 'input[name="payment_method"]', function() {
			jQuery('body').trigger('update_checkout');
		});
	 });
	</script>
	<?php
	}

	/****************************
	COD admin options
	****************************/
	function adv_cod_woocommerce_update_options_payment_gateways_cod_fun($form_fields)
	{
		global $woocommerce;
		$allowed_countries = $woocommerce->countries->get_allowed_countries();
        asort( $allowed_countries );

		$form_fields['cod_adv_title'] = array(
							'title'			=> __('WooCommerce Advanced COD Plugin Settings','askoracle'),
							'type'			=> 'title',
							'default'  		=> 'no',
						);

		$form_fields['disable_cod_adv'] = array(
							'title'			=> __('Disable Advanced COD?','askoracle'),
							'type'			=> 'checkbox',
							'description'	=> __('Select if you want to disable advanced COD plugin settings.','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
						);

		$form_fields['cod_icon'] = array(
							'title'			=> __('Display icon on checkout page?','askoracle'),
							'type'			=> 'checkbox',
							'description'	=> __('Select if you want to display COD icon on checkout page.','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
						);

		$form_fields['min_amount'] = array(
							'title'			=> __('Minimum cart amount to display','askoracle'),
							'type'			=> 'text',
							'description'	=> __('Minimum cart amount to display the payment option on checkout page','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
						);

		$form_fields['max_amount'] = array(
							'title'			=> __('Maximum cart amount to hide','askoracle'),
							'type'			=> 'text',
							'description'	=> __('Maximum cart amount to hide the payment option on checkout page','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
						);

		$form_fields['extra_charge_min_amount'] = array(
							'title'			=> __('Minimum cart amount for free COD','askoracle'),
							'type'			=> 'text',
							'description'	=> __('Maximum cart amount to apply extra charge as per "Extra charges" settings.','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
						);

		$form_fields['extra_charges'] = array(
							'title'			=> __('Extra charges','askoracle'),
							'type'			=> 'text',
							'description'	=> __('Extra charges applied on checkout while select COD payment method','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
						);

		$form_fields['extra_charges_msg'] = array(
							'title'			=> __('Message for extra charges','askoracle'),
							'type'			=> 'text',
							'description'	=> __('Message for extra charges while applied on checkout','askoracle'),
							'default'		=> 'COD Charges',
							'desc_tip'		=> '',
						);

		$form_fields['extra_charges_type'] = array(
							'title'			=> __('Extra charges type','askoracle'),
							'type'			=> 'select',
							'description'	=> __('Extra charges either amount or percentage of cart amount','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
							'options'       => array('amount'=>__('Total Add','askoracle'),'percentage'=>__('Total % Add','askoracle'))
						);

		$form_fields['roundup_type'] = array(
							'title'			=> __('Round up total amount by','askoracle'),
							'type'			=> 'select',
							'description'	=> __('Select the factor to round the order total amount by.','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
							'options'       => array('0'=>0,'5'=>5,'10'=>10,'50'=>50,'100'=>100)
						);


		/**Category**/
		$cat_arr = array();
		$categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		if ( $categories ) foreach ( $categories as $cat ) {
			$cat_arr[esc_attr( $cat->term_id )]=esc_html( $cat->name );
		}
		$form_fields['exclude_cats'] = array(
							'title'			=> __('Exclude categories','askoracle'),
							'type'			=> 'multiselect',
							'description'	=> __('Select categories to hide COD while category products is in the cart.','askoracle'),
							'default'		=> '0',
							'class'			=> 'wc-enhanced-select',
							'options'       => $cat_arr
						);

		/**Country**/
		$country_arr = array();
    // if ( $allowed_countries && $zone_fields && $zone_fields['zone_country']) {
			if ( $allowed_countries ) {
	//		$selections = explode(',', $zone_fields['zone_country']);
			foreach ( $allowed_countries as $key => $val ) {
				//echo '<option value="'.$key.'" ' . selected( in_array( $key, $selections ), true, false ).'>' . $val . '</option>';
				$country_arr[$key] = $val;
				/*$allowed_states = $woocommerce->countries->get_states($key);
				if( $allowed_states ) {
					foreach ($allowed_states as $skey => $sval) {
						$country_arr[$key.':'.$skey] = '&#009;' . $val . ' &mdash; ' . $sval;
						//echo '<option value="'.$key.':'.$skey.'" ' . selected( in_array( $key.':'.$skey, $selections ), true, false ).'>&#009;' . $val . ' &mdash; ' . $sval . '</option>';
					}
				}*/
			}
		}
		$form_fields['country'] = array(
							'title'			=> __('Select Country','askoracle'),
							'type'			=> 'multiselect',
							'description'	=> __('Select country to hide COD while user has same country.','askoracle'),
							'default'		=> '',
							'class'			=> 'wc-enhanced-select',
							'options'       => $country_arr
						);

		$form_fields['in_ex_country'] = array(
							'title'			=> __('Country include/exclude?','askoracle'),
							'type'			=> 'select',
							'description'	=> __('Select country include/exclude to display/hide COD while user have same.','askoracle'),
							'default'  		=> 'no',
							'options'  => array(
								'include' => __('Display COD if user is from above country', 'askoracle' ),
								'exclude'  => __('Hide COD if user is from above country', 'askoracle' )
							),
						);


		/**States**/
		$state_arr = array();
		if ( $woocommerce->countries->get_allowed_country_states() ) {
			$selections = ( isset( $zone_fields['zone_except']['states'] ) ) ? explode(',', $zone_fields['zone_except']['states']) : array();
			foreach ( $woocommerce->countries->get_allowed_country_states() as $key => $val ) {
				if( count( $val ) ) {
					$allowed_states = $woocommerce->countries->get_states($key);
					if( $allowed_states ) {
						foreach ($allowed_states as $skey => $sval) {
							$state_arr[$key.':'.$skey]= '&#009;' . $allowed_countries[$key] . ' &mdash; ' . $sval;
							//echo '<option value="'.$key.':'.$skey.'" ' . selected( in_array( $key.':'.$skey, $selections ), true, false ).'>&#009;' . $allowed_countries[$key] . ' &mdash; ' . $sval . '</option>';
						}
					}
				}
			}
		}
		$form_fields['states'] = array(
							'title'			=> __('Select States/Provinces','askoracle'),
							'type'			=> 'multiselect',
							'description'	=> __('Select state to hide COD while user has same state.','askoracle'),
							'default'		=> '',
							'class'			=> 'wc-enhanced-select',
							'options'       => $state_arr
						);

		$form_fields['in_ex_states'] = array(
							'title'			=> __('States/Provinces include/exclude?','askoracle'),
							'type'			=> 'select',
							'description'	=> __('Select States/Provinces include/exclude to display/hide COD while user have same.','askoracle'),
							'default'  		=> 'no',
							'options'  => array(
								'include' => __('Display COD if user is from above state', 'askoracle' ),
								'exclude'  => __('Hide COD if user is from above state', 'askoracle' )
							),
						);

		$form_fields['city'] = array(
							'title'			=> __('Enter Cities','askoracle'),
							'type'			=> 'textarea',
							'description'	=> __('Enter comma separated city name to hide COD while user has same city.','askoracle'),
							'default'		=> '',
							'desc_tip'		=> '0',
						);

		$form_fields['in_ex_city'] = array(
							'title'			=> __('City include/exclude?','askoracle'),
							'type'			=> 'select',
							'description'	=> __('Select City include/exclude to display/hide COD while user have same.','askoracle'),
							'default'  		=> 'no',
							'options'  => array(
								'include' => __('Display COD if user is from above city', 'askoracle' ),
								'exclude'  => __('Hide COD if user is from above city', 'askoracle' )
							),
						);

		$form_fields['cod_pincodes'] = array(
							'title'			=> __('Postal/Pin codes to hide COD','askoracle'),
							'type'			=> 'textarea',
							'description'	=> __('Enter comma separated postal/pin codes to hide COD on checkout.','askoracle'),
							'default'		=> '',
							'desc_tip'		=> '0',
						);

		$form_fields['in_ex_pincode'] = array(
							'title'			=> __('Postal/Pin code include/exclude?','askoracle'),
							'type'			=> 'select',
							'description'	=> __('Select Postal/Pin code include/exclude to display/hide COD while user have same.','askoracle'),
							'default'  		=> 'no',
							'options'  => array(
								'include' => __('Display COD if user is from above postal code', 'askoracle' ),
								'exclude'  => __('Hide COD if user is from above postal code', 'askoracle' )
							),
						);

		$form_fields['hide_virtual_product'] = array(
							'title'			=> __('Hide for virtual and downloadable products?','askoracle'),
							'type'			=> 'checkbox',
							'description'	=> __('Select to hide COD option for virtual and downloadable products on checkout page.','askoracle'),
							'default'		=> '0',
							'desc_tip'		=> '0',
						);

		$form_fields['hide_virtual_product_msg'] = array(
							'title'			=> __('COD hide message','askoracle'),
							'type'			=> 'textarea',
							'description'	=> __('Message will be display if COD is disabled because of advanced COD settings on checkout page.','askoracle'),
							'default'		=> '',
							'desc_tip'		=> '0',
						);

		return $form_fields;
	}


	/****************************
	COD filter show/hide COD
	****************************/
	function adv_cod_filter_gateways($gateways)
	{
		$min_cod_amount = 0;
		$max_cod_amount = 0;
		$cod_enabled=1;
		global $wpdb,$woocommerce,$hide_virtual_product_msg;
		$settings = get_option('woocommerce_cod_settings');

		if($settings['disable_cod_adv'] && $settings['disable_cod_adv']=='yes'){return $gateways;}
		if(isset($settings) && $settings)
		{
			$min_cod_amount = $settings['min_amount'];
			$max_cod_amount = $settings['max_amount'];
			$exclude_country = $settings['country'];
			$in_ex_country = $settings['in_ex_country'];
			$exclude_states = $settings['states'];
			$in_ex_states = $settings['in_ex_states'];
			$exclude_city = trim($settings['city']);
			$in_ex_city = $settings['in_ex_city'];
			$cod_pincodes = trim($settings['cod_pincodes']);
			$in_ex_pincode = $settings['in_ex_pincode'];
			$exclude_cats = $settings['exclude_cats'];
			$hide_virtual_product = $settings['hide_virtual_product'];
			$hide_virtual_product_msg = trim($settings['hide_virtual_product_msg']);
		}else{
			$min_cod_amount = 0;
			$max_cod_amount = 0;
			$exclude_country = '';
			$in_ex_country = '';
			$exclude_states = '';
			$in_ex_states = '';
			$exclude_city = '';
			$in_ex_city = '';
			$cod_pincodes = '';
			$in_ex_pincode = '';
			$exclude_cats = array();
			$hide_virtual_product = 0;
			$hide_virtual_product_msg = '';
		}
		if($exclude_cats){
			$exclude_cats_str = implode(',',$exclude_cats);
			$exclude_post_ids = $wpdb->get_col("select p.ID from $wpdb->posts p join $wpdb->term_relationships tr on tr.object_id=p.ID join $wpdb->term_taxonomy tt on tt.term_taxonomy_id=tr.term_taxonomy_id where tt.term_id in ($exclude_cats_str) and tt.taxonomy='product_cat' and p.post_type='product' and p.post_status='publish'");
			$the_cart_contents = $woocommerce->cart->cart_contents;
			foreach($the_cart_contents as $key => $prdarr)
			{
				if(in_array($prdarr['product_id'],$exclude_post_ids))
				{
					unset($gateways['cod']);
					$cod_enabled=0;
				}
			}
		}

		global $woocommerce;
		if($_POST['action'] == 'woocommerce_update_order_review' || $_GET['wc-ajax']=='update_order_review'){
			$customer_detail = $_POST;
		}else{
			$customer_detail = WC()->session->get('customer');
		}

		if($cod_enabled && $exclude_country){
			if($customer_detail['s_country']){
				$shipping_country = $customer_detail['s_country'];
			}else{
				$shipping_country = $customer_detail['shipping_country'];
			}
			if($shipping_country && $in_ex_country=='include' && !in_array($shipping_country,$exclude_country)){
				unset($gateways['cod']);
				$cod_enabled=0;
			}else
			if($shipping_country && $in_ex_country=='exclude' && in_array($shipping_country,$exclude_country)){
				unset($gateways['cod']);
				$cod_enabled=0;
			}
		}

		if($cod_enabled && $exclude_states){
			if($customer_detail['s_country'] && $customer_detail['s_state']){
				$shipping_state = $customer_detail['s_country'].':'.$customer_detail['s_state'];
			}else{
				$shipping_state = trim($customer_detail['shipping_country'].':'.$customer_detail['shipping_state']);
			}

			if($shipping_state && $in_ex_states=='include' && !in_array($shipping_state,$exclude_states)){
				unset($gateways['cod']);
				$cod_enabled=0;
			}elseif($shipping_state && $in_ex_states=='exclude' && in_array($shipping_state,$exclude_states)){
				unset($gateways['cod']);
				$cod_enabled=0;
			}
		}
		if($cod_enabled && $exclude_city){
			$exclude_city = strtolower($exclude_city);
			$exclude_city_arr = explode(',',$exclude_city);
			if($customer_detail['s_city']){
				$shipping_city = strtolower(trim($customer_detail['s_city']));
			}else{
				$shipping_city = strtolower(trim($customer_detail['shipping_city']));
			}

			if($exclude_city_arr && $in_ex_city=='include' && !in_array($shipping_city,$exclude_city_arr)){
				unset($gateways['cod']);
				$cod_enabled=0;
			}elseif($exclude_city_arr && $in_ex_city=='exclude' && in_array($shipping_city,$exclude_city_arr)){
				unset($gateways['cod']);
				$cod_enabled=0;
			}
		}

		if($cod_enabled && $cod_pincodes){
			$cod_pincodes_arr = explode(',',$cod_pincodes);
			if($customer_detail['s_city']){
				$shipping_postcode = trim($customer_detail['s_postcode']);
			}else{
				$shipping_postcode = trim($customer_detail['shipping_postcode']);
			}
			if($shipping_postcode && $in_ex_pincode=='include' && !in_array($shipping_postcode,$cod_pincodes_arr)){
				unset($gateways['cod']);
				$cod_enabled=0;
			}elseif($shipping_postcode && $in_ex_pincode=='exclude' && in_array($shipping_postcode,$cod_pincodes_arr)){
				unset($gateways['cod']);
				$cod_enabled=0;
			}
		}

		$total = $woocommerce->cart->total;
		if(!$total){$total = $woocommerce->cart->cart_contents_total;}
		if($cod_enabled && $min_cod_amount && $woocommerce->cart && $total<=$min_cod_amount){
			unset($gateways['cod']);
			$cod_enabled=0;
		}

		if($cod_enabled && $max_cod_amount && $woocommerce->cart && $total>=$max_cod_amount){
			unset($gateways['cod']);
			$cod_enabled=0;
		}

		if($hide_virtual_product=='yes'){
			$the_cart_contents = $woocommerce->cart->cart_contents;
			foreach($the_cart_contents as $key => $prdarr)
			{
				$downloadable = get_post_meta($prdarr['product_id'],'_downloadable',true);
				if($prdarr['data']->virtual=='yes' || $downloadable=='yes'){
					unset($gateways['cod']);
					$cod_enabled=0;
					break;
				}
			}
		}
		if($cod_enabled==0 && $hide_virtual_product_msg!=''){

			add_filter('woocommerce_update_order_review_fragments', 'woocommerce_checkout_after_order_review_filter');
			if(!function_exists('woocommerce_checkout_after_order_review_filter')){
				function woocommerce_checkout_after_order_review_filter($vals)
				{
					global $cod_enabled,$hide_virtual_product_msg;
					$message = '<div class="woocommerce-info">'.$hide_virtual_product_msg.'</div>';
					$vals['.woocommerce-checkout-payment'] = str_replace('<ul class="payment_methods methods">',$message.'<ul class="payment_methods methods">',$vals['.woocommerce-checkout-payment']);
					return $vals;
				}
			}
		}
		return $gateways;
	}

	/****************************
	COD ICON
	****************************/
	function adv_cod_gateway_icon($icon_html,$id)
	{
		$settings = get_option('woocommerce_cod_settings');
		$cod_icon = $settings['cod_icon'];
		if($id=='cod' && $cod_icon=='yes'){
			$image =  plugins_url('images/cod-icon.png', __FILE__);
			$icon_html = '<img src="'.$image.'" alt="" />';
		}
		return $icon_html;
	}

	/****************************
	COD calculate Totals
	****************************/
	public function adv_cod_calculate_totals( $totals ) {
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$current_gateway = WC()->session->chosen_payment_method;
		if($current_gateway=='cod'){
			$current_gateways_detail = $available_gateways[$current_gateway];
			$disable_cod_adv = $current_gateways_detail->settings['disable_cod_adv'];
			if($disable_cod_adv && $disable_cod_adv=='yes'){return $totals;}

			$current_gateway_id = $current_gateways_detail->id;
			$current_gateway_title = $current_gateways_detail->title;
			$extra_charges_id = 'woocommerce_'.$current_gateway_id.'_extra_charges';
			$extra_charges_type = $extra_charges_id.'_type';
			$extra_charge_min_amount = (float)$current_gateways_detail->settings['extra_charge_min_amount'];
			$extra_charges = (float)$current_gateways_detail->settings['extra_charges'];
			$extra_charges_type = $current_gateways_detail->settings['extra_charges_type'];
			$roundup_type = $current_gateways_detail->settings['roundup_type'];

			if($extra_charges && $extra_charge_min_amount>=$totals->cart_contents_total){
				if($extra_charges_type=="percentage"){
					$totals->cart_contents_total = $totals->cart_contents_total + round(($totals->cart_contents_total*$extra_charges)/100,2);
				}else{
					$totals->cart_contents_total = $totals->cart_contents_total + $extra_charges;
				}
				if($roundup_type>0)
				{
					$extra_add = $roundup_type -($totals->cart_contents_total%$roundup_type);
					$totals->cart_contents_total = $totals->cart_contents_total+$extra_add;
					$extra_charges = $extra_charges+$extra_add;
				}
				$this->current_extra_charge_min_amount = $extra_charge_min_amount;
				$this->current_gateway_title = $current_gateway_title;
				$this->current_gateway_extra_charges = $extra_charges;
				$this->current_gateway_extra_charges_type_value = $extra_charges_type;
				add_action( 'woocommerce_review_order_before_order_total',  array( $this, 'adv_cod_add_payment_gateway_extra_charges_row'));

				add_action( 'woocommerce_cart_totals_before_order_total',  array( $this, 'adv_cod_add_payment_gateway_extra_charges_row'));
			}
		}
		return $totals;
	}

	/****************************
	COD extra charge
	****************************/
	function adv_cod_add_payment_gateway_extra_charges_row(){
		?>
		<tr class="payment-extra-charge">
			<th><?php printf(__('%s Extra Charges <small>for purchase less than %s</small>','askoracle'),$this->current_gateway_title,woocommerce_price($this->current_extra_charge_min_amount));?></th>
			<td><?php if($this->current_gateway_extra_charges_type_value=="percentage"){
				echo $this -> current_gateway_extra_charges.'%';
			}else{
			 echo woocommerce_price($this->current_gateway_extra_charges);
		 }?></td>
	 </tr>
	 <?php
	}

	public function woo_add_extra_fee() {
		global $woocommerce;
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$current_gateway = WC()->session->chosen_payment_method;
		if($current_gateway=='cod'){
			$current_gateways_detail = $available_gateways[$current_gateway];

			$disable_cod_adv = $current_gateways_detail->settings['disable_cod_adv'];
			if($disable_cod_adv && $disable_cod_adv=='yes'){return $totals;}

			$current_gateway_id = $current_gateways_detail->id;
			$current_gateway_title = $current_gateways_detail->title;
			$extra_charges_id = 'woocommerce_'.$current_gateway_id.'_extra_charges';
			$extra_charges_type = $extra_charges_id.'_type';
			$extra_charge_min_amount = (float)$current_gateways_detail->settings['extra_charge_min_amount'];
			$extra_charges = (float)$current_gateways_detail->settings['extra_charges'];
			$extra_charges_type = $current_gateways_detail->settings['extra_charges_type'];
			$roundup_type = $current_gateways_detail->settings['roundup_type'];
			$extra_charges_msg = $current_gateways_detail->settings['extra_charges_msg'];
			if(!$extra_charges_msg){$extra_charges_msg = 'COD Charges';}

			//get cart total
			$total = $woocommerce->cart->subtotal;

			if($extra_charges){
				if($extra_charges_type=="percentage"){
					$total = $total + round(($total*$extra_charges)/100,2);
				}else{
					$total = $total + $extra_charges;
				}
				if($roundup_type>0)
				{
					$extra_add = $roundup_type -($total%$roundup_type);
					$total = $total+$extra_add;
					$extra_charges = $extra_charges+$extra_add;
				}

				//$this->current_extra_charge_min_amount = $extra_charge_min_amount;
				//$this->current_gateway_title = $current_gateway_title;
				//$this->current_gateway_extra_charges = $extra_charges;
				//$this->current_gateway_extra_charges_type_value = $extra_charges_type;
				$extra_fee_option_taxable = 0;
				//if cart total less or equal than $min_order, add extra fee
				//$extra_fee_option_label = sprintf(__('%s Extra Charges <small>for purchase less than %s</small>','askoracle'),$this->current_gateway_title,woocommerce_price($this->current_extra_charge_min_amount));
				$extra_fee_option_label = $extra_charges_msg;
				if($extra_charge_min_amount>=$total || empty($extra_charge_min_amount)){
					$woocommerce->cart->add_fee($extra_fee_option_label, $extra_charges, $extra_fee_option_taxable );
				}
			}
		}

	}

}


if( is_admin() )
{
  add_action( 'current_screen', 'az_cod_selective_loading' );
} else new WooCommerceCODAdvanced();

function az_cod_selective_loading() {
  if(function_exists('get_current_screen'))
  {
      $current_screen = get_current_screen();
      if( is_admin() && $current_screen->id != 'woocommerce_page_wc-settings')
        return ;
  }

new WooCommerceCODAdvanced();

}
