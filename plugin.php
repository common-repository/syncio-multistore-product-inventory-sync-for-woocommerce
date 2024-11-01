<?php
/*
Plugin Name: Syncio — Multistore Product & Inventory Sync For WooCommerce
Requires Plugins: woocommerce
Description: Seamlessly sync inventory and products across multiple WooCommerce and Shopify stores. Efficiently manage WooCommerce multistor
Version: 1.0.17
Author: syncio.co
Author URI: https://syncio.co
License: GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


// check if its syncio ready - table list of req
// if its is ready -> connect to syncio

// Define constants.
define( 'SYNCIO_PLUGIN_VERSION', '1.0.17' );


// Jai local
//define( 'SYNCIO_URL', 'https://api.syncio.test' );
//define( 'SYNCIO_INSTALLER_URL', 'https://api.syncio.test/api/wpp-installer' );

//staging
//define( 'SYNCIO_URL', 'https://staging-app.syncio.io' );
//define( 'SYNCIO_INSTALLER_URL', 'https://staging-app.syncio.io/registration/woocommerce/create-account' );

//Production
define( 'SYNCIO_URL', 'https://app-v2.syncio.io/' );
define( 'SYNCIO_INSTALLER_URL', 'https://app-v2.syncio.io/registration/woocommerce/create-account' );


define( 'SYNCIO_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'SYNCIO_RETAILER_REST_PATH', '/wp-json/syncio/retailer/v1/' );
define('SYNCIO_INTERCOM_APP_ID', 'z6dvdx17');

//register activation hook
register_activation_hook( __FILE__, function() {
    add_option('syncio_plugin_just_activated',true);
});


include 'Syncio.php';
