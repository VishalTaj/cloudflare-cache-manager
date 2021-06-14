
window.addEventListener('load', (event) => {
  if ($ === undefined) var $ = jQuery;
  $('#cf-manager-purge-btn').on('click', function() {
    $that = $(this);
    $.ajax({
      url: window.location.origin + '/wp-json/cf-manager/v1/purge',
      method: 'POST',
      dataType: "json",
      data: {url: $that.attr('post-url')},
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', $('input[name="cfm_auth_nonce"]').val());
        $that.attr('disabled', 'disabled');
      },
      success: function(data) {
        $that.removeAttr('disabled', 'disabled');
        alert('Purged Successfully');
      },
      error: function() {
        $that.removeAttr('disabled', 'disabled');
        alert('Unexpected error. Please try again after sometime');
      }
    })
  })
});
