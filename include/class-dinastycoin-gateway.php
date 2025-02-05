<?php
/*
 * Main Gateway of Dinastycoin using either a local daemon or the explorer
 * Authors: SerHack, cryptochangements, mosu-forge
 * Authors Support for Dinastycoin by Dinasty of Freedom trust
 */

defined( 'ABSPATH' ) || exit;

require_once('class-dinastycoin-cryptonote.php');

class Dinastycoin_Gateway extends WC_Payment_Gateway
{
    private static $_id = 'dinastycoin_gateway';
    private static $_title = 'Dinastycoin Gateway';
    private static $_method_title = 'Dinastycoin Gateway';
    private static $_method_description = 'Dinastycoin Gateway Plug-in for WooCommerce.';
    private static $_errors = [];

    private static $discount = false;
    private static $valid_time = null;
    private static $confirms = null;
    private static $confirm_type = null;
    private static $address = null;
    private static $viewkey = null;
    private static $host = null;
    private static $port = null;
    private static $testnet = false;
    private static $onion_service = false;
    private static $show_qr = false;
    private static $use_dinastycoin_price = false;
    private static $use_dinastycoin_price_decimals = DINASTYCOIN_GATEWAY_ATOMIC_UNITS;

    private static $cryptonote;
    private static $dinasty_wallet_rpc;
    private static $dinastycoin_explorer_tools;
    private static $log;

    private static $currencies = array('AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BTC', 'BTN', 'BWP', 'BYN', 'BYR', 'BZD', 'CAD', 'CDF', 'CHF', 'CLF', 'CLP', 'CNY', 'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK', 'JEP', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LTL', 'LVL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VEF', 'VND', 'VUV', 'WST', 'XAF', 'XAG', 'XAU', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMK', 'ZMW', 'ZWL');
    private static $rates = array();

    private static $payment_details = array();

    public function get_icon()
    {
        return apply_filters('woocommerce_gateway_icon', '<img src="'.DINASTYCOIN_GATEWAY_PLUGIN_URL.'assets/images/dinastycoin-icon.png"/>', $this->id);
    }

