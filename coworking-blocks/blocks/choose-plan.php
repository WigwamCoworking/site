<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package coworking-blocks
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type/#enqueuing-block-scripts
 */
function choose_plan_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'choose-plan/index.js';
	wp_register_script(
		'choose-plan-block-editor',
		plugins_url( $index_js, __FILE__ ),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-components',
		),
		filemtime( "$dir/$index_js" )
	);

	$editor_css = 'choose-plan/editor.css';
	wp_register_style(
		'choose-plan-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		array(),
		filemtime( "$dir/$editor_css" )
	);

	$style_css = 'choose-plan/style.css';
	wp_register_style(
		'choose-plan-block',
		plugins_url( $style_css, __FILE__ ),
		array(),
		filemtime( "$dir/$style_css" )
	);

	register_block_type( 'coworking-blocks/choose-plan', array(
		'editor_script'   => 'choose-plan-block-editor',
		'editor_style'    => 'choose-plan-block-editor',
		'style'           => 'choose-plan-block',
		'render_callback' => 'render_block_choose_plan',
	) );
}

/**
 * Enqueue block editor JavaScript and CSS
 */
function blocks_scripts() {

	$front_js = 'choose-plan/front.js';

	// Enqueue frontend JS.
	if ( ! is_admin() ) {
		wp_enqueue_script(
			'jsforwp-blocks-frontend-js',
			plugins_url( $front_js, __FILE__ ),
			[ 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n' ],
			filemtime( plugin_dir_path( __FILE__ ) )
		);
	}

	// Enqueue frontend only CSS.
	// if ( ! is_admin() ) {
	// 	wp_enqueue_style(
	// 		'jsforwp-blocks-frontend-css',
	// 		plugins_url( $index_js, __FILE__ ),
	// 		[ 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n' ],
	// 		filemtime( plugin_dir_path( __FILE__ ) )
	// 	);
	// }
}

// Hook scripts function into block editor hook.
add_action( 'enqueue_block_assets', 'blocks_scripts' );

/**
 * Render the block on the frontend.
 *
 * @since  1.0
 */
function render_block_choose_plan() {

	global $woocommerce, $this_year, $next_year;

	$output  = '<div class="plan"><div class="choose-plan">';

	/*
	 * Display membership section.
	 */
	$output .= '<h3>' . __( 'Mandatory Membership', 'coworking-blocks' ) . '</h3>';

	// Get mandatory yearly membership for current year.
	$product = get_page_by_path( 'adhesion-' . $this_year, OBJECT, 'product' ); // Retrieve post using its slug.

	if ( ! empty( $product ) ) {
		$product = wc_get_product( $product );

		$output .= '<div class="inputGroup"><input type="checkbox" class="memberships" id="product_' . $product->get_id() . '" name="product_' . $product->get_id() . '" value="' . $product->get_id() . '" data-price="' . $product->get_price() . '"><label for="product_' . $product->get_id() . '">' . $product->get_name() . '</label></div>';

	}

	// Get mandatory yearly membership for next year if needed.
	if ( date( 'n' ) >= 9 ) { // Start to show in september.
		$product = get_page_by_path( 'adhesion-' . $next_year, OBJECT, 'product' ); // Retrieve post using its slug.

		if ( ! empty( $product ) ) {
			$product = wc_get_product( $product );

			$output .= '<div class="inputGroup"><input type="checkbox" class="memberships" id="product_' . $product->get_id() . '" name="product_' . $product->get_id() . '" value="' . $product->get_id() . '" data-price="' . $product->get_price() . '"><label for="product_' . $product->get_id() . '">' . $product->get_name() . '</label></div>';

		}
	} else {
		$output .= '<p>' . __( 'The membership for next year will be for sale in September.', 'coworking-blocks' ) . '</p>';
	}

	/*
	 * Display day tickets section.
	 */
	$output .= sprintf( '<h3>' . __( 'Day Tickets', 'coworking-blocks' ) . '</h3>', $this_year );

	$product_id = wc_get_product_id_by_sku( __( 'ticket-journee', 'coworking-blocks' ) );

	if ( ! empty( $product_id ) ) {
		$product = wc_get_product( $product_id );

		$output .= '<div class="inputGroup"><input id="product_' . $product->get_id() . '" name="day_tickets" type="number" value="0" data-price="' . $product->get_price() . '" data-id="' . $product->get_id() . '" /></div>';
	}

	/*
	 * Display plan selection section.
	 */
	$output .= '<h3>' . __( 'Choose Your Plan', 'coworking-blocks' ) . '</h3>';

	$products = wc_get_products(
		array(
			'return'   => 'objects',
			'orderby'  => 'menu_order',
			'order'    => 'ASC',
			'category' => array( 'plan-' . $this_year, 'plan-' . $next_year ), // Get months from current year category, and the year to come ex: "plan-2019, plan-2020".
			'limit'    => 12, // 12 sliding months.
			'offset'   => ( date( 'n' ) - 1 ), // don't take outdated months this year but include current month.
		)
	);

	foreach ( $products as $product ) {
		$output .= '<div class="inputGroup"><input type="checkbox" class="product_ids" id="product_' . $product->get_id() . '" name="product_' . $product->get_id() . '" value="' . $product->get_id() . '" data-price="' . $product->get_price() . '"><label for="product_' . $product->get_id() . '">' . $product->get_name() . '</label></div>';
	}

	wp_reset_postdata();

	$output  .= '</div>';

	$output  .= '<div class="buy-plan">';

	$output  .= '<div class="plan-description">' . __( 'The plan price will be self adjusted upon products selection.', 'coworking-blocks' ) . '</div>';

	/*
	 * Get grouped product (needed for the add-to-cart URL).
	 */
	$plan_id = wc_get_product_id_by_sku( __( 'coworking-plan' ), 'coworking-blocks' );

	// see https://businessbloomer.com/woocommerce-custom-add-cart-urls-ultimate-guide/.
	$output .= '<input type="hidden" id="final_url" value="" />';
	$output .= '<input type="hidden" id="final_price" value="" />';
	$output .= '<a href="" id="addToCart" class="button" data-url="' . wc_get_cart_url() . '?add-to-cart=' . $plan_id . '" data-text="' . __( 'Add to cart', 'coworking-blocks' ) . '">' . __( 'Add to cart', 'coworking-blocks' ) . '</a>';
	$output .= '<div class="plan-discount"></div>';

	$output .= '</div></div>';
	return $output;
}

add_action( 'init', 'choose_plan_block_init' );

/**
 * Count products in cart from category.
 *
 * @since  1.0
 */
function get_cart_category_count( $categories ) {

	$count = 0; // Initializing.

	// Loop through cart items.
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

		foreach ( $categories as $category ) {
			if ( has_term( $category, 'product_cat', $cart_item['product_id'] ) ) {
				$count += $cart_item['quantity'];
			}
		}
	}

	// Returning category count.
	return 0 === $count ? false : $count;
}

