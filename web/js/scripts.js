$.fn.extend({
    treed: function (o) {

        let openedClass = 'glyphicon-minus-sign';
        let closedClass = 'glyphicon-plus-sign';

        if (typeof o != 'undefined') {
            if (typeof o.openedClass != 'undefined') {
                openedClass = o.openedClass;
            }
            if (typeof o.closedClass != 'undefined') {
                closedClass = o.closedClass;
            }
        }

        //initialize each of the top levels
        let tree = $(this);
        tree.addClass("tree");
        tree.find('li').has("ul").each(function () {
            let branch = $(this); //li with children ul
            branch.prepend("<i class='indicator glyphicon " + closedClass + "'></i>");
            branch.addClass('branch');
            branch.on('click', function (e) {
                if (this == e.target) {
                    let icon = $(this).children('i:first');
                    icon.toggleClass(openedClass + " " + closedClass);
                    $(this).children().children().toggle();
                }
            })
            branch.children().children().toggle();
        });
        //fire event from the dynamically added icon
        tree.find('.branch .indicator').each(function () {
            $(this).on('click', function () {
                $(this).closest('li').click();
            });
        });
        //fire event to open branch if the li contains an anchor instead of text
        tree.find('.branch>a').each(function () {
            $(this).on('click', function (e) {
                $(this).closest('li').click();
                e.preventDefault();
            });
        });
        //fire event to open branch if the li contains a button instead of text
        tree.find('.branch>button').each(function () {
            $(this).on('click', function (e) {
                $(this).closest('li').click();
                e.preventDefault();
            });
        });
    }
});

$('.product-tree>ul').treed({openedClass: 'glyphicon-folder-open', closedClass: 'glyphicon-folder-close'});

function post(url, data, callback) {
    return $.ajax({
        type: "POST",
        url: url,
        data: data,
        success: callback,
        dataType: 'json'
    });
}

const formatPrice = (x) => {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

function calcBuyingTotal() {
    let total = 0;

    $("table.buyer-table tr").each(function () {
        let sum = 0;
        let quantity = $(this).find("td.quantity input").val();
        let price = $(this).find("td.price input").val();
        if (!quantity)
            quantity = 0;
        if (!price)
            price = 0;

        if (parseFloat(quantity) && parseFloat(price)) {
            sum = parseFloat(quantity) * parseFloat(price);
            $(this).find("td.total").text(formatPrice(sum) + " сум");
            total = total + sum;
        }
    });
    $("table.buyer-table .total-sum").text(formatPrice(total) + " сум");
}

$().ready(function () {



    let btn = $('#button');

    $(window).scroll(function () {
        if ($(window).scrollTop() > 300) {
            btn.addClass('show');
        } else {
            btn.removeClass('show');
        }
    });

    btn.on('click', function (e) {
        e.preventDefault();
        $('html, body').animate({scrollTop: 0}, '300');
    });


    $('body').on('click', 'a[data-action="sync-iiko"]', function () {
        let obj = $(this);
        let r = confirm("Вы действительно хотите синхронировать данные с iiko?");
        if (r === true) {
            obj.attr("disabled", "disabled");
            post("/ajax/sync-iiko", {}, function (r) {
                obj.removeAttr("disabled");
                if (r.state != "OK") {
                    alert(r.message);
                }
            });
        }
        return false;
    });

    $('body').on('click', 'a[data-action="edit-product"]', function () {
        let obj = $(this);
        let id = obj.attr("data-id");
        post("/ajax/get-product", {id: id}, function (r) {
            console.log(r);
            if (r.state === "OK") {
                $("#productModal h4.modal-title").text(r.data.name);
                $("#products-id").val(r.data.id);
                $("#products-price_start").val(r.data.price_start);
                $("#products-price_end").val(r.data.price_end);
                $("#products-delta").val(r.data.delta);
                $("#products-zone").val(r.data.zone);
                $("#products-description").val(r.data.description);
                $("#products-minbalance").val(r.data.min_balance);
                if (r.data.show_on_report == 1)
                    $("#products-showonreport").prop('checked', true);
                else
                    $("#products-showonreport").prop('checked', false);
                $("#productModal").modal("show");
            }
        });
        return false;
    });

    $('#update-product').submit(function () {
        let form = $(this);
        post("/ajax/update-product", form.serialize(), function (r) {
            console.log(r);
            if (r.state === 'OK') {
                form.trigger("reset");
                $("#productModal").modal("hide");
            } else {
                alert(r.message);
            }
        });
        return false;
    });

    $('body').on('change', 'input.group-check', function () {
        let obj = $(this);
        let checked = $(this).is(":checked");
        obj.closest("li").find("li input.group-check").each(function () {
            $(this).prop('checked', checked);
        });
    });

    $('body').on('keyup', 'input.from_stock', function () {
        let obj = $(this);
        let value = obj.val();
        let quantity = obj.closest("tr").find("td.order_quantity").text();

        if (!value || value == "0") {
            obj.closest("tr").find("td input.to-buy").val(quantity);
            return false;
        }

        if (parseFloat(value) < 0) {
            obj.val("0");
            return false;
        }

        if (!parseFloat(quantity) || !parseFloat(value)) {
            obj.closest("tr").find("td input.to-buy").val("0");
            return false;
        }
        let forBuy = parseFloat(quantity) - parseFloat(value);
        if (forBuy < 1) {
            obj.closest("tr").find("td input.to-buy").val("0");
            return false;
        }
        obj.closest("tr").find("td input.to-buy").val(forBuy.toFixed(2));
    });

    $('.buyer-table').on('keyup', 'input', function () {
        let obj = $(this);
        let tr = obj.closest("tr");
        let quantity = tr.find("td.quantity input").val();
        let price = tr.find("td.price input").val();

        if (!quantity || !price) {
            tr.find("td.total").text("");
            return false;
        }

        if (!parseFloat(quantity) || !parseFloat(price)) {
            tr.find("td.total").text("");
            return false;
        }
        let total = parseFloat(quantity) * parseFloat(price);
        tr.find("td.total").text(formatPrice(total) + " сум");
        calcBuyingTotal();
    });

    $('div.categories').on('click', 'a[data-action="selectAll"]', function () {
        $('ul.category-tree input:checkbox').prop('checked', true);
        return false;
    });
    $('div.categories').on('click', 'a[data-action="deselectAll"]', function () {
        $('ul.category-tree input:checkbox').prop('checked', false);
        return false;
    });
});

$(document).ready(function () {
    // console.log($.fn.datepicker);
    var e = $(".datepicker");
    e.length && e.each(function() {
        $(this).datepicker({
            disableTouchKeyboard: !0,
            todayHighlight: !0,
            autoclose: !0,
            format: "dd.mm.yyyy",
            language: "ru",
            weekStart: 1,
            startDate: "01.01.2000",
            defaultViewDate: {
                year: new Date().getFullYear(),
                month: new Date().getMonth(),
            }
        })
    });
})