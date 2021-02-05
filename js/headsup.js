/**
* @file
*/

(function ($, Drupal, drupalSettings) {
  Drupal.AjaxCommands.prototype.acknowledgeHeadsup = function (ajax, response, status) {
    console.log(response.message);
  }

  let huuid = drupalSettings.headsup.huvalues.uid;
  let hureadmore = drupalSettings.headsup.huvalues.readmore;
  let hureadless = drupalSettings.headsup.huvalues.readless;

  // Expand/collapse headsup-body.
  // Before click, we get the original height of a headsup-body.
  let origHeight = 0;
  let $thisBody = $('body').find('.headsup-body');

  $('body').on('mouseup touchend', '.headsup-more-button', function (e) {
    $thisBody = $(this).siblings('.headsup-body');
    if (!$thisBody.hasClass('expanded')) {
      origHeight = $thisBody.height();
    }

    var whichHeadsup = $(this).attr('rel');
    var $whichHeadsupId = $('#headsup-' + whichHeadsup + ' .headsup-body');

    if ($whichHeadsupId.hasClass('expanded')) {
      $whichHeadsupId.animate({
        height: origHeight
      }, 400, function () {
          $(this).height(origHeight);
        });
      $whichHeadsupId.removeClass('expanded');
      $(this).html(hureadmore);
    }
    else {
      $whichHeadsupId.animate({
        height: $whichHeadsupId.get(0).scrollHeight
      }, 400, function () {
          $(this).height('auto');
        });
      $whichHeadsupId.addClass('expanded');
      $(this).html(hureadless);
    }

  });

  $('body').on('mouseup touchend', '.headsup-acknowledge-button', function (e) {
    if(e.handled !== true) {
      let as_href = $(this).attr('href');
      let hunid = $(this).attr('rel');
      acknowledgeSave(as_href, {'nid': hunid, 'uid': huuid});
      e.handled = true;
    }
    e.preventDefault();
  });

  //AJAX click-logging function with redirect on success
  function acknowledgeSave(as_url, as_data) {
    $.ajax({
      url: as_url,
      async: true,
      dataType: 'text',
      type: "GET",
      cache: false,
      success: function (response_data) {
        dismiss_headsup(
          as_data.nid
        );
      },
    });
  }

  // Dismiss the headsup once it's been acknowledged.
  function dismiss_headsup(nid) {
    $('.headsup-container#headsup-' + nid).remove();
    if ($('.headsup-container').length === 0) {
      $('#block-headsupblock').remove();
    }
  }

})(jQuery, Drupal, drupalSettings);
