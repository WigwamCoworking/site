jQuery(document).ready(function(){

	jQuery('.inputGroup input').change(function(){

		/*
		 * DEFINE VARS
		 * -----------
		 */
		var total = 0;
		var counter = 0;
		var discount = 0;
		var discountDisplay = '';
		var finalUrl = jQuery( '#addToCart' ).data('url');
		var addToCart = jQuery( '#addToCart' ).data('text');
		var hasMembership = false;
		var membershipCost = 0;
		var ticketsTotal = 0;

		/*
		 * MEMBERSHIPS
		 * -----------
		 */
		 jQuery('.memberships:checked').each(function(){

			 url = "&quantity["+jQuery(this).val()+"]=1";
			 finalUrl += url;
			 membershipCost +=parseFloat( jQuery(this).data('price') * 1 );

		 });

		 /*
			* DAY TICKETS
			* -----------
			*/
			jQuery('input[type=number][name=day_tickets]').each(function(){

				url = "&quantity["+jQuery(this).data('id')+"]=" + jQuery(this).val();
				finalUrl += url;
				ticketsTotal = jQuery(this).data('price') * jQuery(this).val();

			});

		/*
		 * MONTHLY PLAN
		 * ------------
		 */
		jQuery('.product_ids:checked').each(function(){

			counter++;

			// Build add to cart custom URL.
			url = "&quantity["+jQuery(this).val()+"]=1";
			finalUrl += url;

			// Calculate total price.
			price =jQuery(this).data('price') * 1;
			total +=parseFloat(price);

		});

		/*
		 * DISCOUNT
		 * ----------------
		 */
		// Calculate discount amount.
		switch( true ) {
			case ( counter >= 3 && counter < 6 ):
				discount = 10;
			break;
			case ( counter >= 6 ):
				discount = 20;
			break;
			default:
				discount = 0;
		}

		/*
		 * FINAL PRICE CALCULATION
		 * -----------------------
		 */
		if( discount != 0 ) {
			jQuery( '#final_price').val( total - (total * discount / 100) + membershipCost + ticketsTotal );
			discountDisplay = 'Vous bénéficiez de <span class="discount-amount">' + discount + '%</span> de remise !';
			jQuery( '.plan-discount' ).html( discountDisplay );
			jQuery( '.plan-discount' ).addClass( 'discount-tada' );
		} else {
			jQuery( '#final_price').val( total + membershipCost + ticketsTotal );
			jQuery( '.plan-discount' ).empty();
			jQuery( '.plan-discount' ).removeClass( 'discount-tada' );
		}

		/*
		 * DISPLAY PRICE & UPDATE ADD TO CART URL
		 * --------------------------------------
		 */
		jQuery( '#final_url' ).val( finalUrl );
		jQuery( '#addToCart' ).attr('href', jQuery('#final_url').val() );

		if( jQuery('#final_price').val() > 0 ) {
			jQuery( '#addToCart' ).text( addToCart + ' - ' + jQuery('#final_price').val() + '€');
		} else {
			jQuery( '#addToCart' ).text( addToCart );
		}

 });

});
