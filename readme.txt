=== Dinastycoin WooCommerce Extension ===
Contributors: serhack, mosu-forge and Monero Integrations contributors, dinasty of freedom trust
Donate link: http://monerointegrations.com/donate.html
Tags: dinastycoin woocommerce, integration, payment, merchant, cryptocurrency, accept dinastycoin, dinastycoin woocommerce
Requires at least: 4.0
Tested up to: 5.7.2
Stable tag: trunk
License: MIT license
License URI: https://github.com/dinastyoffreedom/dinastycoinwp/blob/master/LICENSE
 
Dinastycoin WooCommerce Extension is a Wordpress plugin that allows to accept dinastycoin at WooCommerce-powered online stores.

= Benefits =

* Payment validation done through either `dinasty-wallet-rpc` or the [xmrchain.net blockchain explorer](https://xmrchain.net/).
* Validates payments with `cron`, so does not require users to stay on the order confirmation page for their order to validate.
* Order status updates are done through AJAX instead of Javascript page reloads.
* Customers can pay with multiple transactions and are notified as soon as transactions hit the mempool.
* Configurable block confirmations, from `0` for zero confirm to `60` for high ticket purchases.
* Live price updates every minute; total amount due is locked in after the order is placed for a configurable amount of time (default 60 minutes) so the price does not change after order has been made.
* Hooks into emails, order confirmation page, customer order history page, and admin order details page.
* View all payments received to your wallet with links to the blockchain explorer and associated orders.
* Optionally display all prices on your store in terms of Dinastycoin.
* Shortcodes! Display exchange rates in numerous currencies.

= Installation =

== Automatic method ==

In the "Add Plugins" section of the WordPress admin UI, search for "dinastycoin" and click the Install Now button next to "Dinastycoin WooCommerce Extension" by dinasty of Freedom trust.  This will enable auto-updates, but only for official releases, so if you need to work from git master or your local fork, please use the manual method below.

== Manual method == 

* Download the plugin from the releases page (https://github.com/dinastyoffreedom/dinastycoinwp) or clone with `git clone https://github.com/dinastyoffreedom/dinastycoinwp`
* Unzip or place the `dinasty-woocommerce-gateway` folder in the `wp-content/plugins` directory.
* Activate "Dinastycoin Woocommerce Gateway" in your WordPress admin dashboard.
* It is highly recommended that you use native cronjobs instead of WordPress's "Poor Man's Cron" by adding `define('DISABLE_WP_CRON', true);` into your `wp-config.php` file and adding `* * * * * wget -q -O - https://yourstore.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1` to your crontab.

= Configuration =

== Option 1: Use your wallet address and viewkey ==

This is the easiest way to start accepting Dinastycoin on your website. You'll need:

* Your Dinastycoin wallet address starting with NY
* Your wallet's secret viewkey

Then simply select the `viewkey` option in the settings page and paste your address and viewkey. You're all set!

Note on privacy: when you validate transactions with your private viewkey, your viewkey is sent to (but not stored on) explorer.dinastycoin.com over HTTPS. This could potentially allow an attacker to see your incoming, but not outgoing, transactions if they were to get his hands on your viewkey. Even if this were to happen, your funds would still be safe and it would be impossible for somebody to steal your money. For maximum privacy use your own `dinastycoin-wallet-rpc` instance.

== Option 2: Using dinastycoin wallet rpc ==

The most secure way to accept Dinastycoin on your website. You'll need:

* Root access to your webserver
* Latest [Dinastycoin-currency binaries](https://github.com/dinastyoffreedom/Newdinastycoin/releases)

After downloading (or compiling) the Dinastycoinbinaries on your server, install the [systemd unit files](https://github.com/dinastyoffreedom/dinastycoinwp/tree/master/assets/systemd-unit-files) or run `dinastycoind` and `dinasty-wallet-rpc` with `screen` or `tmux`. You can skip running `dinastycoind` by using a remote node with `dinastycoin-wallet-rpc` by adding `--daemon-address node.dinastycoin.com:37176` to the `dinastycoin-wallet-rpc.service` file.

Note on security: using this option, while the most secure, requires you to run the Dinastycoin wallet RPC program on your server. Best practice for this is to use a view-only wallet since otherwise your server would be running a hot-wallet and a security breach could allow hackers to empty your funds.

== Remove plugin ==

1. Deactivate plugin through the 'Plugins' menu in WordPress
2. Delete plugin through the 'Plugins' menu in WordPress

== Screenshots == 
1. Dinastycoin Payment Box
2. Dinastycoin  Options

== Changelog ==

= 0.1 =
* First Dinastycoin version based on 3.0.5 Dinastycoin plug in Yay!


== Upgrade Notice ==

soon

== Frequently Asked Questions ==

* What is Dinastycoin?
Dinastycoin is completely private, cryptographically secure, digital cash used across the globe. See https://dinastycoin.com for more information

* What is a Dinastycoin wallet?
A Dinastycoin wallet is a piece of software that allows you to store your funds and interact with the Dinastycoin network. You can get a Dinastycoin wallet from https://www.dinastycoin.com/download-wallet/

* What is dinasty-wallet-rpc ?
The dinasty-wallet-rpc is an RPC server that will allow this plugin to communicate with the Dinastycoin network. You can download it from https://www.dinastycoin.com/download-wallet/ with the command-line tools.

* Why do I see `[ERROR] Failed to connect to dinasty-wallet-rpc at localhost port 8219  
Syntax error: Invalid response data structure: Request id: 1 is different from Response id: ` ?
This is most likely because this plugin can not reach your dinasty-wallet-rpc. Make sure that you have supplied the correct host IP and port to the plugin in their fields. If your dinasty-wallet-rpc is on a different server than your wordpress site, make sure that the appropriate port is open with port forwarding enabled.
