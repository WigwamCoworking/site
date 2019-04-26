<?php
/**
 * Plugin Name:     Coworking Blocks
 * Plugin URI:      https://remicorson.com
 * Description:     Adds some specific features to the WooCommerce checkout process for the coworking space.
 * Author:          Remi Corson
 * Author URI:      https://remicorson.com
 * Text Domain:     coworking-blocks
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         Coworking_Blocks
 */

/*
 * Define globals.
 */
global $this_year;
global $next_year;

$this_year = date( 'Y' );
$next_year = date( 'Y', strtotime( '+1 year' ) );

/*
 * Required files.
 */
require_once( 'blocks/choose-plan.php' );

/*
 * Actions.
 */
add_action( 'init', 'text_domain' );
add_action( 'admin_enqueue_scripts', 'enqueue_admin_script' );
add_action( 'wp_dashboard_setup', 'coworking_dashboard_widgets' );
add_action( 'init', 'duplicate_month_product' );
add_filter( 'woocommerce_cart_subtotal', 'filter_woocommerce_cart_subtotal', 10, 3 );

/*
 * Filters.
 */
add_filter( 'woocommerce_cart_item_subtotal', 'if_coupon_slash_item_subtotal', 99, 3 );
add_filter( 'woocommerce_cart_item_price', 'if_coupon_slash_item_subtotal', 99, 3 );
add_filter( 'woocommerce_coupon_message', '__return_null' );

/**
 * Load the translations.
 *
 * @package      WooCommerce
 * @author       Remi Corson
 * @license      https://codex.wordpress.org/GPL   GNU General Public License
 * @see          https://developer.wordpress.org/reference/functions/load_theme_textdomain/
 *
 * @version  1.0.0
 * @since    1.0.0
 *
 * @return   void
 */
