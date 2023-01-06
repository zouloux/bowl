
function patchFlexibleBehavior ( $ ) {
  // Invert flexible layouts collapsed state when we click on flexible content title
  var $flexibleLabel = $('.acf-field-flexible-content > .acf-label > label');
  if ( $flexibleLabel.length === 0 ) return
  var opened = true;
  var collapsedClass = '-collapsed';
  $flexibleLabel
  .text( $flexibleLabel.text() + " ↕️" )
  .on('click', function (e) {
    var $layouts = $(e.currentTarget).parent().parent().find('.values > .layout');
    opened = !opened;
    opened ? $layouts.removeClass(collapsedClass) : $layouts.addClass(collapsedClass);
  })
}

jQuery(document).ready( function ($) {
  if (!window._customMetaboxBehavior) return;
  patchFlexibleBehavior($)
});
