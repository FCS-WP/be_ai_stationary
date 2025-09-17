<?php
add_action('wp_ajax_get_cart_items', 'wp_fcs_get_all_product');
add_action('wp_ajax_nopriv_get_cart_items', 'wp_fcs_get_all_product');
function wp_fcs_get_all_product()
{
    if (!WC()->cart) {
        wp_send_json_error('.....');
        return;
    }
    $cart_items = array();
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $cart_items[] = array(
            'product_id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $cart_item['line_total'],
            'quantity' => $cart_item['quantity']
        );
    }
    wp_send_json_success($cart_items);
}