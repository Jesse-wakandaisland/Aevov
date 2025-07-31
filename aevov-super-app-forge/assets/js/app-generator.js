(function ($) {
  'use strict';

  var AppGenerator = {
    currentUrl: null,

    init: function () {
      $('#app-ingestion-form').on('submit', this.startProcess.bind(this));
      $('#generate-app').on('click', this.weaveApp.bind(this)).text('Weave App');
    },

    startProcess: function (e) {
      e.preventDefault();
      this.currentUrl = $('#app-url').val();
      if (!this.currentUrl) {
        alert('Please enter a URL.');
        return;
      }
      this.simulateGeneration();
    },

    simulateGeneration: function() {
        var $submitButton = $('#app-ingestion-form button[type="submit"]');
        var $weaveButton = $('#generate-app');
        var $output = $('#simulation-output');

        $submitButton.text('Simulating...').prop('disabled', true);
        $weaveButton.prop('disabled', true);
        $output.empty();

        $.ajax({
            url: '/wp-json/aevov-super-app/v1/simulate',
            method: 'POST',
            data: { url: this.currentUrl }
        }).done(function(events) {
            var i = 0;
            function displayEvent() {
                if (i < events.length) {
                    var event = events[i];
                    var eventClass = 'event-' + event.event;
                    var eventHtml = '<div class="sim-event ' + eventClass + '"><span class="event-name">' + event.event + ':</span> ' + event.message + '</div>';
                    if (event.details) {
                        eventHtml += '<div class="event-details">' + JSON.stringify(event.details, null, 2) + '</div>';
                    }
                    $output.append(eventHtml);
                    i++;
                    setTimeout(displayEvent, 300);
                } else {
                    $weaveButton.prop('disabled', false);
                    $output.append('<hr><p>Simulation complete. Ready to weave application.</p>');
                }
            }
            displayEvent();
        }).fail(function() {
            alert('Error during simulation.');
        }).always(function() {
            $submitButton.text('Ingest & Simulate').prop('disabled', false);
        });
    },

    weaveApp: function () {
        if (!this.currentUrl) {
            alert('Please ingest and simulate an app first.');
            return;
        }

        var $weaveButton = $('#generate-app');
        $weaveButton.text('Weaving...').prop('disabled', true);
        var $output = $('#simulation-output');
        $output.append('<hr><p>Starting full application weave...</p>');

        $.ajax({
            url: '/wp-json/aevov-super-app/v1/weave',
            method: 'POST',
            data: { url: this.currentUrl }
        }).done(function (response) {
            if (response.success) {
                $output.append('<p style="color: green;">Weaving complete! ' + response.pages_created.length + ' pages created.</p>');
                var pageLinks = response.pages_created.map(function(pageId) {
                    return '<a href="/?page_id=' + pageId + '" target="_blank">View Page ' + pageId + '</a>';
                });
                $output.append('<div>' + pageLinks.join('<br>') + '</div>');
            } else {
                var error = response.error || 'An unknown error occurred.';
                $output.append('<p style="color: red;">Weaving failed: ' + error + '</p>');
            }
        }).fail(function () {
            alert('Error weaving app.');
        }).always(function() {
            $weaveButton.text('Weave App').prop('disabled', false);
        });
    }
  };

  $(document).ready(function () {
    AppGenerator.init();
    $('#app-ingestion-form button[type="submit"]').text('Ingest & Simulate');
    // Add some styles for the simulation
    $('head').append('<style>' +
        '.sim-event { margin-bottom: 5px; padding: 5px; border-left: 3px solid #ccc; }' +
        '.event-start_simulation { border-color: #0073aa; }' +
        '.event-uad_to_tensor_complete { border-color: #7e8900; }' +
        '.event-tensor_processing_complete { border-color: #d63600; }' +
        '.event-pattern_retrieval_complete { border-color: #46b450; }' +
        '.event-weaving_page_complete { border-color: #a0a5aa; }' +
        '.event-end_simulation { border-color: #0073aa; font-weight: bold; }' +
        '.event-error { border-color: #dc3232; color: #dc3232; }' +
        '.event-name { font-weight: bold; }' +
        '.event-details { white-space: pre-wrap; background: #fff; padding: 5px; margin-top: 5px; }' +
    '</style>');
  });

})(jQuery);
