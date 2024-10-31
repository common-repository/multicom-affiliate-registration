jQuery(document).ready(function($) {
  let $loads = $('.load-multicom-referral-ajax');

  if (sessionStorage) {
    let $refName = $('.load-multicom-referral-ajax');
    if(sessionStorage.getItem('mcomRefName')){
      $refName.html(sessionStorage.getItem('mcomRefName'));
      $refName.css('display', 'inline-block');
    }
  }

  if ($loads.length) {
    $.ajax({
      type: 'POST',
      url: ajaxurl,
      dataType: 'html',
      data: {
        action: 'get_replicated_info',
      },
    }).always(function (html, status) {
      if (status === 'success') {
        html = html.trim();

        if (html !== '') {
          $('body').addClass('multicom-is-referral');
          $loads.each((_, el) => {
            let $el = $(el);

            $el.html(html);
            $el.css('display', 'inline-block');
            if(sessionStorage){
              sessionStorage.setItem('mcomRefName', html);
            }
          });
        }
      }
    });
  }
});
