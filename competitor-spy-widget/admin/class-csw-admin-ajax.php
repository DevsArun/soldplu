<?php
/**
 * Admin AJAX handlers.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Admin_Ajax
 */
class CSW_Admin_Ajax {

    /**
     * Constructor.
     */
    public function __construct() {
        // Competitor actions
        add_action( 'wp_ajax_csw_save_competitor', array( $this, 'save_competitor' ) );
        add_action( 'wp_ajax_csw_delete_competitor', array( $this, 'delete_competitor' ) );
        add_action( 'wp_ajax_csw_toggle_competitor', array( $this, 'toggle_competitor' ) );

        // Price actions
        add_action( 'wp_ajax_csw_save_price', array( $this, 'save_price' ) );
        add_action( 'wp_ajax_csw_delete_price', array( $this, 'delete_price' ) );
        add_action( 'wp_ajax_csw_bulk_import_prices', array( $this, 'bulk_import_prices' ) );

        // Settings actions
        add_action( 'wp_ajax_csw_save_settings', array( $this, 'save_settings' ) );
        add_action( 'wp_ajax_csw_flush_cache', array( $this, 'flush_cache' ) );
        add_action( 'wp_ajax_csw_export_analytics', array( $this, 'export_analytics' ) );

        // Onboarding
        add_action( 'wp_ajax_csw_complete_onboarding', array( $this, 'complete_onboarding' ) );

        // Search
        add_action( 'wp_ajax_csw_search_products', array( $this, 'search_products' ) );

        // Dashboard
        add_action( 'wp_ajax_csw_get_dashboard_data', array( $this, 'get_dashboard_data' ) );
        add_action( 'wp_ajax_csw_get_analytics_data', array( $this, 'get_analytics_data' ) );
    }

