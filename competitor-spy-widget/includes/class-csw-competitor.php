<?php
/**
 * Competitor management class.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Competitor
 */
class CSW_Competitor {

    /**
     * Predefined competitor templates.
     *
     * @return array
     */
    public static function get_templates() {
        return array(
            'amazon' => array(
                'name'        => 'Amazon',
                'website_url' => 'https://www.amazon.com',
                'logo_url'    => CSW_PLUGIN_URL . 'public/assets/images/competitors/amazon.svg',
                'api_type'    => 'manual',
            ),
            'walmart' => array(
                'name'        => 'Walmart',
                'website_url' => 'https://www.walmart.com',
                'logo_url'    => CSW_PLUGIN_URL . 'public/assets/images/competitors/walmart.svg',
                'api_type'    => 'manual',
            ),
            'ebay' => array(
                'name'        => 'eBay',
                'website_url' => 'https://www.ebay.com',
                'logo_url'    => CSW_PLUGIN_URL . 'public/assets/images/competitors/ebay.svg',
                'api_type'    => 'manual',
            ),
            'target' => array(
                'name'        => 'Target',
                'website_url' => 'https://www.target.com',
                'logo_url'    => CSW_PLUGIN_URL . 'public/assets/images/competitors/target.svg',
                'api_type'    => 'manual',
            ),
            'bestbuy' => array(
                'name'        => 'Best Buy',
                'website_url' => 'https://www.bestbuy.com',
                'logo_url'    => CSW_PLUGIN_URL . 'public/assets/images/competitors/bestbuy.svg',
                'api_type'    => 'manual',
            ),
            'custom' => array(
                'name'        => '',
                'website_url' => '',
                'logo_url'    => '',
                'api_type'    => 'manual',
            ),
        );
    }

    /**
     * Create a competitor from template.
     *
     * @param string $template_key Template key.
     * @param array  $overrides    Override data.
     * @return int|false
     */
    public static function create_from_template( $template_key, $overrides = array() ) {
        $templates = self::get_templates();

        if ( ! isset( $templates[ $template_key ] ) ) {
            return false;
        }

        $data = wp_parse_args( $overrides, $templates[ $template_key ] );
        $data['slug'] = sanitize_title( $data['name'] );

        return CSW_Database::insert_competitor( $data );
    }

    /**
     * Get competitors with their price stats.
     *
     * @return array
     */
    public static function get_with_stats() {
        global $wpdb;

        $competitors = CSW_Database::get_competitors( false );
        $prices_table = $wpdb->prefix . 'csw_prices';

        foreach ( $competitors as &$competitor ) {
            $stats = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT 
                        COUNT(*) as total_products,
                        SUM(CASE WHEN is_cheaper = 1 THEN 1 ELSE 0 END) as winning_count,
                        SUM(CASE WHEN is_cheaper = 0 THEN 1 ELSE 0 END) as losing_count,
                        AVG(savings_percent) as avg_savings
                     FROM {$prices_table} 
                     WHERE competitor_id = %d AND is_active = 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $competitor->id
                )
            );

            $competitor->total_products = $stats ? (int) $stats->total_products : 0;
            $competitor->winning_count  = $stats ? (int) $stats->winning_count : 0;
            $competitor->losing_count   = $stats ? (int) $stats->losing_count : 0;
            $competitor->avg_savings    = $stats ? round( (float) $stats->avg_savings, 2 ) : 0;
        }

        return $competitors;
    }

    /**
     * Validate competitor data.
     *
     * @param array $data Competitor data to validate.
     * @return array|WP_Error Validated data or error.
     */
    public static function validate( $data ) {
        $errors = array();

        if ( empty( $data['name'] ) ) {
            $errors[] = __( 'Competitor name is required.', 'competitor-spy-widget' );
        }

        if ( empty( $data['website_url'] ) ) {
            $errors[] = __( 'Website URL is required.', 'competitor-spy-widget' );
        } elseif ( ! filter_var( $data['website_url'], FILTER_VALIDATE_URL ) ) {
            $errors[] = __( 'Please enter a valid website URL.', 'competitor-spy-widget' );
        }

        if ( ! empty( $data['api_endpoint'] ) && ! filter_var( $data['api_endpoint'], FILTER_VALIDATE_URL ) ) {
            $errors[] = __( 'Please enter a valid API endpoint URL.', 'competitor-spy-widget' );
        }

        if ( ! empty( $errors ) ) {
            return new WP_Error( 'validation_failed', implode( ' ', $errors ) );
        }

        return array(
            'name'         => sanitize_text_field( $data['name'] ),
            'slug'         => sanitize_title( $data['name'] ),
            'website_url'  => esc_url_raw( $data['website_url'] ),
            'logo_url'     => isset( $data['logo_url'] ) ? esc_url_raw( $data['logo_url'] ) : '',
            'api_type'     => isset( $data['api_type'] ) ? sanitize_text_field( $data['api_type'] ) : 'manual',
            'api_key'      => isset( $data['api_key'] ) ? sanitize_text_field( $data['api_key'] ) : '',
            'api_endpoint' => isset( $data['api_endpoint'] ) ? esc_url_raw( $data['api_endpoint'] ) : '',
            'is_active'    => isset( $data['is_active'] ) ? absint( $data['is_active'] ) : 1,
            'priority'     => isset( $data['priority'] ) ? absint( $data['priority'] ) : 0,
        );
    }

    /**
     * Search competitors by name.
     *
     * @param string $search Search term.
     * @return array
     */
    public static function search( $search ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_competitors';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE name LIKE %s ORDER BY name ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                '%' . $wpdb->esc_like( $search ) . '%'
            )
        );
    }
}
