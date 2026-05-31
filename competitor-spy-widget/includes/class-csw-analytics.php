<?php
/**
 * Analytics tracking and reporting.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Analytics
 */
class CSW_Analytics {

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! CSW_Settings::is_analytics_enabled() ) {
            return;
        }

        // Track WooCommerce add to cart after widget impression
        add_action( 'woocommerce_add_to_cart', array( $this, 'track_add_to_cart' ), 10, 6 );
    }

    /**
     * Track add to cart event as conversion.
     *
     * @param string $cart_item_key Cart item key.
     * @param int    $product_id    Product ID.
     * @param int    $quantity      Quantity.
     * @param int    $variation_id  Variation ID.
     * @param array  $variation     Variation data.
     * @param array  $cart_item_data Cart item data.
     */
    public function track_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        // Check if widget was shown for this product (via session/cookie)
        $session_key = 'csw_impression_' . $product_id;
        $impression = isset( $_COOKIE[ $session_key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $session_key ] ) ) : '';

        if ( $impression ) {
            CSW_Database::record_event( array(
                'product_id' => $product_id,
                'event_type' => 'conversion',
                'session_id' => $impression,
            ) );
        }
    }

    /**
     * Get conversion stats for dashboard.
     *
     * @param string $period Time period.
     * @return array
     */
    public static function get_conversion_stats( $period = '30days' ) {
        $summary = CSW_Database::get_analytics_summary( $period );

        return array(
            'impressions'     => $summary['total_impressions'],
            'conversions'     => $summary['total_conversions'],
            'conversion_rate' => $summary['conversion_rate'],
            'widget_hidden'   => $summary['widget_hidden'],
            'daily_stats'     => $summary['daily_stats'],
            'top_products'    => self::enrich_top_products( $summary['top_products'] ),
        );
    }

    /**
     * Enrich top products with product data.
     *
     * @param array $products Raw product data.
     * @return array
     */
    private static function enrich_top_products( $products ) {
        $enriched = array();

        foreach ( $products as $item ) {
            $product = wc_get_product( $item->product_id );
            if ( ! $product ) {
                continue;
            }

            $enriched[] = array(
                'id'         => $item->product_id,
                'name'       => $product->get_name(),
                'price'      => $product->get_price(),
                'image'      => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                'views'      => (int) $item->view_count,
                'permalink'  => get_permalink( $item->product_id ),
            );
        }

        return $enriched;
    }

    /**
     * Get widget performance metrics.
     *
     * @return array
     */
    public static function get_widget_performance() {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_analytics';

        // Last 30 days metrics
        $metrics = array();

        // Impression rate (impressions vs page views)
        $views = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} 
             WHERE event_type = 'widget_view' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" // phpcs:ignore
        );

        $impressions = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} 
             WHERE event_type = 'widget_impression' 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" // phpcs:ignore
        );

        $metrics['impression_rate'] = $views > 0 ? round( ( $impressions / $views ) * 100, 1 ) : 0;

        // Device breakdown
        $metrics['device_breakdown'] = $wpdb->get_results(
            "SELECT device_type, COUNT(*) as count 
             FROM {$table} 
             WHERE event_type = 'widget_impression'
             AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY device_type" // phpcs:ignore
        );

        // Hourly distribution
        $metrics['hourly_distribution'] = $wpdb->get_results(
            "SELECT HOUR(created_at) as hour, COUNT(*) as count 
             FROM {$table} 
             WHERE event_type = 'widget_impression'
             AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY HOUR(created_at)
             ORDER BY hour ASC" // phpcs:ignore
        );

        return $metrics;
    }

    /**
     * Export analytics data as CSV.
     *
     * @param string $period Time period.
     * @return string CSV content.
     */
    public static function export_csv( $period = '30days' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_analytics';

        $date_filter = '';
        switch ( $period ) {
            case '7days':
                $date_filter = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $date_filter = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90days':
                $date_filter = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
        }

        $results = $wpdb->get_results(
            "SELECT product_id, event_type, device_type, created_at 
             FROM {$table} {$date_filter} 
             ORDER BY created_at DESC" // phpcs:ignore
        );

        $csv = "Product ID,Product Name,Event Type,Device,Date\n";

        foreach ( $results as $row ) {
            $product = wc_get_product( $row->product_id );
            $name = $product ? $product->get_name() : 'N/A';

            $csv .= sprintf(
                "%d,%s,%s,%s,%s\n",
                $row->product_id,
                '"' . str_replace( '"', '""', $name ) . '"',
                $row->event_type,
                $row->device_type,
                $row->created_at
            );
        }

        return $csv;
    }
}

// Initialize analytics
new CSW_Analytics();
