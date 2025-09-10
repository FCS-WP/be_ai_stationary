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
      form = $(this).parents("form");
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
      subtotal_promise = calculate_total(form).then(res => {
        subtotal_promise = null;
        sub_total_html.remove();
        if (res.success) {
          let html = "<tr class='sub_total'><td></td><td></td><td></td><td>Subtotal</td><td>" + res.data.total + "</td></tr>";
          $(html).insertAfter(form.find("table tbody tr").last());
        }
      }).catch(() => {
        subtotal_promise = null;
      });
    }, 200);
  });


function calculate_total(form) {
  let data = [];
  form.find(".product_select:checked").each(function () {
    let qty = $(this).parents("tr").find(".qty").val();
    data.push({
      id: $(this).val(),
      qty: qty
    });
  });

  return ajax({
    url: "/wp-admin/admin-ajax.php",
    type: 'POST',
    data: {
      action: 'checkout_filter_subtotal',
      data: data
    }
  });
}
