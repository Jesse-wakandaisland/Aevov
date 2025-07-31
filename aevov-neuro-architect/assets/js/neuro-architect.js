(function ($) {
  'use strict';

  var NeuroArchitect = {
    init: function () {
      $('#blueprint-form').on('submit', this.composeModel);
    },

    composeModel: function (e) {
      e.preventDefault();
      var blueprintName = $('#blueprint-name').val();
      var blueprintLayers = $('#blueprint-layers').val();

      $.ajax({
        url: '/wp-json/aevov-neuro/v1/compose',
        method: 'POST',
        data: {
          blueprint: {
            name: blueprintName,
            layers: blueprintLayers
          }
        },
        beforeSend: function (xhr) {
          // You might need a nonce if the endpoint requires authentication.
        }
      }).done(function (response) {
        if (response.model_id) {
          NeuroArchitect.displayModel(response);
        } else {
          alert('Error composing model.');
        }
      }).fail(function () {
        alert('Error composing model.');
      });
    },

    displayModel: function (model) {
      var container = $('#model-visualizer-container');
      container.html('<pre>' + JSON.stringify(model, null, 2) + '</pre>');
    }
  };

  $(document).ready(function () {
    NeuroArchitect.init();
  });

})(jQuery);
