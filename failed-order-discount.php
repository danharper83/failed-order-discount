<?php
/**
 * Plugin Name: WooCommerce failed order discount reset
 * Plugin URI: https://github.com/hrpr-uk/failed-order-discount
 * description: Reset the coupon usage count and customer usage record when an order fails.
 * Version: 1.0.0
 * Author: HRPR
 * Author URI: https://www.hrpr.co.uk/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package failed-order-discount
 */

/**
 * Resets the coupon usage count and customer usage record when an order fails.
 *
 * This function hooks into the 'woocommerce_order_status_failed' action, which is triggered
 * when an order status changes to "failed". It retrieves the coupons used in the failed order
 * and decreases their general usage count. Additionally, if the coupon is set to "1 per customer",
 * it removes the customer ID from the list of users who have used the coupon, allowing the same
 * customer to use the coupon again.
 *
 * @param int $order_id The ID of the failed order.
 */
function reset_coupon_usage_on_failed_order( $order_id ) {

	$order = wc_get_order( $order_id );
	if ( $order ) {

		$used_coupons = $order->get_coupon_codes();
		$customer_id  = $order->get_user_id();

		foreach ( $used_coupons as $coupon_code ) {
			$coupon      = new WC_Coupon( $coupon_code );
			$usage_count = $coupon->get_usage_count();

			// Reset the general usage count.
			if ( $usage_count > 0 ) {
				$coupon->set_usage_count( $usage_count - 1 );
				$coupon->save();
			}

			// Reset the usage for the specific customer.
			if ( $customer_id ) {
				$used_by = $coupon->get_used_by();
				$key     = array_search( $customer_id, $used_by, true );
				if ( false !== $key ) {
					unset( $used_by[ $key ] );
					$coupon->set_used_by( $used_by );
					$coupon->save();
				}
			}
		}
	}
}
add_action( 'woocommerce_order_status_failed', 'reset_coupon_usage_on_failed_order' );
