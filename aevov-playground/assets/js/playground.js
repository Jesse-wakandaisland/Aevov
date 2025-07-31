(function ($) {
  'use strict';

  var Playground = {
    init: function () {
      var self = this;
      $('.draggable-block').draggable({
        helper: 'clone',
        revert: 'invalid'
      });

      $('#playground-canvas').droppable({
        drop: function (event, ui) {
          var block = $(ui.draggable).clone();
          block.removeClass('draggable-block');
          block.addClass('playground-block');
          $(this).append(block);
          self.addEndpoints(block);
        }
      });

      $('#playground-canvas').on('click', '.endpoint', function (e) {
        self.handleEndpointClick($(this));
      });
    },

    addEndpoints: function (block) {
      block.append('<div class="endpoint input"></div>');
      block.append('<div class="endpoint output"></div>');
    },

    handleEndpointClick: function (endpoint) {
      if (this.selectedEndpoint) {
        this.connectEndpoints(this.selectedEndpoint, endpoint);
        this.selectedEndpoint.removeClass('selected');
        this.selectedEndpoint = null;
      } else {
        endpoint.addClass('selected');
        this.selectedEndpoint = endpoint;
      }
    },

    connectEndpoints: function (start, end) {
      var startPos = start.offset();
      var endPos = end.offset();
      var canvasPos = $('#playground-canvas').offset();

      var line = $('<div class="connection-line"></div>');
      line.css({
        top: startPos.top - canvasPos.top + 5,
        left: startPos.left - canvasPos.left + 5,
        width: endPos.left - startPos.left
      });

      $('#playground-canvas').append(line);
    }
  };

  $(document).ready(function () {
    Playground.init();
  });

})(jQuery);
