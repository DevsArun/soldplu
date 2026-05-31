<?php
/**
 * Price Optimization Engine.
 *
 * Analyzes competitor prices and generates smart pricing suggestions
 * to help store owners maximize profit while staying competitive.
 *
 * @package CompetitorSpyWidget
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Optimizer
 */
class CSW_Optimizer {

    /**
     * Get optimization suggestions for a product.
     *
     * @param int $product_id Product ID.
     * @return array Array of suggestions.
     */
    public static function get_product_suggestions( $product_id ) {
        if ( ! CSW_License::is_pro() ) {
            return array();
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return array();
        }

        $our_price = CSW_Price_Engine::get_product_price( $product );
        $prices = CSW_Database::get_product_prices( $product_id );

        if ( empty( $prices ) || $our_price <= 0 ) {
            return array();
        }

        $suggestions = array();

        // Get all competitor prices
        $competitor_prices = array();
        foreach ( $prices as $p ) {
            $competitor_prices[] = (float) $p->competitor_price;
        }

        $min_competitor = min( $competitor_prices );
        $max_competitor = max( $competitor_prices );
        $avg_competitor = array_sum( $competitor_prices ) / count( $competitor_prices );

        // Suggestion 1: Price increase opportunity
        if ( $our_price < $min_competitor ) {
            $gap = $min_competitor - $our_price;
            $safe_increase = floor( $gap * 0.6 ); // 60% of gap is safe to capture

            if ( $safe_increase > 1 ) {
                $suggested_price = $our_price + $safe_increase;
                $profit_increase_percent = round( ( $safe_increase / $our_price ) * 100, 1 );

                $suggestions[] = array(
                    'type'       => 'increase',
                    'priority'   => 'high',
                    'icon'       => '💰',
                    'title'      => __( 'Price Increase Opportunity', 'competitor-spy-widget' ),
                    'message'    => sprintf(
                        /* translators: 1: amount, 2: percentage, 3: suggested price */
                        __( 'You can increase your price by %1$s (+%2$s%%) and still be the cheapest. Suggested: %3$s', 'competitor-spy-widget' ),
                        wp_strip_all_tags( wc_price( $safe_increase ) ),
                        $profit_increase_percent,
                        wp_strip_all_tags( wc_price( $suggested_price ) )
                    ),
                    'current'    => $our_price,
                    'suggested'  => $suggested_price,
                    'impact'     => sprintf(
                        /* translators: %s: percentage */
                        __( '+%s%% profit per sale', 'competitor-spy-widget' ),
                        $profit_increase_percent
                    ),
                    'confidence' => 'high',
                );
            }
        }

        // Suggestion 2: Too expensive — consider price match
        if ( $our_price > $min_competitor ) {
            $overprice = $our_price - $min_competitor;
            $overprice_percent = round( ( $overprice / $min_competitor ) * 100, 1 );

            // Only suggest if overprice is within reasonable matching range (< 20%)
            if ( $overprice_percent <= 20 ) {
                $match_price = $min_competitor - 1; // Undercut by $1

                $suggestions[] = array(
                    'type'       => 'match',
                    'priority'   => 'medium',
                    'icon'       => '⚡',
                    'title'      => __( 'Price Match Opportunity', 'competitor-spy-widget' ),
                    'message'    => sprintf(
                        /* translators: 1: competitor name, 2: their price, 3: difference */
                        __( 'Reduce by %3$s to beat %1$s (%2$s). Widget will start showing, boosting conversions.', 'competitor-spy-widget' ),
                        esc_html( $prices[0]->competitor_name ),
                        wp_strip_all_tags( wc_price( $min_competitor ) ),
                        wp_strip_all_tags( wc_price( $overprice + 1 ) )
                    ),
                    'current'    => $our_price,
                    'suggested'  => $match_price,
                    'impact'     => __( 'Widget will become visible → more conversions', 'competitor-spy-widget' ),
                    'confidence' => 'medium',
                );
            }
        }

        // Suggestion 3: Competitor price trending up — hold or increase
        $history = CSW_Database::get_price_history( $product_id, null, '30days' );
        if ( count( $history ) >= 5 ) {
            $recent = array_slice( $history, -5 );
            $older = array_slice( $history, 0, 5 );

            $recent_avg = 0;
            $older_avg = 0;
            foreach ( $recent as $h ) {
                $recent_avg += (float) $h->competitor_price;
            }
            foreach ( $older as $h ) {
                $older_avg += (float) $h->competitor_price;
            }
            $recent_avg /= count( $recent );
            $older_avg /= count( $older );

            if ( $recent_avg > $older_avg * 1.05 ) {
                // Competitors trending UP (5%+ increase)
                $trend_percent = round( ( ( $recent_avg - $older_avg ) / $older_avg ) * 100, 1 );

                $suggestions[] = array(
                    'type'       => 'trend',
                    'priority'   => 'low',
                    'icon'       => '📈',
                    'title'      => __( 'Competitor Prices Rising', 'competitor-spy-widget' ),
                    'message'    => sprintf(
                        /* translators: %s: percentage */
                        __( 'Competitors have increased prices by ~%s%% in the last 30 days. You have room to increase yours too.', 'competitor-spy-widget' ),
                        $trend_percent
                    ),
                    'current'    => $our_price,
                    'suggested'  => null,
                    'impact'     => __( 'Market supports higher prices', 'competitor-spy-widget' ),
                    'confidence' => 'medium',
                );
            } elseif ( $recent_avg < $older_avg * 0.95 ) {
                // Competitors trending DOWN
                $trend_percent = round( ( ( $older_avg - $recent_avg ) / $older_avg ) * 100, 1 );

                $suggestions[] = array(
                    'type'       => 'alert',
                    'priority'   => 'high',
                    'icon'       => '⚠️',
                    'title'      => __( 'Competitor Prices Dropping', 'competitor-spy-widget' ),
                    'message'    => sprintf(
                        /* translators: %s: percentage */
                        __( 'Competitors have dropped prices by ~%s%% recently. Monitor closely or consider adjusting.', 'competitor-spy-widget' ),
                        $trend_percent
                    ),
                    'current'    => $our_price,
                    'suggested'  => null,
                    'impact'     => __( 'Risk of losing price advantage', 'competitor-spy-widget' ),
                    'confidence' => 'high',
                );
            }
        }

        // Suggestion 4: Sweet spot pricing
        if ( $our_price < $avg_competitor && $our_price >= $min_competitor ) {
            $suggestions[] = array(
                'type'       => 'optimal',
                'priority'   => 'info',
                'icon'       => '✅',
                'title'      => __( 'Optimally Priced', 'competitor-spy-widget' ),
                'message'    => __( 'Your price is well-positioned — below average competitor price but maintaining good margins.', 'competitor-spy-widget' ),
                'current'    => $our_price,
                'suggested'  => null,
                'impact'     => __( 'No action needed', 'competitor-spy-widget' ),
                'confidence' => 'high',
            );
        }

        return $suggestions;
    }

