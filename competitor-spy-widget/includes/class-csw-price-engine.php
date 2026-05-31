<?php
/**
 * Price comparison engine.
 *
 * Handles fetching, comparing, and processing competitor prices.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Price_Engine
 */
class CSW_Price_Engine {

    /**
     * Get comparison data for a product.
     *
     * @param int $product_id WooCommerce product ID.
     * @return array|false Comparison data or false if no data.
     */
    public static function get_comparison( $product_id ) {
        // Check cache first
        $cached = CSW_Cache::get_product_comparison( $product_id );
        if ( false !== $cached ) {
            return $cached;
        }

        // Get product price
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return false;
        }

        $our_price = self::get_product_price( $product );
        if ( ! $our_price || $our_price <= 0 ) {
            return false;
        }

        // Get competitor prices
        $hide_when_expensive = CSW_Settings::get( 'hide_when_expensive', '1' );
        $max_competitors     = (int) CSW_Settings::get( 'max_competitors', 3 );

        $prices = CSW_Database::get_product_prices( $product_id, '1' === $hide_when_expensive );

        if ( empty( $prices ) ) {
            return false;
        }

        // Limit competitors shown
        $prices = array_slice( $prices, 0, $max_competitors );

        // Build comparison data
        $comparison = array(
            'product_id'   => $product_id,
            'our_price'    => $our_price,
            'currency'     => get_woocommerce_currency(),
            'competitors'  => array(),
            'best_savings' => 0,
            'show_widget'  => false,
        );

        foreach ( $prices as $price_entry ) {
            $competitor_data = array(
                'id'               => (int) $price_entry->competitor_id,
                'name'             => $price_entry->competitor_name,
                'slug'             => $price_entry->competitor_slug,
                'logo'             => $price_entry->competitor_logo,
                'price'            => (float) $price_entry->competitor_price,
                'url'              => $price_entry->competitor_url,
                'price_difference' => (float) $price_entry->price_difference,
                'savings_percent'  => (float) $price_entry->savings_percent,
                'is_cheaper'       => (bool) $price_entry->is_cheaper,
                'last_checked'     => $price_entry->last_checked,
            );

            $comparison['competitors'][] = $competitor_data;

            if ( $competitor_data['savings_percent'] > $comparison['best_savings'] ) {
                $comparison['best_savings'] = $competitor_data['savings_percent'];
            }
        }

        // Determine if widget should show
        $comparison['show_widget'] = ! empty( $comparison['competitors'] );

        if ( '1' === $hide_when_expensive ) {
            // Only show if we're cheaper than at least one competitor
            $has_winning = false;
            foreach ( $comparison['competitors'] as $comp ) {
                if ( $comp['is_cheaper'] ) {
                    $has_winning = true;
                    break;
                }
            }
            $comparison['show_widget'] = $has_winning;
        }

        // Cache the result
        CSW_Cache::set_product_comparison( $product_id, $comparison );

