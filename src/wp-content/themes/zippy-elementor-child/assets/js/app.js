// import { DisplayLabel } from './components/DisplayLabel';

const { ajax } = require("jquery");

let Main = {
  init: async function () {

    // initialize demo javascript component - async/await invokes some 
    //  level of babel transformation 
    const displayLabel = new DisplayLabel();
    await displayLabel.init();

  }
};


// Main.init();


let subtotal_promise = null,
  subtotal_timeout = null;

$("body")
  .on("change", ".select_all", function () {
    let checked = $(this).is(':checked'),
      parents = $(this).parents("table");

    parents.find(".product_select").prop('checked', checked).trigger('change');
  })
  .on("change", ".qty", function () {
    let value = parseInt($(this).val()),
      product_select = $(this).parents("tr").find(".product_select")
    if (value > 0) {
      product_select.prop('checked', true).trigger('change');
    } else {
      product_select.prop('checked', false).trigger('change');
    }
  })
  .on("change", ".product_select, .select_all", function () {
    let _this = $(this),
      checked = _this.is(':checked'),
      qty_input = _this.parents("tr").find(".qty"),
      sub_total_html = _this.parents("table").find(".sub_total"),
      product_table = $(".product-table");

    if (checked) {
      if (parseInt($(qty_input).val()) === 0) {
        $(qty_input).val(1);
      }
    } else {
      $(qty_input).val(0);
    }

    // Debounce API call
    clearTimeout(subtotal_timeout);
    subtotal_timeout = setTimeout(() => {
      if (subtotal_promise) return;
      subtotal_promise = calculate_total(product_table).then(res => {
        subtotal_promise = null;

        // Clean previous dynamic rows / product list
        product_table.find(".sub_total").remove();
        $(".cart_total .cart_total_products").remove();

        if (res.success && res.data) {
          // Per-category subtotals
          if (res.data.subtotals) {
            Object.entries(res.data.subtotals).forEach(([category, html]) => {
              let table = $(`.product-table[data-category="${category}"]`);
              if (table.length) {
                let subtotalRow = `<tr class="sub_total"><td></td><td></td><td></td><td>Subtotal</td><td>${html}</td></tr>`;
                table.find("tbody tr").last().after(subtotalRow);
              }
            });
          }

          // Product list (name x quantity)
          // Build product table (Product | Qty) + final Total row
          if (res.data.details) {
            const escapeHtml = s => String(s)
              .replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#39;');

            let bodyRows = '';

            // Flatten products across categories preserving category order
            Object.entries(res.data.details).forEach(([category, items]) => {
              items.forEach(item => {
                bodyRows += `<tr>
                <td>${escapeHtml(item.name)}</td>
                <td>x${escapeHtml(item.quantity)}</td>
                </tr>`;
              });
            });

            if (bodyRows) {
              // Append grand total row (if provided)
              if (res.data.total) {
                bodyRows += `<tr class="grand_total_row">
                <td><strong>Total</strong></td>
                <td><strong>${res.data.total}</strong></td>
                </tr>`;
              }
              const tableHtml = `
                <table class="cart_total_products">
                <thead>
                  <tr><th>Product</th><th>Qty</th></tr>
                </thead>
                <tbody>
                  ${bodyRows}
                  <tr>
                  <td colspan="2" style="text-align:center;">
                  <button class="add-to-cart-btn">
                    Add to Cart
                  </button>
                  </td>
                  </tr>
                </tbody>
                </table>
                `;
              // Attach click handler for Add to Cart button
              setTimeout(() => {
                $(".add-to-cart-btn").off("click").on("click", function () {
                  // Gather checked products and quantities
                  let products = [];
                  $(".product-table .product_select:checked").each(function () {
                    let qty = parseInt($(this).closest("tr").find(".qty").val(), 10),
                      id = parseInt($(this).val(), 10);
                    if (qty > 0) {
                      products.push({ id, qty });
                    }
                  });

                  if (products.length === 0) return;
                  console.log(products);

                  // Send AJAX request to add to cart
                    ajax({
                    url: "/wp-admin/admin-ajax.php",
                    type: "POST",
                    data: {
                      action: "add_checked_products_to_cart",
                      products: products
                    },
                    success: function (response) {
                      // Optionally show a success message or update UI
                      alert(response.data.message);
                    },
                    error: function (err) {
                      console.log(err.message);
                    }
                    });
                });
              }, 0);
              $(".cart_total").append(tableHtml);
            }
          }
        }
      }).catch(() => {
        subtotal_promise = null;
      });
    }, 200);
  });


function calculate_total(form) {
  let post_data = {};
  form.find(".product_select:checked").each(function () {
    let qty = parseInt($(this).parents("tr").find(".qty").val(), 10),
      category = $(this).closest("table").data("category"),
      id = parseInt($(this).val(), 10);

    if (!post_data[category]) {
      post_data[category] = [];
    }
    post_data[category].push({
      id: id,
      qty: qty
    });
  });

  return ajax({
    url: "/wp-admin/admin-ajax.php",
    type: 'POST',
    data: {
      action: 'checkout_filter_subtotal',
      data: post_data
    }
  });
}
