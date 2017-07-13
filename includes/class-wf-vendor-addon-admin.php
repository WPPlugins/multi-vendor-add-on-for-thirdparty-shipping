<?php
class class_wf_vendor_addon_admin{
    public function __construct() {
    	$this->wf_init();
	}

	public function wf_init(){
		add_filter('wf_dhl_filter_label_packages', array($this, 'wf_vendor_label_packages'), 10, 4);
		add_filter('wf_filter_label_packages', array($this, 'wf_vendor_label_packages'), 10, 4);
		add_filter('wf_filter_label_from_address', array($this, 'wf_vendor_label_from_address'), 10, 4);

        $splitcart = get_option('wc_settings_wf_vendor_addon_splitcart');
        if( $splitcart == 'sum_cart' ){
			add_filter('wf_filter_package_address', array($this, 'wf_splited_packages'), 10, 4);
        }else{
			add_filter('woocommerce_cart_shipping_packages', array($this, 'wf_splited_packages'), 10, 4);
        }
	}

	private function get_vendor_id_from_product($order_details){
		if(empty($order_details)){
			return false;
		}
		if( $order_details['data'] instanceof wf_product ) { //call from cart page, instanceof wf_product
			$product = $order_details['data'];
		}else{
			$product = $this->wf_load_product( $order_details['data'] );
		}
		if( VENDOR_PLUGIN == 'product_vendor' && WC_Product_Vendors_Utils::is_vendor_product($product->id) ){
			//get associated user with vendor.
			$woo_vendor = WC_Product_Vendors_Utils::get_vendor_id_from_product( $product->id );
			$vendor = WC_Product_Vendors_Utils::get_vendor_data_by_id($woo_vendor);
			$vendor = explode(',', $vendor['admins']);
			if( !empty($vendor[0]) ){
				return $vendor[0]; //assume only one user associated with vendor, taking fist user.
			}
			// if not user found let retun post auther.
		}
		$post = get_post( $product->id ) ;
		return $post->post_author;
	}

	public function wf_vendor_label_packages($packages, $ship_from_address_context) {
		//if origin preference is not vendor address , Do nothing.
		if ($ship_from_address_context !== 'vendor_address')
			return $packages;

		$vendor_packages = array();
		foreach ($packages as $package) {
			foreach ($package['contents'] as $order_details) {
				$vendor_id = $this->get_vendor_id_from_product($order_details);
				if(!$vendor_id)continue;
				
				$vendor_packages[$vendor_id]['contents'][] = $order_details;
				$vendor_packages[$vendor_id]['destination'] = $package['destination'];

				$vendor_address = $this->get_vendor_address( $vendor_id );
				$vendor_packages[$vendor_id]['origin'] = array(
						'country'=>$vendor_address['vendor_country'],
						'company'=>$vendor_address['vendor_company'],
						'first_name'=>$vendor_address['vendor_fname'],
						'last_name'=>$vendor_address['vendor_lname'],
						'address_1'=>$vendor_address['vendor_address1'],
						'address_2'=>$vendor_address['vendor_address2'],
						'city'=>$vendor_address['vendor_city'],
						'state'=>$vendor_address['vendor_state'],
						'postcode'=>$vendor_address['vendor_zip'],
						'phone'=>$vendor_address['vendor_phone'],
						'email'=>$vendor_address['email'],
					);
			}
		}
		// Now the packages array will be indexed by vendor ID.
		return $vendor_packages;
	}

