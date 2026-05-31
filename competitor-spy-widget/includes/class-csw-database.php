<?php
/**
 * Database management class.
 *
 * Handles all database operations including table creation,
 * migrations, and CRUD operations.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Database
 */
class CSW_Database {

    /**
     * Create all plugin database tables.
     *
     * @since 1.0.0
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Competitors table
        $table_competitors = $wpdb->prefix . 'csw_competitors';
        $sql_competitors = "CREATE TABLE {$table_competitors} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            website_url varchar(500) NOT NULL,
            logo_url varchar(500) DEFAULT '',
            api_type varchar(50) DEFAULT 'manual',
            api_key varchar(255) DEFAULT '',
            api_endpoint varchar(500) DEFAULT '',
            is_active tinyint(1) DEFAULT 1,
            priority int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_slug (slug),
            KEY idx_is_active (is_active)
        ) {$charset_collate};";

        dbDelta( $sql_competitors );

        // Price comparisons table
        $table_prices = $wpdb->prefix . 'csw_prices';
        $sql_prices = "CREATE TABLE {$table_prices} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            competitor_id bigint(20) unsigned NOT NULL,
            competitor_price decimal(10,2) NOT NULL,
            competitor_url varchar(500) DEFAULT '',
            our_price decimal(10,2) NOT NULL,
            price_difference decimal(10,2) DEFAULT 0,
            savings_percent decimal(5,2) DEFAULT 0,
            is_cheaper tinyint(1) DEFAULT 0,
            last_checked datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            source varchar(50) DEFAULT 'manual',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_product_id (product_id),
            KEY idx_competitor_id (competitor_id),
            KEY idx_product_competitor (product_id, competitor_id),
            KEY idx_is_cheaper (is_cheaper),
            KEY idx_is_active (is_active),
            KEY idx_last_checked (last_checked)
        ) {$charset_collate};";

        dbDelta( $sql_prices );

        // Analytics table
        $table_analytics = $wpdb->prefix . 'csw_analytics';
        $sql_analytics = "CREATE TABLE {$table_analytics} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            event_type varchar(50) NOT NULL,
            competitor_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(100) DEFAULT '',
            user_agent varchar(500) DEFAULT '',
            ip_hash varchar(64) DEFAULT '',
            device_type varchar(20) DEFAULT 'desktop',
            referrer varchar(500) DEFAULT '',
            metadata longtext DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_product_id (product_id),
            KEY idx_event_type (event_type),
            KEY idx_created_at (created_at),
            KEY idx_product_event (product_id, event_type),
            KEY idx_session (session_id)
        ) {$charset_collate};";

        dbDelta( $sql_analytics );

        // Price history table
        $table_history = $wpdb->prefix . 'csw_price_history';
        $sql_history = "CREATE TABLE {$table_history} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            competitor_id bigint(20) unsigned NOT NULL,
            competitor_price decimal(10,2) NOT NULL,
            our_price decimal(10,2) NOT NULL,
            recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_product_id (product_id),
            KEY idx_competitor_id (competitor_id),
            KEY idx_recorded_at (recorded_at),
            KEY idx_product_recorded (product_id, recorded_at)
        ) {$charset_collate};";

        dbDelta( $sql_history );

        update_option( 'csw_db_version', CSW_DB_VERSION );
    }

    /**
     * Drop all plugin tables (used on uninstall).
     *
     * @since 1.0.0
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'csw_competitors',
            $wpdb->prefix . 'csw_prices',
            $wpdb->prefix . 'csw_analytics',
            $wpdb->prefix . 'csw_price_history',
        );

        foreach ( $tables as $table ) {
            $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %i", $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }

    /**
     * Get a competitor by ID.
     *
     * @param int $id Competitor ID.
     * @return object|null
     */
    public static function get_competitor( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_competitors';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
    }

