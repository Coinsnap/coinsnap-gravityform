<?php
/*
 * Plugin Name:     Coinsnap for Gravity Forms
 * Plugin URI:      https://www.coinsnap.io
 * Description:     Provides a <a href="https://coinsnap.io">Coinsnap</a>  - Bitcoin + Lightning Payment Gateway for Gravity Forms Wordpress Plugin.
 * Version:         1.0.0
 * Author:          Coinsnap
 * Author URI:      https://coinsnap.io/
 * Text Domain:     gravityform-coinsnap
 * Domain Path:     /languages
 * Requires PHP:    7.4
 * Tested up to:    6.6.1
 * Requires at least: 5.2
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:         true
 */ 

if (!defined( 'ABSPATH' )) exit;

define( 'SERVER_PHP_VERSION', '7.4' );
define( 'COINSNAP_VERSION', '1.0.0' );
define( 'COINSNAP_REFERRAL_CODE', 'D19826' );
define( 'COINSNAP_PLUGIN_ID', 'gravityforms_coinsnap' );
define( 'COINSNAP_SERVER_URL', 'https://app.coinsnap.io' );

add_action('gform_loaded', array('GF_Coinsnap', 'load'), 5);

class GF_Coinsnap {
    public static function load(){
        if ( ! method_exists('GFForms', 'include_payment_addon_framework')) {
            return;
        }
        require_once (plugin_dir_path(__FILE__) . '/library/loader.php');	
        require_once('class-gf-coinsnap.php');

        GFAddOn::register('GFCoinsnap');
    }
}

function gf_coinsnap() {
    return GFCoinsnap::get_instance();
}