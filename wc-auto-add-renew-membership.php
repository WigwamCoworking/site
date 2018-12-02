<?php
/*
 * Plugin Name: Auto Add or Renew Membership for WooCommerce
 * Plugin URI: 
 * Description: Automatically add the Adhesion product to cart when the user doesn't have a membership or it expires soon.
 * Author: Maria Gorska
 * Author URI: 
 * License: GPLv3
 * Version: 1.0.0
 * Requires at least: 4.0
 * Tested up to: 4.9.5
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package		Auto Add or Renew Membership for WooCommerce
 * @author		Maria Gorska
 * @since		1.0
 */

function add_membership_on_adding_product_to_cart ( $cart_key, $product_id, $quantity ) {
	$membership_product_id = 226; 
	$current_plan_slug = 'adhesion-2018';
	$found = false;
	
	// bail if Memberships isn't active
	if ( ! function_exists( 'wc_memberships' ) ) {
		return;
	}
	
	$user_id = get_current_user_id();
	// If the user is not yet a member, add the membership product to cart, unless it's already there
	if ( ! wc_memberships_is_user_active_member( $user_id, $current_plan_slug ) ) {
	//check if product already in cart
		if ( sizeof( WC()->cart->get_cart() ) > 0 ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$_product = $values['data'];
				if ( $_product->id == $membership_product_id )
					$found = true;
			}
			// if product not found, add it
			if ( ! $found ) {
				WC()->cart->add_to_cart( $membership_product_id );
				wc_add_notice( sprintf( __( 'The yearly membership was added to cart automatically, as we couldn\'t find it on your account. If you have the membership on another account, <a href="%s">log in here</a>.', 'wc-auto-add-renew-membership' ), wc_get_page_permalink( 'myaccount' ) ), 'notice' );
			}
		}
	} 
	maybe_add_next_year_membership_to_cart ( $user_id, $product_id, $current_plan_slug );

}

add_action( 'woocommerce_add_to_cart', 'add_membership_on_adding_product_to_cart', 10, 3 );

function maybe_add_next_year_membership_to_cart ( $user_id, $product_id, $current_plan_slug ) {
	$next_year_product_id = 291; 
	
	$months_to_expire = 0;
	
	switch ( $product_id ) {
		case 246: // one month plan
			$months_to_expire = 1;
		break;
		case 250: // 3 month plan
			$months_to_expire = 3;
		break;
		case 252: // 6 month plan
			$months_to_expire = 6;
		break;			
	}	
	// bail if Memberships isn't active
	if ( ! function_exists( 'wc_memberships' ) ) {
		return;
	}	
	
	$today = time();
	//$today = mktime( 0, 0, 0, 11, 10, 2018 ); // for testing. Params are: hour, minute, second, MONTH, DAY, year.
	$current_year = date( 'Y', $today );
	$expiry_year = date( 'Y', strtotime( sprintf( '+%d month', $months_to_expire ), $today ) );
	
	if ( $expiry_year > $current_year ) {
		WC()->cart->add_to_cart( $next_year_product_id );	
		if ( wc_memberships_is_user_active_member( $user_id, $current_plan_slug ) ) {
			wc_add_notice( sprintf( __( 'Your yearly membership expires before the end of the current billing period. The membership for new year has been added to cart automatically.', 'wc-auto-add-renew-membership' ), wc_get_page_permalink( 'myaccount' ) ), 'notice' );		
		}
	}

}