    function __construct($add_action=true)
    {
        $this->id = self::$_id;
        $this->method_title = __(self::$_method_title, 'dinastycoin_gateway');
        $this->method_description = __(self::$_method_description, 'dinastycoin_gateway');
        $this->has_fields = false;
        $this->supports = array(
            'products',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change'
        );

        $this->enabled = $this->get_option('enabled') == 'yes';

        $this->init_form_fields();
        $this->init_settings();

        self::$_title = $this->settings['title'];
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        self::$discount = $this->settings['discount'];
        self::$valid_time = $this->settings['valid_time'];
        self::$confirms = $this->settings['confirms'];
        self::$confirm_type = $this->settings['confirm_type'];
        self::$address = $this->settings['dinastycoin_address'];
        self::$viewkey = $this->settings['viewkey'];
        self::$host = $this->settings['daemon_host'];
        self::$port = $this->settings['daemon_port'];
        self::$testnet = $this->settings['testnet'] == 'yes';
        self::$onion_service = $this->settings['onion_service'] == 'yes';
        self::$show_qr = $this->settings['show_qr'] == 'yes';
        self::$use_dinastycoin_price = $this->settings['use_dinastycoin_price'] == 'yes';
        self::$use_dinastycoin_price_decimals = $this->settings['use_dinastycoin_price_decimals'];

        $explorer_url = self::$testnet ? DINASTYCOIN_GATEWAY_TESTNET_EXPLORER_URL : DINASTYCOIN_GATEWAY_MAINNET_EXPLORER_URL;
        defined('DINASTYCOIN_GATEWAY_EXPLORER_URL') || define('DINASTYCOIN_GATEWAY_EXPLORER_URL', $explorer_url);

        // Add the currency of the shop to $currencies array. Needed for do_update_event() function
        $currency_shop = get_woocommerce_currency();
		array_push(self::$currencies, $currency_shop);
        
        if($add_action)
            add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));

        // Initialize helper classes
        self::$cryptonote = new Dinastycoin_Cryptonote();
        if(self::$confirm_type == 'dinastycoin-wallet-rpc') {
            require_once('class-dinastycoin-wallet-rpc.php');
            self::$dinastycoin_wallet_rpc = new Dinastycoin_Wallet_Rpc(self::$host, self::$port);
        } else {
            require_once('class-dinastycoin-explorer-tools.php');
            self::$dinastycoin_explorer_tools = new Dinastycoin_Explorer_Tools(self::$testnet);
        }

        self::$log = new WC_Logger();
    }

    public function init_form_fields()
    {
        $this->form_fields = include 'admin/dinastycoin-gateway-admin-settings.php';
    }

    public function validate_dinastycoin_address_field($key,$address)
    {
        if($this->settings['confirm_type'] == 'viewkey') {
            if (strlen($address) == 95 && substr($address, 0, 1) == '4')
                if(self::$cryptonote->verify_checksum($address))
                    return $address;
            self::$_errors[] = 'Dinastycoin address is invalid';
        }
        return $address;
    }

    public function validate_viewkey_field($key,$viewkey)
    {
        if($this->settings['confirm_type'] == 'viewkey') {
            if(preg_match('/^[a-z0-9]{64}$/i', $viewkey)) {
                return $viewkey;
            } else {
                self::$_errors[] = 'Viewkey is invalid';
                return '';
            }
        }
        return $viewkey;
    }

    public function validate_confirms_field($key,$confirms)
    {
        if($confirms >= 0 && $confirms <= 60)
            return $confirms;
        self::$_errors[] = 'Number of confirms must be between 0 and 60';
    }

    public function validate_valid_time_field($key,$valid_time)
    {
        if($valid_time >= 600 && $valid_time < 86400*7)
            return $valid_time;
        self::$_errors[] = 'Order valid time must be between 600 (10 minutes) and 604800 (1 week)';
    }

    public function admin_options()
    {
        $confirm_type = self::$confirm_type;
        if($confirm_type === 'dinastycoin-wallet-rpc')
            $balance = self::admin_balance_info();

        $settings_html = $this->generate_settings_html(array(), false);
        $errors = array_merge(self::$_errors, $this->admin_php_module_check(), $this->admin_ssl_check());
        include DINASTYCOIN_GATEWAY_PLUGIN_DIR . '/templates/dinastycoin-gateway/admin/settings-page.php';
    }

    public static function admin_balance_info()
    {
        if(!is_admin()) {
            return array(
                'height' => 'Not Available',
                'balance' => 'Not Available',
                'unlocked_balance' => 'Not Available',
            );
        }
        $wallet_amount = self::$dinastycoin_wallet_rpc->getbalance();
        $height = self::$dinastycoin_wallet_rpc->getheight();
        if (!isset($wallet_amount)) {
            self::$_errors[] = 'Cannot connect to dinastycoin-wallet-rpc';
            self::$log->add('Dinastycoin_Payments', '[ERROR] Cannot connect to dinastycoin-wallet-rpc');
            return array(
                'height' => 'Not Available',
                'balance' => 'Not Available',
                'unlocked_balance' => 'Not Available',
            );
        } else {
            return array(
                'height' => $height,
                'balance' => self::format_dinastycoin($wallet_amount['balance']).' Dinastycoin',
                'unlocked_balance' => self::format_dinastycoin($wallet_amount['unlocked_balance']).' Dinastycoin'
            );
        }
    }

    protected function admin_ssl_check()
    {
        $errors = array();
        if ($this->enabled && !self::$onion_service)
            if (get_option('woocommerce_force_ssl_checkout') == 'no')
                $errors[] = sprintf('%s is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>', self::$_method_title, admin_url('admin.php?page=wc-settings&tab=checkout'));
        return $errors;
    }

    protected function admin_php_module_check()
    {
        $errors = array();
        if(!extension_loaded('bcmath'))
            $errors[] = 'PHP extension bcmath must be installed';
        return $errors;
    }

    public function process_payment($order_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'dinastycoin_gateway_quotes';

        $order = wc_get_order($order_id);

        if(self::$confirm_type != 'dinastycoin-wallet-rpc') {
          // Generate a unique payment id
          do {
              $payment_id = bin2hex(openssl_random_pseudo_bytes(8));
              $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE payment_id=%s", array($payment_id));
              $payment_id_used = $wpdb->get_var($query);
          } while ($payment_id_used);
        }
        else {
          // Generate subaddress
          $payment_id = self::$dinastycoin_wallet_rpc->create_address(0, 'Order: ' . $order_id);
          if(isset($payment_id['address'])) {
            $payment_id = $payment_id['address'];
          }
          else {
            self::$log->add('Dinastycoin_Gateway', 'Couldn\'t create subaddress for order ' . $order_id);
          }
        }

        $currency = $order->get_currency();
        $rate = self::get_live_rate($currency);
        $fiat_amount = $order->get_total('');
        
        if($rate != 0)
            $dinastycoin_amount = 1e8 * $fiat_amount / $rate;
        else{
            // Critical, the price has not been retrivied.
            $dinastycoin_amount = -1;
            $error_message = "The price for Dinastycoin could not be retrieved. Please contact the merchant.";
            self::$log->add('Dinastycoin_Payments', "[ERROR] Impossible to retrieve price for order: ".$order_id);
            wc_add_notice( __('Payment error:', 'woothemes') . $error_message, 'error' );
            return;
        }
        
        if(self::$discount)
            $dinastycoin_amount = $dinastycoin_amount - $dinastycoin_amount * self::$discount / 100;

        $dinastycoin_amount = intval($dinastycoin_amount * DINASTYCOIN_GATEWAY_ATOMIC_UNITS_POW);

        $query = $wpdb->prepare("INSERT INTO $table_name (order_id, payment_id, currency, rate, amount) VALUES (%d, %s, %s, %d, %d)", array($order_id, $payment_id, $currency, $rate, $dinastycoin_amount));
        $wpdb->query($query);

        $order->update_status('on-hold', __('Awaiting offline payment', 'dinastycoin_gateway'));
        wc_reduce_stock_levels(  $order_id ); 
        WC()->cart->empty_cart(); // Remove cart

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    /*
     * function for verifying payments
     * This cron runs every 30 seconds
     */
    public static function do_update_event()
    {
        global $wpdb;

        // Get Live Price
        $currencies = implode(',', self::$currencies);
        $api_link = 'https://api.coingecko.com/api/v3/simple/price?ids=dinastycoin&vs_currencies='.$currencies;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $api_link,
        ));
        $resp = curl_exec($curl);
        curl_close($curl);
        $price = json_decode($resp, true);

        if(isset($price)) {
            $table_name = $wpdb->prefix.'dinastycoin_gateway_live_rates';
            foreach($price['dinastycoin'] as $currency=>$rate) {
                // shift decimal eight places for precise int storage
                $rate = intval($rate * 1e8);
                $query = $wpdb->prepare("INSERT INTO $table_name (currency, rate, updated) VALUES (%s, %d, NOW()) ON DUPLICATE KEY UPDATE rate=%d, updated=NOW()", array($currency, $rate, $rate));
                $result = $wpdb->query($query);
              	if(!$result){
                    self::$log->add('Dinastycoin_Payments', "[ERROR] Impossible to write DB. Please check your DB connection or enable Debugging.");
                }
            }
        }
        else{
             self::$log->add('Dinastycoin_Payments', "[ERROR] Unable to fetch prices from coingecko.com.");
        }


        // Get current network/wallet height
        if(self::$confirm_type == 'dinastycoin-wallet-rpc')
            $height = self::$dinastycoin_wallet_rpc->getheight();
        else
            $height = self::$dinastycoin_explorer_tools->getheight();
        set_transient('dinastycoin_gateway_network_height', $height);

        // Get pending payments
        $table_name_1 = $wpdb->prefix.'dinastycoin_gateway_quotes';
        $table_name_2 = $wpdb->prefix.'dinastycoin_gateway_quotes_txids';

        $query = $wpdb->prepare("SELECT *, $table_name_1.payment_id AS payment_id, $table_name_1.amount AS amount_total, $table_name_2.amount AS amount_paid, NOW() as now FROM $table_name_1 LEFT JOIN $table_name_2 ON $table_name_1.payment_id = $table_name_2.payment_id WHERE pending=1", array());
        $rows = $wpdb->get_results($query);

        $pending_payments = array();

        // Group the query into distinct orders by payment_id
        foreach($rows as $row) {
            if(!isset($pending_payments[$row->payment_id]))
                $pending_payments[$row->payment_id] = array(
                    'quote' => null,
                    'txs' => array()
                );
            $pending_payments[$row->payment_id]['quote'] = $row;
            if($row->txid)
                $pending_payments[$row->payment_id]['txs'][] = $row;
        }

        // Loop through each pending payment and check status
        foreach($pending_payments as $pending) {
            $quote = $pending['quote'];
            $old_txs = $pending['txs'];
            $order_id = $quote->order_id;
            $order = wc_get_order($order_id);
            $payment_id = self::sanatize_id($quote->payment_id);
            $amount_dinastycoin = $quote->amount_total;

            if(self::$confirm_type == 'dinastycoin-wallet-rpc')
                $new_txs = self::check_payment_rpc($payment_id);
            else
                $new_txs = self::check_payment_explorer($payment_id);

            foreach($new_txs as $new_tx) {
                $is_new_tx = true;
                foreach($old_txs as $old_tx) {
                    if($new_tx['txid'] == $old_tx->txid && $new_tx['amount'] == $old_tx->amount_paid) {
                        $is_new_tx = false;
                        break;
                    }
                }
                if($is_new_tx) {
                    $old_txs[] = (object) $new_tx;
                }

                $query = $wpdb->prepare("INSERT INTO $table_name_2 (payment_id, txid, amount, height) VALUES (%s, %s, %d, %d) ON DUPLICATE KEY UPDATE height=%d", array($payment_id, $new_tx['txid'], $new_tx['amount'], $new_tx['height'], $new_tx['height']));
                $wpdb->query($query);
            }

            $txs = $old_txs;
            $heights = array();
            $amount_paid = 0;
            foreach($txs as $tx) {
                $amount_paid += $tx->amount;
                $heights[] = $tx->height;
            }

            $paid = $amount_paid > $amount_dinastycoin - DINASTYCOIN_GATEWAY_ATOMIC_UNIT_THRESHOLD;

            if($paid) {
                if(self::$confirms == 0) {
                    $confirmed = true;
                } else {
                    $highest_block = max($heights);
                    if($height - $highest_block >= self::$confirms && !in_array(0, $heights)) {
                        $confirmed = true;
                    } else {
                        $confirmed = false;
                    }
                }
            } else {
                $confirmed = false;
            }

            if($paid && $confirmed) {
                self::$log->add('Dinastycoin_Payments', "[SUCCESS] Payment has been confirmed for order id $order_id and payment id $payment_id");
                $query = $wpdb->prepare("UPDATE $table_name_1 SET confirmed=1,paid=1,pending=0 WHERE payment_id=%s", array($payment_id));
                $wpdb->query($query);

                unset(self::$payment_details[$order_id]);

                if(self::is_virtual_in_cart($order_id) == true){
                    $order->update_status('completed', __('Payment has been received.', 'dinastycoin_gateway'));
                } else {
                    $order->update_status('processing', __('Payment has been received.', 'dinastycoin_gateway'));
                }

            } else if($paid) {
                self::$log->add('Dinastycoin_Payments', "[SUCCESS] Payment has been received for order id $order_id and payment id $payment_id");
                $query = $wpdb->prepare("UPDATE $table_name_1 SET paid=1 WHERE payment_id=%s", array($payment_id));
                $wpdb->query($query);

                unset(self::$payment_details[$order_id]);

            } else {
                $timestamp_created = new DateTime($quote->created);
                $timestamp_now = new DateTime($quote->now);
                $order_age_seconds = $timestamp_now->getTimestamp() - $timestamp_created->getTimestamp();
                if($order_age_seconds > self::$valid_time) {
                    self::$log->add('Dinastycoin_Payments', "[FAILED] Payment has expired for order id $order_id and payment id $payment_id");
                    $query = $wpdb->prepare("UPDATE $table_name_1 SET pending=0 WHERE payment_id=%s", array($payment_id));
                    $wpdb->query($query);

                    unset(self::$payment_details[$order_id]);

                    $order->update_status('cancelled', __('Payment has expired.', 'dinastycoin_gateway'));
                }
            }
        }
    }

    protected static function check_payment_rpc($subaddress)
    {
        $txs = array();
        $address_index = self::$dinastycoin_wallet_rpc->get_address_index($subaddress);
        if(isset($address_index['index']['minor'])){
          $address_index = $address_index['index']['minor'];
        }
        else {
          self::$log->add('Dinastycoin_Gateway', '[ERROR] Couldn\'t get address index of subaddress: ' . $subaddress);
          return $txs;
        }
        $payments = self::$dinastycoin_wallet_rpc->get_transfers(array( 'in' => true, 'pool' => true, 'subaddr_indices' => array($address_index)));
        if(isset($payments['in'])) {
          foreach($payments['in'] as $payment) {
              $txs[] = array(
                  'amount' => $payment['amount'],
                  'txid' => $payment['txid'],
                  'height' => $payment['height']
              );
          }
        }
        if(isset($payments['pool'])) {
          foreach($payments['pool'] as $payment) {
              $txs[] = array(
                  'amount' => $payment['amount'],
                  'txid' => $payment['txid'],
                  'height' => $payment['height']
              );
          }
        }
        return $txs;
    }

    public static function check_payment_explorer($payment_id)
    {
        $txs = array();
        $outputs = self::$dinastycoin_explorer_tools->get_outputs(self::$address, self::$viewkey);
        foreach($outputs as $payment) {
            if($payment['payment_id'] == $payment_id) {
                $txs[] = array(
                    'amount' => $payment['amount'],
                    'txid' => $payment['tx_hash'],
                    'height' => $payment['block_no']
                );
            }
        }
        return $txs;
    }

    protected static function get_payment_details($order_id)
    {
        if(!is_integer($order_id))
            $order_id = $order_id->get_id();

        if(isset(self::$payment_details[$order_id]))
            return self::$payment_details[$order_id];

        global $wpdb;
        $table_name_1 = $wpdb->prefix.'dinastycoin_gateway_quotes';
        $table_name_2 = $wpdb->prefix.'dinastycoin_gateway_quotes_txids';
        $query = $wpdb->prepare("SELECT *, $table_name_1.payment_id AS payment_id, $table_name_1.amount AS amount_total, $table_name_2.amount AS amount_paid, NOW() as now FROM $table_name_1 LEFT JOIN $table_name_2 ON $table_name_1.payment_id = $table_name_2.payment_id WHERE order_id=%d", array($order_id));
        $details = $wpdb->get_results($query);
        if (count($details)) {
            $txs = array();
            $heights = array();
            $amount_paid = 0;
            foreach($details as $tx) {
                if(!isset($tx->txid))
                    continue;
                $txs[] = array(
                    'txid' => $tx->txid,
                    'height' => $tx->height,
                    'amount' => $tx->amount_paid,
                    'amount_formatted' => self::format_dinastycoin($tx->amount_paid)
                );
                $amount_paid += $tx->amount_paid;
                $heights[] = $tx->height;
            }

            usort($txs, function($a, $b) {
                if($a['height'] == 0) return -1;
                return $b['height'] - $a['height'];
            });

            if(count($heights) && !in_array(0, $heights)) {
                $height = get_transient('dinastycoin_gateway_network_height');
                $highest_block = max($heights);
                $confirms = $height - $highest_block;
                $blocks_to_confirm = self::$confirms - $confirms;
            } else {
                $blocks_to_confirm = self::$confirms;
            }
            $time_to_confirm = self::format_seconds_to_time($blocks_to_confirm * DINASTYCOIN_GATEWAY_DIFFICULTY_TARGET);

            $amount_total = $details[0]->amount_total;
            $amount_due = max(0, $amount_total - $amount_paid);

            $timestamp_created = new DateTime($details[0]->created);
            $timestamp_now = new DateTime($details[0]->now);

            $order_age_seconds = $timestamp_now->getTimestamp() - $timestamp_created->getTimestamp();
            $order_expires_seconds = self::$valid_time - $order_age_seconds;

            $address = self::$address;
            $payment_id = self::sanatize_id($details[0]->payment_id);

            if(self::$confirm_type == 'dinastycoin-wallet-rpc') {
                $integrated_addr = $payment_id;
            } else {
                if ($address) {
                    $decoded_address = self::$cryptonote->decode_address($address);
                    $pub_spendkey = $decoded_address['spendkey'];
                    $pub_viewkey = $decoded_address['viewkey'];
                    $integrated_addr = self::$cryptonote->integrated_addr_from_keys($pub_spendkey, $pub_viewkey, $payment_id);
                } else {
                    self::$log->add('Dinastycoin_Gateway', '[ERROR] Merchant has not set Dinastycoin address');
                    return '[ERROR] Merchant has not set Dinastycoin address';
                }
            }

            $status = '';
            $paid = $details[0]->paid == 1;
            $confirmed = $details[0]->confirmed == 1;
            $pending = $details[0]->pending == 1;

            if($confirmed) {
                $status = 'confirmed';
            } else if($paid) {
                $status = 'paid';
            } else if($pending && $order_expires_seconds > 0) {
                if(count($txs)) {
                    $status = 'partial';
                } else {
                    $status = 'unpaid';
                }
            } else {
                if(count($txs)) {
                    $status = 'expired_partial';
                } else {
                    $status = 'expired';
                }
            }

            $amount_formatted = self::format_dinastycoin($amount_due);
            $qrcode_uri = 'dinastycoin:'.$integrated_addr.'?tx_amount='.$amount_formatted.'&tx_payment_id='.$payment_id;
            $my_order_url = wc_get_endpoint_url('view-order', $order_id, wc_get_page_permalink('myaccount'));

            $payment_details = array(
                'order_id' => $order_id,
                'payment_id' => $payment_id,
                'integrated_address' => $integrated_addr,
                'qrcode_uri' => $qrcode_uri,
                'my_order_url' => $my_order_url,
                'rate' => $details[0]->rate,
                'rate_formatted' => sprintf('%.8f', $details[0]->rate / 1e8),
                'currency' => $details[0]->currency,
                'amount_total' => $amount_total,
                'amount_paid' => $amount_paid,
                'amount_due' => $amount_due,
                'amount_total_formatted' => self::format_dinastycoin($amount_total),
                'amount_paid_formatted' => self::format_dinastycoin($amount_paid),
                'amount_due_formatted' => self::format_dinastycoin($amount_due),
                'status' => $status,
                'created' => $details[0]->created,
                'order_age' => $order_age_seconds,
                'order_expires' => self::format_seconds_to_time($order_expires_seconds),
                'blocks_to_confirm' => $blocks_to_confirm,
                'time_to_confirm' => $time_to_confirm,
                'txs' => $txs
            );
            self::$payment_details[$order_id] = $payment_details;
            return $payment_details;
        } else {
            return '[ERROR] Quote not found';
        }

    }

    public static function get_payment_details_ajax() {

        $user = wp_get_current_user();
        if($user === 0)
            self::ajax_output(array('error' => '[ERROR] User not logged in'));
        
        if(isset($_GET['order_id'])){
            $order_id = preg_replace("/[^0-9]+/", "", $_GET['order_id']);
            $order = wc_get_order($order_id);
            
            if($order->get_customer_id() != $user->ID)
                self::ajax_output(array('error' => '[ERROR] Order does not belong to this user'));

            if($order->get_payment_method() != self::$_id)
                self::ajax_output(array('error' => '[ERROR] Order not paid for with Dinastycoin'));
    
            $details = self::get_payment_details($order);
            if(!is_array($details))
                self::ajax_output(array('error' => $details));
    
            self::ajax_output($details);
        }
    }
    public static function ajax_output($response) {
        header('Content-type: application/json');
        if (ob_get_length() > 0){
            ob_clean();
        }
        echo json_encode($response);
        wp_die();
    }

    public static function admin_order_page($post)
    {
        $order = wc_get_order($post->ID);
        if($order->get_payment_method() != self::$_id)
            return;

        $method_title = self::$_title;
        $details = self::get_payment_details($order);
        if(!is_array($details)) {
            $error = $details;
            include DINASTYCOIN_GATEWAY_PLUGIN_DIR . '/templates/dinastycoin-gateway/admin/order-history-error-page.php';
            return;
        }
        include DINASTYCOIN_GATEWAY_PLUGIN_DIR . '/templates/dinastycoin-gateway/admin/order-history-page.php';
    }

    public static function customer_order_page($order)
    {
        if(is_integer($order)) {
            $order_id = $order;
            $order = wc_get_order($order_id);
        } else {
            $order_id = $order->get_id();
        }

        if($order->get_payment_method() != self::$_id)
            return;

        $method_title = self::$_title;
        $details = self::get_payment_details($order_id);
        if(!is_array($details)) {
            $error = $details;
            include DINASTYCOIN_GATEWAY_PLUGIN_DIR . '/templates/dinastycoin-gateway/customer/order-error-page.php';
            return;
        }
        $show_qr = self::$show_qr;
        $details_json = json_encode($details);
        $ajax_url = WC_AJAX::get_endpoint('dinastycoin_gateway_payment_details');
        include DINASTYCOIN_GATEWAY_PLUGIN_DIR . '/templates/dinastycoin-gateway/customer/order-page.php';
    }

    public static function customer_order_email($order)
    {
        if(is_integer($order)) {
            $order_id = $order;
            $order = wc_get_order($order_id);
        } else {
            $order_id = $order->get_id();
        }

        if($order->get_payment_method() != self::$_id)
            return;

        $method_title = self::$_title;
        $details = self::get_payment_details($order_id);
        if(!is_array($details)) {
            include DINASTYCOIN_GATEWAY_PLUGIN_DIR . '/templates/dinastycoin-gateway/customer/order-email-error-block.php';
            return;
        }
        include DINASTYCOIN_GATEWAY_PLUGIN_DIR . '/templates/dinastycoin-gateway/customer/order-email-block.php';
    }

    public static function get_id()
    {
        return self::$_id;
    }

    public static function get_confirm_type()
    {
        return self::$confirm_type;
    }

    public static function use_qr_code()
    {
        return self::$show_qr;
    }

    public static function use_dinastycoin_price()
    {
        return self::$use_dinastycoin_price;
    }


    public static function convert_wc_price($price, $currency)
    {
        $rate = self::get_live_rate($currency);
        $dinastycoin_amount = intval(DINASTYCOIN_GATEWAY_ATOMIC_UNITS_POW * 1e8 * $price / $rate) / DINASTYCOIN_GATEWAY_ATOMIC_UNITS_POW;
        $dinastycoin_amount_formatted = sprintf('%.'.self::$use_dinastycoin_price_decimals.'f', $dinastycoin_amount);

        return <<<HTML
            <span class="woocommerce-Price-amount amount" data-price="$price" data-currency="$currency"
        data-rate="$rate" data-rate-type="live">
            $dinastycoin_amount_formatted
            <span class="woocommerce-Price-currencySymbol">DCY</span>
        </span>

HTML;
    }

    public static function convert_wc_price_order($price_html, $order)
    {
        if($order->get_payment_method() != self::$_id)
            return $price_html;

        $order_id = $order->get_id();
        $payment_details = self::get_payment_details($order_id);
        if(!is_array($payment_details))
            return $price_html;

        // Experimental regex, may fail with other custom price formatters
        $match_ok = preg_match('/data-price="([^"]*)"/', $price_html, $matches);
        if($match_ok !== 1) // regex failed
            return $price_html;

        $price = array_pop($matches);
        $currency = $payment_details['currency'];
        $rate = $payment_details['rate'];
        $dinastycoin_amount = intval(DINASTYCOIN_GATEWAY_ATOMIC_UNITS_POW * 1e8 * $price / $rate) / DINASTYCOIN_GATEWAY_ATOMIC_UNITS_POW;
        $dinastycoin_amount_formatted = sprintf('%.'.DINASTYCOIN_GATEWAY_ATOMIC_UNITS.'f', $dinastycoin_amount);

        return <<<HTML
            <span class="woocommerce-Price-amount amount" data-price="$price" data-currency="$currency"
        data-rate="$rate" data-rate-type="fixed">
            $dinastycoin_amount_formatted
            <span class="woocommerce-Price-currencySymbol">DCY</span>
        </span>

HTML;
    }

    public static function get_live_rate($currency)
    {
        if(isset(self::$rates[$currency]))
            return self::$rates[$currency];

        global $wpdb;
        $table_name = $wpdb->prefix.'dinastycoin_gateway_live_rates';
        $query = $wpdb->prepare("SELECT rate FROM $table_name WHERE currency=%s", array($currency));

        $rate = $wpdb->get_row($query)->rate;
        self::$rates[$currency] = $rate;

        return $rate;
    }

    protected static function sanatize_id($payment_id)
    {
        // Limit payment id to alphanumeric characters
        $sanatized_id = preg_replace("/[^a-zA-Z0-9]+/", "", $payment_id);
        return $sanatized_id;
    }

    protected static function is_virtual_in_cart($order_id)
    {
        $order = wc_get_order($order_id);
        $items = $order->get_items();
        $cart_size = count($items);
        $virtual_items = 0;

        foreach ( $items as $item ) {
            $product = new WC_Product( $item['product_id'] );
            if ($product->is_virtual()) {
                $virtual_items += 1;
            }
        }
        return $virtual_items == $cart_size;
    }

    public static function format_dinastycoin($atomic_units) {
        return sprintf(DINASTYCOIN_GATEWAY_ATOMIC_UNITS_SPRINTF, $atomic_units / DINASTYCOIN_GATEWAY_ATOMIC_UNITS_POW);
    }

    public static function format_seconds_to_time($seconds)
    {
        $units = array();

        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        $diff = $dtF->diff($dtT);

        $d = $diff->format('%a');
        $h = $diff->format('%h');
        $m = $diff->format('%i');

        if($d == 1)
            $units[] = "$d day";
        else if($d > 1)
            $units[] = "$d days";

        if($h == 0 && $d != 0)
            $units[] = "$h hours";
        else if($h == 1)
            $units[] = "$h hour";
        else if($h > 0)
            $units[] = "$h hours";

        if($m == 1)
            $units[] = "$m minute";
        else
            $units[] = "$m minutes";

        return implode(', ', $units) . ($seconds < 0 ? ' ago' : '');
    }

}
