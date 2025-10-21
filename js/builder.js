jQuery(document).ready(function($) {
  // Ensure canvas fields look and behave like sections in the builder
  $('li.frm_field_box[data-type="canvas_background"]').addClass(
    'frm_divider frm_section_heading frm_has_fields frm_collapse_section'
  );

  // Restrict dragging in sortable lists
  $(document).on('sortreceive', '.frm_sortable_field_list', function(event, ui) {
    var dragged = ui.helper || ui.item;
    var fieldType = '';
    if (dragged && dragged.attr('id')) {
      fieldType = dragged.attr('id').replace('frm_', '').replace('_field', '');
    }

    var targetLi = $(event.target).closest('li.frm_field_box');
    var isCanvas = targetLi.data('type') === 'canvas_background';

    if (isCanvas) {
      if (fieldType !== 'html' && fieldType !== 'simulator_layer') {
        $(this).sortable('cancel');
        window.alert('Only HTML and Simulator Layer fields are allowed inside Canvas Background.');
      }
    } else if (fieldType === 'simulator_layer') {
      $(this).sortable('cancel');
      window.alert('Simulator Layer can only be added inside a Canvas Background.');
    }
  });
});
