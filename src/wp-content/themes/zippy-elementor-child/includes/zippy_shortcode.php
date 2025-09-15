<?php

function init_student_form()
{
    ob_start();

    $level_html = "";
    $mother_tongue_html = "";

    $level_slug = [
        "p1",
        "p2",
        "p3",
        "p4",
        "p5",
        "p6"
    ];

    $mother_tongue_slugs = ['c-m-edu-tl', 'c-m-edul-ml', 'cm-edu', 'chinese', 'cl-only', 'english', 'exercise-books-compulsory-items', 'foundation-chinese', 'foundation-english', 'foundation-malay', 'foundation-maths', 'foundation-science', 'higher-chinese', 'higher-malay', 'higher-tamil', 'malay', 'maths', 'ml-tl-only', 'optional-items'];

    foreach ($level_slug as $slug) {
        $category = get_term_by('slug', $slug, 'product_cat');
        if ($category) {
            $level_html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($category->slug),
                esc_html($category->name)
            );
        }
    }

    foreach ($mother_tongue_slugs as $slug) {
        $cate = get_term_by('slug', $slug, 'product_cat');
        if ($cate) {
            $mother_tongue_html .= sprintf(
                '<option value="%s">%s</option>',
                esc_attr($cate->slug),
                esc_html($cate->name)
            );
        }
    }

    ?>
    <form id="student_form" method="post" action="/checkout-filter">
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
						'min_value'   => apply_filters('woocommerce_quantity_input_min', $prod->get_min_purchase_quantity(), $prod),
						'max_value'   => apply_filters('woocommerce_quantity_input_max', $prod->get_max_purchase_quantity(), $prod),
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

// function create_woocommerce_product_category() {
//         $cat_list = [
//             'English',
//             'Foundation English',
//             'Maths',
//             'Foundation Maths',
//             'Science',
//             'Foundation Science',
//             'Social Studies',
//             'Chinese',
//             'Higher Chinese',
//             'Foundation Chinese',
//             'Malay',
//             'Higher Malay',
//             'Foundation Malay',
//             'C & M Edul ML',
//             'Tamil',
//             'Higher Tamil',
//             'C & M EDU TL',
//             'PE',
//             'CL Only',
//             'ML/TL Only',
//             'Optional Items',
//             'C&M Edu',
//             "Exercise Books & Compulsory Items"
//         ];
//         $category_slugs = [];
//         foreach ($cat_list as $cat_name) {
//             if (!term_exists($cat_name, 'product_cat')) {
//                 $result = wp_insert_term($cat_name, 'product_cat');
//                 if (!is_wp_error($result) && isset($result['term_id'])) {
//                     $term = get_term($result['term_id'], 'product_cat');
//                     if ($term && !is_wp_error($term)) {
//                         // Return or use the slug as needed
//                         $category_slugs[] = $term->slug;
//                     }
//                 }
//             }
//         }
//         // You can return or use the $category_slugs array as needed
//         return $category_slugs;
//     }
// add_action( 'admin_init', 'create_woocommerce_product_category' );