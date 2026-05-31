<?php
/**
 * Uninstall handler.
 *
 * Fires when the plugin is deleted via WordPress admin.
 * Removes all plugin data from the database.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Delete all plugin options
$options = $wpdb->get_col(
    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'csw_%'"
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Delete transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_csw_cache_%' OR option_name LIKE '_transient_timeout_csw_cache_%'"
);

// Drop custom tables
$tables = array(
    $wpdb->prefix . 'csw_competitors',
    $wpdb->prefix . 'csw_prices',
    $wpdb->prefix . 'csw_analytics',
    $wpdb->prefix . 'csw_price_history',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Clear scheduled cron events
wp_clear_scheduled_hook( 'csw_refresh_prices' );
wp_clear_scheduled_hook( 'csw_cleanup_data' );
wp_clear_scheduled_hook( 'csw_warm_cache' );

// Flush rewrite rules
flush_rewrite_rules();
