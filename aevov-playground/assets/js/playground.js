(function ($) {
  'use strict';

  var Playground = {
    init: function () {
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
        }
      });

      $('#generate-app').on('click', function () {
        var blocks = [];
        $('.playground-block').each(function () {
          blocks.push($(this).data('engine'));
        });

        $.ajax({
          url: '/wp-json/aevov-sim/v1/visualize',
          method: 'POST',
          data: {
            model: {
              blocks: blocks
            }
          }
        }).done(function (response) {
          if (response.visualization) {
            $('#playground-canvas').html(response.visualization);
          } else {
            alert('Error generating visualization.');
          }
        }).fail(function () {
          alert('Error generating visualization.');
        });
      });

      $('#spawn-as-application').on('click', function () {
        var blocks = [];
        $('.playground-block').each(function () {
          blocks.push($(this).data('engine'));
        });

        $.ajax({
          url: '/wp-json/aevov-app/v1/spawn',
          method: 'POST',
          data: {
            workflow: {
              blocks: blocks
            }
          }
        }).done(function (response) {
          if (response.job_id) {
            alert('Application spawning with job ID: ' + response.job_id);
          } else {
            alert('Error spawning application.');
          }
        }).fail(function () {
          alert('Error spawning application.');
        });
      });

      $('#save-as-pattern').on('click', function () {
        var blocks = [];
        $('.playground-block').each(function () {
          blocks.push($(this).data('engine'));
        });

        $.ajax({
          url: '/wp-json/aevov-playground/v1/save-pattern',
          method: 'POST',
          data: {
            workflow: {
              blocks: blocks
            }
          }
        }).done(function (response) {
          if (response.pattern_id) {
            alert('Pattern saved with ID: ' + response.pattern_id);
          } else {
            alert('Error saving pattern.');
          }
        }).fail(function () {
          alert('Error saving pattern.');
        });
      });
    }
  };

  $(document).ready(function () {
    Playground.init();
  });

})(jQuery);
