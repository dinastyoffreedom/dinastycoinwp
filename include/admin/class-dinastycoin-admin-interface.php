<?php
/*
 * Copyright (c) 2018, Ryo Currency Project
 * Admin interface for Dinastycoin gateway
 * Authors: mosu-forge
 * support for Dinastycoin by Dinasty of Freedom Trust
 */

defined( 'ABSPATH' ) || exit;

require_once('class-dinastycoin-admin-payments-list.php');

if (class_exists('Dinastycoin_Admin_Interface', false)) {
    return new Dinastycoin_Admin_Interface();
}

class Dinastycoin_Admin_Interface {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'meta_boxes'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_head', array( $this, 'admin_menu_update'));
    }

    /**
     * Add meta boxes.
     */
    public function meta_boxes() {
        add_meta_box(
            'dinastycoin_admin_order_details',
            __('Dinastycoin Gateway','dinastycoin_gateway'),
            array($this, 'meta_box_order_details'),
            'shop_order',
            'normal',
            'high'
        );
    }

    /**
     * Meta box for order page
     */
    public function meta_box_order_details($order) {
        dinastycoin_Gateway::admin_order_page($order);
    }

    /**
     * Add menu items.
     */
    public function admin_menu() {
        add_menu_page(
            __('Dinastycoin', 'dinastycoin_gateway'),
            __('Dinastycoin', 'dinastycoin_gateway'),
            'manage_woocommerce',
            'dinastycoin_gateway',
            array($this, 'orders_page'),
            DINASTYCOIN_GATEWAY_PLUGIN_URL.'/assets/images/dinastycoin-icon-admin.png',
            56 // Position on menu, woocommerce has 55.5, products has 55.6
        );

        add_submenu_page(
            'dinastycoin_gateway',
            __('Payments', 'dinastycoin_gateway'),
            __('Payments', 'dinastycoin_gateway'),
            'manage_woocommerce',
            'dinastycoin_gateway_payments',
            array($this, 'payments_page')
        );

        $settings_page = add_submenu_page(
            'dinastycoin_gateway',
            __('Settings', 'dinastycoin_gateway'),
            __('Settings', 'dinastycoin_gateway'),
            'manage_options',
            'dinastycoin_gateway_settings',
            array($this, 'settings_page')
        );
        add_action('load-'.$settings_page, array($this, 'settings_page_init'));
    }

    /**
     * Remove duplicate sub-menu item
     */
    public function admin_menu_update() {
        global $submenu;
        if (isset($submenu['dinastycoin_gateway'])) {
            unset($submenu['dinastycoin_gateway'][0]);
        }
    }

    /**
     * Monero payments page
     */
    public function payments_page() {
        $payments_list = new Dinastycoin_Admin_Payments_List();
        $payments_list->prepare_items();
        $payments_list->display();
    }

    /**
     * Monero settings page
     */
    public function settings_page() {
        WC_Admin_Settings::output();
    }

    public function settings_page_init() {
        global $current_tab, $current_section;

        $current_section = 'dinastycoin_gateway';
        $current_tab = 'checkout';

        // Include settings pages.
        WC_Admin_Settings::get_settings_pages();

        // Save settings if data has been posted.
        if (apply_filters("woocommerce_save_settings_{$current_tab}_{$current_section}", !empty($_POST))) {
            WC_Admin_Settings::save();
        }

        // Add any posted messages.
        if (!empty($_GET['wc_error'])) {
            WC_Admin_Settings::add_error(wp_kses_post(wp_unslash($_GET['wc_error'])));
        }

        if (!empty($_GET['wc_message'])) {
            WC_Admin_Settings::add_message(wp_kses_post(wp_unslash($_GET['wc_message'])));
        }

        do_action('woocommerce_settings_page_init');
    }

}

return new Dinastycoin_Admin_Interface();
