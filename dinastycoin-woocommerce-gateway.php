<?php
/*
Plugin Name: Dinastycoin Woocommerce Gateway
Plugin URI: https://github.com/dinastyoffreedom/dinastycoinwp
Description: Extends WooCommerce by adding a Dinastycoin Gateway
Version: 3.0.5
Tested up to: 5.7.2
Author: mosu-forge, SerHack
Author URI: https://monerointegrations.com/
modified for Dinastycoin by Dinasty of Freedom trust
*/
// This code isn't for Dark Net Markets, please report them to Authority!

defined( 'ABSPATH' ) || exit;

// Constants, you can edit these if you fork this repo
define('DINASTYCOIN_GATEWAY_MAINNET_EXPLORER_URL', 'https://explorer.dinastycoin.com/');
define('DINASTYCOIN_GATEWAY_TESTNET_EXPLORER_URL', 'https://testnet.xmrchain.com/');
define('DINASTYCOIN_GATEWAY_ADDRESS_PREFIX', 0x6c80);
define('DINASTYCOIN_GATEWAY_ADDRESS_PREFIX_INTEGRATED', 0x7980);
define('DINASTYCOIN_GATEWAY_ATOMIC_UNITS', 9);
define('DINASTYCOIN_GATEWAY_ATOMIC_UNIT_THRESHOLD', 7); // Amount under in atomic units payment is valid
define('DINASTYCOIN_GATEWAY_DIFFICULTY_TARGET', 120);

// Do not edit these constants
define('DINASTYCOIN_GATEWAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DINASTYCOIN_GATEWAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DINASTYCOIN_GATEWAY_ATOMIC_UNITS_POW', pow(10, DINASTYCOIN_GATEWAY_ATOMIC_UNITS));
define('DINASTYCOIN_GATEWAY_ATOMIC_UNITS_SPRINTF', '%.'.DINASTYCOIN_GATEWAY_ATOMIC_UNITS.'f');

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action('plugins_loaded', 'dinastycoin_init', 1);
function dinastycoin_init() {

    // If the class doesn't exist (== WooCommerce isn't installed), return NULL
    if (!class_exists('WC_Payment_Gateway')) return;

    // If we made it this far, then include our Gateway Class
    require_once('include/class-dinastycoin-gateway.php');

    // Create a new instance of the gateway so we have static variables set up
    new dinastycoin_Gateway($add_action=false);

    // Include our Admin interface class
    require_once('include/admin/class-dinastycoin-admin-interface.php');

    add_filter('woocommerce_payment_gateways', 'dinastycoin_gateway');
    function dinastycoin_gateway($methods) {
        $methods[] = 'Dinastycoin_Gateway';
        return $methods;
    }

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dinastycoin_payment');
    function dinastycoin_payment($links) {
        $plugin_links = array(
            '<a href="'.admin_url('admin.php?page=dinastycoin_gateway_settings').'">'.__('Settings', 'dinastycoin_gateway').'</a>'
        );
        return array_merge($plugin_links, $links);
    }

    add_filter('cron_schedules', 'dinastycoin_cron_add_one_minute');
    function dinastycoin_cron_add_one_minute($schedules) {
        $schedules['one_minute'] = array(
            'interval' => 60,
            'display' => __('Once every minute', 'dinastycoin_gateway')
        );
        return $schedules;
    }

    add_action('wp', 'dinastycoin_activate_cron');
    function dinastycoin_activate_cron() {
        if(!wp_next_scheduled('dinastycoin_update_event')) {
            wp_schedule_event(time(), 'one_minute', 'dinastycoin_update_event');
        }
    }

    add_action('dinastycoin_update_event', 'dinastycoin_update_event');
    function dinastycoin_update_event() {
        Dinastycoin_Gateway::do_update_event();
    }

    add_action('woocommerce_thankyou_'.Dinastycoin_Gateway::get_id(), 'dinastycoin_order_confirm_page');
    add_action('woocommerce_order_details_after_order_table', 'dinastycoin_order_page');
    add_action('woocommerce_email_after_order_table', 'dinastycoin_order_email');

    function dinastycoin_order_confirm_page($order_id) {
        Dinastycoin_Gateway::customer_order_page($order_id);
    }
    function dinastycoin_order_page($order) {
        if(!is_wc_endpoint_url('order-received'))
            Dinastycoin_Gateway::customer_order_page($order);
    }
    function dinastycoin_order_email($order) {
        Dinastycoin_Gateway::customer_order_email($order);
    }

    add_action('wc_ajax_dinastycoin_gateway_payment_details', 'dinastycoin_get_payment_details_ajax');
    function dinastycoin_get_payment_details_ajax() {
        Dinastycoin_Gateway::get_payment_details_ajax();
    }

    add_filter('woocommerce_currencies', 'dinastycoin_add_currency');
    function dinastycoin_add_currency($currencies) {
        $currencies['Dinastycoin'] = __('Dinastycoin', 'dinastycoin_gateway');
        return $currencies;
    }

    add_filter('woocommerce_currency_symbol', 'dinastycoin_add_currency_symbol', 10, 2);
    function dinastycoin_add_currency_symbol($currency_symbol, $currency) {
        switch ($currency) {
        case 'Dinastycoin':
            $currency_symbol = 'XMR';
            break;
        }
        return $currency_symbol;
    }

    if(Dinastycoin_Gateway::use_dinastycoin_price()) {

        // This filter will replace all prices with amount in Dinastycoin (live rates)
        add_filter('wc_price', 'dinastycoin_live_price_format', 10, 3);
        function dinastycoin_live_price_format($price_html, $price_float, $args) {
            $price_float = wc_format_decimal($price_float);
            if(!isset($args['currency']) || !$args['currency']) {
                global $woocommerce;
                $currency = strtoupper(get_woocommerce_currency());
            } else {
                $currency = strtoupper($args['currency']);
            }
            return Dinastycoin_Gateway::convert_wc_price($price_float, $currency);
        }

        // These filters will replace the live rate with the exchange rate locked in for the order
        // We must be careful to hit all the hooks for price displays associated with an order,
        // else the exchange rate can change dynamically (which it should for an order)
        add_filter('woocommerce_order_formatted_line_subtotal', 'dinastycoin_order_item_price_format', 10, 3);
        function dinastycoin_order_item_price_format($price_html, $item, $order) {
            return Dinastycoin_Gateway::convert_wc_price_order($price_html, $order);
        }

        add_filter('woocommerce_get_formatted_order_total', 'dinastycoin_order_total_price_format', 10, 2);
        function dinastycoin_order_total_price_format($price_html, $order) {
            return Dinastycoin_Gateway::convert_wc_price_order($price_html, $order);
        }

        add_filter('woocommerce_get_order_item_totals', 'dinastycoin_order_totals_price_format', 10, 3);
        function dinastycoin_order_totals_price_format($total_rows, $order, $tax_display) {
            foreach($total_rows as &$row) {
                $price_html = $row['value'];
                $row['value'] = Dinastycoin_Gateway::convert_wc_price_order($price_html, $order);
            }
            return $total_rows;
        }

    }

    add_action('wp_enqueue_scripts', 'dinastycoin_enqueue_scripts');
    function dinastycoin_enqueue_scripts() {
        if(Dinastycoin_Gateway::use_dinastycoin_price())
            wp_dequeue_script('wc-cart-fragments');
        if(Dinastycoin_Gateway::use_qr_code())
            wp_enqueue_script('dinastycoin-qr-code', DINASTYCOIN_GATEWAY_PLUGIN_URL.'assets/js/qrcode.min.js');

        wp_enqueue_script('dinastycoin-clipboard-js', DINASTYCOIN_GATEWAY_PLUGIN_URL.'assets/js/clipboard.min.js');
        wp_enqueue_script('dinastycoin-gateway', DINASTYCOIN_GATEWAY_PLUGIN_URL.'assets/js/dinastycoin-gateway-order-page.js');
        wp_enqueue_style('dinastycoin-gateway', DINASTYCOIN_GATEWAY_PLUGIN_URL.'assets/css/dinastycoin-gateway-order-page.css');
    }

    // [dinastycoin-price currency="USD"]
    // currency: BTC, GBP, etc
    // if no none, then default store currency
    function dinastycoin_price_func( $atts ) {
        global  $woocommerce;
        $a = shortcode_atts( array(
            'currency' => get_woocommerce_currency()
        ), $atts );

        $currency = strtoupper($a['currency']);
        $rate = Dinastycoin_Gateway::get_live_rate($currency);
        if($currency == 'BTC')
            $rate_formatted = sprintf('%.8f', $rate / 1e8);
        else
            $rate_formatted = sprintf('%.5f', $rate / 1e8);

        return "<span class=\"dinastycoin-price\">1 DCY = $rate_formatted $currency</span>";
    }
    add_shortcode('dinastycoin-price', 'dinastycoin_price_func');


    // [dinastycoin-accepted-here]
    function dinastycoin_accepted_func() {
        return '<img src="'.DINASTYCOIN_GATEWAY_PLUGIN_URL.'assets/images/dinastycoin-accepted-here.png" />';
    }
    add_shortcode('dinastycoin-accepted-here', 'dinastycoin_accepted_func');

}

