<?php
class wf_vendor_addon_settings_page{
    public function __construct() {
    	add_filter( 'woocommerce_settings_tabs_array',  array($this ,'add_settings_tab'), 50 );
    	add_action( 'woocommerce_settings_tabs_wf_vendor_addon',  array($this ,'settings_tab') );
    	add_action( 'woocommerce_update_options_wf_vendor_addon',  array($this ,'update_settings' ));
    	// add_action( 'admin_menu' ,  array($this ,'wf_vendor_addon_admin_menu_option'));
	}

	public function add_settings_tab( $settings_tabs ) {
	    $settings_tabs['wf_vendor_addon'] = __( 'Multi-vendor addon', 'wf_vendor_addon' );
	    return $settings_tabs;
	}

	public function settings_tab() {
        include('market.php');
	    woocommerce_admin_fields( $this->get_settings() );
	}

	public function update_settings() {
	    woocommerce_update_options( $this->get_settings() );
	}


	public function get_settings() {
	    $settings = array(
	        'section_title' => array(
	            'name'     => __( 'Multi-Vendor AddOn', 'wf_vendor_addon' ),
	            'type'     => 'title',
	            'desc' => __( 'This plugin is an add-on for WooForce DHL Express and FedEx plugin to support Product vendor plugin.', 'wf_vendor_addon' ),
	        ),
			'license'    => array(
				'type'        => 'license',
				'default' 	  => 'no',
				'label'	  =>  __( 'Check if you have large number of rows for rate matrix.', 'wf_vendor_addon' ),
                'id'   => 'license'
			),
			'splitcart'    => array(
				'title'   	  => __( 'Show rate as', 'wf_vendor_addon' ),
				'type'        => 'select',
				'options'         => array(
					'sum_cart'       => __( 'Split and sum', 'wf-shipping-fedex' ),
					'split_cat'      => __( 'Split and seperate', 'wf-shipping-fedex' ),
				),
				'id'   => 'wc_settings_wf_vendor_addon_splitcart'
			),
			'section_end' => array(
			     'type' => 'sectionend',
			     'id' => 'wc_settings_wf_vendor_addon_section_end'
			)
	    );
        return apply_filters( 'wc_settings_wf_vendor_addon_settings', $settings );
	}
/*
	public  function wf_vendor_addon_admin_menu_option() {
	    $hf_dynamic_discount_settings_page = add_submenu_page('woocommerce', __('Dynamic Discount', 'wf_vendor_addon'), __('wf_vendor_addon', 'wf_vendor_addon'), 'manage_woocommerce', 'admin.php?page=wc-settings&tab=hf_dynamic_discount');
	}*/
}
new wf_vendor_addon_settings_page;
