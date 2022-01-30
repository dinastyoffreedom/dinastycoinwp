<?php

defined( 'ABSPATH' ) || exit;

return array(
    'enabled' => array(
        'title' => __('Enable / Disable', 'dinastycoin_gateway'),
        'label' => __('Enable this payment gateway', 'dinastycoin_gateway'),
        'type' => 'checkbox',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'dinastycoin_gateway'),
        'type' => 'text',
        'desc_tip' => __('Payment title the customer will see during the checkout process.', 'dinastycoin_gateway'),
        'default' => __('Dinastycoin Gateway', 'dinastycoin_gateway')
    ),
    'description' => array(
        'title' => __('Description', 'dinastycoin_gateway'),
        'type' => 'textarea',
        'desc_tip' => __('Payment description the customer will see during the checkout process.', 'dinastycoin_gateway'),
        'default' => __('Pay securely using Dinastycoin. You will be provided payment details after checkout.', 'dinastycoin_gateway')
    ),
    'discount' => array(
        'title' => __('Discount for using Dinastycoin', 'dinastycoin_gateway'),
        'desc_tip' => __('Provide a discount to your customers for making a private payment with Dinastycoin', 'dinastycoin_gateway'),
        'description' => __('Enter a percentage discount (i.e. 5 for 5%) or leave this empty if you do not wish to provide a discount', 'dinastycoin_gateway'),
        'type' => __('number'),
        'default' => '0'
    ),
    'valid_time' => array(
        'title' => __('Order valid time', 'dinastycoin_gateway'),
        'desc_tip' => __('Amount of time order is valid before expiring', 'dinastycoin_gateway'),
        'description' => __('Enter the number of seconds that the funds must be received in after order is placed. 3600 seconds = 1 hour', 'dinastycoin_gateway'),
        'type' => __('number'),
        'default' => '3600'
    ),
    'confirms' => array(
        'title' => __('Number of confirmations', 'dinastycoin_gateway'),
        'desc_tip' => __('Number of confirms a transaction must have to be valid', 'dinastycoin_gateway'),
        'description' => __('Enter the number of confirms that transactions must have. Enter 0 to zero-confim. Each confirm will take approximately four minutes', 'dinastycoin_gateway'),
        'type' => __('number'),
        'default' => '5'
    ),
    'confirm_type' => array(
        'title' => __('Confirmation Type', 'dinastycoin_gateway'),
        'desc_tip' => __('Select the method for confirming transactions', 'dinastycoin_gateway'),
        'description' => __('Select the method for confirming transactions', 'dinastycoin_gateway'),
        'type' => 'select',
        'options' => array(
            'viewkey'        => __('viewkey', 'dinastycoin_gateway'),
            'dinastycoin-wallet-rpc' => __('dinastycoin-wallet-rpc', 'dinastycoin_gateway')
        ),
        'default' => 'viewkey'
    ),
    'dinastycoin_address' => array(
        'title' => __('Dinastycoin Address', 'dinastycoin_gateway'),
        'label' => __('Useful for people that have not a daemon online'),
        'type' => 'text',
        'desc_tip' => __('Dinastycoin Wallet Address (DinastycoinL)', 'dinastycoin_gateway')
    ),
    'viewkey' => array(
        'title' => __('Secret Viewkey', 'dinastycoin_gateway'),
        'label' => __('Secret Viewkey'),
        'type' => 'text',
        'desc_tip' => __('Your secret Viewkey', 'dinastycoin_gateway')
    ),
    'daemon_host' => array(
        'title' => __('Dinastycoin wallet RPC Host/IP', 'dinastycoin_gateway'),
        'type' => 'text',
        'desc_tip' => __('This is the Daemon Host/IP to authorize the payment with', 'dinastycoin_gateway'),
        'default' => '127.0.0.1',
    ),
    'daemon_port' => array(
        'title' => __('Dinastycoin wallet RPC port', 'dinastycoin_gateway'),
        'type' => __('number'),
        'desc_tip' => __('This is the Wallet RPC port to authorize the payment with', 'dinastycoin_gateway'),
        'default' => '18080',
    ),
    'testnet' => array(
        'title' => __(' Testnet', 'dinastycoin_gateway'),
        'label' => __(' Check this if you are using testnet ', 'dinastycoin_gateway'),
        'type' => 'checkbox',
        'description' => __('Advanced usage only', 'dinastycoin_gateway'),
        'default' => 'no'
    ),
    'javascript' => array(
        'title' => __(' Javascript', 'dinastycoin_gateway'),
        'label' => __(' Check this to ENABLE Javascript in Checkout page ', 'dinastycoin_gateway'),
        'type' => 'checkbox',
        'default' => 'no'
     ),
    'onion_service' => array(
        'title' => __(' SSL warnings ', 'dinastycoin_gateway'),
        'label' => __(' Check to Silence SSL warnings', 'dinastycoin_gateway'),
        'type' => 'checkbox',
        'description' => __('Check this box if you are running on an Onion Service (Suppress SSL errors)', 'dinastycoin_gateway'),
        'default' => 'no'
    ),
    'show_qr' => array(
        'title' => __('Show QR Code', 'dinastycoin_gateway'),
        'label' => __('Show QR Code', 'dinastycoin_gateway'),
        'type' => 'checkbox',
        'description' => __('Enable this to show a QR code after checkout with payment details.'),
        'default' => 'no'
    ),
    'use_dinastycoin_price' => array(
        'title' => __('Show Prices in Dinastycoin', 'dinastycoin_gateway'),
        'label' => __('Show Prices in Dinastycoin', 'dinastycoin_gateway'),
        'type' => 'checkbox',
        'description' => __('Enable this to convert ALL prices on the frontend to Dinastycoin (experimental)'),
        'default' => 'no'
    ),
    'use_dinastycoin_price_decimals' => array(
        'title' => __('Display Decimals', 'dinastycoin_gateway'),
        'type' => __('number'),
        'description' => __('Number of decimal places to display on frontend. Upon checkout exact price will be displayed.'),
        'default' => 9,
    ),
);
