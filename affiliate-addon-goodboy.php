<?php
/**
 * Affiliate Add-on for The Goodboy
 *
 * @package   AffiliateAddon
 * @author    Ronaldo Bartolome
 * @link      http://www.mister-fixit.com/
 * @copyright Copyright (c) 2018 Mister FixIT
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Affiliate Add-on for The Goodboy
 * Plugin URI:        http://mister-fixit.com/wp-plugins/affiliate-addon-goodboy
 * Description:       Create coupons for each new registered user and automatically apply coupon codes for referral links from that user.
 * Version:           1.0.0
 * Author:            Ronaldo Bartolome (@linuxbastard)
 * Author URI:        http://www.mister-fixit.com/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create a corresponding coupon code when a new user registers.
 *
 * @since 1.0.0
 */
function affgb_generate_coupon($user_id) {
	// Bail if WooCommerce or sessions aren't available.
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	/**
	 *
	 */
	if ($user_id) {
		$user_info = get_userdata($user_id);
		$coupon_code = $user_info->user_login; //set username as coupon code
		$amount = '10';
		$discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product

		$coupon = array(
			'post_title' => $coupon_code,
			'post_content' => '',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type'		=> 'shop_coupon'
		);

		$new_coupon_id = wp_insert_post( $coupon );

		// Add meta
		update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
		update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
		update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
		update_post_meta( $new_coupon_id, 'product_ids', '' );
		update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
		update_post_meta( $new_coupon_id, 'usage_limit', '' );
		update_post_meta( $new_coupon_id, 'expiry_date', '' );
		update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
		update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
		update_post_meta( $new_coupon_id, 'usage_limit_per_user', '1' );
	}
}
add_action( 'user_register', 'affgb_generate_coupon', 10, 1 );

/**
 * Automatically apply a coupon passed via URL to the cart.
 *
 * @since 1.0.0
 */
function affgb_affiliate_coupon_links() {
	// Bail if WooCommerce or sessions aren't available.
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	// Don't attempt to apply coupon in AJAX requests.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	/**
	 * Filter the coupon code query variable name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $query_var Query variable name.
	 */
	$query_var = get_option('uap_referral_variable');

	// Quit if a coupon code isn't in the query string.
	if ( empty( $_GET[ $query_var ] ) ) {
		return;
	}

	// Set a session cookie to persist the coupon in case the cart is empty.
	WC()->session->set_customer_session_cookie( true );

	// Apply the coupon to the cart if necessary.
	if ( ! WC()->cart->has_discount( $_GET[ $query_var ] ) ) {
		// WC_Cart::add_discount() sanitizes the coupon code.
		WC()->cart->add_discount( $_GET[ $query_var ] );
	}
}
add_action( 'wp_loaded', 'affgb_affiliate_coupon_links', 30 );
add_action( 'woocommerce_add_to_cart', 'affgb_affiliate_coupon_links', 30 );

