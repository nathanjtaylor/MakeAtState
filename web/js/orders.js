$(document).ready(function(){
    var orders =  JSON.parse(localStorage.getItem("orders")) || [];
    checkExpiration();
    updateOrdersUiBadge();
    updateOrders(orders, show=false);

    // Session storage for orders
	$(document).on('click', '.add_to_order', function(e){
		e.preventDefault();
        checkExpiration();
        orders.push($(this).data('filename') + '-order-'+ $(this).data('fileid') )
        setExpiration();
        localStorage.setItem('orders', JSON.stringify(orders));
        updateOrders(orders, true);
	})

	$(document).on('click', '.prime_remove_file', function(e){
        if (orders !== null || orders.length !== 0) {
            const file_pointer = $(this).attr('id');
            const file_id = file_pointer.replace('remove-file-', '')
            $.each(orders, function(idx, order) {
                const order_split = order.split("-order-");
                const name = order_split[0];
                const id = order_split[1];
                if (id == file_id) {
                    // remove the badge
                    $('#file-'+id).find('.badge').addClass('d-none');
                    $('#order-'+id).addClass('btn-success add_to_order').removeClass('disabled');
                    // update orders in local storage
                    orders.splice(idx,1);
                }
            });
            localStorage.setItem('orders', JSON.stringify(orders));
            setExpiration();
            updateOrders(orders, false);
        }
	})

    /**
    * Updates the order modal
    * @param  {Array} orders Orders from the browser local storage
    */
	function updateOrders(orders, show=true){
        var oders_modal = $('#odersModal');
        if (orders == null || orders.length === 0) {
            const no_items = '<div class="prime_orders">Currently there are no items in your project. To add items to your project use '+feather['icons']['file-plus'].toSvg()+'(Add to project) button.</div>';
            oders_modal.find('.prime_orders').html(no_items);
        }  else {
            var $ul = $('<ul>', { class: "list-group added_orders prime_orders" });
            $.each(orders, function(idx, file) {
                const file_split = file.split("-order-");
                const name = file_split[0];
                const id = file_split[1];
                var $li =$('<li class="'+id+' list-group-item">').text(name).append (
                    $('<button aria-label ="Remove file from order" class ="btn btn-secondary float-end prime_icon_btn prime_remove_file" data-toggle="tooltip" data-placement="top" title="Remove file from order" id=remove-file-'+id+'>'+feather.icons.trash.toSvg()+'</button>')
                );
                $ul.append($li);
                // disable the add to order button
                $('#order-'+id).removeClass('btn-success add_to_order').addClass('disabled');
                $('#file-'+id).find('.badge').removeClass('d-none');
            });
            oders_modal.find('.prime_orders').replaceWith($ul);
        }
        // Update url and btn status
        // open the modal
        if (show) {
            var open_orders_modal = new bootstrap.Modal(document.getElementById("odersModal"), {})
            open_orders_modal.show();
        }
        updateCartBtn(orders);
    }

    /**
    *  Set expiration on the local storage items
    */
    function setExpiration(){
        const date = new Date()
        // set the expiry time for orders
        date.setTime(date.getTime() + (1* 60 * 1000));
        localStorage.setItem( 'expiretime', date );
    }

    /**
    * Checks for expiration on the local storage items
    */
    function checkExpiration(){
        //check if past expiration date
        const current_time = new Date();
        const exp_time = new Date(localStorage.getItem("expiretime"));
        if (current_time > exp_time) {
            localStorage.clear()
        }
    }

    /**
    * Updates the UI badge for files on page load
    */
    function updateOrdersUiBadge() {
        if (orders !== null || orders.length !== 0) {
            var orders =  JSON.parse(localStorage.getItem("orders"));
            $.each(orders, function(idx, order) {
                order = order.split("-order-");
                const name = order[0];
                const id = order[1];
                $('#order-'+id).removeClass('btn-success add_to_order').addClass('disabled');
                $('#file-'+id).find('.badge').removeClass('d-none');
            });
            // set items back into the local storage
            localStorage.setItem('orders', JSON.stringify(orders));
            setExpiration();
        }
    }

    /**
    * Updates the UI badge for files on page load
    * @param  {Array} orders Orders from the browser local storage
    */
    function updateCartBtn(orders) {
        if (orders.length !== 0) {
            var orders =  JSON.parse(localStorage.getItem("orders"));
            var cart_url = '/?t=add_to_cart'
            $.each(orders, function(idx, order) {
                order = order.split("-order-");
                const name = order[0];
                const id = order[1];
                cart_url = cart_url +'&file_id[]='+id;
            });
            // set items back into the local storage
            localStorage.setItem('orders', JSON.stringify(orders));
            $('.prime_select_order_options').removeClass('d-none');
            $(".prime_select_order_options").attr("href", cart_url)
        } else {
            $('.prime_select_order_options').addClass('d-none');
            $(".prime_select_order_options").attr("href", "#")
        }
    }

});