register_deactivation_hook(__FILE__, 'dinastycoin_deactivate');
function dinastycoin_deactivate() {
    $timestamp = wp_next_scheduled('dinastycoin_update_event');
    wp_unschedule_event($timestamp, 'dinastycoin_update_event');
}

register_activation_hook(__FILE__, 'dinastycoin_install');
function dinastycoin_install() {
    global $wpdb;
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . "dinastycoin_gateway_quotes";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               order_id BIGINT(20) UNSIGNED NOT NULL,
               payment_id VARCHAR(95) DEFAULT '' NOT NULL,
               currency VARCHAR(6) DEFAULT '' NOT NULL,
               rate BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               amount BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               paid TINYINT NOT NULL DEFAULT 0,
               confirmed TINYINT NOT NULL DEFAULT 0,
               pending TINYINT NOT NULL DEFAULT 1,
               created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (order_id)
               ) $charset_collate;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . "dinastycoin_gateway_quotes_txids";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               payment_id VARCHAR(95) DEFAULT '' NOT NULL,
               txid VARCHAR(64) DEFAULT '' NOT NULL,
               amount BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               height MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
               PRIMARY KEY (id),
               UNIQUE KEY (payment_id, txid, amount)
               ) $charset_collate;";
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . "dinastycoin_gateway_live_rates";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
               currency VARCHAR(6) DEFAULT '' NOT NULL,
               rate BIGINT UNSIGNED DEFAULT 0 NOT NULL,
               updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (currency)
               ) $charset_collate;";
        dbDelta($sql);
    }
}
