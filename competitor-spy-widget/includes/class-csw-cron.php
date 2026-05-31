<?php
/**
 * Cron job management with auto price monitoring.
 *
 * Handles scheduled tasks: auto-refresh prices from URLs,
 * send email alerts on price changes, and data cleanup.
 *
 * @package CompetitorSpyWidget
 * @since 1.1.0
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
        add_action( 'csw_auto_refresh_prices', array( $this, 'auto_refresh_prices' ) );
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
        $schedules['csw_twice_daily'] = array(
            'interval' => 43200,
            'display'  => esc_html__( 'Twice Daily', 'competitor-spy-widget' ),
        );

        $schedules['csw_four_hours'] = array(
            'interval' => 14400,
            'display'  => esc_html__( 'Every 4 Hours', 'competitor-spy-widget' ),
        );

        $schedules['csw_six_hours'] = array(
            'interval' => 21600,
            'display'  => esc_html__( 'Every 6 Hours', 'competitor-spy-widget' ),
        );

        return $schedules;
    }

    /**
     * Schedule all cron events.
     */
    public static function schedule_events() {
        // Auto refresh prices (twice daily by default)
        if ( ! wp_next_scheduled( 'csw_auto_refresh_prices' ) ) {
            wp_schedule_event( time() + 3600, 'csw_twice_daily', 'csw_auto_refresh_prices' );
        }

        // Data cleanup - weekly
        if ( ! wp_next_scheduled( 'csw_cleanup_data' ) ) {
            wp_schedule_event( time(), 'weekly', 'csw_cleanup_data' );
        }

        // Cache warming - every 6 hours
        if ( ! wp_next_scheduled( 'csw_warm_cache' ) ) {
            wp_schedule_event( time(), 'csw_six_hours', 'csw_warm_cache' );
        }
    }

    /**
     * Clear all cron events.
     */
    public static function clear_events() {
        $events = array( 'csw_auto_refresh_prices', 'csw_cleanup_data', 'csw_warm_cache' );

        foreach ( $events as $event ) {
            $timestamp = wp_next_scheduled( $event );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $event );
            }
        }
    }

    /**
     * Auto-refresh prices from competitor URLs.
     * This is the KEY value-add — runs automatically without user intervention.
     */
    public function auto_refresh_prices() {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';

        // Get all active entries that have a URL (auto-monitorable)
        $entries = $wpdb->get_results(
            "SELECT * FROM {$table} 
             WHERE is_active = 1 
             AND competitor_url != ''
             AND competitor_url IS NOT NULL
             ORDER BY last_checked ASC
             LIMIT 30" // phpcs:ignore
        );

        if ( empty( $entries ) ) {
            return;
        }

        $price_changes = array();

        foreach ( $entries as $entry ) {
            // Fetch new price
            $result = CSW_Price_Fetcher::fetch_price( $entry->competitor_url );

            if ( is_wp_error( $result ) ) {
                // Log failure but continue
                self::log( 'Fetch failed for URL: ' . $entry->competitor_url . ' - ' . $result->get_error_message() );
                continue;
            }

            $new_price = $result['price'];
            $old_price = (float) $entry->competitor_price;

            // Only update if price actually changed
            if ( abs( $new_price - $old_price ) < 0.01 ) {
                // Price same, just update last_checked
                $wpdb->update(
                    $table,
                    array( 'last_checked' => current_time( 'mysql' ) ),
                    array( 'id' => $entry->id ),
                    array( '%s' ),
                    array( '%d' )
                );
                continue;
            }

            // Price changed! Update it.
            $product = wc_get_product( $entry->product_id );
            $our_price = $product ? CSW_Price_Engine::get_product_price( $product ) : (float) $entry->our_price;

            CSW_Database::upsert_price( array(
                'product_id'       => $entry->product_id,
                'competitor_id'    => $entry->competitor_id,
                'competitor_price' => $new_price,
                'our_price'        => $our_price,
                'competitor_url'   => $entry->competitor_url,
                'source'           => 'auto_fetch',
            ) );

            // Record history
            CSW_Database::record_price_history( $entry->product_id, $entry->competitor_id, $new_price, $our_price );

            // Clear cache
            CSW_Cache::delete_product_comparison( $entry->product_id );

            // Track significant price changes for alerts
            $was_winning = $our_price < $old_price;
            $now_winning = $our_price < $new_price;

            // Alert if status changed (we were winning, now losing OR competitor dropped price significantly)
            if ( $was_winning && ! $now_winning ) {
                $price_changes[] = array(
                    'product_id'      => $entry->product_id,
                    'product_name'    => $product ? $product->get_name() : 'Product #' . $entry->product_id,
                    'competitor_name' => $this->get_competitor_name( $entry->competitor_id ),
                    'old_price'       => $old_price,
                    'new_price'       => $new_price,
                    'our_price'       => $our_price,
                    'status'          => 'lost',
                    'url'             => $entry->competitor_url,
                );
            } elseif ( ! $was_winning && $now_winning ) {
                $price_changes[] = array(
                    'product_id'      => $entry->product_id,
                    'product_name'    => $product ? $product->get_name() : 'Product #' . $entry->product_id,
                    'competitor_name' => $this->get_competitor_name( $entry->competitor_id ),
                    'old_price'       => $old_price,
                    'new_price'       => $new_price,
                    'our_price'       => $our_price,
                    'status'          => 'won',
                    'url'             => $entry->competitor_url,
                );
            }

            // Polite delay between requests
            usleep( 1000000 ); // 1 second
        }

        // Send email alerts for important price changes
        if ( ! empty( $price_changes ) ) {
            $this->send_price_alert_email( $price_changes );
        }
    }

    /**
     * Send email alert when competitor prices change significantly.
     *
     * @param array $changes Array of price change data.
     */
    private function send_price_alert_email( $changes ) {
        $admin_email = get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );

        // Count losses vs wins
        $losses = array_filter( $changes, function( $c ) { return 'lost' === $c['status']; } );
        $wins = array_filter( $changes, function( $c ) { return 'won' === $c['status']; } );

        $subject = sprintf(
            /* translators: 1: site name, 2: number of alerts */
            __( '[%1$s] Competitor Price Alert — %2$d change(s) detected', 'competitor-spy-widget' ),
            $site_name,
            count( $changes )
        );

        // Build HTML email
        $body = '<html><body style="font-family: -apple-system, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
        $body .= '<div style="background: #F9FAFB; border-radius: 12px; padding: 24px; border: 1px solid #E5E7EB;">';
        $body .= '<h2 style="margin: 0 0 16px; color: #1F2937; font-size: 20px;">🔔 Competitor Price Alert</h2>';
        $body .= '<p style="color: #6B7280; margin: 0 0 20px; font-size: 14px;">';
        $body .= esc_html__( 'The following competitor price changes were detected by Competitor Spy Widget:', 'competitor-spy-widget' );
        $body .= '</p>';

        // Lost prices (URGENT)
        if ( ! empty( $losses ) ) {
            $body .= '<div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 8px; padding: 16px; margin-bottom: 16px;">';
            $body .= '<h3 style="color: #DC2626; margin: 0 0 12px; font-size: 14px;">⚠️ ' . esc_html__( 'Competitors Now Cheaper (Widget Hidden)', 'competitor-spy-widget' ) . '</h3>';

            foreach ( $losses as $change ) {
                $body .= '<div style="padding: 8px 0; border-bottom: 1px solid #FECACA;">';
                $body .= '<strong style="color: #1F2937;">' . esc_html( $change['product_name'] ) . '</strong><br>';
                $body .= '<span style="font-size: 13px; color: #6B7280;">' . esc_html( $change['competitor_name'] ) . ': ';
                $body .= '<del>' . wc_price( $change['old_price'] ) . '</del> → <strong style="color: #DC2626;">' . wc_price( $change['new_price'] ) . '</strong>';
                $body .= ' (Your price: ' . wc_price( $change['our_price'] ) . ')</span>';
                $body .= '</div>';
            }

            $body .= '<p style="font-size: 12px; color: #DC2626; margin: 12px 0 0;">';
            $body .= esc_html__( '→ Widget has been auto-hidden for these products to protect your store.', 'competitor-spy-widget' );
            $body .= '</p></div>';
        }

        // Won prices (good news)
        if ( ! empty( $wins ) ) {
            $body .= '<div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 16px; margin-bottom: 16px;">';
            $body .= '<h3 style="color: #16A34A; margin: 0 0 12px; font-size: 14px;">✅ ' . esc_html__( 'You\'re Now Cheaper (Widget Showing)', 'competitor-spy-widget' ) . '</h3>';

            foreach ( $wins as $change ) {
                $body .= '<div style="padding: 8px 0; border-bottom: 1px solid #BBF7D0;">';
                $body .= '<strong style="color: #1F2937;">' . esc_html( $change['product_name'] ) . '</strong><br>';
                $body .= '<span style="font-size: 13px; color: #6B7280;">' . esc_html( $change['competitor_name'] ) . ' raised to: ';
                $body .= '<strong>' . wc_price( $change['new_price'] ) . '</strong>';
                $body .= ' (Your price: ' . wc_price( $change['our_price'] ) . ')</span>';
                $body .= '</div>';
            }

            $body .= '<p style="font-size: 12px; color: #16A34A; margin: 12px 0 0;">';
            $body .= esc_html__( '→ Widget is now showing for these products — customers can see you\'re cheaper!', 'competitor-spy-widget' );
            $body .= '</p></div>';
        }

        $body .= '<p style="font-size: 12px; color: #9CA3AF; margin: 16px 0 0;">';
        $body .= esc_html__( 'This is an automated alert from Competitor Spy Widget. Prices are checked automatically twice daily.', 'competitor-spy-widget' );
        $body .= ' <a href="' . esc_url( admin_url( 'admin.php?page=csw-prices' ) ) . '">' . esc_html__( 'View all prices →', 'competitor-spy-widget' ) . '</a>';
        $body .= '</p></div></body></html>';

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . $admin_email . '>',
        );

        wp_mail( $admin_email, $subject, $body, $headers );
    }

    /**
     * Get competitor name by ID.
     *
     * @param int $competitor_id Competitor ID.
     * @return string
     */
    private function get_competitor_name( $competitor_id ) {
        $competitor = CSW_Database::get_competitor( $competitor_id );
        return $competitor ? $competitor->name : __( 'Competitor', 'competitor-spy-widget' );
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

    /**
     * Log a message (debug only).
     *
     * @param string $message Message to log.
     */
    private static function log( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[CSW Cron] ' . $message ); // phpcs:ignore
        }
    }
}

// Initialize cron
new CSW_Cron();
