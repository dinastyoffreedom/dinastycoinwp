/*
 * Copyright (c) 2018, Ryo Currency Project
*/
function dinastycoin_showNotification(message, type='success') {
    var toast = jQuery('<div class="' + type + '"><span>' + message + '</span></div>');
    jQuery('#dinastycoin_toast').append(toast);
    toast.animate({ "right": "12px" }, "fast");
    setInterval(function() {
        toast.animate({ "right": "-400px" }, "fast", function() {
            toast.remove();
        });
    }, 2500)
}
function dinastycoin_showQR(show=true) {
    jQuery('#dinastycoin_qr_code_container').toggle(show);
}
function dinastycoin_fetchDetails() {
    var data = {
        '_': jQuery.now(),
        'order_id': dinastycoin_details.order_id
    };
    jQuery.get(dinastycoin_ajax_url, data, function(response) {
        if (typeof response.error !== 'undefined') {
            console.log(response.error);
        } else {
            dinastycoin_details = response;
            dinastycoin_updateDetails();
        }
    });
}

function dinastycoin_updateDetails() {

    var details = dinastycoin_details;

    jQuery('#dinastycoin_payment_messages').children().hide();
    switch(details.status) {
        case 'unpaid':
            jQuery('.dinastycoin_payment_unpaid').show();
            jQuery('.dinastycoin_payment_expire_time').html(details.order_expires);
            break;
        case 'partial':
            jQuery('.dinastycoin_payment_partial').show();
            jQuery('.dinastycoin_payment_expire_time').html(details.order_expires);
            break;
        case 'paid':
            jQuery('.dinastycoin_payment_paid').show();
            jQuery('.dinastycoin_confirm_time').html(details.time_to_confirm);
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'confirmed':
            jQuery('.dinastycoin_payment_confirmed').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'expired':
            jQuery('.dinastycoin_payment_expired').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
        case 'expired_partial':
            jQuery('.dinastycoin_payment_expired_partial').show();
            jQuery('.button-row button').prop("disabled",true);
            break;
    }

    jQuery('#dinastycoin_exchange_rate').html('1 DCY = '+details.rate_formatted+' '+details.currency);
    jQuery('#dinastycoin_total_amount').html(details.amount_total_formatted);
    jQuery('#dinastycoin_total_paid').html(details.amount_paid_formatted);
    jQuery('#dinastycoin_total_due').html(details.amount_due_formatted);

    jQuery('#dinastycoin_integrated_address').html(details.integrated_address);

    if(dinastycoin_show_qr) {
        var qr = jQuery('#dinastycoin_qr_code').html('');
        new QRCode(qr.get(0), details.qrcode_uri);
    }

    if(details.txs.length) {
        jQuery('#dinastycoin_tx_table').show();
        jQuery('#dinastycoin_tx_none').hide();
        jQuery('#dinastycoin_tx_table tbody').html('');
        for(var i=0; i < details.txs.length; i++) {
            var tx = details.txs[i];
            var height = tx.height == 0 ? 'N/A' : tx.height;
            var row = ''+
                '<tr>'+
                '<td style="word-break: break-all">'+
                '<a href="'+dinastycoin_explorer_url+'/tx/'+tx.txid+'" target="_blank">'+tx.txid+'</a>'+
                '</td>'+
                '<td>'+height+'</td>'+
                '<td>'+tx.amount_formatted+' Dinastycoin</td>'+
                '</tr>';

            jQuery('#dinastycoin_tx_table tbody').append(row);
        }
    } else {
        jQuery('#dinastycoin_tx_table').hide();
        jQuery('#dinastycoin_tx_none').show();
    }

    // Show state change notifications
    var new_txs = details.txs;
    var old_txs = dinastycoin_order_state.txs;
    if(new_txs.length != old_txs.length) {
        for(var i = 0; i < new_txs.length; i++) {
            var is_new_tx = true;
            for(var j = 0; j < old_txs.length; j++) {
                if(new_txs[i].txid == old_txs[j].txid && new_txs[i].amount == old_txs[j].amount) {
                    is_new_tx = false;
                    break;
                }
            }
            if(is_new_tx) {
                dinastycoin_showNotification('Transaction received for '+new_txs[i].amount_formatted+' Dinastycoin');
            }
        }
    }

    if(details.status != dinastycoin_order_state.status) {
        switch(details.status) {
            case 'paid':
                dinastycoin_showNotification('Your order has been paid in full');
                break;
            case 'confirmed':
                dinastycoin_showNotification('Your order has been confirmed');
                break;
            case 'expired':
            case 'expired_partial':
                dinastycoin_showNotification('Your order has expired', 'error');
                break;
        }
    }

    dinastycoin_order_state = {
        status: dinastycoin_details.status,
        txs: dinastycoin_details.txs
    };

}
jQuery(document).ready(function($) {
    if (typeof dinastycoin_details !== 'undefined') {
        dinastycoin_order_state = {
            status: dinastycoin_details.status,
            txs: dinastycoin_details.txs
        };
        setInterval(dinastycoin_fetchDetails, 30000);
        dinastycoin_updateDetails();
        new ClipboardJS('.clipboard').on('success', function(e) {
            e.clearSelection();
            if(e.trigger.disabled) return;
            switch(e.trigger.getAttribute('data-clipboard-target')) {
                case '#dinastycoin_integrated_address':
                    dinastycoin_showNotification('Copied destination address!');
                    break;
                case '#dinastycoin_total_due':
                    dinastycoin_showNotification('Copied total amount due!');
                    break;
            }
            e.clearSelection();
        });
    }
});