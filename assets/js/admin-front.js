jQuery(document).ready(function($) {
  let $changeInput = $('#changesync');
  if ($changeInput.length) {
    let aFields = ['#startsync', '#eachsync', '#autosync'],
      $inputs = $(aFields.join(','));

    $inputs.on('change', function () {
      let oJson = {};

      $inputs.each((_, el) => {
        let $el = $(el);

        if ($el.prop('type') === 'checkbox') {
          oJson[$el.prop('name')] = $el.get(0).checked ? 'on' : 'off';
        } else {
          oJson[$el.prop('name')] = $el.val();
        }
      });

      $changeInput.val(JSON.stringify(oJson));
    });
  }

  let $syncBtn = $('#sync-auto-now');
  if ($syncBtn.length) {
    $syncBtn.on('click', function () {
      $syncBtn.prop('disabled', true);
      $syncBtn.closest('td').find('label').prepend(
        '<span class="spinner is-active" style="display: inline-block;float: none;vertical-align: middle;margin: 0 8px 0 0;"></span>'
      );
      $.ajax({
        url: admin_ajax,
        type: "post",
        data: {
          action: 'sendAutoAllData',
        },
      }).always(function (data) {
        $syncBtn.closest('td').find('.spinner').remove();
        $syncBtn.prop('disabled', false);
        console.log('Response sync auto: ', data);
      });
    });
  }
});