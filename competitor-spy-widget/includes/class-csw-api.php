<?php
/**
 * REST API endpoints.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_API
 */
class CSW_API {

    /**
     * API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'csw/v1';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        // Get comparison for a product
        register_rest_route( self::NAMESPACE, '/comparison/(?P<product_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_comparison' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'product_id' => array(
                    'required'          => true,
                    'validate_callback' => function ( $param ) {
                        return is_numeric( $param );
                    },
                ),
            ),
        ) );

        // Admin: Get all competitors
        register_rest_route( self::NAMESPACE, '/competitors', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_competitors' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'create_competitor' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
        ) );

        // Admin: Single competitor
        register_rest_route( self::NAMESPACE, '/competitors/(?P<id>\d+)', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_competitor' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'update_competitor' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'delete_competitor' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
        ) );

        // Admin: Prices
        register_rest_route( self::NAMESPACE, '/prices', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_prices' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'upsert_price' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
        ) );

        // Admin: Delete price
        register_rest_route( self::NAMESPACE, '/prices/(?P<id>\d+)', array(
            'methods'             => 'DELETE',
            'callback'            => array( $this, 'delete_price' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );

        // Admin: Analytics
        register_rest_route( self::NAMESPACE, '/analytics', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_analytics' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
            'args'                => array(
                'period' => array(
                    'default'           => '30days',
                    'validate_callback' => function ( $param ) {
                        return in_array( $param, array( '7days', '30days', '90days', 'all' ), true );
                    },
                ),
            ),
        ) );

        // Admin: Settings
        register_rest_route( self::NAMESPACE, '/settings', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_settings' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'update_settings' ),
                'permission_callback' => array( $this, 'admin_permission_check' ),
            ),
        ) );

        // Admin: Dashboard stats
        register_rest_route( self::NAMESPACE, '/dashboard', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_dashboard' ),
            'permission_callback' => array( $this, 'admin_permission_check' ),
        ) );
    }

    /**
     * Admin permission check.
     *
     * @return bool
     */
    public function admin_permission_check() {
        return current_user_can( 'manage_woocommerce' );
    }

    /**
     * Get comparison data for a product.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_comparison( $request ) {
        $product_id = absint( $request['product_id'] );

        // Rate limiting for public endpoint
        if ( ! CSW_Security::rate_limit_check( 'comparison', 30, 60 ) ) {
            return new WP_REST_Response( array( 'message' => 'Rate limit exceeded' ), 429 );
        }

        $comparison = CSW_Price_Engine::get_comparison( $product_id );

        if ( ! $comparison ) {
            return new WP_REST_Response( array( 'show' => false ), 200 );
        }

        return new WP_REST_Response( $comparison, 200 );
    }

    /**
     * Get all competitors.
     *
     * @return WP_REST_Response
     */
    public function get_competitors() {
        $competitors = CSW_Competitor::get_with_stats();
        return new WP_REST_Response( $competitors, 200 );
    }

