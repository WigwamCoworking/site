jQuery(document).ready(function(){

	// Add buttons to product screen.
	var product_screen = jQuery( '.edit-php.post-type-product' );
	var title_action   = product_screen.find( '.page-title-action:first' );

		jQuery( title_action ).after('<a href="#" class="page-title-action">Coworking Plan (CSV)</a>');

});
