<?php
/**
 * Admin functionality.
 *
 * Handles admin menus, pages, and asset loading.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Admin
 */
class CSW_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'handle_activation_redirect' ) );
        add_filter( 'plugin_action_links_' . CSW_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );

        // WooCommerce product data hooks
        add_action( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'product_data_panel' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );

        // Listen for product price changes
        add_action( 'woocommerce_product_set_regular_price', array( $this, 'on_price_change' ), 10, 2 );
        add_action( 'woocommerce_product_set_sale_price', array( $this, 'on_price_change' ), 10, 2 );
    }

    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __( 'Competitor Spy', 'competitor-spy-widget' ),
            __( 'Competitor Spy', 'competitor-spy-widget' ),
            'manage_woocommerce',
            'competitor-spy-widget',
            array( $this, 'render_dashboard_page' ),
            'data:image/svg+xml;base64,' . base64_encode( '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5" stroke="#a7aaad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' ), // phpcs:ignore
            56
        );

        // Dashboard
        add_submenu_page(
            'competitor-spy-widget',
            __( 'Dashboard', 'competitor-spy-widget' ),
            __( 'Dashboard', 'competitor-spy-widget' ),
            'manage_woocommerce',
            'competitor-spy-widget',
            array( $this, 'render_dashboard_page' )
        );

        // Competitors
        add_submenu_page(
            'competitor-spy-widget',
            __( 'Competitors', 'competitor-spy-widget' ),
            __( 'Competitors', 'competitor-spy-widget' ),
            'manage_woocommerce',
            'csw-competitors',
            array( $this, 'render_competitors_page' )
        );

        // Prices
        add_submenu_page(
            'competitor-spy-widget',
            __( 'Prices', 'competitor-spy-widget' ),
            __( 'Prices', 'competitor-spy-widget' ),
            'manage_woocommerce',
            'csw-prices',
            array( $this, 'render_prices_page' )
        );

        // Analytics
        add_submenu_page(
            'competitor-spy-widget',
            __( 'Analytics', 'competitor-spy-widget' ),
            __( 'Analytics', 'competitor-spy-widget' ),
            'manage_woocommerce',
            'csw-analytics',
            array( $this, 'render_analytics_page' )
        );

        // Settings
        add_submenu_page(
            'competitor-spy-widget',
            __( 'Settings', 'competitor-spy-widget' ),
            __( 'Settings', 'competitor-spy-widget' ),
            'manage_options',
            'csw-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Page hook.
     */
    public function enqueue_assets( $hook ) {
        // Only load on our pages
        $our_pages = array(
            'toplevel_page_competitor-spy-widget',
            'competitor-spy_page_csw-competitors',
            'competitor-spy_page_csw-prices',
            'competitor-spy_page_csw-analytics',
            'competitor-spy_page_csw-settings',
        );

        $is_our_page = in_array( $hook, $our_pages, true );
        $is_product_edit = in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'product' === get_post_type();

        if ( ! $is_our_page && ! $is_product_edit ) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'csw-admin',
            CSW_PLUGIN_URL . 'admin/assets/css/admin.css',
            array(),
            CSW_VERSION
        );

        // Chart library
        if ( $is_our_page ) {
            wp_enqueue_script(
                'csw-chart',
                CSW_PLUGIN_URL . 'admin/assets/js/chart.min.js',
                array(),
                '4.4.0',
                true
            );
        }

        // Admin JS
        wp_enqueue_script(
            'csw-admin',
            CSW_PLUGIN_URL . 'admin/assets/js/admin.js',
            array( 'jquery', 'wp-util' ),
            CSW_VERSION,
            true
        );

        // Localize
        wp_localize_script( 'csw-admin', 'cswAdmin', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'restUrl'    => rest_url( 'csw/v1/' ),
            'nonce'      => wp_create_nonce( 'csw_admin_nonce' ),
            'restNonce'  => wp_create_nonce( 'wp_rest' ),
            'pluginUrl'  => CSW_PLUGIN_URL,
            'currentPage' => isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
            'i18n'       => array(
                'confirm_delete'  => esc_html__( 'Are you sure you want to delete this?', 'competitor-spy-widget' ),
                'saving'          => esc_html__( 'Saving...', 'competitor-spy-widget' ),
                'saved'           => esc_html__( 'Saved successfully!', 'competitor-spy-widget' ),
                'error'           => esc_html__( 'An error occurred. Please try again.', 'competitor-spy-widget' ),
                'loading'         => esc_html__( 'Loading...', 'competitor-spy-widget' ),
                'no_results'      => esc_html__( 'No results found.', 'competitor-spy-widget' ),
                'search_products' => esc_html__( 'Search products...', 'competitor-spy-widget' ),
            ),
        ) );

        // WooCommerce select2 for product search
        if ( $is_our_page ) {
            wp_enqueue_script( 'wc-enhanced-select' );
            wp_enqueue_style( 'woocommerce_admin_styles' );
        }
    }

    /**
     * Handle activation redirect to onboarding.
     */
    public function handle_activation_redirect() {
        if ( get_option( 'csw_activation_redirect', false ) ) {
            delete_option( 'csw_activation_redirect' );

            if ( ! CSW_Settings::is_onboarding_complete() ) {
                wp_safe_redirect( admin_url( 'admin.php?page=competitor-spy-widget&onboarding=1' ) );
                exit;
            }
        }
    }

    /**
     * Add plugin action links.
     *
     * @param array $links Existing links.
     * @return array
     */
    public function plugin_action_links( $links ) {
        $custom_links = array(
            '<a href="' . admin_url( 'admin.php?page=csw-settings' ) . '">' . esc_html__( 'Settings', 'competitor-spy-widget' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=competitor-spy-widget' ) . '">' . esc_html__( 'Dashboard', 'competitor-spy-widget' ) . '</a>',
        );

        return array_merge( $custom_links, $links );
    }

    /**
     * Render dashboard page.
     */
    public function render_dashboard_page() {
        // Check for onboarding
        $onboarding = isset( $_GET['onboarding'] ) && '1' === $_GET['onboarding']; // phpcs:ignore WordPress.Security.NonceVerification
        if ( $onboarding && ! CSW_Settings::is_onboarding_complete() ) {
            include CSW_PLUGIN_DIR . 'admin/views/onboarding.php';
            return;
        }

        include CSW_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render competitors page.
     */
    public function render_competitors_page() {
        include CSW_PLUGIN_DIR . 'admin/views/competitors.php';
    }

    /**
     * Render prices page.
     */
    public function render_prices_page() {
        include CSW_PLUGIN_DIR . 'admin/views/prices.php';
    }

    /**
     * Render analytics page.
     */
    public function render_analytics_page() {
        include CSW_PLUGIN_DIR . 'admin/views/analytics.php';
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        include CSW_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Add product data tab for competitor prices.
     *
     * @param array $tabs Existing tabs.
     * @return array
     */
    public function add_product_data_tab( $tabs ) {
        $tabs['csw_prices'] = array(
            'label'    => __( 'Competitor Prices', 'competitor-spy-widget' ),
            'target'   => 'csw_prices_panel',
            'class'    => array( 'show_if_simple', 'show_if_variable', 'show_if_external' ),
            'priority' => 80,
        );

        return $tabs;
    }

    /**
     * Render product data panel.
     */
    public function product_data_panel() {
        global $post;
        $product_id = $post->ID;
        $prices = CSW_Database::get_product_prices( $product_id );

        include CSW_PLUGIN_DIR . 'admin/views/product-panel.php';
    }

    /**
     * Save product data — new simplified approach.
     * User just types competitor name + price. We auto-create competitors.
     *
     * @param int $product_id Product ID.
     */
    public function save_product_data( $product_id ) {
        if ( ! isset( $_POST['csw_prices_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['csw_prices_nonce'] ) ), 'csw_save_product_prices' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_product', $product_id ) ) {
            return;
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        $our_price = CSW_Price_Engine::get_product_price( $product );

        // First, deactivate all existing prices for this product (we'll re-add active ones)
        global $wpdb;
        $prices_table = $wpdb->prefix . 'csw_prices';
        $wpdb->update(
            $prices_table,
            array( 'is_active' => 0 ),
            array( 'product_id' => $product_id ),
            array( '%d' ),
            array( '%d' )
        );

        // Process new price entries
        if ( isset( $_POST['csw_prices'] ) && is_array( $_POST['csw_prices'] ) ) {
            $entries = wp_unslash( $_POST['csw_prices'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

            foreach ( $entries as $entry ) {
                $comp_name  = isset( $entry['name'] ) ? sanitize_text_field( $entry['name'] ) : '';
                $comp_price = isset( $entry['price'] ) ? floatval( $entry['price'] ) : 0;
                $comp_url   = isset( $entry['url'] ) ? esc_url_raw( $entry['url'] ) : '';

                // Skip empty rows
                if ( empty( $comp_name ) || $comp_price <= 0 ) {
                    continue;
                }

                // Find or create the competitor
                $competitor_id = $this->find_or_create_competitor( $comp_name );

                if ( ! $competitor_id ) {
                    continue;
                }

                // Save the price
                CSW_Database::upsert_price( array(
                    'product_id'       => $product_id,
                    'competitor_id'    => $competitor_id,
                    'competitor_price' => $comp_price,
                    'our_price'        => $our_price,
                    'competitor_url'   => $comp_url,
                    'source'           => 'manual',
                ) );

                // Record history
                CSW_Database::record_price_history( $product_id, $competitor_id, $comp_price, $our_price );
            }
        }

        // Clear cache
        CSW_Cache::delete_product_comparison( $product_id );
    }

    /**
     * Find competitor by name or auto-create if not exists.
     *
     * @param string $name Competitor name.
     * @return int|false Competitor ID.
     */
    private function find_or_create_competitor( $name ) {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_competitors';

        // Try to find existing competitor by name (case-insensitive)
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE LOWER(name) = LOWER(%s) LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $name
            )
        );

        if ( $existing ) {
            return (int) $existing;
        }

        // Auto-create competitor
        $slug = sanitize_title( $name );

        // Check if we have a built-in logo for known competitors
        $logo_url = '';
        $known_logos = array(
            'amazon'    => CSW_PLUGIN_URL . 'public/assets/images/competitors/amazon.svg',
            'walmart'   => CSW_PLUGIN_URL . 'public/assets/images/competitors/walmart.svg',
            'ebay'      => CSW_PLUGIN_URL . 'public/assets/images/competitors/ebay.svg',
            'target'    => CSW_PLUGIN_URL . 'public/assets/images/competitors/target.svg',
            'best-buy'  => CSW_PLUGIN_URL . 'public/assets/images/competitors/bestbuy.svg',
            'bestbuy'   => CSW_PLUGIN_URL . 'public/assets/images/competitors/bestbuy.svg',
        );

        if ( isset( $known_logos[ $slug ] ) ) {
            $logo_url = $known_logos[ $slug ];
        }

        $id = CSW_Database::insert_competitor( array(
            'name'        => $name,
            'slug'        => $slug,
            'website_url' => '',
            'logo_url'    => $logo_url,
            'api_type'    => 'manual',
            'is_active'   => 1,
            'priority'    => 0,
        ) );

        return $id ? $id : false;
    }

    /**
     * Handle product price change.
     *
     * @param string     $price   New price.
     * @param WC_Product $product Product object.
     */
    public function on_price_change( $price, $product ) {
        if ( $product && $product->get_id() ) {
            // Use WordPress built-in scheduled event instead of Action Scheduler
            wp_schedule_single_event(
                time() + 5,
                'csw_recalculate_product_prices',
                array( $product->get_id() )
            );
        }
    }
}

// Handle scheduled price recalculation
add_action( 'csw_recalculate_product_prices', function ( $product_id ) {
    if ( class_exists( 'CSW_Price_Engine' ) ) {
        CSW_Price_Engine::on_product_price_change( $product_id );
    }
} );

// Initialize admin
new CSW_Admin();
