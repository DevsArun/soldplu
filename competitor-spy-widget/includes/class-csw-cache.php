<?php
/**
 * Caching layer for the plugin.
 *
 * Uses WordPress transients with optional object cache support.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Cache
 */
class CSW_Cache {

    /**
     * Cache prefix.
     *
     * @var string
     */
    const PREFIX = 'csw_cache_';

    /**
     * Get cached product comparison data.
     *
     * @param int $product_id Product ID.
     * @return array|false Cached data or false if not cached.
     */
    public static function get_product_comparison( $product_id ) {
        $key = self::PREFIX . 'comparison_' . $product_id;

        // Try object cache first
        $cached = wp_cache_get( $key, 'csw' );
        if ( false !== $cached ) {
            return $cached;
        }

        // Fall back to transients
        $cached = get_transient( $key );
        if ( false !== $cached ) {
            // Store in object cache for this request
            wp_cache_set( $key, $cached, 'csw', self::get_duration() );
            return $cached;
        }

        return false;
    }

    /**
     * Set cached product comparison data.
     *
     * @param int   $product_id Product ID.
     * @param array $data       Comparison data.
     */
    public static function set_product_comparison( $product_id, $data ) {
        $key = self::PREFIX . 'comparison_' . $product_id;
        $duration = self::get_duration();

        // Store in object cache
        wp_cache_set( $key, $data, 'csw', $duration );

        // Store in transient
        set_transient( $key, $data, $duration );
    }

    /**
     * Delete cached product comparison data.
     *
     * @param int $product_id Product ID.
     */
    public static function delete_product_comparison( $product_id ) {
        $key = self::PREFIX . 'comparison_' . $product_id;

        wp_cache_delete( $key, 'csw' );
        delete_transient( $key );
    }

    /**
     * Get cached widget HTML.
     *
     * @param int    $product_id Product ID.
     * @param string $context    Context identifier.
     * @return string|false
     */
    public static function get_widget_html( $product_id, $context = 'default' ) {
        $key = self::PREFIX . 'widget_' . $product_id . '_' . $context;

        $cached = wp_cache_get( $key, 'csw' );
        if ( false !== $cached ) {
            return $cached;
        }

        return get_transient( $key );
    }

    /**
     * Set cached widget HTML.
     *
     * @param int    $product_id Product ID.
     * @param string $html       Widget HTML.
     * @param string $context    Context identifier.
     */
    public static function set_widget_html( $product_id, $html, $context = 'default' ) {
        $key = self::PREFIX . 'widget_' . $product_id . '_' . $context;
        $duration = self::get_duration();

        wp_cache_set( $key, $html, 'csw', $duration );
        set_transient( $key, $html, $duration );
    }

    /**
     * Delete cached widget HTML.
     *
     * @param int $product_id Product ID.
     */
    public static function delete_widget_html( $product_id ) {
        $contexts = array( 'default', 'mobile', 'tablet' );
        foreach ( $contexts as $context ) {
            $key = self::PREFIX . 'widget_' . $product_id . '_' . $context;
            wp_cache_delete( $key, 'csw' );
            delete_transient( $key );
        }
    }

    /**
     * Get cache duration in seconds.
     *
     * @return int
     */
    public static function get_duration() {
        return (int) CSW_Settings::get( 'cache_duration', 3600 );
    }

    /**
     * Flush all plugin caches.
     */
    public static function flush_all() {
        global $wpdb;

        // Delete all plugin transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . self::PREFIX . '%',
                '_transient_timeout_' . self::PREFIX . '%'
            )
        );

        // Flush object cache group if supported
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'csw' );
        }
    }

    /**
     * Get cache statistics.
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;

        $total_transients = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::PREFIX . '%'
            )
        );

        return array(
            'total_cached_items'  => $total_transients,
            'cache_duration'      => self::get_duration(),
            'object_cache_active' => wp_using_ext_object_cache(),
        );
    }

    /**
     * Warm cache for popular products.
     *
     * @param int $limit Number of products to warm.
     */
    public static function warm_popular_products( $limit = 20 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';

        $products = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT product_id FROM {$table} WHERE is_active = 1 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $limit
            )
        );

        foreach ( $products as $product_id ) {
            CSW_Price_Engine::get_comparison( (int) $product_id );
        }
    }
}
