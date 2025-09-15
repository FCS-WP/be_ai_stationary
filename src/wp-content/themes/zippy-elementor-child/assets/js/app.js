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
      show_loading();
      subtotal_promise = calculate_total(product_table).then(res => {
        subtotal_promise = null;
        hide_loading();
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
                <td>${item.price}</td>
                </tr>`;
              });
            });

            if (bodyRows) {
              // Append grand total row (if provided)
              if (res.data.total) {
                bodyRows += `<tr class="grand_total_row">
                <td><strong>Total</strong></td>
                <td colspan="2"><strong>${res.data.total}</strong></td>
                </tr>`;
              }
              let table_html = `
                <tbody>
                  ${bodyRows}
                  <tr>
                  <td colspan="3" style="text-align:center;">
                  <button class="add-to-cart-btn">
                    Add to Cart
                  </button>
                  </td>
                  </tr>
                </tbody>
                `;
              $(".order-summary").find("tbody").remove();
              $(".order-summary").append(table_html);
            }
          }
        }
      }).catch(() => {
        subtotal_promise = null;
      });
    }, 200);
  })
  .on("click", ".add-to-cart-btn", function () {
    let product_table = $(".product-table");
    let post_data = {};
    product_table.find(".product_select:checked").each(function () {
      let qty = parseInt($(this).parents("tr").find(".qty").val(), 10),
        id = parseInt($(this).val(), 10);

      post_data[id] = qty;
    });

    if (Object.keys(post_data).length === 0) {
      alert("Please select at least one product.");
      return;
    }

    // Disable button to prevent multiple clicks
    let button = $(this);
    button.prop('disabled', true).text('Adding...');
    show_loading();
    ajax({
      url: "/wp-admin/admin-ajax.php",
      type: 'POST',
      data: {
        action: 'filter_add_to_cart',
        data: post_data
      }
    }).then(res => {
      if (res.success) {
        hide_loading();
        // Redirect to cart page
        window.location.href = "/cart/";
      } else {
        alert(res.data || "An error occurred while adding products to the cart.");
        button.prop('disabled', false).text('Add to Cart');
      }
    }).catch(() => {
      hide_loading();
      alert("An error occurred while adding products to the cart.");
      button.prop('disabled', false).text('Add to Cart');
    });
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
  console.log(post_data);
  
  return ajax({
    url: "/wp-admin/admin-ajax.php",
    type: 'POST',
    data: {
      action: 'checkout_filter_subtotal',
      data: post_data
    }
  });
}


function show_loading() {
  $(".loading-container").addClass('loading');
}

function hide_loading() {
  $(".loading-container").removeClass('loading');
}