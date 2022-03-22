
function initCustomMetaBoxBehavior ( $ )
{
  // Because we removed default draggable behavior for meta boxes
  // We need to add back open / close feature.
  // No need to filter acf-metabox here since we also need it for them
  $('.postbox .handle-actions button.handlediv').on('click', function (e) {
    $(e.currentTarget).parent().parent().parent().toggleClass('closed');
  });

  // By default, we close yoast panel
  $('.postbox.yoast').addClass('closed');
}

function patchFlexibleBehavior ( $ )
{
  // Invert flexible layouts collapsed state when we click on flexible content title
  var $flexibleLabel = $('.acf-field-flexible-content .acf-label');
  if ( $flexibleLabel.length > 0 )
  {
    var opened = true;
    var collapsedClass = '-collapsed';
    $flexibleLabel.on('click', function (e) {
      var $layouts = $(e.currentTarget).parent().parent().find('.values > .layout');
      opened = !opened;
      opened ? $layouts.removeClass(collapsedClass) : $layouts.addClass(collapsedClass);
    })
  }
}

jQuery(document).ready( function ($)
{
  // Check if we removed postbox script at admin_init (in admin.config.php)
  if (window._customMetaboxBehavior)
  {
    initCustomMetaBoxBehavior($);
    patchFlexibleBehavior($)
  }
});
