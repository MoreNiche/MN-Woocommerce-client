<?php
/*
Plugin Name: MoreNiche Tracking
Plugin URI: https://moreniche.com
Description: Adds MoreNiche affiliate tracking to WooCommerce
Version: 1.0.0
Author: MoreNiche
Author URI: https://moreniche.com/
License: GPL2
*/


// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'MN_AFFILIATE_TRACKING_ROOT' ) )
	define( 'MN_AFFILIATE_TRACKING_ROOT', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'MN_AFFILIATE_TRACKING_DATA_PATH' ) )
	define( 'MN_AFFILIATE_TRACKING_DATA_PATH', MN_AFFILIATE_TRACKING_ROOT . 'data' . DIRECTORY_SEPARATOR );

if ( ! defined( 'MN_AFFILIATE_TRACKING_DATA_URL' ) )
	define( 'MN_AFFILIATE_TRACKING_DATA_URL', plugins_url( 'data', __FILE__ ) );


require_once 'includes/admin/page-settings.php';
require_once 'includes/export/writer/exception.php';
require_once 'includes/export/writer/interface.php';
require_once 'includes/export/writer/csv.php';
require_once 'includes/export/export.php';

if( is_admin() ) {
	new WC_AffiliateTracking_Admin();
} else {
	new WC_AffiliateTracking_Export();
}


/**
 * Twist_WC_Affiliate_Tracking class
 *
 * @class Twist_WC_Affiliate_Tracking The class that holds the entire plugin
 */
class Twist_WC_Affiliate_Tracking {

    public function __construct() {

        // Localize our plugin
        add_action( 'init', array($this, 'localization_setup') );

	    // register integration
        add_filter( 'woocommerce_integrations', array($this, 'register_integration') );

    }

    /**
     * Initializes the Twist_WC_Affiliate_Tracking() class
     *
     * Checks for an existing Twist_WC_Affiliate_Tracking() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( !$instance ) {
            $instance = new Twist_WC_Affiliate_Tracking();
        }

        return $instance;
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'wc-affiliate-tracking', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Register integration
     *
     * @param array $interations
     * @return array
     */
    function register_integration( $interations ) {

        include dirname( __FILE__ ) . '/includes/integration.php';

        $interations[] = 'Twist_WC_Affiliate_Integration';

        return $interations;
    }
}

$wc_tracking = Twist_WC_Affiliate_Tracking::init();


// Add settings link on plugin page
function affiliatetracking_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=wc-settings&tab=integration&section=wc_aff_tracking">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'affiliatetracking_settings_link' );