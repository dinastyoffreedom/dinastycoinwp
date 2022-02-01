<?php foreach($errors as $error): ?>
<div class="error"><p><strong>Dinastycoin Gateway Error</strong>: <?php echo $error; ?></p></div>
<?php endforeach; ?>

<h1>Dinastycoin Gateway Settings</h1>

<?php if($confirm_type === 'dinastycoin-wallet-rpc'): ?>
<div style="border:1px solid #ddd;padding:5px 10px;">
    <?php
         echo 'Wallet height: ' . $balance['height'] . '</br>';
         echo 'Your balance is: ' . $balance['balance'] . '</br>';
         echo 'Unlocked balance: ' . $balance['unlocked_balance'] . '</br>';
         ?>
</div>
<?php endif; ?>

<table class="form-table">
    <?php echo $settings_html ?>
</table>

<h4><a href="https://github.com/dinastyoffreedom/dinastycoinwp">Learn more about using the dinastycoin payment gateway</a></h4>

<script>
function dinastycoinUpdateFields() {
    var confirmType = jQuery("#woocommerce_dinastycoin_gateway_confirm_type").val();
    if(confirmType == "dinastycoin-wallet-rpc") {
        jQuery("#woocommerce_dinastycoin_gateway_dinastycoin_address").closest("tr").hide();
        jQuery("#woocommerce_dinastycoin_gateway_viewkey").closest("tr").hide();
        jQuery("#woocommerce_dinastycoin_gateway_daemon_host").closest("tr").show();
        jQuery("#woocommerce_dinastycoin_gateway_daemon_port").closest("tr").show();
    } else {
        jQuery("#woocommerce_dinastycoin_gateway_dinastycoin_address").closest("tr").show();
        jQuery("#woocommerce_dinastycoin_gateway_viewkey").closest("tr").show();
        jQuery("#woocommerce_dinastycoin_gateway_daemon_host").closest("tr").hide();
        jQuery("#woocommerce_dinastycoin_gateway_daemon_port").closest("tr").hide();
    }
    var usedinastycoinPrices = jQuery("#woocommerce_dinastycoin_gateway_use_dinastycoin_price").is(":checked");
    if(usedinastycoinPrices) {
        jQuery("#woocommerce_dinastycoin_gateway_use_dinastycoin_price_decimals").closest("tr").show();
    } else {
        jQuery("#woocommerce_dinastycoin_gateway_use_dinastycoin_price_decimals").closest("tr").hide();
    }
}
dinastycoinUpdateFields();
jQuery("#woocommerce_dinastycoin_gateway_confirm_type").change(dinastycoinUpdateFields);
jQuery("#woocommerce_dinastycoin_gateway_use_dinastycoin_price").change(dinastycoinUpdateFields);
</script>

<style>
#woocommerce_dinastycoin_gateway_dinastycoin_address,
#woocommerce_dinastycoin_gateway_viewkey {
    width: 100%;
}
</style>