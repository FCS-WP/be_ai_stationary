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
    $cart = new WC_Cart();
    if (isset($_POST['data']) && is_array($_POST['data'])) {
        foreach ($_POST['data'] as $id => $qty) {
            if (is_numeric($id) && is_numeric($qty) && $qty > 0) {
                $added = $cart->add_to_cart($id, $qty);
            }
        }
        wp_send_json_success(['message' => 'Products added to cart.']);
    } else {
        wp_send_json_error('No products provided.');
    }
    wp_die();
}

add_action('wp_ajax_filter_add_to_cart', 'add_products_to_cart');
add_action('wp_ajax_nopriv_filter_add_to_cart', 'add_products_to_cart');