(function ($) {
  'use strict';

  var TextGenerator = {
    init: function () {
      $('#text-generator-form').on('submit', this.startTextGeneration);
    },

    startTextGeneration: function (e) {
      e.preventDefault();
      var prompt = $('#prompt').val();

      $.ajax({
        url: '/wp-json/aevov-language/v1/generate',
        method: 'POST',
        data: {
          prompt: prompt
        },
        beforeSend: function (xhr) {
          // You might need a nonce if the endpoint requires authentication.
        }
      }).done(function (response) {
        if (response.text) {
          $('#text-result-container').html(response.text);
        } else {
          alert('Error generating text.');
        }
      }).fail(function () {
        alert('Error generating text.');
      });
    }
  };

  $(document).ready(function () {
    TextGenerator.init();
  });

})(jQuery);