add_action( 'woocommerce_before_cart', 'apply_matched_coupons' );

/**
 * Apply coupon to cart if product count and category in cart match.
 *
 * @since  1.0
 * @todo   Replace add_discount() by apply_coupon(), see https://docs.woocommerce.com/wc-apidocs/class-WC_Discounts.html
 */
function apply_matched_coupons() {

	global $this_year, $next_year;

	// Count products in cart.
	$products_count = get_cart_category_count( array( 'plan-' . $this_year, 'plan-' . $next_year ) );

	// Set $cat_in_cart to false.
	$cat_in_cart = false;

	// Choose coupon to apply based on count products in cart.
	switch ( $products_count ) {
		case $products_count >= 3 && $products_count < 6:
			$coupon_code = '10percent';
			break;
		case $products_count >= 6:
			$coupon_code = '20percent';
			break;
		default:
			$coupon_code = '';
			break;
	}

	// If coupon already in cart then return.
	if ( WC()->cart->has_discount( $coupon_code ) ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

		// If Cart has category "plan-XXXX", set $cat_in_cart to true.
		if ( has_term( 'plan-' . $this_year, 'product_cat', $cart_item['product_id'] ) || has_term( 'plan-' . $next_year, 'product_cat', $cart_item['product_id'] ) ) {
			$cat_in_cart = true;
		}
	}

	// Apply coupon.
	if ( true === $cat_in_cart && $products_count >= 3 ) {
		WC()->cart->add_discount( $coupon_code );
	}

}

/**
 * Remove coupons from cart if products count is too low.
 *
 * @since  1.0
 * @param  array $cart_item_key Query Cart item key.
 * @param  obj   $instance Cart instance.
 */
function remove_coupons_on_product_delete( $cart_item_key, $instance ) {

	global $this_year, $next_year;

	// Count products in cart.
	$products_count = get_cart_category_count( array( 'plan-' . $this_year, 'plan-' . $next_year ) );

	// Remove coupon if products count < 3.
	if ( $products_count < 3 ) {
		WC()->cart->remove_coupons();
	}
}

add_action( 'woocommerce_cart_item_removed', 'remove_coupons_on_product_delete', 10, 2 );
