<?php
/**
 * Plugin Name: Affiliates Stats Filters Order Details
 * Plugin URI: http://www.itthinx.com/shop/affiliates-pro/
 * Description: Example plugin for the [affiliates_affiliate_stats type="stats-referrals"] shortcode rendering filters.
 * Version: 1.0.2
 * Author: George Tsiokos
 * Author URI: http://www.netpad.gr
 * License: GPLv3
 *
 * @package affiliates-stats-fitlers-order-details
 * @since 1.0.0
 * @author gtsiokos
 */

/**
 * Implements various filters used to modify the output of the [affiliates_affiliate_stats type="stats-referrals"] shortcode.
 */
class Affiliates_Stats_Filters_Order_Details {

	/**
	 * Add filters.
	 */
	public static function init() {
		add_filter( 'affiliates_affiliate_stats_renderer_data', array( __CLASS__, 'affiliates_affiliate_stats_renderer_data' ), 10, 2 );
		add_filter( 'affiliates_affiliate_stats_renderer_data_output', array( __CLASS__, 'affiliates_affiliate_stats_renderer_data_output' ), 10, 2 );
		add_filter( 'affiliates_affiliate_stats_renderer_column_display_names', array( __CLASS__, 'affiliates_affiliate_stats_renderer_column_display_names' ), 10, 1 );
		add_filter( 'affiliates_affiliate_stats_renderer_column_output', array( __CLASS__, 'affiliates_affiliate_stats_renderer_column_output' ), 10, 3 );
	}

	/**
	 * Allows to modify or extend the stored data set displayed for a referral.
	 *
	 * The additional data will only be displayed if you include the field key in the shortcode's data attribute, for example:
	 * [affiliates_affiliate_stats type="stats-referrals" data="custom-data"]
	 *
	 * @param array $data data set for the referral
	 * @param object $result referral row
	 * @return array
	 */
	public static function affiliates_affiliate_stats_renderer_data( $data, $result ) {

		$data['origin-type'] = array(
			'title'  => '',
			'domain' => 'affiliates',
			'value'  => sprintf( 'Some custom data could be displayed here for the referral with ID %d', intval( $result->referral_id ) )
		);

		return $data;
	}

	/**
	 * Allows to modify the output of the Details column displayed for a referral.
	 *
	 * Here we simply wrap the data output in a div with a blue border and some added padding.
	 *
	 * @param string $output
	 * @param string $result referral row
	 * @return string
	 */
	public static function affiliates_affiliate_stats_renderer_data_output( $output, $result ) {
		$output = '<div style="border: 1px solid #33f; padding: 4px;">' . $output . '</div>';
		return $output;
	}

	/**
	 * Allows to modify and reorder the columns used to display referrals.
	 *
	 * @param array $column_display_names array maps keys to column display names
	 */
	public static function affiliates_affiliate_stats_renderer_column_display_names( $column_display_names ) {
		$column_display_names['extra_info'] = 'Extra Info';
		return $column_display_names;
	}

	/**
	 * This filter is used to create the output for additional columns added via the
	 * affiliates_affiliate_stats_renderer_column_display_names filter.
	 *
	 * @param string $output
	 * @param string $key column key
	 * @param object $result
	 * @return string
	 */
	public static function affiliates_affiliate_stats_renderer_column_output( $output, $key, $result ) {
		switch ( $key ) {
			case 'extra_info' :
	            if( isset( $result->post_id ) ) {
        		    if( get_post_type( $result->post_id ) == 'shop_order' ) {
        		        
        		    	// Order id
        		        $order = new WC_Order( $result->post_id );
        		        $output .= 'Order #' . $result->post_id;
        		        $output .= '<br />';
        		        
        		        // Order items
        		        if ( sizeof( $order->get_items() ) > 0 ) {
        		            foreach ( $order->get_items() as $item ) {
        		            	$product = self::get_the_product_from_item( $item );
        		                if ( $product !== null ) {
        		                    $output .= '<a href=" ' . get_permalink( $product->get_id() ) . ' " >';
            	                    $output .= $product->get_name();
            	                    $output .= '</a>';
            	                    $output .= '<br />';
        		                }
        		            }
        		        }

        		        // Customer details
        		        $customer_id = $order->get_customer_id();
        		        $output .= $customer_id;
        		        $output .= '<br />';
        		        if ( $customer = get_user_by( 'ID', $customer_id ) ) {
        		        	$output .= $customer->first_name . ' ' . $customer->last_name;
        		        	$output .= '<br />';
        		        	$output .= $customer->user_email;
        		        	$output .= '<br />';
        		        } else {
        		        	$output .= $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        		        	$output .= '<br />';
        		        	$output .= $order->get_billing_email();
        		        	$output .= '<br />';
        		        }

						// Coupon details
						$coupon_codes = $order->get_used_coupons();
						if ( count( $coupon_codes ) > 0 ) {
							foreach ( $coupon_codes as $coupon_code ) {
								if ( class_exists( 'Affiliates_Attributes_WordPress' ) ) {
									if ( null !== Affiliates_Attributes_WordPress::get_affiliate_for_coupon( $coupon_code ) ) {
										if ( $result->affiliate_id == Affiliates_Attributes_WordPress::get_affiliate_for_coupon( $coupon_code ) ) {
											$output .= 'Affiliate referred by coupon: ';
											$output .= $coupon_code;
											$output .= '<br />';
										}
									}
								}
							}
						}

						// Order status
						$output .= $order->get_status();
						$output .= '<br />';
					}
				}
				$data_result = unserialize( $result->data );
				if ( $data_result ) {
					$referral_origin = 'affiliate';
					$referral_origin_value = '';
					if ( isset( $data_result ) && isset( $data_result['tier_level'] ) ) {
						$referral_origin = 'tier level';
						$referral_origin_value = $data_result['tier_level']['value'];
					}
					$output .= sprintf( '<strong>Referral origin:</strong> %s %s', $referral_origin, $referral_origin_value );
				}
			break;
		} // switch
		return $output;
	}

	/**
	 * Retrieve the product from an order item
	 *
	 * @param WC_Order_Item_Product $item
	 * @return WC_Product|null
	 */
	public static function get_the_product_from_item( $item ) {
		if ( method_exists( 'WC_Order_Item_Product', 'get_product_id' ) ) {
			$product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
		} else {
			$product_id = $item->variation_id ? $item->variation_id : $item->product_id;
		}
		return new WC_Product( $product_id ) ? new WC_Product( $product_id ) : null;
	}
}
Affiliates_Stats_Filters_Order_Details::init();
