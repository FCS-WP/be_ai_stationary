<?php

function calculate_total()
{
    if (isset($_POST["data"]) && is_array($_POST["data"])) {
        $totals = [];
        $grand_total = 0;
        $details = [];
        foreach ($_POST["data"] as $cate => $prd) {
            $sub_total = 0;
            $details[$cate] = [];
            if (!empty($prd)) {
                foreach ($prd as $value) {
                    $product_id = $value["id"];
                    $product_qty = $value["qty"];
                    $product = wc_get_product($product_id);
                    if ($product && is_numeric($product_qty) && $product_qty > 0) {
                        $price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_price();
                        $sub_total += $price * $product_qty;
                        $details[$cate][] = [
                            'name' => $product->get_name(),
                            'quantity' => $product_qty,
                            'price' => wc_price($price),
                            'subtotal' => wc_price($price * $product_qty)
                        ];
                    }
                }
            }
            $totals[$cate] = wc_price($sub_total);
            $grand_total += $sub_total;
        }
        wp_send_json_success([
            'subtotals' => $totals,
            'total' => wc_price($grand_total),
            'details' => $details
        ]);
    } else {
        wp_send_json_error('No products selected.');
    }
    wp_die();
}

add_action('wp_ajax_checkout_filter_subtotal', 'calculate_total');
add_action('wp_ajax_nopriv_checkout_filter_subtotal', 'calculate_total');



function add_products_to_cart()
{
    if (isset($_POST['products']) && is_array($_POST['products'])) {
        $added_products = [];
        foreach ($_POST['products'] as $item) {
            if (isset($item['id']) && isset($item['qty']) && is_numeric($item['id']) && is_numeric($item['qty']) && $item['qty'] > 0) {
                $cart_item_key = WC()->cart->add_to_cart($item['id'], $item['qty']);
                if ($cart_item_key) {
                    $product = wc_get_product($item['id']);
                    $added_products[] = [
                        'id' => $item['id'],
                        'name' => $product ? $product->get_name() : '',
                        'quantity' => $item['qty'],
                        'price' => $product ? wc_price($product->get_price()) : '',
                        'cart_item_key' => $cart_item_key
                    ];
                }
            }
        }
        wp_send_json_success([
            'message' => 'Products added to cart.',
            'products' => $added_products
        ]);
    } else {
        wp_send_json_error('No products provided.');
    }
    wp_die();
}

add_action('wp_ajax_add_checked_products_to_cart', 'add_products_to_cart');
add_action('wp_ajax_nopriv_add_checked_products_to_cart', 'add_products_to_cart');




add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');
function custom_override_checkout_fields($fields)
{
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_postcode']);

    return $fields;
}


