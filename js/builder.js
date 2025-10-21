jQuery(function ($) {
  // Treat Canvas Background as a section in the builder UI
  $('li.frm_field_box[data-type="canvas_background"]').addClass(
    'frm_divider frm_section_heading frm_has_fields frm_collapse_section'
  );

  // Allow layers and group containers inside Canvas; block layers outside Canvas
  $(document).on('sortreceive', '.frm_sortable_field_list', function (event, ui) {
    var helperId = ui.helper && ui.helper.attr('id') ? ui.helper.attr('id') : '';
    var fieldType = helperId
      ? helperId.replace('frm_', '').replace('_field', '')
      : '';

    // Is this drop target nested anywhere within a Canvas Background field?
    var inCanvas = $(event.target)
      .closest('li.frm_field_box[data-type="canvas_background"]')
      .length > 0;

    var allowedInCanvas = ['html', 'simulator_layer', 'section', 'repeater'];

    if (inCanvas) {
      if (allowedInCanvas.indexOf(fieldType) === -1) {
        $(this).sortable('cancel');
        alert(
          'Only HTML, Simulator Layer, and Group/Repeater fields are allowed inside Canvas Background.'
        );
      }
    } else {
      if (fieldType === 'simulator_layer') {
        $(this).sortable('cancel');
        alert('Simulator Layer can only be added inside a Canvas Background.');
      }
    }
  });
});
