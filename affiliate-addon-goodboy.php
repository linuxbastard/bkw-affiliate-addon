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

/**
 * Clean up URL to remove query string and keep URL pretty.
 *
 * @since 1.0.0
 */
function affgb_coupon_links_clean_url() {
	$query_var = get_option('uap_referral_variable');

	if ( ! isset( $_GET[ $query_var ] ) ) {
		return;
	}
	?>
	<script>
        (function() {
            var queryVar = '<?php echo esc_js( $query_var ); ?>',
                queryParams = window.location.search.substr( 1 ).split( '&' ),
                url = window.location.href.split( '?' ).shift();

            for ( var i = queryParams.length; i-- > 0; ) {
                if ( 0 === queryParams[ i ].indexOf( queryVar + '=' ) ) {
                    queryParams.splice( i, 1 );
                }
            }

            if ( queryParams.length > 0 ) {
                url += '?' + queryParams.join( '&' );
            }

            url += window.location.hash;

            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, url );
            }
        })();
	</script>
	<?php
}
add_action( 'wp_head', 'affgb_coupon_links_clean_url' );

/**
 * Remove the coupon code query string parameter from the WooCommerce AJAX
 * endpoint.
 *
 * WooCommerce includes a custom AJAX endpoint, which is basically just the
 * current URL with a 'wc-ajax' query parameter appended. In some cases the
 * value of that parameter includes an '%%endpoint%%' token, which gets replaced
 * in JavaScript with the AJAX handler.
 *
 * When filtering the endpoint to remove query arguments, the call to
 * remove_query_arg() ends up URL encoding argument values, which changes
 * the '%%endpoint%% token, causing AJAX requests to fail.
 *
 * This replaces the '%%endpoint%%' token with a temporary token that won't
 * be URL encoded, then swaps the tokens after calling remove_query_arg().
 *
 * @see WC_AJAX::get_endpoint()
 *
 * @since 1.0.0
 *
 * @param  string $endpoint AJAX endpoint URL.
 * @return string
 */
function affgb_clean_ajax_endpoint( $endpoint ) {
	$query_var = get_option('uap_referral_variable');
	$token = 'affgb-affiliate-links-url-safe-token';
	$endpoint = str_replace( '%%endpoint%%', $token, $endpoint );
	$endpoint = remove_query_arg( $query_var, $endpoint );
	return str_replace( $token, '%%endpoint%%', $endpoint );
}
add_filter( 'woocommerce_ajax_get_endpoint', 'affgb_clean_ajax_endpoint' );

