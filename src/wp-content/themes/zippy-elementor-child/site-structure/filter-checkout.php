<?php
/*
Template Name: Filter Checkout
*/

get_header();
the_content();
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['student_name'], $_GET['level'], $_GET['class'], $_GET['mother_tongue'])) {
    $student_name = sanitize_text_field($_GET['student_name']);
    $level = sanitize_text_field($_GET['level']);
    $class = sanitize_text_field($_GET['class']);
    $mother_tongue = sanitize_text_field($_GET['mother_tongue']);
    $categories_arr = [$level, $mother_tongue];

    // Get cart
    $cart = WC()->cart->get_cart();
    usort($cart, function ($a, $b) {
        return $b['product_id'] - $a['product_id']; // Tăng dần
    });
?>
    <div>
        <div class="checkout-filter-container container" id="checkout_filter">
            <div class="left loading-container">
                <span class="loader"></span>
                <?php
                foreach ($categories_arr as $category_slug) {
                    $term = get_term_by('slug', $category_slug, 'product_cat');
                    if ($term) {
                ?>
                        <h3><?php echo esc_html($term->name); ?></h3>
                        <?php
                        $args = array(
                            'category' => $category_slug,
                            'limit' => -1
                        );
                        $products = wc_get_products($args);
                        if (empty($products)) {
                            echo "<p>No products found for {$term->name} category.</p>";
                            continue;
                        }
                        ?>
                        <table class="product-table" data-category="<?php echo esc_attr($category_slug); ?>">
                            <tr>
                                <th>Code</th>
                                <th>Item Description</th>
                                <th>Quantity</th>
                                <th><input type="checkbox" class="select_all"></th>
                                <th>Price</th>
                            </tr>
                            <?php foreach ($products as $prod) {
                                $product_id = $prod->get_id();
                                // Check if product in cart
                                $in_cart = false;
                                $cart_quantity = 0;
                                foreach ($cart as $cart_item_key => $cart_item) {
                                    if ($cart_item['product_id'] == $product_id) {
                                        $in_cart = true;
                                        $cart_quantity = $cart_item['quantity'];
                                        break;
                                    }
                                }
                            ?>
                                <tr>
                                    <td><?php echo esc_html($product_id); ?><input type="hidden" name="product[id]"
                                            value="<?php echo esc_attr($product_id); ?>"></td>
                                    <td><?php echo esc_html($prod->get_name()); ?></td>
                                    <td>
                                        <?php
                                        woocommerce_quantity_input(
                                            array(
                                                'min_value' => apply_filters(
                                                    'woocommerce_quantity_input_min',
                                                    $prod->get_min_purchase_quantity(),
                                                    $prod
                                                ),
                                                'max_value' => apply_filters(
                                                    'woocommerce_quantity_input_max',
                                                    $prod->get_max_purchase_quantity(),
                                                    $prod
                                                ),
                                                'input_value' => $in_cart ? $cart_quantity : $prod->get_min_purchase_quantity(),
                                            )
                                        );
                                        ?>
                                    </td>
                                    <td><input type="checkbox" class="product_select" value="<?php echo esc_attr($product_id); ?>" <?php echo $in_cart ? 'checked' : ''; ?>></td>
                                    <td>$<?php echo esc_html($prod->get_sale_price() ? $prod->get_sale_price() : $prod->get_price()); ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                <?php }
                } ?>
            </div>
            <div class="right loading-container">
                <span class="loader"></span>
                <div class="box">
                    <div class="box-header">
                        <h3>Order Summary</h3>
                    </div>
                    <div class="box-content">
                        <table class="order-summary">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <?php
                            if (empty($cart)) {
                                echo "<tr><td colspan='4'>No product in cart</td></tr>";
                            } else {
                                foreach ($cart as $cart_item_key => $cart_item) {
                                    $product = $cart_item['data'];
                                    $product_name = $product->get_name();
                                    $quantity = $cart_item['quantity'];
                                    $price = WC()->cart->get_product_price($product);
                                    $subtotal = WC()->cart->get_product_subtotal($product, $quantity);
                            ?>
                                    <tr>
                                        <td><?php echo esc_html($product_name); ?></td>
                                        <td><?php echo esc_html($quantity); ?></td>
                                        <td><?php echo $price; ?></td>
                                        <td><?php echo $subtotal; ?></td>
                                    </tr>
                            <?php
                                }
                            }
                            ?>
                            <tr class="grand_total_row">
                                <td colspan="3"><strong>Total</strong></td>
                                <td class="total"><strong><?php echo WC()->cart->get_total(); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
get_footer();
?>