    /**
     * Get all active competitors.
     *
     * @param bool $active_only Whether to return only active competitors.
     * @return array
     */
    public static function get_competitors( $active_only = true ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_competitors';

        $where = $active_only ? 'WHERE is_active = 1' : '';

        return $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY priority ASC, name ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
    }

    /**
     * Insert a new competitor.
     *
     * @param array $data Competitor data.
     * @return int|false Insert ID or false on failure.
     */
    public static function insert_competitor( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_competitors';

        $defaults = array(
            'name'         => '',
            'slug'         => '',
            'website_url'  => '',
            'logo_url'     => '',
            'api_type'     => 'manual',
            'api_key'      => '',
            'api_endpoint' => '',
            'is_active'    => 1,
            'priority'     => 0,
        );

        $data = wp_parse_args( $data, $defaults );

        if ( empty( $data['slug'] ) ) {
            $data['slug'] = sanitize_title( $data['name'] );
        }

        $result = $wpdb->insert(
            $table,
            array(
                'name'         => sanitize_text_field( $data['name'] ),
                'slug'         => sanitize_title( $data['slug'] ),
                'website_url'  => esc_url_raw( $data['website_url'] ),
                'logo_url'     => esc_url_raw( $data['logo_url'] ),
                'api_type'     => sanitize_text_field( $data['api_type'] ),
                'api_key'      => sanitize_text_field( $data['api_key'] ),
                'api_endpoint' => esc_url_raw( $data['api_endpoint'] ),
                'is_active'    => absint( $data['is_active'] ),
                'priority'     => absint( $data['priority'] ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a competitor.
     *
     * @param int   $id   Competitor ID.
     * @param array $data Data to update.
     * @return bool
     */
    public static function update_competitor( $id, $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_competitors';

        $update_data = array();
        $formats = array();

        $allowed_fields = array(
            'name'         => '%s',
            'slug'         => '%s',
            'website_url'  => '%s',
            'logo_url'     => '%s',
            'api_type'     => '%s',
            'api_key'      => '%s',
            'api_endpoint' => '%s',
            'is_active'    => '%d',
            'priority'     => '%d',
        );

        foreach ( $allowed_fields as $field => $format ) {
            if ( isset( $data[ $field ] ) ) {
                if ( '%s' === $format ) {
                    $update_data[ $field ] = sanitize_text_field( $data[ $field ] );
                } else {
                    $update_data[ $field ] = absint( $data[ $field ] );
                }
                $formats[] = $format;
            }
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            $formats,
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Delete a competitor.
     *
     * @param int $id Competitor ID.
     * @return bool
     */
    public static function delete_competitor( $id ) {
        global $wpdb;

        // Delete associated prices first
        $prices_table = $wpdb->prefix . 'csw_prices';
        $wpdb->delete( $prices_table, array( 'competitor_id' => $id ), array( '%d' ) );

        // Delete competitor
        $table = $wpdb->prefix . 'csw_competitors';
        $result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

        return false !== $result;
    }

    /**
     * Get prices for a product.
     *
     * @param int  $product_id Product ID.
     * @param bool $cheaper_only Return only cheaper prices.
     * @return array
     */
    public static function get_product_prices( $product_id, $cheaper_only = false ) {
        global $wpdb;

        $prices_table = $wpdb->prefix . 'csw_prices';
        $competitors_table = $wpdb->prefix . 'csw_competitors';

        $where_cheaper = $cheaper_only ? 'AND p.is_cheaper = 1' : '';

        $query = $wpdb->prepare(
            "SELECT p.*, c.name as competitor_name, c.slug as competitor_slug, 
                    c.website_url as competitor_website, c.logo_url as competitor_logo
             FROM {$prices_table} p
             INNER JOIN {$competitors_table} c ON p.competitor_id = c.id
             WHERE p.product_id = %d 
             AND p.is_active = 1 
             AND c.is_active = 1
             {$where_cheaper}
             ORDER BY p.competitor_price DESC",
            $product_id
        ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Insert or update a price entry.
     *
     * @param array $data Price data.
     * @return int|false
     */
    public static function upsert_price( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';

        $product_id    = absint( $data['product_id'] );
        $competitor_id = absint( $data['competitor_id'] );

        // Check if entry exists
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE product_id = %d AND competitor_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $product_id,
                $competitor_id
            )
        );

        $competitor_price = floatval( $data['competitor_price'] );
        $our_price        = floatval( $data['our_price'] );
        $price_difference = $competitor_price - $our_price;
        $savings_percent  = $competitor_price > 0 ? ( $price_difference / $competitor_price ) * 100 : 0;
        $is_cheaper       = $our_price < $competitor_price ? 1 : 0;

        $insert_data = array(
            'product_id'       => $product_id,
            'competitor_id'    => $competitor_id,
            'competitor_price' => $competitor_price,
            'competitor_url'   => isset( $data['competitor_url'] ) ? esc_url_raw( $data['competitor_url'] ) : '',
            'our_price'        => $our_price,
            'price_difference' => $price_difference,
            'savings_percent'  => round( $savings_percent, 2 ),
            'is_cheaper'       => $is_cheaper,
            'last_checked'     => current_time( 'mysql' ),
            'is_active'        => 1,
            'source'           => isset( $data['source'] ) ? sanitize_text_field( $data['source'] ) : 'manual',
        );

        if ( $existing ) {
            unset( $insert_data['product_id'], $insert_data['competitor_id'] );
            $wpdb->update(
                $table,
                $insert_data,
                array( 'id' => $existing ),
                array( '%d', '%s', '%f', '%f', '%f', '%d', '%s', '%d', '%s' ),
                array( '%d' )
            );
            return $existing;
        } else {
            $wpdb->insert(
                $table,
                $insert_data,
                array( '%d', '%d', '%f', '%s', '%f', '%f', '%f', '%d', '%s', '%d', '%s' )
            );
            return $wpdb->insert_id;
        }
    }

    /**
     * Delete a price entry.
     *
     * @param int $id Price ID.
     * @return bool
     */
    public static function delete_price( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';

        $result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
        return false !== $result;
    }

    /**
     * Record an analytics event.
     *
     * @param array $data Event data.
     * @return int|false
     */
    public static function record_event( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_analytics';

        $insert_data = array(
            'product_id'    => absint( $data['product_id'] ),
            'event_type'    => sanitize_text_field( $data['event_type'] ),
            'competitor_id' => isset( $data['competitor_id'] ) ? absint( $data['competitor_id'] ) : null,
            'session_id'    => isset( $data['session_id'] ) ? sanitize_text_field( $data['session_id'] ) : '',
            'user_agent'    => isset( $data['user_agent'] ) ? sanitize_text_field( substr( $data['user_agent'], 0, 500 ) ) : '',
            'ip_hash'       => isset( $data['ip_hash'] ) ? sanitize_text_field( $data['ip_hash'] ) : '',
            'device_type'   => isset( $data['device_type'] ) ? sanitize_text_field( $data['device_type'] ) : 'desktop',
            'referrer'      => isset( $data['referrer'] ) ? esc_url_raw( $data['referrer'] ) : '',
            'metadata'      => isset( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : '',
        );

        $result = $wpdb->insert(
            $table,
            $insert_data,
            array( '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get analytics summary.
     *
     * @param string $period Period (7days, 30days, 90days, all).
     * @return array
     */
    public static function get_analytics_summary( $period = '30days' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_analytics';

        $date_filter = '';
        switch ( $period ) {
            case '7days':
                $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '90days':
                $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            default:
                $date_filter = '';
                break;
        }

        $summary = array();

        // Total widget views
        $summary['total_views'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE event_type = 'widget_view' {$date_filter}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Total widget impressions (widget actually displayed)
        $summary['total_impressions'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE event_type = 'widget_impression' {$date_filter}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Total conversions (add to cart after seeing widget)
        $summary['total_conversions'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE event_type = 'conversion' {$date_filter}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Conversion rate
        $summary['conversion_rate'] = $summary['total_impressions'] > 0
            ? round( ( $summary['total_conversions'] / $summary['total_impressions'] ) * 100, 2 )
            : 0;

        // Widget hidden count (when competitor is cheaper)
        $summary['widget_hidden'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE event_type = 'widget_hidden' {$date_filter}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Top performing products
        $summary['top_products'] = $wpdb->get_results(
            "SELECT product_id, COUNT(*) as view_count 
             FROM {$table} 
             WHERE event_type = 'widget_impression' {$date_filter}
             GROUP BY product_id 
             ORDER BY view_count DESC 
             LIMIT 10" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Daily stats for chart
        $summary['daily_stats'] = $wpdb->get_results(
            "SELECT DATE(created_at) as date, 
                    SUM(CASE WHEN event_type = 'widget_impression' THEN 1 ELSE 0 END) as impressions,
                    SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) as conversions
             FROM {$table}
             WHERE 1=1 {$date_filter}
             GROUP BY DATE(created_at)
             ORDER BY date ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        return $summary;
    }

    /**
     * Record price history.
     *
     * @param int   $product_id    Product ID.
     * @param int   $competitor_id Competitor ID.
     * @param float $competitor_price Competitor price.
     * @param float $our_price Our price.
     */
    public static function record_price_history( $product_id, $competitor_id, $competitor_price, $our_price ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_price_history';

        $wpdb->insert(
            $table,
            array(
                'product_id'       => absint( $product_id ),
                'competitor_id'    => absint( $competitor_id ),
                'competitor_price' => floatval( $competitor_price ),
                'our_price'        => floatval( $our_price ),
            ),
            array( '%d', '%d', '%f', '%f' )
        );
    }

    /**
     * Get price history for a product.
     *
     * @param int    $product_id Product ID.
     * @param int    $competitor_id Competitor ID (optional).
     * @param string $period Period.
     * @return array
     */
    public static function get_price_history( $product_id, $competitor_id = null, $period = '30days' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_price_history';

        $where = array( 'product_id = %d' );
        $params = array( $product_id );

        if ( $competitor_id ) {
            $where[] = 'competitor_id = %d';
            $params[] = $competitor_id;
        }

        switch ( $period ) {
            case '7days':
                $where[] = 'recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case '30days':
                $where[] = 'recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                break;
            case '90days':
                $where[] = 'recorded_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
                break;
        }

        $where_clause = implode( ' AND ', $where );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY recorded_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                ...$params
            )
        );
    }

    /**
     * Clean old analytics data.
     *
     * @param int $days Days to keep.
     */
    public static function cleanup_old_data( $days = 90 ) {
        global $wpdb;

        $analytics_table = $wpdb->prefix . 'csw_analytics';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$analytics_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $days
            )
        );

        $history_table = $wpdb->prefix . 'csw_price_history';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$history_table} WHERE recorded_at < DATE_SUB(NOW(), INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $days * 2
            )
        );
    }

    /**
     * Get total counts for dashboard.
     *
     * @return array
     */
    public static function get_dashboard_counts() {
        global $wpdb;

        $competitors_table = $wpdb->prefix . 'csw_competitors';
        $prices_table = $wpdb->prefix . 'csw_prices';

        return array(
            'total_competitors'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$competitors_table} WHERE is_active = 1" ), // phpcs:ignore
            'total_price_entries' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prices_table} WHERE is_active = 1" ), // phpcs:ignore
            'winning_products'    => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT product_id) FROM {$prices_table} WHERE is_cheaper = 1 AND is_active = 1" ), // phpcs:ignore
            'losing_products'     => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT product_id) FROM {$prices_table} WHERE is_cheaper = 0 AND is_active = 1" ), // phpcs:ignore
        );
    }
}
