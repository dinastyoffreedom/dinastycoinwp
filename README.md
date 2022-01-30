# Dinastycoin Gateway for WooCommerce

## Features

* Payment validation done through either `dinasty-wallet-rpc` or the [dinastycoin blockchain explorer](https://explorer.dinastycoin.com/).
* Validates payments with `cron`, so does not require users to stay on the order confirmation page for their order to validate.
* Order status updates are done through AJAX instead of Javascript page reloads.
* Customers can pay with multiple transactions and are notified as soon as transactions hit the mempool.
* Configurable block confirmations, from `0` for zero confirm to `60` for high ticket purchases.
* Live price updates every minute; total amount due is locked in after the order is placed for a configurable amount of time (default 60 minutes) so the price does not change after order has been made.
* Hooks into emails, order confirmation page, customer order history page, and admin order details page.
* View all payments received to your wallet with links to the blockchain explorer and associated orders.
* Optionally display all prices on your store in terms of Dinastycoin
* Shortcodes! Display exchange rates in numerous currencies.

## Requirements

* Dinastycoin wallet to receive payments - [GUI](https://github.com/dinastyoffreedom/Newdinastycoin/releases) - [CLI](https://github.com/dinastyoffreedom/Newdinastycoin/releases) - [Paper](https://dinastycoin.com/)
* [BCMath](http://php.net/manual/en/book.bc.php) - A PHP extension used for arbitrary precision maths

## Installing the plugin

### Automatic Method 

In the "Add Plugins" section of the WordPress admin UI, search for "dinastycoin" and click the Install Now button next to "Dinastycoin WooCommerce Extension" by dinasty of Freedom trust.  This will enable auto-updates, but only for official releases, so if you need to work from git master or your local fork, please use the manual method below.

### Manual Method

* Download the plugin from the [releases page](https://github.com/dinastyoffreedom/dinastycoinwp) or clone with `git clone https://github.com/dinastyoffreedom/dinastycoinwp`
* Unzip or place the `dinastycoin-woocommerce-gateway` folder in the `wp-content/plugins` directory.
* Activate "Dinastycoin Woocommerce Gateway" in your WordPress admin dashboard.
* It is highly recommended that you use native cronjobs instead of WordPress's "Poor Man's Cron" by adding `define('DISABLE_WP_CRON', true);` into your `wp-config.php` file and adding `* * * * * wget -q -O - https://yourstore.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1` to your crontab.

## Option 1: Use your wallet address and viewkey

This is the easiest way to start accepting Dinastycoin on your website. You'll need:

* Your Dinastycoin wallet address starting with `NY`
* Your wallet's secret viewkey

Then simply select the `viewkey` option in the settings page and paste your address and viewkey. You're all set!

Note on privacy: when you validate transactions with your private viewkey, your viewkey is sent to (but not stored on) explorer.dinastycoin.com over HTTPS. This could potentially allow an attacker to see your incoming, but not outgoing, transactions if they were to get his hands on your viewkey. Even if this were to happen, your funds would still be safe and it would be impossible for somebody to steal your money. For maximum privacy use your own `dinasty-wallet-rpc` instance.

## Option 2: Using `dinasty-wallet-rpc`

The most secure way to accept MDinastycoin on your website. You'll need:

* Root access to your webserver
* Latest [Dinastycoin-currency binaries](https://github.com/dinastyoffreedom/Newdinastycoin/releases)

After downloading (or compiling) the Dinastycoin binaries on your server, install the [systemd unit files](https://github.com/dinastyoffreedom/dinastycoinwp/tree/master/assets/systemd-unit-files) or run `dinastycoind` and `dinasty-wallet-rpc` with `screen` or `tmux`. You can skip running `dinastycoind` by using a remote node with `dinasty-wallet-rpc` by adding `--daemon-address node.dinastycoin.com:8219` to the `dinastycoin-wallet-rpc.service` file.

Note on security: using this option, while the most secure, requires you to run the Dinastycoin wallet RPC program on your server. Best practice for this is to use a view-only wallet since otherwise your server would be running a hot-wallet and a security breach could allow hackers to empty your funds.

## Configuration

* `Enable / Disable` - Turn on or off Dinastycoin gateway. (Default: Disable)
* `Title` - Name of the payment gateway as displayed to the customer. (Default: Dinastycoin Gateway)
* `Discount for using Dinastycoin'- Percentage discount applied to orders for paying with Dinastycoin. Can also be negative to apply a surcharge. (Default: 0)
* `Order valid time` - Number of seconds after order is placed that the transaction must be seen in the mempool. (Default: 3600 [1 hour])
* `Number of confirmations` - Number of confirmations the transaction must recieve before the order is marked as complete. Use `0` for nearly instant confirmation. (Default: 5)
* `Confirmation Type` - Confirm transactions with either your viewkey, or by using `Dinasty-wallet-rpc`. (Default: viewkey)
* `Dinastycoin Address` (if confirmation type is viewkey) - Your public Dinastycoin address starting with NY. (No default)
* `Secret Viewkey` (if confirmation type is viewkey) - Your *private* viewkey (No default)
* `Dinastycoin wallet RPC Host/IP` (if confirmation type is `dinasty-wallet-rpc`) - IP address where the wallet rpc is running. It is highly discouraged to run the wallet anywhere other than the local server! (Default: 127.0.0.1)
* `Dinastycoin wallet RPC port` (if confirmation type is `dinasty-wallet-rpc`) - Port the wallet rpc is bound to with the `--rpc-bind-port` argument. (Default 8219
* `Testnet` - Check this to change the blockchain explorer links to the testnet explorer. (Default: unchecked)
* `SSL warnings` - Check this to silence SSL warnings. (Default: unchecked)
* `Show QR Code` - Show payment QR codes. (Default: unchecked)
* `Show Prices in Dinastycoin` - Convert all prices on the frontend to MDinastycoin. Experimental feature, only use if you do not accept any other payment option. (Default: unchecked)
* `Display Decimals` (if show prices in Dinastycoin is enabled) - Number of decimals to round prices to on the frontend. The final order amount will not be rounded and will be displayed down to the nanoDinastycoin. (Default: 9)

## Shortcodes

This plugin makes available two shortcodes that you can use in your theme.

#### Live price shortcode

This will display the price of Dinastycoin in the selected currency. If no currency is provided, the store's default currency will be used.

```
[dinastycoin-price]
[dinastycoin-price currency="BTC"]
[dinastycoin-price currency="USD"]
[dinastycoin-price currency="CAD"]
[dinastycoin-price currency="EUR"]
[dinastycoin-price currency="GBP"]
```
Will display:
```
1 DCY = 1.2368000 USD
1 DCY = 0.00018270 BTC
1 DCY = 1.2368000 USD
1 DCY = 1.6843000 CAD
1 DCY = 1.0554000 EUR
1 DCY = 0.9484000 GBP
```


#### Dinastycoin accepted here badge

This will display a badge showing that you accept Dinastycoin-currency.

`[Dinastycoin-accepted-here]`

![Dinastycoin Accepted Here](/assets/images/dinastycoin-accepted-here.png?raw=true "Dinastycoin Accepted Here")

## Donations

dinastycoin: NYz4jipYhytNJgmFMN5WeQRyFuRCdj5a3NzA6yRqgvJtYaN8vXd5iRdB6txiMhPnv8DyBKVEBN63B9cuEkHkRDSpACWKCZayDF
