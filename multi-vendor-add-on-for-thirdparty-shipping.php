<?php
/*
	Plugin Name: Multi-Vendor Add-On for XA Shipping Plugins
	Plugin URI: https://www.xadapter.com/product/multi-vendor-addon/
	Description: XA Vendor Plugin Addon for Print shipping labels via FedEx and DHL Shipping API.
	Version: 1.1.2
	Author: XAdapter
	Author URI: https://www.xadapter.com/
*/


class wf_vendor_addon_setup {

    public function __construct() {
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'wf_plugin_action_links'));
		include_once('includes/class-wf-vendor-addon.php');
		include_once('includes/class-wf-vendor-addon-admin.php');
		
    }

    public function wf_plugin_action_links($links) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=' . wf_get_settings_url() . '&tab=wf_vendor_addon' ) . '">' . __( 'Settings', 'wf-shipping-vendor-addon' ) . '</a>',
		    '<a href="https://www.xadapter.com/category/product/woocommerce-fedex-multi-vendor-addon/" target="_blank">' . __('Documentation', 'wf-shipping-vendor-addon') . '</a>',
            '<a href="https://wordpress.org/support/plugin/multi-vendor-add-on-for-thirdparty-shipping" target="_blank">' . __('Support', 'wf-shipping-vendor-addon') . '</a>'
		);
	return array_merge($plugin_links, $links);
    }

}

new wf_vendor_addon_setup();


if(!defined('VENDOR_PLUGIN') ){
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$plugin_list = array(
		'product_vendor' => 'woocommerce-product-vendors/woocommerce-product-vendors.php', 
		'dokan_lite' => 'wedevs-dokan-plugin-3d6894748b11/dokan.php', 
		'wf_product_vendor' => 'wf-product-vendor/product-vendor-map.php',
		'wc_vendors_pro' => 'wc-vendors-pro/wcvendors-pro.php'
	);
	foreach ($plugin_list as $plugin_name => $slug) {
		if ( is_plugin_active($slug) ){
			define('VENDOR_PLUGIN',$plugin_name);
			break;
		}
	}
}

/* Add Vendor Option in settings and in Print label request.
 * 
 */
add_filter('wf_filter_label_ship_from_address_options', 'wf_vendor_label_ship_from_address_options', 10, 4);

if (!function_exists('wf_get_settings_url')){
		function wf_get_settings_url(){
			return version_compare(WC()->version, '2.1', '>=') ? "wc-settings" : "woocommerce_settings";
		}
}
//Add vendor address option to shipping address options if vendor plugin is enabled.
if(!function_exists('wf_vendor_label_ship_from_address_options')){
	function wf_vendor_label_ship_from_address_options($args) {
		if( defined('VENDOR_PLUGIN') && !empty(VENDOR_PLUGIN) ){
			$args['vendor_address'] = __('Vendor Address', 'wf-shipping-vendor-addon');
		}
		return $args;
	}
}

/*
* Option to change Shipping name.
* default is set to seller company name.
*/
add_filter('woocommerce_shipping_package_name', 'wf_vendor_change_shipping_name', 10, 3 );

function wf_vendor_change_shipping_name( $name, $shipping_number, $package){
	if( !empty($package['origin']) )
		return !empty( $package['origin']['company'] ) ? $package['origin']['company'] : $package['origin']['first_name'] ;
	else
		return $name;
}
