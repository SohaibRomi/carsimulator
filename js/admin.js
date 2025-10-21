jQuery(function ($) {
  // Media uploader for background/layer images
  $(document).on('click', '.frm_sim_upload_button', function (e) {
    e.preventDefault();
    var button = $(this);
    var fieldId = button.data('field-id');
    var uploadType = button.data('upload-type');

    var frame = wp.media({
      title: uploadType === 'background' ? 'Select Background Image' : 'Select Layer Image',
      button: { text: 'Use image' },
      multiple: false,
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      var inputId = uploadType === 'background' ? '#background_image_' + fieldId : '#layer_image_' + fieldId;
      var previewId = uploadType === 'background' ? '#bg-preview-' + fieldId : '#layer-preview-' + fieldId;
      $(inputId).val(attachment.id);
      var $preview = $(previewId);
      if ($preview.length) {
        $preview.attr('src', attachment.url).show();
      } else {
        $('<img>', { id: previewId.replace('#', ''), src: attachment.url, style: 'max-width:200px; display:block; margin-bottom:10px;' }).insertBefore(button);
      }
    });

    frame.open();
  });
});