    /**
     * Get all suggestions across all monitored products.
     *
     * @param int $limit Max suggestions to return.
     * @return array
     */
    public static function get_all_suggestions( $limit = 20 ) {
        if ( ! CSW_License::is_pro() ) {
            return array();
        }

        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';

        // Get distinct product IDs
        $product_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT product_id FROM {$table} WHERE is_active = 1 LIMIT %d", // phpcs:ignore
                50
            )
        );

        $all_suggestions = array();

        foreach ( $product_ids as $pid ) {
            $product_suggestions = self::get_product_suggestions( (int) $pid );
            foreach ( $product_suggestions as $suggestion ) {
                $suggestion['product_id'] = (int) $pid;
                $product = wc_get_product( (int) $pid );
                $suggestion['product_name'] = $product ? $product->get_name() : 'Product #' . $pid;
                $all_suggestions[] = $suggestion;
            }
        }

        // Sort by priority
        $priority_order = array( 'high' => 1, 'medium' => 2, 'low' => 3, 'info' => 4 );
        usort( $all_suggestions, function( $a, $b ) use ( $priority_order ) {
            $a_order = isset( $priority_order[ $a['priority'] ] ) ? $priority_order[ $a['priority'] ] : 5;
            $b_order = isset( $priority_order[ $b['priority'] ] ) ? $priority_order[ $b['priority'] ] : 5;
            return $a_order - $b_order;
        } );

        return array_slice( $all_suggestions, 0, $limit );
    }

    /**
     * Calculate potential revenue impact of suggestions.
     *
     * @return array Revenue impact summary.
     */
    public static function get_revenue_impact() {
        $suggestions = self::get_all_suggestions( 50 );

        $total_increase_opportunity = 0;
        $products_with_opportunity = 0;

        foreach ( $suggestions as $s ) {
            if ( 'increase' === $s['type'] && ! empty( $s['suggested'] ) && ! empty( $s['current'] ) ) {
                $total_increase_opportunity += ( $s['suggested'] - $s['current'] );
                $products_with_opportunity++;
            }
        }

        return array(
            'total_increase_potential'  => $total_increase_opportunity,
            'products_with_opportunity' => $products_with_opportunity,
            'avg_increase_per_product'  => $products_with_opportunity > 0
                ? round( $total_increase_opportunity / $products_with_opportunity, 2 )
                : 0,
        );
    }
}
