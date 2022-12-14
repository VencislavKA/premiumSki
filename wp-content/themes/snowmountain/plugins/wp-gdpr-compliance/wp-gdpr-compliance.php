<?php
/* WP GDPR Compliance support functions
------------------------------------------------------------------------------- */

// Theme init priorities:
// 9 - register other filters (for installer, etc.)
if ( ! function_exists( 'snowmountain_wp_gdpr_compliance_feed_theme_setup9' ) ) {
    add_action( 'after_setup_theme', 'snowmountain_wp_gdpr_compliance_theme_setup9', 9 );
    function snowmountain_wp_gdpr_compliance_theme_setup9() {
        if ( is_admin() ) {
            add_filter( 'snowmountain_filter_tgmpa_required_plugins', 'snowmountain_wp_gdpr_compliance_tgmpa_required_plugins' );
        }
    }
}

// Filter to add in the required plugins list
if ( ! function_exists( 'snowmountain_wp_gdpr_compliance_tgmpa_required_plugins' ) ) {
    //Handler of the add_filter('snowmountain_filter_tgmpa_required_plugins',	'snowmountain_wp_gdpr_compliance_tgmpa_required_plugins');
    function snowmountain_wp_gdpr_compliance_tgmpa_required_plugins( $list = array() ) {
        if (in_array('wp-gdpr-compliance', snowmountain_storage_get('required_plugins'))) {
            $list[] = array(
                'name' 		=> esc_html__('Cookie Information', 'snowmountain'),
                'slug' 		=> 'wp-gdpr-compliance',
                'required' 	=> false
            );
        }
        return $list;
    }
}

// Check if this plugin installed and activated
if ( ! function_exists( 'snowmountain_exists_wp_gdpr_compliance' ) ) {
    function snowmountain_exists_wp_gdpr_compliance() {
        return defined( 'WP_GDPR_C_ROOT_FILE' ) || defined( 'WPGDPRC_ROOT_FILE' );
    }
}