//v2.3.1

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

    ///////////////////////////////////
    // handle repeater fields
    ///////////////////////////////////

    $(document).on('click', '.ds-repeater-add-row', function () {
        var fieldId     = $(this).data('field-id');
        var $repeater   = $('#' + fieldId + '_repeater');
        var $template   = $('#' + fieldId + '_template');
        var optionBase  = $repeater.data('option-base');
        var newIndex    = $repeater.find('.ds-repeater-row').length;

        // Clone the template row and wire up real input names
        var $newRow = $template.find('.ds-repeater-row').clone(true, true);
        $newRow.find('input[data-col-key]').each(function () {
            var colKey = $(this).data('col-key');
            $(this).attr('name', optionBase + '[' + newIndex + '][' + colKey + ']');
            $(this).removeAttr('data-col-key');
        });

        $repeater.find('tbody').append($newRow);
    });

    $(document).on('click', '.ds-repeater-remove-row', function () {
        var $tbody = $(this).closest('tbody');
        // Refuse to remove the last row — leave a blank row instead
        if ($tbody.find('.ds-repeater-row').length <= 1) {
            $(this).closest('.ds-repeater-row').find('input').val('');
            return;
        }
        $(this).closest('.ds-repeater-row').remove();

        // Re-index remaining rows so the name array is sequential on save
        var $repeater  = $tbody.closest('.ds-repeater');
        var optionBase = $repeater.data('option-base');
        $tbody.find('.ds-repeater-row').each(function (i) {
            $(this).find('input').each(function () {
                // name format: option_base[field_id][oldIndex][col_key]
                $(this).attr('name', function (_, oldName) {
                    return oldName.replace(/\[\d+\]\[/, '[' + i + '][');
                });
            });
        });
    });
    $(document).on('click', '.ds-repeater-move-up, .ds-repeater-move-down', function () {
        var $btn    = $(this);
        var $row    = $btn.closest('.ds-repeater-row');
        var $tbody  = $row.closest('tbody');
        var isUp    = $btn.hasClass('ds-repeater-move-up');

        if (isUp) {
            var $prev = $row.prev('.ds-repeater-row');
            if ($prev.length) {
                $prev.before($row);
            }
        } else {
            var $next = $row.next('.ds-repeater-row');
            if ($next.length) {
                $next.after($row);
            }
        }

        // Re-index all rows after the move — same logic as remove
        var $repeater  = $tbody.closest('.ds-repeater');
        var optionBase = $repeater.data('option-base');
        $tbody.find('.ds-repeater-row').each(function (i) {
            $(this).find('input').each(function () {
                $(this).attr('name', function (_, oldName) {
                    return oldName.replace(/\[\d+\]\[/, '[' + i + '][');
                });
            });
        });

        // Keep focus on the button after the DOM move
        $tbody.find('.ds-repeater-row').eq(
            isUp
                ? $row.index()
                : $row.index()
        ).find($btn.hasClass('ds-repeater-move-up') ? '.ds-repeater-move-up' : '.ds-repeater-move-down')
         .trigger('focus');
    });
    
    /* Select2 */
    if ($.fn.select2) {
      // Standard (non-AJAX) post selects
      $('.ds-post-select--select2').not('.ds-post-select--ajax').select2({
        width: '100%',
        allowClear: true,
        placeholder: function(){ return $(this).data('placeholder') || ''; }
      });

      // AJAX-powered post selects
      $('.ds-post-select--ajax').each(function() {
        var $el = $(this);
        $el.select2({
          width: '100%',
          allowClear: true,
          placeholder: $el.data('placeholder') || '',
          minimumInputLength: 2,
          ajax: {
            url: dsWpSettingsApi.ajaxUrl,
            dataType: 'json',
            delay: 300,
            data: function(params) {
              return {
                action:      dsWpSettingsApi.postSearchAction,
                _nonce:      dsWpSettingsApi.postSearchNonce,
                q:           params.term,
                post_type:   $el.data('post-type') || 'post',
                post_status: $el.data('post-status') || 'publish',
                page:        params.page || 1
              };
            },
            processResults: function(data) {
              return {
                results:    data.results,
                pagination: data.pagination
              };
            },
            cache: true
          }
        });
      });
    }
  }); //end $(document).ready(function()


});