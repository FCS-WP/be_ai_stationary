<?php

function calculate_total(){
    if (isset($_POST["data"]) && is_array($_POST["data"])) {
        $total = 0;
        foreach ($_POST["data"] as $prd) {
            $product = wc_get_product($prd['id']);
            if ($product && is_numeric($prd['qty']) && $prd['qty'] > 0) {
                $price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_price();
                $total += $price * $prd['qty'];
            }
        }
        wp_send_json_success(['total' => wc_price($total)]);
    } else {
        wp_send_json_error('No products selected.');
    }
    wp_die();
}

add_action('wp_ajax_checkout_filter_subtotal', 'calculate_total');
add_action('wp_ajax_nopriv_checkout_filter_subtotal', 'calculate_total');