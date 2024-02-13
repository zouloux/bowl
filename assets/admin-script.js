
function patchFlexibleBehavior ( $ ) {
  // Invert flexible layouts collapsed state when we click on flexible content title
  var $flexibleLabel = $('.acf-field-flexible-content > .acf-label > label');
  $flexibleLabel.each(function (i, el) {
    var opened = true;
    var collapsedClass = '-collapsed';
    $(el)
      .html( $(el).html() + " ↕️" )
      .on('click', function (e) {
        var $layouts = $(e.currentTarget).parent().parent().find('.values > .layout');
        opened = !opened;
        opened ? $layouts.removeClass(collapsedClass) : $layouts.addClass(collapsedClass);
      })
  })
}

jQuery(document).ready( function ($) {
  if (!window._customMetaboxBehavior) return;
  patchFlexibleBehavior($)
});
