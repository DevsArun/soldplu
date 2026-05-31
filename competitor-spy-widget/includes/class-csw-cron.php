<?php
/**
 * Cron job management.
 *
 * Handles scheduled tasks like price refreshing and data cleanup.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Cron
 */
class CSW_Cron {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'cron_schedules', array( $this, 'add_custom_intervals' ) );
        add_action( 'csw_refresh_prices', array( $this, 'refresh_prices' ) );
        add_action( 'csw_cleanup_data', array( $this, 'cleanup_data' ) );
        add_action( 'csw_warm_cache', array( $this, 'warm_cache' ) );
    }

    /**
     * Add custom cron intervals.
     *
     * @param array $schedules Existing schedules.
     * @return array
     */
    public function add_custom_intervals( $schedules ) {
        $schedules['csw_fifteen_minutes'] = array(
            'interval' => 900,
            'display'  => esc_html__( 'Every 15 Minutes', 'competitor-spy-widget' ),
        );

        $schedules['csw_thirty_minutes'] = array(
            'interval' => 1800,
            'display'  => esc_html__( 'Every 30 Minutes', 'competitor-spy-widget' ),
        );

        $schedules['csw_four_hours'] = array(
            'interval' => 14400,
            'display'  => esc_html__( 'Every 4 Hours', 'competitor-spy-widget' ),
        );

        return $schedules;
    }

    /**
     * Schedule all cron events.
     */
    public static function schedule_events() {
        // Price refresh - every hour
        if ( ! wp_next_scheduled( 'csw_refresh_prices' ) ) {
            wp_schedule_event( time(), 'hourly', 'csw_refresh_prices' );
        }

        // Data cleanup - daily
        if ( ! wp_next_scheduled( 'csw_cleanup_data' ) ) {
            wp_schedule_event( time(), 'daily', 'csw_cleanup_data' );
        }

        // Cache warming - every 4 hours
        if ( ! wp_next_scheduled( 'csw_warm_cache' ) ) {
            wp_schedule_event( time(), 'csw_four_hours', 'csw_warm_cache' );
        }
    }

    /**
     * Clear all cron events.
     */
    public static function clear_events() {
        $events = array( 'csw_refresh_prices', 'csw_cleanup_data', 'csw_warm_cache' );

        foreach ( $events as $event ) {
            $timestamp = wp_next_scheduled( $event );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $event );
            }
        }
    }

    /**
     * Refresh stale prices.
     */
    public function refresh_prices() {
        $stale_products = CSW_Price_Engine::get_stale_products( 50 );

        foreach ( $stale_products as $product ) {
            CSW_Price_Engine::refresh_product_prices( $product->product_id );
        }
    }

    /**
     * Clean up old data.
     */
    public function cleanup_data() {
        CSW_Database::cleanup_old_data( 90 );
    }

    /**
     * Warm popular product caches.
     */
    public function warm_cache() {
        CSW_Cache::warm_popular_products( 50 );
    }
}

// Initialize cron
new CSW_Cron();
