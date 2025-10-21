jQuery(function ($) {
  // Move layers into their nearest canvas, even inside nested sections/groups
  $('.frm_form_field').has('img.simulator-layer-img').each(function () {
    var container = $(this);
    var img = container.find('img.simulator-layer-img');
    if (img.length === 0) {
      return;
    }

    // Find the closest Canvas Background section wrapper for this layer
    var section = container.closest('.frm_form_field.frm_section_heading');
    var canvas = section.find('> .frm_fields_container .simulator-canvas');
    if (canvas.length === 0) {
      // Fallback: search within the section for any canvas
      canvas = section.find('.simulator-canvas').first();
    }
    if (canvas.length === 0) {
      // No canvas found for this layer; skip
      return;
    }

    img.css({
      position: 'absolute',
      top: 0,
      left: 0,
      width: '100%',
      height: '100%',
      objectFit: 'cover',
    });

    canvas.append(img);
    container.css('display', 'none');

    // Keep visibility in sync with the original container (e.g., conditional logic)
    var observer = new MutationObserver(function () {
      img.css('display', container.is(':visible') ? 'block' : 'none');
    });
    observer.observe(container[0], { attributes: true, attributeFilter: ['style', 'class'] });
    img.css('display', container.is(':visible') ? 'block' : 'none');
  });

  // Merge canvases on submit
  $(document).on('click', '.frm_button_submit', function (e) {
    e.preventDefault();
    var form = $(this).closest('form');
    var canvases = form.find('.simulator-canvas');
    var processed = 0;

    if (canvases.length === 0) {
      form[0].submit();
      return;
    }

    canvases.each(function () {
      var canvasDiv = $(this);
      var canvasId = canvasDiv.attr('id');
      var c = document.createElement('canvas');
      var rect = canvasDiv[0].getBoundingClientRect();
      var w = Math.max(1, Math.round(rect.width));
      var h = Math.max(1, Math.round(rect.height));
      c.width = w;
      c.height = h;
      var ctx = c.getContext('2d');

      var bgImg = canvasDiv.find('img').eq(0)[0];

      function drawAndProcess() {
        if (bgImg) {
          ctx.drawImage(bgImg, 0, 0, w, h);
        }
        var layers = canvasDiv.find('img.simulator-layer-img:visible');
        var loaded = 0;
        if (layers.length === 0) {
          finalize();
          return;
        }
        layers.each(function (i, layerImg) {
          if (layerImg.complete) {
            ctx.drawImage(layerImg, 0, 0, w, h);
            loaded++;
            if (loaded === layers.length) finalize();
          } else {
            layerImg.onload = function () {
              ctx.drawImage(layerImg, 0, 0, w, h);
              loaded++;
              if (loaded === layers.length) finalize();
            };
          }
        });
      }

      function finalize() {
        var base64 = c.toDataURL('image/png');
        $('#merged_' + canvasId).val(base64);
        processed++;
        if (processed === canvases.length) {
          form[0].submit();
        }
      }

      if (!bgImg || bgImg.complete) {
        drawAndProcess();
      } else {
        bgImg.onload = drawAndProcess;
      }
    });
  });
});