        return $comparison;
    }

    /**
     * Get product price (handles variations).
     *
     * @param WC_Product $product Product object.
     * @return float
     */
    public static function get_product_price( $product ) {
        if ( $product->is_type( 'variable' ) ) {
            $price = $product->get_variation_price( 'min', true );
        } else {
            $price = $product->get_price();
        }

        return (float) $price;
    }

    /**
     * Update prices for a product from all active competitors.
     *
     * @param int $product_id Product ID.
     * @return bool
     */
    public static function refresh_product_prices( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return false;
        }

        $our_price = self::get_product_price( $product );
        $competitors = CSW_Database::get_competitors( true );

        foreach ( $competitors as $competitor ) {
            // Get current price entry
            $prices = CSW_Database::get_product_prices( $product_id );
            $found = false;

            foreach ( $prices as $price ) {
                if ( (int) $price->competitor_id === (int) $competitor->id ) {
                    // Update with current our_price
                    CSW_Database::upsert_price( array(
                        'product_id'       => $product_id,
                        'competitor_id'    => $competitor->id,
                        'competitor_price' => $price->competitor_price,
                        'our_price'        => $our_price,
                        'competitor_url'   => $price->competitor_url,
                        'source'           => $price->source,
                    ) );
                    $found = true;
                    break;
                }
            }

            // If API-based, try fetching new price
            if ( 'api' === $competitor->api_type && ! empty( $competitor->api_endpoint ) ) {
                $fetched_price = self::fetch_price_from_api( $product, $competitor );
                if ( $fetched_price ) {
                    CSW_Database::upsert_price( array(
                        'product_id'       => $product_id,
                        'competitor_id'    => $competitor->id,
                        'competitor_price' => $fetched_price['price'],
                        'our_price'        => $our_price,
                        'competitor_url'   => isset( $fetched_price['url'] ) ? $fetched_price['url'] : '',
                        'source'           => 'api',
                    ) );

                    // Record history
                    CSW_Database::record_price_history(
                        $product_id,
                        $competitor->id,
                        $fetched_price['price'],
                        $our_price
                    );
                }
            }
        }

        // Clear cache for this product
        CSW_Cache::delete_product_comparison( $product_id );

        return true;
    }

    /**
     * Fetch price from competitor API.
     *
     * @param WC_Product $product    Product object.
     * @param object     $competitor Competitor object.
     * @return array|false
     */
    private static function fetch_price_from_api( $product, $competitor ) {
        if ( empty( $competitor->api_endpoint ) ) {
            return false;
        }

        $sku = $product->get_sku();
        $name = $product->get_name();

        // Build API URL with product identifier
        $api_url = add_query_arg(
            array(
                'sku'  => $sku,
                'name' => urlencode( $name ),
            ),
            $competitor->api_endpoint
        );

        $args = array(
            'timeout'   => 15,
            'headers'   => array(
                'Accept' => 'application/json',
            ),
            'sslverify' => true,
        );

        // Add API key if available
        if ( ! empty( $competitor->api_key ) ) {
            $args['headers']['Authorization'] = 'Bearer ' . $competitor->api_key;
        }

        $response = wp_remote_get( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            self::log_error( 'API fetch failed for ' . $competitor->name . ': ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status_code ) {
            self::log_error( 'API returned status ' . $status_code . ' for ' . $competitor->name );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! $data || ! isset( $data['price'] ) ) {
            self::log_error( 'Invalid API response from ' . $competitor->name );
            return false;
        }

        return array(
            'price' => floatval( $data['price'] ),
            'url'   => isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '',
        );
    }

    /**
     * Bulk update prices when WooCommerce product price changes.
     *
     * @param int $product_id Product ID.
     */
    public static function on_product_price_change( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        $new_price = self::get_product_price( $product );

        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';

        // Get all price entries for this product
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE product_id = %d AND is_active = 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $product_id
            )
        );

        foreach ( $entries as $entry ) {
            $price_difference = (float) $entry->competitor_price - $new_price;
            $savings_percent = $entry->competitor_price > 0
                ? ( $price_difference / (float) $entry->competitor_price ) * 100
                : 0;

            $wpdb->update(
                $table,
                array(
                    'our_price'        => $new_price,
                    'price_difference' => $price_difference,
                    'savings_percent'  => round( $savings_percent, 2 ),
                    'is_cheaper'       => $new_price < (float) $entry->competitor_price ? 1 : 0,
                    'last_checked'     => current_time( 'mysql' ),
                ),
                array( 'id' => $entry->id ),
                array( '%f', '%f', '%f', '%d', '%s' ),
                array( '%d' )
            );
        }

        // Clear cache
        CSW_Cache::delete_product_comparison( $product_id );
    }

    /**
     * Get products that need price refresh.
     *
     * @param int $limit Number of products to return.
     * @return array
     */
    public static function get_stale_products( $limit = 50 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';
        $cache_duration = (int) CSW_Settings::get( 'cache_duration', 3600 );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT product_id FROM {$table} 
                 WHERE is_active = 1 
                 AND last_checked < DATE_SUB(NOW(), INTERVAL %d SECOND)
                 ORDER BY last_checked ASC
                 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $cache_duration,
                $limit
            )
        );
    }

    /**
     * Calculate stats for all products.
     *
     * @return array
     */
    public static function get_overall_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';

        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(DISTINCT product_id) as total_products,
                COUNT(*) as total_entries,
                SUM(CASE WHEN is_cheaper = 1 THEN 1 ELSE 0 END) as winning_entries,
                AVG(CASE WHEN is_cheaper = 1 THEN savings_percent ELSE 0 END) as avg_savings,
                MAX(savings_percent) as max_savings,
                AVG(price_difference) as avg_difference
             FROM {$table}
             WHERE is_active = 1" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        return array(
            'total_products'  => $stats ? (int) $stats->total_products : 0,
            'total_entries'   => $stats ? (int) $stats->total_entries : 0,
            'winning_entries' => $stats ? (int) $stats->winning_entries : 0,
            'avg_savings'     => $stats ? round( (float) $stats->avg_savings, 2 ) : 0,
            'max_savings'     => $stats ? round( (float) $stats->max_savings, 2 ) : 0,
            'avg_difference'  => $stats ? round( (float) $stats->avg_difference, 2 ) : 0,
            'win_rate'        => $stats && $stats->total_entries > 0
                ? round( ( (int) $stats->winning_entries / (int) $stats->total_entries ) * 100, 1 )
                : 0,
        );
    }

    /**
     * Log error message.
     *
     * @param string $message Error message.
     */
    private static function log_error( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Competitor Spy Widget] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }
}