	private function get_vendor_address( $vndr_id ){
		$vendor_profile = get_user_meta($vndr_id);

		$vendor_addon = '';
		if( isset($vendor_profile['_wcv_store_address1']) ){
			$vendor_addon = 'wc_vendor';	
		}elseif( isset($vendor_profile['_wcv_store_address1']) ){
			$vendor_addon = 'dokan';	
		}else{
			$vendor_addon = 'woothemes';
		}

		$vendor_details= array();
		switch ($vendor_addon) {
			case 'dokan':
				$dokan_profile = isset( $vendor_profile['dokan_profile_settings'][0] ) ? unserialize( $vendor_profile['dokan_profile_settings'][0] ) : '';
				
				$vendor_details['vendor_country'] 	= isset( $dokan_profile['address']['country'] ) ? $dokan_profile['address']['country'] : '';
				$vendor_details['vendor_fname']		= isset( $vendor_profile['billing_first_name'][0] ) ? $vendor_profile['billing_first_name'][0] : '' ;
				$vendor_details['vendor_lname']		= isset( $vendor_profile['billing_last_name'][0] ) ? $vendor_profile['billing_last_name'][0] : '';
				$vendor_details['vendor_company']	= isset( $dokan_profile['store_name'] ) ? $dokan_profile['store_name'] : '';
				$vendor_details['vendor_address1']	= isset( $dokan_profile['address']['street_1'] ) ? $dokan_profile['address']['street_1'] : '';
				$vendor_details['vendor_address2']	= isset( $dokan_profile['address']['street_2'] ) ? $dokan_profile['address']['street_2'] : '';
				$vendor_details['vendor_city']		= isset( $dokan_profile['address']['city'] ) ? $dokan_profile['address']['city'] : '';
				$vendor_details['vendor_state']		= isset( $dokan_profile['address']['state'] ) ? $dokan_profile['address']['state'] : '';
				$vendor_details['vendor_zip']		= isset( $dokan_profile['address']['zip'] ) ? $dokan_profile['address']['zip'] : '';
				$vendor_details['vendor_phone']		= isset( $dokan_profile['phone'] ) ? $dokan_profile['phone'] : '';
				$vendor_details['email']			= isset( $vendor_profile['billing_email'][0] ) ? $vendor_profile['billing_email'][0] : '';
				break;

			case 'wc_vendor':
				$vendor_details['vendor_country'] 	= isset( $vendor_profile['_wcv_store_country'][0] ) ? $vendor_profile['_wcv_store_country'][0] : '';
				$vendor_details['vendor_fname']		= isset( $vendor_profile['first_name'][0] ) ? $vendor_profile['first_name'][0] : '';
				$vendor_details['vendor_lname']		= isset( $vendor_profile['last_name'][0] ) ? $vendor_profile['last_name'][0] : '';
				$vendor_details['vendor_company']	= isset( $vendor_profile['pv_shop_name'][0] ) ? $vendor_profile['pv_shop_name'][0] : '';
				$vendor_details['vendor_address1']	= isset( $vendor_profile['_wcv_store_address1'][0] ) ? $vendor_profile['_wcv_store_address1'][0] : '';
				$vendor_details['vendor_address2']	= isset( $vendor_profile['_wcv_store_address2'][0] ) ? $vendor_profile['_wcv_store_address2'][0] : '';
				$vendor_details['vendor_city']		= isset( $vendor_profile['_wcv_store_city'][0] ) ? $vendor_profile['_wcv_store_city'][0] : '';
				$vendor_details['vendor_state']		= isset( $vendor_profile['_wcv_store_state'][0] ) ? $vendor_profile['_wcv_store_state'][0] : '';
				$vendor_details['vendor_zip']		= isset( $vendor_profile['_wcv_store_postcode'][0] ) ? $vendor_profile['_wcv_store_postcode'][0] : '';
				$vendor_details['vendor_phone']		= isset( $vendor_profile['_wcv_store_phone'][0] ) ? $vendor_profile['_wcv_store_phone'][0] : '';
				$vendor_details['email']			= isset( $vendor_profile['billing_email'][0] ) ? $vendor_profile['billing_email'][0] : '';
				break;
			
			default:
				$vendor_details['vendor_country'] 	= isset( $vendor_profile['billing_country'][0] ) ? $vendor_profile['billing_country'][0] : '';
				$vendor_details['vendor_fname']		= isset( $vendor_profile['billing_first_name'][0] ) ? $vendor_profile['billing_first_name'][0] : '';
				$vendor_details['vendor_lname']		= isset( $vendor_profile['billing_last_name'][0] ) ? $vendor_profile['billing_last_name'][0] : '';
				$vendor_details['vendor_company']	= isset( $vendor_profile['billing_company'][0] ) ? $vendor_profile['billing_company'][0] : '';
				$vendor_details['vendor_address1']	= isset( $vendor_profile['billing_address_1'][0] ) ? $vendor_profile['billing_address_1'][0] : '';
				$vendor_details['vendor_address2']	= isset( $vendor_profile['billing_address_2'][0] ) ? $vendor_profile['billing_address_2'][0] : '';
				$vendor_details['vendor_city']		= isset( $vendor_profile['billing_city'][0] ) ? $vendor_profile['billing_city'][0] : '';
				$vendor_details['vendor_state']		= isset( $vendor_profile['billing_state'][0] ) ? $vendor_profile['billing_state'][0] : '';
				$vendor_details['vendor_zip']		= isset( $vendor_profile['billing_postcode'][0] ) ? $vendor_profile['billing_postcode'][0] : '';
				$vendor_details['vendor_phone']		= isset( $vendor_profile['billing_phone'][0] ) ? $vendor_profile['billing_phone'][0] : '';
				$vendor_details['email']			= isset( $vendor_profile['billing_email'][0] ) ? $vendor_profile['billing_email'][0] : '';
				break;
		}
		return $vendor_details;
	}

