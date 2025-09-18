<?php

function init_student_form()
{
    ob_start();


    // Get Product Category 
    $args = array(
        'taxonomy' => 'product_cat',
        'orderby' => 'name',
        'show_count' => 0,
        'pad_counts' => 0,
        'hierarchical' => 1,
        'title_li' => '',
        'hide_empty' => 0
    );
    $level_html = "";
    $level_parent_category = get_term_by('slug', 'level', 'product_cat');
    if ($level_parent_category) {
        $args = array(
            'taxonomy' => 'product_cat',
            'parent' => $level_parent_category->term_id,
            'orderby' => 'name',
            'hide_empty' => 0,
        );
        $level_child_categories = get_categories($args);
        foreach ($level_child_categories as $category) {
            $level_html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($category->slug),
                esc_html($category->name)
            );
        }
    }

    $mother_tongue_html ='';
    $subject_parent_category = get_term_by('slug', 'subject', 'product_cat');
    if ($subject_parent_category) {
        $args = array(
            'taxonomy' => 'product_cat',
            'parent' => $subject_parent_category->term_id,
            'orderby' => 'name',
            'hide_empty' => 0,
        );
        $subject_child_categories = get_categories($args);
        foreach ($subject_child_categories as $category) {
              $mother_tongue_html .= sprintf(
                    '<option value="%s">%s</option>',
                    esc_attr($category->slug),
                    esc_html($category->name)
                );
        }
    }
    ?>
    <form id="student_form" method="GET" action="/checkout-filter">
        <div class="input_wrapper">
            <label for="student-name">Student Name *</label>
            <input type="text" id="student-name" name="student_name" required placeholder="as per school's record">
        </div>

        <div class="input_wrapper">
            <label for="level">Level *</label>
            <div class="select_wrapper">
                <span class="arrow"></span>
                <select id="level" name="level" required>
                    <option value="">Select Level</option>
                    <?php echo $level_html; ?>
                </select>
            </div>
        </div>
        <div class="input_wrapper">
            <label for="class">Class *</label>
            <input type="text" id="class" name="class" required placeholder="for new academic year">
        </div>

        <div class="input_wrapper">
            <label for="mother-tongue">Mother Tongue *</label>
            <div class="select_wrapper">
                <span class="arrow"></span>
                <select id="mother-tongue" name="mother_tongue" required>
                    <option value="">Select Mother Tongue</option>
                    <?php echo $mother_tongue_html; ?>
                </select>
            </div>
        </div>
        <div class="input_wrapper">
            <input type="submit" value="Add Student" class="submit_button">
        </div>
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('student_form', 'init_student_form');


function checkout_filter()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_name'], $_POST['level'], $_POST['class'], $_POST['mother_tongue'])) {
        $student_name = sanitize_text_field($_POST['student_name']);
        $level = sanitize_text_field($_POST['level']);
        $class = sanitize_text_field($_POST['class']);
        $mother_tongue = sanitize_text_field($_POST['mother_tongue']);
        // Get products by selected level and mother tongue categories
        $slug_list = [
            $level,
            $mother_tongue
        ];

        foreach ($slug_list as $slug) {
            echo parse_products_table($slug);
        }
    }
}
add_shortcode('checkout_filter', 'checkout_filter');



function parse_products_table($category_slug)
{
    $term = get_term_by('slug', $category_slug, 'product_cat');
    $products = [];
    $html = "";

    if (empty($term)) {
        return "<p>Term with slug '{$category_slug}' not found.</p>";
    }

    $term_name = esc_html($term->name);

    $args = array(
        'category' => $category_slug,
        'limit' => -1
    );
    $products = wc_get_products($args);

    if (empty($products)) {
        return "<p>No products found for {$term_name} category.</p>";
    }

    $html .= '<h3>' . $term_name . '</h3>';
    $html .= '<table class="product-table" data-category="' . esc_attr($category_slug) . '">
        <tr>
            <th>Code</th>
            <th>Item Description</th>
            <th>Qty</th>
            <th><input type="checkbox" class="select_all"></th>
            <th>Price</th>
        </tr>';
    foreach ($products as $prod) {
        $html .= '<tr>';
        $html .= '<td>' . esc_html($prod->get_id()) . '<input type="hidden" name="product[id]" value="' . esc_attr($prod->get_id()) . '"></td>';
        $html .= '<td>' . esc_html($prod->get_name()) . '</td>';
        $html .= '<td>' . woocommerce_quantity_input(
            array(
                'min_value' => apply_filters('woocommerce_quantity_input_min', $prod->get_min_purchase_quantity(), $prod),
                'max_value' => apply_filters('woocommerce_quantity_input_max', $prod->get_max_purchase_quantity(), $prod),
                'input_value' => $prod->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
            )
        ) . '</td>';
        $html .= '<td><input type="checkbox" class="product_select" value="' . esc_attr($prod->get_id()) . '"></td>';
        $html .= '<td>$' . esc_html(($prod->get_sale_price() ? $prod->get_sale_price() : $prod->get_price())) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';

    return $html;
}

function get_student_name(){
    if (isset($_GET['student_name'])){
        $name_value = isset($_GET['student_name']) ? $_GET['student_name'] : '';
        return esc_html($name_value);
    } else{
        return "Ivalid Student Name";
    }
}
add_shortcode('get_student_name','get_student_name');

function get_student_class(){
    if(isset($_GET['class'])){
        $class_value = sanitize_text_field($_GET['class'])? $_GET['class'] : '';
        return esc_html($class_value);
    }else{
        return "Ivalid Class";
    }
}
add_shortcode('get_student_class','get_student_class');