    /**
     * Get single competitor.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_competitor( $request ) {
        $competitor = CSW_Database::get_competitor( absint( $request['id'] ) );

        if ( ! $competitor ) {
            return new WP_REST_Response( array( 'message' => 'Competitor not found' ), 404 );
        }

        return new WP_REST_Response( $competitor, 200 );
    }

    /**
     * Create a competitor.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function create_competitor( $request ) {
        $data = $request->get_json_params();

        $validated = CSW_Competitor::validate( $data );
        if ( is_wp_error( $validated ) ) {
            return new WP_REST_Response( array( 'message' => $validated->get_error_message() ), 400 );
        }

        $id = CSW_Database::insert_competitor( $validated );

        if ( ! $id ) {
            return new WP_REST_Response( array( 'message' => 'Failed to create competitor' ), 500 );
        }

        $competitor = CSW_Database::get_competitor( $id );
        return new WP_REST_Response( $competitor, 201 );
    }

    /**
     * Update a competitor.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function update_competitor( $request ) {
        $id = absint( $request['id'] );
        $data = $request->get_json_params();

        $existing = CSW_Database::get_competitor( $id );
        if ( ! $existing ) {
            return new WP_REST_Response( array( 'message' => 'Competitor not found' ), 404 );
        }

        $result = CSW_Database::update_competitor( $id, $data );

        if ( ! $result ) {
            return new WP_REST_Response( array( 'message' => 'Failed to update competitor' ), 500 );
        }

        $competitor = CSW_Database::get_competitor( $id );
        return new WP_REST_Response( $competitor, 200 );
    }

    /**
     * Delete a competitor.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function delete_competitor( $request ) {
        $id = absint( $request['id'] );

        $existing = CSW_Database::get_competitor( $id );
        if ( ! $existing ) {
            return new WP_REST_Response( array( 'message' => 'Competitor not found' ), 404 );
        }

        $result = CSW_Database::delete_competitor( $id );

        if ( ! $result ) {
            return new WP_REST_Response( array( 'message' => 'Failed to delete competitor' ), 500 );
        }

        return new WP_REST_Response( array( 'message' => 'Competitor deleted successfully' ), 200 );
    }

    /**
     * Get prices for a product.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_prices( $request ) {
        $product_id = $request->get_param( 'product_id' );

        if ( $product_id ) {
            $prices = CSW_Database::get_product_prices( absint( $product_id ) );
        } else {
            global $wpdb;
            $table = $wpdb->prefix . 'csw_prices';
            $competitors_table = $wpdb->prefix . 'csw_competitors';

            $prices = $wpdb->get_results(
                "SELECT p.*, c.name as competitor_name, c.logo_url as competitor_logo
                 FROM {$table} p
                 INNER JOIN {$competitors_table} c ON p.competitor_id = c.id
                 WHERE p.is_active = 1
                 ORDER BY p.product_id ASC, p.competitor_price DESC" // phpcs:ignore
            );
        }

        return new WP_REST_Response( $prices, 200 );
    }

    /**
     * Create or update a price entry.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function upsert_price( $request ) {
        $data = $request->get_json_params();

        $schema = array(
            'product_id'       => array( 'type' => 'integer', 'required' => true, 'min' => 1 ),
            'competitor_id'    => array( 'type' => 'integer', 'required' => true, 'min' => 1 ),
            'competitor_price' => array( 'type' => 'float', 'required' => true, 'min' => 0 ),
            'competitor_url'   => array( 'type' => 'url', 'required' => false ),
        );

        $validated = CSW_Security::validate_input( $data, $schema );
        if ( is_wp_error( $validated ) ) {
            return new WP_REST_Response( array( 'message' => $validated->get_error_message() ), 400 );
        }

        // Get our price from the product
        $product = wc_get_product( $validated['product_id'] );
        if ( ! $product ) {
            return new WP_REST_Response( array( 'message' => 'Product not found' ), 404 );
        }

        $validated['our_price'] = CSW_Price_Engine::get_product_price( $product );
        $validated['source'] = 'manual';

        $id = CSW_Database::upsert_price( $validated );

        if ( ! $id ) {
            return new WP_REST_Response( array( 'message' => 'Failed to save price' ), 500 );
        }

        // Record price history
        CSW_Database::record_price_history(
            $validated['product_id'],
            $validated['competitor_id'],
            $validated['competitor_price'],
            $validated['our_price']
        );

        // Clear cache
        CSW_Cache::delete_product_comparison( $validated['product_id'] );

        return new WP_REST_Response( array( 'id' => $id, 'message' => 'Price saved successfully' ), 200 );
    }

    /**
     * Delete a price entry.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function delete_price( $request ) {
        $id = absint( $request['id'] );

        $result = CSW_Database::delete_price( $id );

        if ( ! $result ) {
            return new WP_REST_Response( array( 'message' => 'Failed to delete price' ), 500 );
        }

        return new WP_REST_Response( array( 'message' => 'Price deleted successfully' ), 200 );
    }

    /**
     * Get analytics data.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_analytics( $request ) {
        $period = $request->get_param( 'period' ) ?: '30days';
        $stats = CSW_Analytics::get_conversion_stats( $period );

        return new WP_REST_Response( $stats, 200 );
    }

    /**
     * Get settings.
     *
     * @return WP_REST_Response
     */
    public function get_settings() {
        return new WP_REST_Response( CSW_Settings::get_all(), 200 );
    }

    /**
     * Update settings.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function update_settings( $request ) {
        $data = $request->get_json_params();

        foreach ( $data as $key => $value ) {
            $sanitized_value = CSW_Settings::sanitize_setting( $key, $value );
            CSW_Settings::update( $key, $sanitized_value );
        }

        // Flush caches
        CSW_Cache::flush_all();

        return new WP_REST_Response( array(
            'message'  => 'Settings updated successfully',
            'settings' => CSW_Settings::get_all(),
        ), 200 );
    }

    /**
     * Get dashboard data.
     *
     * @return WP_REST_Response
     */
    public function get_dashboard() {
        $data = array(
            'counts'     => CSW_Database::get_dashboard_counts(),
            'stats'      => CSW_Price_Engine::get_overall_stats(),
            'analytics'  => CSW_Analytics::get_conversion_stats( '30days' ),
            'cache_info' => CSW_Cache::get_stats(),
        );

        return new WP_REST_Response( $data, 200 );
    }
}

// Initialize API
new CSW_API();