	//function to get vendor address for api request
	public function wf_vendor_label_from_address($from_address , $package, $ship_from_address_context) {

		//if origin preference is not vendor address , Do nothing.
		if ($ship_from_address_context !== 'vendor_address')
		return $from_address;

		$addr = array(
		'Contact' 		=> array(
			'PersonName' 	=> $package['origin']['first_name'] . ' ' . $package['origin']['last_name'],
			'CompanyName' 	=> $package['origin']['company'],
			'PhoneNumber' 	=> $package['origin']['phone']
		),
		'Address' 		=> array(
			'StreetLines' 	=> array($package['origin']['address_1'], $package['origin']['address_2']),
			'City' 			=> strtoupper($package['origin']['city']),
			'StateOrProvinceCode' => strlen($package['origin']['state']) == 2 ? strtoupper($package['origin']['state']) : '',
			'PostalCode' 	=> str_replace(' ', '', strtoupper($package['origin']['postcode'])),
			'CountryCode' 	=> $package['origin']['country'],
			'Residential' 	=> (isset($fedex_settings['shipper_residential']) && 'yes' === $fedex_settings['shipper_residential']) ? TRUE : FALSE //fedex settings are loaded in init
		)
		);
		return $addr;
	}

	function wf_splited_packages($packages, $ship_from_address_context='' ){
		//if origin preference is not vendor address , Do nothing.
		if ( $ship_from_address_context != '' && $ship_from_address_context !== 'vendor_address'){
			
			return $packages;
		}
		$new_packages              	= array();		
		//Init splitted package
		$splitted_packages		=	array();
		$vendor_id = '';
		// group items by vendor
		foreach ( WC()->cart->get_cart() as $item_key => $item ) {
			$item['data'] = $this->wf_load_product($item['data']);

			if ( $item['data']->needs_shipping() ) {
				$vendor_id	=	$this->get_vendor_id_from_product($item);
				$splitted_packages[$vendor_id][$item_key]	=	$item;
			}
		}
		
		// Add grouped items as packages 
		if(is_array($splitted_packages)){
			
			foreach($splitted_packages as $vendor_id => $splitted_package_items){
				$vendor_address = $this->get_vendor_address($vendor_id);

				$new_packages[] = array(
					'contents'        => $splitted_package_items,
					'contents_cost'   => array_sum( wp_list_pluck( $splitted_package_items, 'line_total' ) ),
					'applied_coupons' => WC()->cart->get_applied_coupons(),
					'user'            => array(
						 'ID' => $vendor_id
					),
					'origin'		=>array(
						'country' 		=> $vendor_address['vendor_country'],
						'first_name' 	=> $vendor_address['vendor_fname'],
						'last_name'		=> $vendor_address['vendor_lname'],
						'company'		=> $vendor_address['vendor_company'],
						'address_1' 	=> $vendor_address['vendor_address1'],
						'address_2' 	=> $vendor_address['vendor_address2'],
						'city' 			=> $vendor_address['vendor_city'],
						'state'			=> $vendor_address['vendor_state'],
						'postcode' 		=> $vendor_address['vendor_zip'],
						'phone' 		=> $vendor_address['vendor_phone'],
						'email' 		=> $vendor_address['email'],
					),
					'destination'    => array(
						'country'    => WC()->customer->get_shipping_country(),
						'state'      => WC()->customer->get_shipping_state(),
						'postcode'   => WC()->customer->get_shipping_postcode(),
						'city'       => WC()->customer->get_shipping_city(),
						'address'    => WC()->customer->get_shipping_address(),
						'address_2'  => WC()->customer->get_shipping_address_2()
					)
				);
			}
		}
		
		return $new_packages;
	}

	private function wf_load_product( $product ){
		if( !$product ){
			return false;
		}
		if( !class_exists('wf_product') ){
			include('class-wf-legacy.php');
		}
		return ( WC()->version < '2.7.0' ) ? $product : new wf_product( $product );
	}
}
new class_wf_vendor_addon_admin;