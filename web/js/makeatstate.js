var cartWarning = "Missing Required Fields";
$(document).ready(function(){

    //Validation for register form
    $(".register_btn").click(function(event) {

        //Fetch form to apply custom Bootstrap validation
        var form = $("#registerForm");
    $('#confirmPassword').removeClass('prime_password_invalid');
    $('#confirmInvalid').hide();

        if (form[0].checkValidity() === false ) {
              event.preventDefault();
              event.stopPropagation()
        }

            form.addClass('was-validated');
    if($('#registerPassword').val() != $('#confirmPassword').val()){
              event.preventDefault();
              event.stopPropagation();

        $('#confirmPassword').addClass('prime_password_invalid');
        $('#confirmInvalid').show();

    }

      });

    //Validation for settings form
    $(".settings_btn").click(function(event) {

        //Fetch form to apply custom Bootstrap validation
        var form = $("#settingsForm");

        if (form[0].checkValidity() === false ) {
              event.preventDefault();
              event.stopPropagation()
        }

        form.addClass('was-validated');


      });


    //Validation for password reset  from the settings page
    $(".pass_reset_btn").click(function(event) {

        //Fetch form to apply custom Bootstrap validation
        var form = $("#settingsPasswordForm");
    $('#confirmPassword').removeClass('prime_password_invalid');
    $('#confirmInvalid').hide();

        if (form[0].checkValidity() === false ) {
              event.preventDefault();
              event.stopPropagation()
        }

            form.addClass('was-validated');
    if($('#newPassword').val() != $('#confirmPassword').val()){
              event.preventDefault();
              event.stopPropagation();

        $('#confirmPassword').addClass('prime_password_invalid');
        $('#confirmInvalid').show();

    }

      });

    //Validation for password reset form form
    $(".reset_password_btn").click(function(event) {

        //Fetch form to apply custom Bootstrap validation
        var form = $("#resetForm");
    $('#confirmRestPassword').removeClass('prime_password_invalid');
    $('#confirmInvalid').hide();

        if (form[0].checkValidity() === false ) {
              event.preventDefault();
              event.stopPropagation()
        }

            form.addClass('was-validated');
    if($('#resetPassword').val() != $('#confirmResetPassword').val()){
              event.preventDefault();
              event.stopPropagation();

        $('#confirmResetPassword').addClass('prime_password_invalid');
        $('#confirmInvalid').show();

    }

      });

    //Validation for price set form  form form
    $(".set_price_btn").click(function(event) {

        //Fetch form to apply custom Bootstrap validation
        var form = $("#setPriceForm");

        if (form[0].checkValidity() === false ) {
              event.preventDefault();
              event.stopPropagation()
        }

       form.addClass('was-validated');

      });


    //Validation for 3dprime validation forms
    $(".prime_validate_btn").click(function(event) {

        //Fetch form to apply custom Bootstrap validation
        var form = $("#prime_validate_form");

        if (form[0].checkValidity() === false ) {
              event.preventDefault();
              event.stopPropagation()
        }

       form.addClass('was-validated');

      });

    //Validation for 3dprime worflow steps
    $(".prime_workflow_step_validate_btn").click(function(event) {

        //Fetch form to apply custom Bootstrap validation
        var id = event.currentTarget.id
        var loop_id = id.replace("prime_workflow_step_validate_btn_", "");
        var form = $("#prime_workflow_step_validate_form_"+loop_id);
        if (form[0].checkValidity() === false ) {
              event.preventDefault();
              event.stopPropagation()
        }

       form.addClass('was-validated');

      });


    $('.quantity_input').on('input', function(){
        var id;
        var index;
        var value;
        var cons;
        var cons_val;
        var subtotal;
        var unit_total = 0;
        var file_id = $(this).data('file_id');
        $('.quantity_input_'+file_id).each(function() {
            id = $(this).attr('id');
            index = id.replace('price_quantity_', '');
            value = $(this).val();

            cons = $('#price_const_'+index+'_'+file_id);
            cons_val = cons.val();

            subtotal = cons_val * value;
            unit_total += subtotal;
        });

        var count = parseInt($('.print_count_'+file_id).val());
        var total_price_before_discount = unit_total * count;
        total_price_before_discount = parseFloat(total_price_before_discount);
        $('.total_price_before_discount_'+file_id).val(total_price_before_discount.toFixed(2));
        var discount = parseInt($('.discount_'+file_id).val());
        if(typeof discount === 'number' && typeof total_price_before_discount === 'number' && !isNaN(discount)){
            var total_price = total_price_before_discount - (total_price_before_discount * (discount/100));
            total_price = total_price.toFixed(2);
            $('.total_price_'+file_id).val(total_price);
        } else {
            total_price_before_discount = total_price_before_discount.toFixed(2);
            $('.total_price_'+file_id).val(total_price_before_discount);
        }
        setGrandTotal();
    });

    // Update total price before discount based on unit price
    $('.unit_price').on('input' ,function(){
        var file_id = $(this).data('file_id');
        var count = parseInt($('.print_count_'+file_id).val());
        var unit_price = parseFloat($('.unit_price_'+file_id).val());
        var x = typeof count;
        if(typeof count === 'number' && typeof unit_price === 'number'){
            var total_price_before_discount = unit_price * count;
            total_price_before_discount = parseFloat(total_price_before_discount.toFixed(2));
            $('.total_price_before_discount_'+file_id).val(total_price_before_discount.toFixed(2));
        }
        var discount = parseInt($('.discount_'+file_id).val());
        if(typeof discount === 'number' && typeof total_price_before_discount === 'number' && !isNaN(discount)){
            var total_price = total_price_before_discount - (total_price_before_discount * (discount/100));
            total_price = total_price.toFixed(2);
            $('.total_price_'+file_id).val(total_price);
        } else {
            total_price_before_discount = total_price_before_discount.toFixed(2);
            $('.total_price_'+file_id).val(total_price_before_discount);
        }

        setGrandTotal();
    });

    // Update unit price based on total price
    $('.total_price_before_discount').on('input' ,function(){
        var file_id = $(this).data('file_id');
        var count = parseInt($('.print_count_'+file_id).val());
        var total_price_before_discount = parseFloat($('.total_price_before_discount_'+file_id).val());
        var x = typeof count;

        if(typeof count === 'number' && typeof total_price_before_discount === 'number'){
            var unit_price = total_price_before_discount / count;
            unit_price = unit_price.toFixed(2);
            $('.unit_price_'+file_id).val(unit_price);
        }
        var discount = parseInt($('.discount_'+file_id).val());
        if(typeof discount === 'number' && typeof total_price_before_discount === 'number' && !isNaN(discount)){
            var total_price = total_price_before_discount - (total_price_before_discount * (discount/100));
            total_price = total_price.toFixed(2);
            $('.total_price_'+file_id).val(total_price);
        } else {
            $('.total_price_'+file_id).val(total_price_before_discount);
        }

        setGrandTotal();
    });

    // Update total price based on total price
    $('.discount').on('input' ,function(){
        var file_id = $(this).data('file_id');
        var total_price_before_discount = parseFloat($('.total_price_before_discount_'+file_id).val());
        var discount = parseInt($('.discount_'+file_id).val());
        if(typeof discount === 'number' && typeof total_price_before_discount === 'number' && !isNaN(discount)){
            var total_price = total_price_before_discount - (total_price_before_discount * (discount/100));
            total_price = total_price.toFixed(2);
            $('.total_price_'+file_id).val(total_price);
        } else {
            $('.total_price_'+file_id).val(total_price_before_discount);
        }
        setGrandTotal();
    });

    $('.total_price').on('input' ,function(){
        var file_id = $(this).data('file_id');
        var total_price_before_discount = parseFloat($('.total_price_before_discount_'+file_id).val());
        var total_price = parseFloat($('.total_price_'+file_id).val());
        if(typeof total_price_before_discount === 'number' && !isNaN(total_price_before_discount)){
            var discount = (100 - (total_price/total_price_before_discount *100)).toFixed(2);
            if (discount > 0) {
                $('.discount_'+file_id).val(discount);
            } else {
                 $('.discount_'+file_id).val(0);
            }
        }
        setGrandTotal();

    });

    // update the grand total of the files
    function setGrandTotal() {
        var grand_total = 0;
        $('.total_price').each(function(i, total) {
            var file_total = parseFloat($(total).val());
            if(typeof file_total === 'number' && !isNaN(file_total)){
                grand_total += file_total
            }
        });
        grand_total = grand_total.toFixed(2);
        $(".prime_grand_total").val(grand_total)
        console.log(grand_total)

    }


    //Bootstrap tooltip
    $("[data-toggle=tooltip]").tooltip();

    //Bootstrap popover
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })

    //Adding the file name into the inputbox on upload
    $('#customFile').on('change', function(){
        var input = document.getElementById('customFile');
        //for every file...
        file_names = "";
        for (var x = 0; x < input.files.length; x++) {
            //add to list
            file_name =  input.files[x].name;
            file_names +=  file_name + " ";
        }
        console.log(file_names);
        if(file_names){
            $("#add_file_label").html(file_names);
        }

    });

    //workflow selection dropdown
    $(document).on('change','.prime_select_types', function(){
        var id = this.id.replace("prime_select_types_", "");
        $('.prime_materials_'+id).html('');
        $('.prime_copies_'+id).html('');
        $('.prime_colors_'+id).html('');

        var type = this.value;
        var options = {options : {id: id, type: type, path:'AddToCart', func: 'getPrinters' } };

        var url = "\?t=ajax";
        updateDisplay("GET", options, url);
        savetoCartButtons();
    });

    //printers selection dropdown
    $(document).on('change','.prime_select_printers', function(){
        var id = this.id.replace("prime_select_printers_", "");
        $('.prime_colors_'+id).html('');
        var type = $('#prime_select_types_'+id).val();
        var printer = this.value;
        var options = {options : {id: id, type: type, printer: printer, path:'AddToCart', func: 'getMaterials' } };

        var url = "\?t=ajax";
        updateDisplay("GET", options, url);
        savetoCartButtons();
    });

    // Materials selection dropdown
    $(document).on('change' ,'.prime_select_materials', function(){
        var id = this.id.replace("prime_select_materials_", "");
        var type = $('#prime_select_types_'+id).val();
        var printer = $('#prime_select_printers_'+id).val();
        var material  = this.value;
        var options = {options : {id: id, type: type, printer: printer, material:material,  path:'AddToCart', func: 'getColors' } };
        var url = "\?t=ajax";
        updateDisplay("GET", options, url);
        savetoCartButtons();

    });

    //Colors selection dropdown
    $(document).on('change' ,'.prime_select_colors', function(){
        var id = this.id.replace("prime_select_colors_", "");
        var type = $('#prime_select_types_'+id).val();
        var printer = $('#prime_select_printers_'+id).val();
        var material = $('#prime_select_materials_'+id).val();
        var color = this.value;

        var options = {options : {id: id, type: type, printer: printer, material:material, color:color, path:'AddToCart', func: 'getCopies' } };

        var url = "\?t=ajax";
        var copies = $('#prime_set_copies_'+ id).val();
        if(!copies){
            updateDisplay("GET", options, url);
        }
        savetoCartButtons();
    });

    //pickup option selection dropdown
    $(document).on('change' ,'#inputGroupDelivery', function(){
        var delivery = $('#inputGroupDelivery').val();
        if(delivery === "shipping"){
            $("#inputCampus").hide();
            $("#inputShipping").show();
        }
        else if(delivery === "campus-mail"){
            $("#inputShipping").hide();
            $("#inputCampus").show();
        }
        else{
            $("#inputShipping").hide();
            $("#inputCampus").hide();

        }
        savetoCartButtons();
    });

    $(document).on('keyup' ,'#inputGroupCopies', function(){
        var copies = this.value;
        savetoCartButtons();
    });


    $(document).on('keyup' ,'#inputGroupDimensions', function(){
        savetoCartButtons();
    });

    $(document).on('keyup' ,"#inputGroupAddress", function(){
        savetoCartButtons();
    });

    $(document).on('keyup' ,"#inputGroupAddress", delay(function(){
        ValidateShippingInfo();
    }, 300));

    $(document).on('keyup' ,'#inputGroupCity', function(){
        savetoCartButtons();
    });

    $(document).on('keyup' ,'#inputGroupCity', delay(function(){
        ValidateShippingInfo();
    }, 300));

    $(document).on('keyup' ,'#inputGroupState', function(){
        savetoCartButtons()
    });

    $(document).on('keyup' ,'#inputGroupState', delay(function(){
        ValidateShippingInfo();
    }, 300));

    $(document).on('keyup' ,'#inputGroupZip', function(){
        savetoCartButtons();
    });

    $(document).on('keyup' ,'#inputGroupZip', delay(function(){
        ValidateShippingInfo();
    }, 300));

    $(document).on('keyup' ,'#ship-field-info', function(){
        savetoCartButtons();
    });

    $(document).on('keyup' ,'#project_name', function(){
        savetoCartButtons();
    });

    $(document).on('keyup' ,'.prime_set_copies', function(){
        savetoCartButtons();
    });



    // click action to send a message on job status page
    $('#send_message').on('click' ,function(e){

        var message_text = $('#message_text').val();
        $('#message_text').val('');
        var job_id = $('#message_job_id').val();
        var job_step_id = $('#message_step_id').val();
        var url = "\?t=ajax";
        var options = {options : {text:message_text, job_id:job_id ,job_step_id:job_step_id ,  path: 'SendMessage', func:'sendJobMessage'}};
        if(message_text.trim() !== ""){
            updateDisplay("GET", options, url);
        }
        e.preventDefault();
    });
    // click action to send a message on job status page
    $('#send_message_to_staff').on('click' ,function(e){

        var message_text = $('#message_text').val();
        $('#message_text').val('');
        var job_id = $('#message_job_id').val();
        var job_step_id = $('#message_step_id').val();
        var url = "\?t=ajax";
        var options = {options : {text:message_text, job_id:job_id ,job_step_id:job_step_id ,  path: 'SendMessage', func:'sendJobMessageToStaff'}};
        if(message_text.trim() !== ""){
            updateDisplay("GET", options, url);
        }
        e.preventDefault();
    });
    //click action for adding a note
    $('#add_note').on('click' ,function(e){

        var note_text = $('#note_text').val();
        $('#note_text').val('');
        var job_id = $('#note_job_id').val();
        var url = "\?t=ajax";
        var options = {options : {text:note_text, job_id:job_id ,  path: 'AddNote', func:'addJobNote'}};
        if(note_text.trim() !== ""){

            updateDisplay("GET", options, url);
        }
        e.preventDefault();
    });

    $('#show_notes').on('click' ,function(e){
        if($("#notes").is(":visible")) {
            $("#notes").hide();
            $("#job_add_note_icon").html(feather.icons.plus.toSvg())
        } else {
            $("#notes").show();
            $("#job_add_note_icon").html(feather.icons.minus.toSvg())
        }
        /*
        var notes = $(".notes");
        var icon = document.getElementById("note_icon")
        //icon.data_feather = ("minus")

        if (notes.style.display === "none") {
            notes.style.display = "block";
        } else {
            notes.style.display = "none";
        }

        //updateDisplay("GET", options, url);
        */
        e.preventDefault();

    });

    $('#show_add_note').on('click' ,function(e){
        if($("#add_note_block").is(":visible")) {
            $("#add_note_block").hide();
            $("#add_note_footer").hide();
            $("#show_add_note span").html(feather.icons.plus.toSvg())
        } else {
            $("#add_note_block").show();
            $("#add_note_block").show();
            $("#add_note_footer").show();
            $("#show_add_note span").html(feather.icons.minus.toSvg())
        }

        //updateDisplay("GET", options, url);

        e.preventDefault();

    });





    function updateDisplay(type, options, url){
        $.ajax({
          type: type,
          url: url,
          data: options,
          dataType: 'json',
          success: function ( data, status){
            updateBlocks(data['selector'], data['action'], data['value']);
          },
          error: function (data, status){
              alert("An error has occured. Please try again!")
          }
        });

    }

    function updateBlocks(selector, action, data){
        switch (action) {
            case 'replace':
                $('.'+selector).html(data);
                break;
            case 'replace_id':
                $('#'+selector).html(data);
                break;
            default :
                break;
        }

    }

    function delay(fn, ms) {
        let timer = 0
        return function(...args) {
            clearTimeout(timer)
            timer = setTimeout(fn.bind(this, ...args), ms || 0)
        }
    }

    function USPSValidate(options){
        return $.ajax({
            type: "GET",
            url: "\?t=ajax",
            data: options,
        });
    }

    /**
     * @return {boolean}
     */
    function ValidateShippingInfo(){
        var info = ShippingInfoComplete();
        parser = new DOMParser();
        var valid = false;
        if(info) {
            var options = {options: {info: info, path: 'verifyShipping', func: 'verifyShipping'}};
            ajaxRequest = USPSValidate(options);
            ajaxRequest.done(function (response, textStatus, jqXHR) {
                $xml = $( response );
                console.log(response);
                if($xml.find("error").text()){
                    console.log("Invalid Address");
                    $("#inputShipping").css( "border", " 1px solid red" );
                }
                else{
                    $("#inputShipping").css('border', "1px solid green");
                    console.log("valid address");
                    cartWarning = "Missing required fields";
                    valid = true;
                }

            });

            ajaxRequest.fail(function (response) {
                console.log("failure to verify shipping info");
                console.log(response);
            });
        }
        else{
            $("#inputShipping").css('border', " 1px solid #e3e3e3");

        }
        }

    /**
     * @return
     */
    function ShippingInfoComplete(){
            var address = $("#inputGroupAddress").val();
            var city = $("#inputGroupCity").val();
            var state = $("#inputGroupState").val();
            var zip = $("#inputGroupZip").val();

            if(address && city && state && zip){
                return { "ship-address" : address, "ship-city" : city, "ship-state" : state, "ship-zip" : zip };
            }
            else{
                cartWarning = "Shipping information incomplete";
            }

    }

    function savetoCartButtons(){
        $('.save_cart_disabled').attr('data-content', cartWarning);
        var types = Array()
        $('.prime_select_types').each(function(i, print_type) {
            var file_id = print_type.id.replace("prime_select_types_", "");
            types.push(file_id);
        });
        var printers = Array()
        $('.prime_select_printers').each(function(i, printer_type) {
            var file_id = printer_type.id.replace("prime_select_printers_", "");
            printers.push(file_id);
        });
        var materials = Array()
        $('.prime_select_materials').each(function(i, material_type) {
            var file_id = material_type.id.replace("prime_select_materials_", "");
            materials.push(file_id);
        });
        var colors = Array()
        $('.prime_select_colors').each(function(i, color_type) {
            var file_id = color_type.id.replace("prime_select_colors_", "");
            colors.push(file_id);
        });
        var dimensions = Array()
        $('.prime_set_dimensions').each(function(i, dimension_type) {
            var file_id = dimension_type.id.replace("prime_set_dimensions_", "");
            dimensions.push(file_id);
        });
        var copies = Array()
        $('.prime_set_copies').each(function(i, copy_type) {
            var file_id = copy_type.id.replace("prime_set_copies_", "");
            //$.isNumeric(copies) && copies !== 0
            copies.push(file_id);
        });
        var can_edit_project_name = false;

        if($('#project_name').length > 0) {
            var project_name = $('#project_name').val();
            project_name = project_name.trim().replace( /<.*?>/g, '' );
            can_edit_project_name = true;
        }
        var delivery = $('#inputGroupDelivery').val();

        var shipInfo = $("#ship-field-info").val();
        // FIXME: check for updates to copies when editing a file

        if(( (can_edit_project_name && project_name.length > 3) || !can_edit_project_name) && ((types.length == printers.length) && (types.length == materials.length) && (types.length == colors.length)  && (types.length == dimensions.length) && (types.length == copies.length) && delivery) && ((shipInfo || (delivery==="Locker-pickup") || ( delivery==="ServiceDesk-pickup" )) || ShippingInfoComplete())){
            $('.save_cart').show();
            $('.save_cart_disabled').hide();

        }else{
            $('.save_cart').hide();
            $('.save_cart_disabled').show();
        }
    }

    $('.save_cart').click(function(e){

        var delivery = $('#inputGroupDelivery').val();
        localStorage.clear()
        if(delivery==='shipping' && $("#input_action").val()!=="update"){
            e.preventDefault();
            var info = ShippingInfoComplete();
            var options = {options: {info: info, path: 'verifyShipping', func: 'verifyShipping'}};
            ajaxRequest = USPSValidate(options);
            ajaxRequest.done(function (response, textStatus, jqXHR) {
                var modal = $("#InvalidAddressModal");
                $xml = $( response );
                if($xml.find("error").text()){
                    modal.modal('show');
                    console.log("could not validate address");
                }
                else{
                    $('.save_cart_modal').click();
                }


            });

            ajaxRequest.fail(function (response) {
                var modal = $("#InvalidAddressModal");
                $xml = $( response );
                if($xml.find("error").text()){
                    modal.modal('show');
                }
            });
        }
    });

    $('.prime_messages').hover(
        function(){
            $(this).css("background-color","#EDF7FD");
        },function(){
            $(this).css("background-color","#ffffff");

        }
    );

    var last_cancel_id;
    $('input[type=radio][name=cancelRadios]').on('change', function() {

        var id = $(this).val();
        var textarea = document.getElementById("cancel_more_info_"+id);
        $('#cancel_more_info_'+last_cancel_id).hide(120);
        $('#more_break_' + last_cancel_id).hide(100);
        if(textarea){
            $('#cancel_more_info_'+id).show(120);
            $('#more_break_'+id).show(100);
            $('#cancel_more_info_'+id).focus();

            last_cancel_id = id;
        }

    });

    //click function for messages
    $('.prime_messages').on('click', function(){
        var job_id = parseInt($(this).attr("data-message-job-id"));
        var user_id = parseInt($(this).attr("data-message-user-id"));
        if(typeof job_id  === "number" && typeof user_id ==="number"){
            window.location.href = '/?t=message_details&job_id='+job_id+'&user_id='+user_id;
        }
    });
    // Back button for the application
    $('.prime_go_back').on('click', function(){
        window.history.back();
    });


    //Cancel edit user status
    $('.cancel_edit_status').on('click', function(e){
        e.preventDefault();
        $('.prime_edit_user_status').hide();
        $('.prime_user_status').show();


    });
    //Edit user status
    $('.edit_user_status').on('click', function(e){
        e.preventDefault();
        $('.prime_user_status').hide();
        $('.prime_edit_user_status').show();


    });
    //Cancel edit user status
    $('.cancel_edit_user_details').on('click', function(e){
        e.preventDefault();
        $('.prime_edit_user_details').hide();
        $('.prime_user_details').show();


    });
    //Edit user status
    $('.edit_user_details').on('click', function(e){
        e.preventDefault();
        $('.prime_user_details').hide();
        $('.prime_edit_user_details').show();


    });

    //Edit workflows printers etc
    // common function for all edits in infrastructure
    $('.edit_btn').on('click', function(e){
        e.preventDefault();
        var id = this.id;
        var edit_card_id = id.replace('edit_','');
        $('#'+edit_card_id).find('.prime_card_solid_form').hide();
        $('#'+edit_card_id).find('.prime_card_hidden_form').show();



    });
    // Cancel edit for all forms in manage infrastructure
    $('.back_btn').on('click', function(e){
        e.preventDefault();
        var id = this.id;
        var edit_card_id = id.replace('back_','');
        $('#'+edit_card_id).find('.prime_card_hidden_form').hide();
        $('#'+edit_card_id).find('.prime_card_solid_form').show();


    });

    // Add button for infrastructure
    $('.prime_add_card, .prime_long_add_card').on('click', function(e){
        e.preventDefault();
        $('.prime_add_card, .prime_long_add_card').hide();
        $('.prime_add_hidden_card').show();
    });
    // Back btn for add button for infrastructure
    $('.add_back_btn').on('click', function(e){
        e.preventDefault();
        $('.prime_add_hidden_card').hide();
        $('.prime_add_card, .prime_long_add_card').show();
    })


});
