<?php
/**
 * Plugin Name: Competitor Spy Widget
 * Plugin URI: https://competitorspywidget.com
 * Description: Real-time price comparison widget for WooCommerce — shows competitor prices on your product pages to boost conversions. Only displays when YOUR price wins.
 * Version: 1.0.0
 * Author: SoldPlu
 * Author URI: https://soldplu.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: competitor-spy-widget
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package CompetitorSpyWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'CSW_VERSION', '1.0.0' );
define( 'CSW_PLUGIN_FILE', __FILE__ );
define( 'CSW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CSW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CSW_DB_VERSION', '1.0.0' );

/**
 * Main plugin class using Singleton pattern.
 *
 * @since 1.0.0
 */
final class Competitor_Spy_Widget {

    /**
     * Single instance of the class.
     *
     * @var Competitor_Spy_Widget|null
     */
    private static $instance = null;

    /**
     * Plugin settings.
     *
     * @var array
     */
    private $settings = array();

    /**
     * Get single instance of the class.
     *
     * @return Competitor_Spy_Widget
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->check_requirements();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Check plugin requirements.
     */
    private function check_requirements() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Core includes
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-database.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-settings.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-competitor.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-price-engine.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-price-fetcher.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-cache.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-widget-display.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-analytics.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-cron.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-security.php';
        require_once CSW_PLUGIN_DIR . 'includes/class-csw-api.php';

        // Admin includes
        if ( is_admin() ) {
            require_once CSW_PLUGIN_DIR . 'admin/class-csw-admin.php';
            require_once CSW_PLUGIN_DIR . 'admin/class-csw-admin-ajax.php';
            require_once CSW_PLUGIN_DIR . 'admin/class-csw-onboarding.php';
        }
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        register_activation_hook( CSW_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( CSW_PLUGIN_FILE, array( $this, 'deactivate' ) );

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
        add_action( 'admin_notices', array( $this, 'check_woocommerce' ) );

        // Initialize widget display after WooCommerce is loaded
        add_action( 'woocommerce_init', array( $this, 'init_widget_display' ) );

        // Fallback: also try on init with low priority if woocommerce_init didn't fire
        add_action( 'wp', array( $this, 'init_widget_display' ) );

        // HPOS compatibility
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
    }

    /**
     * Initialize the frontend widget display.
     */
    public function init_widget_display() {
        if ( class_exists( 'CSW_Widget_Display' ) ) {
            CSW_Widget_Display::init();
        }
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Check WooCommerce
        if ( ! class_exists( 'WooCommerce' ) ) {
            deactivate_plugins( CSW_PLUGIN_BASENAME );
            wp_die(
                esc_html__( 'Competitor Spy Widget requires WooCommerce to be installed and active.', 'competitor-spy-widget' ),
                esc_html__( 'Plugin Activation Error', 'competitor-spy-widget' ),
                array( 'back_link' => true )
            );
        }

        // Create database tables
        CSW_Database::create_tables();

        // Set default options
        $this->set_default_options();

        // Schedule cron events
        CSW_Cron::schedule_events();

        // Set activation flag for onboarding
        update_option( 'csw_activation_redirect', true );

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Clear scheduled events
        CSW_Cron::clear_events();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'competitor-spy-widget',
            false,
            dirname( CSW_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Initialize plugin.
     */
    public function init() {
        $this->settings = CSW_Settings::get_all();
    }

    /**
     * Actions to perform after all plugins are loaded.
     */
    public function on_plugins_loaded() {
        // Check for database updates
        $installed_version = get_option( 'csw_db_version', '0' );
        if ( version_compare( $installed_version, CSW_DB_VERSION, '<' ) ) {
            CSW_Database::create_tables();
            update_option( 'csw_db_version', CSW_DB_VERSION );
        }
    }

    /**
     * Check if WooCommerce is active.
     */
    public function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'Competitor Spy Widget requires WooCommerce to be installed and active.', 'competitor-spy-widget' );
            echo '</p></div>';
        }
    }

    /**
     * Declare HPOS compatibility.
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CSW_PLUGIN_FILE, true );
        }
    }

    /**
     * Set default options on activation.
     */
    private function set_default_options() {
        $defaults = array(
            'csw_enabled'              => '1',
            'csw_widget_position'      => 'after_price',
            'csw_display_mode'         => 'smart',
            'csw_hide_when_expensive'  => '1',
            'csw_widget_style'         => 'modern',
            'csw_color_scheme'         => 'auto',
            'csw_primary_color'        => '#10B981',
            'csw_show_savings'         => '1',
            'csw_show_competitor_logo'  => '1',
            'csw_animation_enabled'    => '1',
            'csw_cache_duration'       => '3600',
            'csw_max_competitors'      => '3',
            'csw_analytics_enabled'    => '1',
            'csw_lazy_load'            => '1',
            'csw_onboarding_complete'  => '0',
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }

    /**
     * Get plugin settings.
     *
     * @return array
     */
    public function get_settings() {
        return $this->settings;
    }
}

/**
 * Returns the main instance of Competitor_Spy_Widget.
 *
 * @return Competitor_Spy_Widget
 */
function CSW() {
    return Competitor_Spy_Widget::get_instance();
}

// Initialize the plugin
CSW();
