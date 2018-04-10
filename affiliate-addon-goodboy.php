<?php
/**
 * Affiliate Addon for The Goodboy
 *
 * @package   AffiliateAddonGoodboy
 * @author    Ronaldo Bartolome
 * @link      http://www.mister-fixit.com/
 * @copyright Copyright (c) 2018 Mister FixIT
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Affiliate Addon for The Goodboy
 * Plugin URI:        https://bitbucket.org/linuxbastard/affiliate-addon-goodboy
 * Description:       Create coupons for each new registered user and automatically apply coupon codes for referral links from that user.
 * Version:           1.0.0
 * Author:            linuxbastard
 * Author URI:        http://www.mister-fixit.com/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Bitbucket Plugin URI: linuxbastard/affiliate-addon-goodboy
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
function affgb_generate_coupon() {
	// Bail if WooCommerce or sessions aren't available.
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	/**
	 *
	 */

}
add_action( 'user_register', 'affgb_generate_coupon', 10, 1 );