//v2.0.3

jQuery(function($) {

  $(document).ready(function() {

    // Add Color Picker to all inputs that have 'cpa-color-picker' class

    $('.cpa-color-picker').wpColorPicker();

    $('.fontawesome-picker').iconpicker({
      placement: 'topRight',
      component: '.iconpicker-component',
      hideOnSelect: true
    });


    // Reset AJAX form
    var wp_settings_api_reset_settings_form = $('#ds-wp-settings-reset');

    var wp_settings_api_response = $('#ds-wp-settings-reset-response');

    $(wp_settings_api_reset_settings_form).submit(function(event){
      event.preventDefault();

      // get the item slug 
      var wp_settings_api_item_slug = $(event.target).data('item-slug');

      var ds_wp_settings_api_remove_data = $('#ds_wp_settings_api_remove_data').prop('checked');

      var wp_settings_api_response_data = '<hr>';

      var wp_settings_api_action =
      $.ajax({
          type: 'POST',
          url: ajaxurl,
          data: {
            action: 'ds_wp_api_reset_settings-' + wp_settings_api_item_slug,
            remove_data: ds_wp_settings_api_remove_data,
          },
          success: function (response) {

            var current_time = new Date($.now());
            wp_settings_api_response_data += '<p><strong>' + current_time + '</strong></p>';

            wp_settings_api_response_data += '<h4>Result: ' + response.messages + '</h4>';
            //final
            $(wp_settings_api_response).prepend(wp_settings_api_response_data);
          }
      }); //end $.ajax

    }); //end $(wp_settings_api_reset_settings_form).submit(function(event)
    
    /*
     * Scripts to show or hide sections of the Settings UI based upon
     * selected radio buttons or select menu options.
     */

    //function to show or hide or show input fields
    function ShowHideFields() {

      //get the value of the button clicked
      var toggleTypeValue = $(this).val();
      var toggleClasses = $(this).attr('class');
      
      // handle situations where there's more than one class
      toggleClasses = /(toggle_)\w+/.exec(toggleClasses)[0];
 
      var radioParent = $(this).parentsUntil('tr').parent();
      radioParent.addClass('show-option');
 
      //select all the types we want to show
      var toggles = $('.' + toggleClasses).parentsUntil('tr').parent();
      
      var togglesToShow =  $('.' + toggleClasses + '.' + toggleTypeValue).parentsUntil('tr').parent();
       
      toggles.addClass('hide-option');
      togglesToShow.removeClass('hide-option');
    }
     //show or hide the sections based on clicking the radio button or choosing a select value
     $('input[class^="toggle_"][type="radio"],input[class^=" toggle_"][type="radio"]').on('change', ShowHideFields );
     $('select[class^="toggle_"]').on('change', ShowHideFields );
 
     //Show or hide on page load
     $('input[class^="toggle_"][type="radio"]:checked,input[class^=" toggle_"][type="radio"]:checked').trigger( 'change' );
     $('select[class^="toggle_"]').trigger('change');

    ///////////////////////////////////
    // handle a multifield_text input
    ///////////////////////////////////

    // delete one of the inputs 
    $('.ds-wp-api-expanding-input-fields .ds-wp-api-expanding-input .ds-wp-api-expanding-input-remove').after().on('click', function(){
        $(this).parent().remove();
    });

    // add an input 
    $('.ds-wp-api-expanding-input-fields .ds-wp-api-expanding-input-fields-add').after().on('click', function(){
      var cloned_input = $(this).prev().clone(true, true).insertBefore('.ds-wp-api-expanding-input-fields-add');
      $(cloned_input).find("input").val('');
    });
    
  }); //end $(document).ready(function()


});