function text_domain() {

	// Make theme ready for translation.
	load_plugin_textdomain( 'coworking-blocks', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Enqueue a script in the WordPress admin, excluding edit.php.
 *
 * @package      WooCommerce
 * @author       Remi Corson
 * @license      https://codex.wordpress.org/GPL   GNU General Public License
 * @see          https://developer.wordpress.org/reference/functions/wp_enqueue_script/
 *
 * @version  1.0.0
 * @since    1.0.0
 * @param int $hook Hook suffix for the current admin page.
 *
 * @return   void
 */
function enqueue_admin_script( $hook ) {
	if ( 'edit.php' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'coworking_script', plugin_dir_url( __FILE__ ) . '/javascript/admin.js', array(), '1.0' );
}

/**
 * Add the dashboard widget.
 *
 * @package      WooCommerce
 * @author       Remi Corson
 * @license      https://codex.wordpress.org/GPL   GNU General Public License
 * @see          https://codex.wordpress.org/Function_Reference/wp_add_dashboard_widget
 *
 * @version  1.0.0
 * @since    1.0.0
 *
 * @return   void
 */
function coworking_dashboard_widgets() {
	global $wp_meta_boxes;

	wp_add_dashboard_widget( 'who_paid_what', __( 'WigWam: Qui est à jour ?', 'coworking-blocks' ), 'who_paid_what_widget' );
}

/**
 * Render the dashboard widget content.
 *
 * @package      WooCommerce
 * @author       Remi Corson
 * @license      https://codex.wordpress.org/GPL   GNU General Public License
 * @see          https://docs.woocommerce.com/wc-apidocs/function-wc_get_products.html
 * @see          https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
 *
 * @version  1.0.0
 * @since    1.0.0
 *
 * @return   mixed  The widget content in HTML.
 */
function who_paid_what_widget() {

	global $this_year, $next_year;

	// Get 12 products corresponding to months.
	$products = wc_get_products(
		array(
			'return'   => 'objects',
			'orderby'  => 'menu_order',
			'order'    => 'ASC',
			'exclude'  => array( 315 ), // Exclude grouped product itself.
			'category' => array( 'plan-' . $this_year ), // Get months from current year category, ex: "plan-2019".
			'limit'    => 12,
		)
	);

	echo '<p>Voici la liste des membres à jour:</p>';
?>
	<table>
		<tbody>
			<?php foreach ( $products as $product ) : ?>
			<tr>
				<td><strong><?php echo esc_html( $product->get_name() ); ?></strong></td>
			</tr>
			<tr>
				<td>
					<?php
					$args      = array(
						'orderby'  => 'nicename',
						'role__in' => array( 'administrator', 'customer' ),
					);
					$blogusers = get_users( $args );

					// Array of WP_User objects.
					echo '<ul class="">';
					foreach ( $blogusers as $user ) {

						// Checks if user purchased product.
						if ( wc_customer_bought_product( $user->user_email, $user->ID, $product->get_id() ) ) {
							echo '<li class="">
							<span>' . esc_html( $user->first_name ) . '</span>
							<span>' . esc_html( $user->last_name ) . '</span>
							</li>';
						}
					}
					echo '<ul>';
					?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

<?php

}

/**
 * Duplicate the product for year+1 for the corresponding month.
 *
 * @package      WooCommerce
 * @author       Remi Corson
 * @license      https://codex.wordpress.org/GPL   GNU General Public License
 * @see          https://docs.woocommerce.com/wc-apidocs/class-WC_Admin_Duplicate_Product.html
 * @see          https://docs.woocommerce.com/wc-apidocs/source-class-WC_Product.html#1322-1345
 *
 * @version  1.0.0
 * @since    1.0.0
 *
 * @return   Int  The duplicated post ID.
 */
function duplicate_month_product() {

	global $this_year, $next_year;

	// Check if month next year exists.
	$id_exists = wc_get_product_id_by_sku( $next_year . date( 'm' ) );

	// If corresponding products exists for next year, return.
	if ( $id_exists ) {
		return;
	}

	// Get the product corresponding to this month.
	$product_id         = wc_get_product_id_by_sku( $this_year . date( 'm' ) );
	$product            = wc_get_product( $product_id );
	$wc_duplicate       = new WC_Admin_Duplicate_Product();
	$duplicated_product = $wc_duplicate->product_duplicate( wc_get_product( $product->get_ID() ) );

	$duplicated_id = $duplicated_product->get_ID();

	$duplicated = new WC_Product( $duplicated_id );

	// Update name, replace current year by year +1.
	$duplicated->set_name( str_replace( $this_year, $next_year, $product->get_name() ) );

	// Update SKU, set it to year +1 followed by current month, ex: 201901.
	$duplicated->set_sku( $next_year . date( 'm' ) );

	// Update slug, set it to $product sku followed by year +1, ex: janvier-2019.
	$duplicated->set_sku( str_replace( $this_year, $next_year, $product->get_sku() ) );

	// Update menu order (position) to next year followed by month number with 0, ex: 201901.
	$duplicated->set_menu_order( $next_year . date( 'm' ) );

	// Update status from draft to publish.
	$duplicated->set_status( 'publish' );

	// Update cagtegory if needed.
	$term_slug = 'plan-' . $next_year;
	$term      = term_exists( $term_slug, 'product_cat' ); // returns 0 or term ID.

	if ( 0 !== $term && null !== $term ) {
		$category_id = $term;
	} else {
		$category_id = wp_insert_term( $term_slug, 'product_cat' );
	}

	$duplicated->set_category_ids( array( $category_id['term_id'] ) );

	$duplicated->save();
}

/**
 * Display months discounted prices on cart page table.
 *
 * @package      WooCommerce
 * @author       Remi Corson
 * @license      https://codex.wordpress.org/GPL   GNU General Public License
 * @see          https://docs.woocommerce.com/wc-apidocs/class-WC_Cart.html
 * @see          https://docs.woocommerce.com/wc-apidocs/class-WC_Coupon.html
 *
 * @version 1.0.0
 * @since   1.0.0
 * @param   float $subtotal   The cart subtotal value.
 * @param   array $cart_item   The cart item values.
 * @param   int   $cart_item_key   The cart item key.
 *
 * @return   string  The modified subtotal.
 */
function if_coupon_slash_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {

	global $woocommerce, $this_year, $next_year;

	// Check if cart has coupon.
	if ( ! $woocommerce->cart->has_discount() ) {
		return $subtotal;
	}

	// Get coupons.
	$coupons = WC()->cart->get_applied_coupons();

	// Loop through coupons.
	foreach ( $coupons as $coupon ) {

		// Create coupon object.
		$coupon = new WC_Coupon( $coupon );

		// Check if specific coupon is applied.
		if ( $woocommerce->cart->has_discount( $coupon->get_code() ) ) {

			// Check if products in cart are from specific category (ex: plan-2019 & plan-2020).
			if ( has_term( 'plan-' . $this_year, 'product_cat', $cart_item['product_id'] ) || has_term( 'plan-' . $next_year, 'product_cat', $cart_item['product_id'] ) ) {

				// If coupon type is fixed_product.
				if ( 'fixed_product' === $coupon->get_discount_type() ) {
					$newsubtotal = wc_price( $cart_item['data']->get_price() - $coupon->get_amount() * $cart_item['quantity'] );
				} else { // If coupon type is percentage.
					$newsubtotal = wc_price( $cart_item['data']->get_price() * ( 1 - ( '0.' . $coupon->get_amount() ) ) * $cart_item['quantity'] );
				}

				$subtotal = sprintf( '<span class="discounted_price">%s</span>', $newsubtotal );
			}
		}
	}

	return $subtotal;
}

/**
 * Replace cart subtotal by cart total.
 * This avoids the customers to see the cart total.
 * Since coupons are hidden, this could be confusing.
 *
 * @package      Coworking
 * @author       Remi Corson
 * @license      https://codex.wordpress.org/GPL   GNU General Public License
 * @see          https://docs.woocommerce.com/wc-apidocs/class-WC_Cart.html
 *
 * @version 1.0.0
 * @since   1.0.0
 * @param   string $cart_subtotal   The cart subtotal (before calculation).
 * @param   bool   $compound   Whether to include compound taxes.
 * @param   string $instance   The instance.
 *
 * @return   float Cart total.
 */
function filter_woocommerce_cart_subtotal( $cart_subtotal, $compound, $instance ) {

	$totals = WC()->cart->get_totals();
	return $totals['total'];
};
