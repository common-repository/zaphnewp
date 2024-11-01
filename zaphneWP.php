<?php
/*
 * Plugin Name: zaphneWP
 * Plugin URI: https://wordpress.zaphne.com
 * Description: Zaphne WordPress Content Accelerator Plugin.
 * Version: 1.2.6
 * Author: Zaphne, Inc.
 * Author URI: https://www.zaphne.com
 * License: GPL2
 */

defined( 'ABSPATH' )or die( 'No script kiddies please!' );

define( 'ZWP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZWP_PLUGIN_URL_PATH', plugin_dir_url( __FILE__ ) );
define( 'ZWP_PLUGIN_MAIN_PATH',ZWP_PLUGIN_DIR_PATH."/zaphneWP.php"  );
define( 'ZWPURL', 'https://wpapi.zaphne.com/zwpapi.php/' );
require_once ZWP_PLUGIN_DIR_PATH . '/zwp_functions.php';

//$GLOBALS[ 'zwp_plugin_version' ] = zaphne_version();

$ZaphneRegistrationQueryResponse = 0;


register_activation_hook(  ZWP_PLUGIN_MAIN_PATH, 'zwp_activation' );

add_action( 'zwp_post_request', 'zwp_get_next_post' );

register_deactivation_hook( ZWP_PLUGIN_MAIN_PATH, 'zwp_deactivation' );

add_action( 'wp_enqueue_scripts', 'zwp_plugin_scripts' );

// add_shortcode('zwp-ad-shortcode', 'zwp_shortcode_function');

// register the stylesheet - stylesheet.css
add_action( 'admin_init', 'zwp_plugin_admin_init' );

//add_action( 'wp_enqueue_scripts', 'wpdocs_theme_name_scripts' );

add_action( 'admin_menu', 'zwp_plugin_menu' );






?>