    /**
     * Save competitor.
     */
    public function save_competitor() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_competitors' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        $data = array(
            'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
            'website_url'  => isset( $_POST['website_url'] ) ? esc_url_raw( wp_unslash( $_POST['website_url'] ) ) : '',
            'logo_url'     => isset( $_POST['logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['logo_url'] ) ) : '',
            'api_type'     => isset( $_POST['api_type'] ) ? sanitize_text_field( wp_unslash( $_POST['api_type'] ) ) : 'manual',
            'api_key'      => isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '',
            'api_endpoint' => isset( $_POST['api_endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['api_endpoint'] ) ) : '',
            'is_active'    => isset( $_POST['is_active'] ) ? absint( $_POST['is_active'] ) : 1,
            'priority'     => isset( $_POST['priority'] ) ? absint( $_POST['priority'] ) : 0,
        );

        $validated = CSW_Competitor::validate( $data );
        if ( is_wp_error( $validated ) ) {
            wp_send_json_error( array( 'message' => $validated->get_error_message() ) );
        }

        $competitor_id = isset( $_POST['competitor_id'] ) ? absint( $_POST['competitor_id'] ) : 0;

        if ( $competitor_id ) {
            // Update
            $result = CSW_Database::update_competitor( $competitor_id, $validated );
            if ( ! $result ) {
                wp_send_json_error( array( 'message' => __( 'Failed to update competitor.', 'competitor-spy-widget' ) ) );
            }
            $competitor = CSW_Database::get_competitor( $competitor_id );
        } else {
            // Create
            $id = CSW_Database::insert_competitor( $validated );
            if ( ! $id ) {
                wp_send_json_error( array( 'message' => __( 'Failed to create competitor.', 'competitor-spy-widget' ) ) );
            }
            $competitor = CSW_Database::get_competitor( $id );
        }

        wp_send_json_success( array(
            'message'    => __( 'Competitor saved successfully.', 'competitor-spy-widget' ),
            'competitor' => $competitor,
        ) );
    }

    /**
     * Delete competitor.
     */
    public function delete_competitor() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_competitors' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        $id = isset( $_POST['competitor_id'] ) ? absint( $_POST['competitor_id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid competitor ID.', 'competitor-spy-widget' ) ) );
        }

        $result = CSW_Database::delete_competitor( $id );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => __( 'Failed to delete competitor.', 'competitor-spy-widget' ) ) );
        }

        // Flush related caches
        CSW_Cache::flush_all();

        wp_send_json_success( array( 'message' => __( 'Competitor deleted successfully.', 'competitor-spy-widget' ) ) );
    }

    /**
     * Toggle competitor active status.
     */
    public function toggle_competitor() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_competitors' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        $id = isset( $_POST['competitor_id'] ) ? absint( $_POST['competitor_id'] ) : 0;
        $active = isset( $_POST['is_active'] ) ? absint( $_POST['is_active'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid competitor ID.', 'competitor-spy-widget' ) ) );
        }

        $result = CSW_Database::update_competitor( $id, array( 'is_active' => $active ) );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => __( 'Failed to update competitor.', 'competitor-spy-widget' ) ) );
        }

        CSW_Cache::flush_all();

        wp_send_json_success( array( 'message' => __( 'Competitor updated.', 'competitor-spy-widget' ) ) );
    }

    /**
     * Save price entry.
     */
    public function save_price() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_prices' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        $product_id       = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $competitor_id    = isset( $_POST['competitor_id'] ) ? absint( $_POST['competitor_id'] ) : 0;
        $competitor_price = isset( $_POST['competitor_price'] ) ? floatval( $_POST['competitor_price'] ) : 0;
        $competitor_url   = isset( $_POST['competitor_url'] ) ? esc_url_raw( wp_unslash( $_POST['competitor_url'] ) ) : '';

        if ( ! $product_id || ! $competitor_id || $competitor_price <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required fields.', 'competitor-spy-widget' ) ) );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( array( 'message' => __( 'Product not found.', 'competitor-spy-widget' ) ) );
        }

        $our_price = CSW_Price_Engine::get_product_price( $product );

        $id = CSW_Database::upsert_price( array(
            'product_id'       => $product_id,
            'competitor_id'    => $competitor_id,
            'competitor_price' => $competitor_price,
            'our_price'        => $our_price,
            'competitor_url'   => $competitor_url,
            'source'           => 'manual',
        ) );

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Failed to save price.', 'competitor-spy-widget' ) ) );
        }

        // Record history
        CSW_Database::record_price_history( $product_id, $competitor_id, $competitor_price, $our_price );

        // Clear cache
        CSW_Cache::delete_product_comparison( $product_id );

        wp_send_json_success( array(
            'message' => __( 'Price saved successfully.', 'competitor-spy-widget' ),
            'data'    => array(
                'id'               => $id,
                'is_cheaper'       => $our_price < $competitor_price,
                'savings_percent'  => $competitor_price > 0 ? round( ( ( $competitor_price - $our_price ) / $competitor_price ) * 100, 2 ) : 0,
                'price_difference' => $competitor_price - $our_price,
            ),
        ) );
    }

    /**
     * Delete price entry.
     */
    public function delete_price() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_prices' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        $id = isset( $_POST['price_id'] ) ? absint( $_POST['price_id'] ) : 0;

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid price ID.', 'competitor-spy-widget' ) ) );
        }

        $result = CSW_Database::delete_price( $id );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => __( 'Failed to delete price.', 'competitor-spy-widget' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'Price deleted successfully.', 'competitor-spy-widget' ) ) );
    }

    /**
     * Bulk import prices from CSV.
     */
    public function bulk_import_prices() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_prices' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        if ( ! isset( $_FILES['csv_file'] ) ) {
            wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'competitor-spy-widget' ) ) );
        }

        $file = $_FILES['csv_file'];

        // Validate file type
        $allowed_types = array( 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel' );
        if ( ! in_array( $file['type'], $allowed_types, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid file type. Please upload a CSV file.', 'competitor-spy-widget' ) ) );
        }

        $handle = fopen( $file['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        if ( ! $handle ) {
            wp_send_json_error( array( 'message' => __( 'Could not read the file.', 'competitor-spy-widget' ) ) );
        }

        $imported = 0;
        $errors = 0;
        $row = 0;

        while ( ( $data = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
            $row++;

            // Skip header row
            if ( 1 === $row ) {
                continue;
            }

            // Expected format: product_id, competitor_id, competitor_price, competitor_url
            if ( count( $data ) < 3 ) {
                $errors++;
                continue;
            }

            $product_id       = absint( $data[0] );
            $competitor_id    = absint( $data[1] );
            $competitor_price = floatval( $data[2] );
            $competitor_url   = isset( $data[3] ) ? esc_url_raw( trim( $data[3] ) ) : '';

            if ( ! $product_id || ! $competitor_id || $competitor_price <= 0 ) {
                $errors++;
                continue;
            }

            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                $errors++;
                continue;
            }

            $our_price = CSW_Price_Engine::get_product_price( $product );

            $result = CSW_Database::upsert_price( array(
                'product_id'       => $product_id,
                'competitor_id'    => $competitor_id,
                'competitor_price' => $competitor_price,
                'our_price'        => $our_price,
                'competitor_url'   => $competitor_url,
                'source'           => 'csv_import',
            ) );

            if ( $result ) {
                $imported++;
                CSW_Cache::delete_product_comparison( $product_id );
            } else {
                $errors++;
            }
        }

        fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: 1: imported count, 2: error count */
                __( 'Import complete: %1$d prices imported, %2$d errors.', 'competitor-spy-widget' ),
                $imported,
                $errors
            ),
            'imported' => $imported,
            'errors'   => $errors,
        ) );
    }

    /**
     * Save settings.
     */
    public function save_settings() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_settings' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        $settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if ( ! is_array( $settings ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid settings data.', 'competitor-spy-widget' ) ) );
        }

        foreach ( $settings as $key => $value ) {
            $sanitized_key = sanitize_text_field( $key );
            $sanitized_value = CSW_Settings::sanitize_setting( $sanitized_key, $value );
            CSW_Settings::update( $sanitized_key, $sanitized_value );
        }

        CSW_Cache::flush_all();

        wp_send_json_success( array(
            'message'  => __( 'Settings saved successfully.', 'competitor-spy-widget' ),
            'settings' => CSW_Settings::get_all(),
        ) );
    }

    /**
     * Flush all caches.
     */
    public function flush_cache() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_settings' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        CSW_Cache::flush_all();

        wp_send_json_success( array( 'message' => __( 'Cache cleared successfully.', 'competitor-spy-widget' ) ) );
    }

    /**
     * Export analytics as CSV.
     */
    public function export_analytics() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'export_data' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        $period = isset( $_POST['period'] ) ? sanitize_text_field( wp_unslash( $_POST['period'] ) ) : '30days';
        $csv = CSW_Analytics::export_csv( $period );

        wp_send_json_success( array(
            'csv'      => $csv,
            'filename' => 'csw-analytics-' . gmdate( 'Y-m-d' ) . '.csv',
        ) );
    }

    /**
     * Complete onboarding.
     */
    public function complete_onboarding() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! CSW_Security::can( 'manage_settings' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'competitor-spy-widget' ) ) );
        }

        CSW_Settings::update( 'onboarding_complete', '1' );

        // Create competitors if provided
        if ( isset( $_POST['competitors'] ) && is_array( $_POST['competitors'] ) ) {
            foreach ( $_POST['competitors'] as $comp_data ) {
                $template = isset( $comp_data['template'] ) ? sanitize_text_field( $comp_data['template'] ) : 'custom';
                $name = isset( $comp_data['name'] ) ? sanitize_text_field( wp_unslash( $comp_data['name'] ) ) : '';

                if ( 'custom' === $template && ! empty( $name ) ) {
                    CSW_Database::insert_competitor( array(
                        'name'        => $name,
                        'website_url' => isset( $comp_data['url'] ) ? esc_url_raw( wp_unslash( $comp_data['url'] ) ) : '',
                    ) );
                } else {
                    CSW_Competitor::create_from_template( $template );
                }
            }
        }

        wp_send_json_success( array(
            'message'  => __( 'Setup complete! Welcome to Competitor Spy Widget.', 'competitor-spy-widget' ),
            'redirect' => admin_url( 'admin.php?page=competitor-spy-widget' ),
        ) );
    }

    /**
     * Search WooCommerce products.
     */
    public function search_products() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error();
        }

        $search = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

        if ( empty( $search ) ) {
            wp_send_json( array() );
        }

        $products = wc_get_products( array(
            'status' => 'publish',
            's'      => $search,
            'limit'  => 20,
        ) );

        $results = array();
        foreach ( $products as $product ) {
            $results[] = array(
                'id'    => $product->get_id(),
                'text'  => $product->get_name() . ' (#' . $product->get_id() . ') - ' . wc_price( $product->get_price() ),
                'price' => $product->get_price(),
            );
        }

        wp_send_json( $results );
    }

    /**
     * Get dashboard data via AJAX.
     */
    public function get_dashboard_data() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error();
        }

        $data = array(
            'counts'    => CSW_Database::get_dashboard_counts(),
            'stats'     => CSW_Price_Engine::get_overall_stats(),
            'analytics' => CSW_Analytics::get_conversion_stats( '30days' ),
        );

        wp_send_json_success( $data );
    }

    /**
     * Get analytics data via AJAX.
     */
    public function get_analytics_data() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error();
        }

        $period = isset( $_POST['period'] ) ? sanitize_text_field( wp_unslash( $_POST['period'] ) ) : '30days';

        $data = array(
            'conversion_stats' => CSW_Analytics::get_conversion_stats( $period ),
            'performance'      => CSW_Analytics::get_widget_performance(),
        );

        wp_send_json_success( $data );
    }
}

// Initialize admin AJAX
new CSW_Admin_Ajax();
