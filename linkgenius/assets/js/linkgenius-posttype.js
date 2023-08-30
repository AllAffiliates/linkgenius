jQuery(document).ready(function($) {
    $('#post input[type=submit]').on('click', function(event) {
      var generalSlugField = $('#general_slug');
      var titleField = $('#title');
  
      if (generalSlugField.val().trim() === '') {
        generalSlugField.val(titleField.val());
      }
      $('#post input[type=number]').each(function(index, el) {
        if ($(el).val().trim() === '' && $(el).attr('required') !== undefined && $(el).attr('data-default') !== undefined) {
          $(el).val($(el).attr('data-default'));
        }
      });
    })
});