<?php
/*
Template Name: Filter Checkout
*/

get_header();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name'], $_POST['level'], $_POST['class'], $_POST['mother_tongue'])) {
    $student_name = sanitize_text_field($_POST['student_name']);
    $level = sanitize_text_field($_POST['level']);
    $class = sanitize_text_field($_POST['class']);
    $mother_tongue = sanitize_text_field($_POST['mother_tongue']);
    $categories_arr = [$level, $mother_tongue];
?>
    <div class="checkout-filter-container container">
        <h2>Student: <?php echo esc_html($student_name); ?></h2>
        <h3>Class: <?php echo esc_html($class); ?></h3>
    </div>
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
                        return "<p>No products found for {$term->name} category.</p>";
                    }
                    ?>
                    <table class="product-table" data-category="<?php echo esc_attr($category_slug); ?>">
                        <tr>
                            <th>Code</th>
                            <th>Item Description</th>
                            <th>Qty</th>
                            <th><input type="checkbox" class="select_all"></th>
                            <th>Price</th>
                        </tr>
                        <?php foreach ($products as $prod) { ?>
                            <tr>
                                <td><?php echo esc_html($prod->get_id()); ?><input type="hidden" name="product[id]"
                                        value="<?php echo esc_attr($prod->get_id()); ?>"></td>
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
                                                'input_value' => $prod->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
                                            )
                                        )
                                    ?>
                                </td>
                                <td><input type="checkbox" class="product_select" value="<?php echo esc_attr($prod->get_id()); ?>"></td>
                                <td>$<?php echo esc_html(($prod->get_sale_price() ? $prod->get_sale_price() : $prod->get_price())); ?></td>
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
                                <th>Qty</th>
                                <th>Price</th>
                            </tr>
                        </thead>

                    </table>
                </div>
            </div>
        </div>
    </div>

<?php
}
get_footer(); ?>