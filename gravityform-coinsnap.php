<?php
/*
 * Plugin Name:     Gravity Forms Coinsnap Add-On
 * Plugin URI:      https://www.coinsnap.io
 * Description:     Integrates Gravity Forms with Coinsnap.
 * Version:         1.1
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io/
 * Text Domain:     coinsnap-for-gravityform
 * Domain Path:     /languages
 * Version:         1.0.0
 * Requires PHP:    7.4
 * Tested up to:    6.4.3
 * Requires at least: 5.2
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:         true
 */ 

define( 'SERVER_PHP_VERSION', '7.4' );
define( 'COINSNAP_VERSION', '1.0.0' );
define( 'COINSNAP_REFERRAL_CODE', 'D19826' );
define( 'COINSNAP_PLUGIN_ID', 'coinsnap-for-gravityform' );
define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );

add_action('gform_loaded', array('GF_Coinsnap', 'load'), 5);

class GF_Coinsnap {
    public static function load(){
        if ( ! method_exists('GFForms', 'include_payment_addon_framework')) {
            return;
        }
        require_once (plugin_dir_path(__FILE__) . '/library/autoload.php');	
        require_once('class-gf-coinsnap.php');

        GFAddOn::register('GFCoinsnap');
    }
}

function gf_coinsnap() {
    return GFCoinsnap::get_instance();
}