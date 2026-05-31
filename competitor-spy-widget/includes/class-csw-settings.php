<?php
/**
 * Settings management class.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Settings
 */
class CSW_Settings {

    /**
     * Settings cache.
     *
     * @var array|null
     */
    private static $cache = null;

    /**
     * Get all settings.
     *
     * @return array
     */
    public static function get_all() {
        if ( null !== self::$cache ) {
            return self::$cache;
        }

        self::$cache = array(
            'enabled'             => get_option( 'csw_enabled', '1' ),
            'widget_position'     => get_option( 'csw_widget_position', 'after_price' ),
            'display_mode'        => get_option( 'csw_display_mode', 'smart' ),
            'hide_when_expensive' => get_option( 'csw_hide_when_expensive', '1' ),
            'widget_style'        => get_option( 'csw_widget_style', 'modern' ),
            'color_scheme'        => get_option( 'csw_color_scheme', 'auto' ),
            'primary_color'       => get_option( 'csw_primary_color', '#10B981' ),
            'show_savings'        => get_option( 'csw_show_savings', '1' ),
            'show_competitor_logo' => get_option( 'csw_show_competitor_logo', '1' ),
            'animation_enabled'   => get_option( 'csw_animation_enabled', '1' ),
            'cache_duration'      => get_option( 'csw_cache_duration', '3600' ),
            'max_competitors'     => get_option( 'csw_max_competitors', '3' ),
            'analytics_enabled'   => get_option( 'csw_analytics_enabled', '1' ),
            'lazy_load'           => get_option( 'csw_lazy_load', '1' ),
            'onboarding_complete' => get_option( 'csw_onboarding_complete', '0' ),
            'widget_title'        => get_option( 'csw_widget_title', __( 'Price Comparison', 'competitor-spy-widget' ) ),
            'savings_label'       => get_option( 'csw_savings_label', __( 'You save', 'competitor-spy-widget' ) ),
            'cheaper_label'       => get_option( 'csw_cheaper_label', __( 'cheaper here', 'competitor-spy-widget' ) ),
            'badge_text'          => get_option( 'csw_badge_text', __( 'Best Price', 'competitor-spy-widget' ) ),
            'custom_css'          => get_option( 'csw_custom_css', '' ),
            'excluded_products'   => get_option( 'csw_excluded_products', array() ),
            'excluded_categories' => get_option( 'csw_excluded_categories', array() ),
        );

        return self::$cache;
    }

    /**
     * Get a specific setting.
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get( $key, $default = '' ) {
        $settings = self::get_all();
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }

    /**
     * Update a setting.
     *
     * @param string $key   Setting key.
     * @param mixed  $value Setting value.
     * @return bool
     */
    public static function update( $key, $value ) {
        $option_key = 'csw_' . $key;
        $result = update_option( $option_key, $value );

        // Clear cache
        self::$cache = null;

        return $result;
    }

    /**
     * Update multiple settings.
     *
     * @param array $settings Array of key => value pairs.
     * @return bool
     */
    public static function update_bulk( $settings ) {
        $success = true;

        foreach ( $settings as $key => $value ) {
            if ( ! self::update( $key, $value ) ) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if widget is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return '1' === self::get( 'enabled' );
    }

    /**
     * Check if analytics is enabled.
     *
     * @return bool
     */
    public static function is_analytics_enabled() {
        return '1' === self::get( 'analytics_enabled' );
    }

    /**
     * Check if onboarding is complete.
     *
     * @return bool
     */
    public static function is_onboarding_complete() {
        return '1' === self::get( 'onboarding_complete' );
    }

    /**
     * Get widget position hook.
     *
     * @return array Hook name and priority.
     */
    public static function get_widget_hook() {
        $position = self::get( 'widget_position', 'after_price' );

        $hooks = array(
            'before_price'     => array( 'hook' => 'woocommerce_single_product_summary', 'priority' => 9 ),
            'after_price'      => array( 'hook' => 'woocommerce_single_product_summary', 'priority' => 11 ),
            'after_add_to_cart' => array( 'hook' => 'woocommerce_single_product_summary', 'priority' => 31 ),
            'after_meta'       => array( 'hook' => 'woocommerce_single_product_summary', 'priority' => 41 ),
            'after_summary'    => array( 'hook' => 'woocommerce_after_single_product_summary', 'priority' => 5 ),
            'custom'           => array( 'hook' => 'csw_custom_position', 'priority' => 10 ),
        );

        return isset( $hooks[ $position ] ) ? $hooks[ $position ] : $hooks['after_price'];
    }

    /**
     * Check if product is excluded.
     *
     * @param int $product_id Product ID.
     * @return bool
     */
    public static function is_product_excluded( $product_id ) {
        $excluded_products = self::get( 'excluded_products', array() );
        if ( ! is_array( $excluded_products ) ) {
            $excluded_products = array();
        }

        if ( in_array( $product_id, $excluded_products, true ) ) {
            return true;
        }

        // Check category exclusions
        $excluded_categories = self::get( 'excluded_categories', array() );
        if ( ! is_array( $excluded_categories ) ) {
            $excluded_categories = array();
        }

        if ( ! empty( $excluded_categories ) ) {
            $product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $product_categories ) ) {
                $intersect = array_intersect( $product_categories, $excluded_categories );
                if ( ! empty( $intersect ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get sanitization rules for settings.
     *
     * @return array
     */
    public static function get_sanitization_rules() {
        return array(
            'enabled'              => 'boolean',
            'widget_position'      => 'select',
            'display_mode'         => 'select',
            'hide_when_expensive'  => 'boolean',
            'widget_style'         => 'select',
            'color_scheme'         => 'select',
            'primary_color'        => 'color',
            'show_savings'         => 'boolean',
            'show_competitor_logo' => 'boolean',
            'animation_enabled'    => 'boolean',
            'cache_duration'       => 'integer',
            'max_competitors'      => 'integer',
            'analytics_enabled'    => 'boolean',
            'lazy_load'            => 'boolean',
            'widget_title'         => 'text',
            'savings_label'        => 'text',
            'cheaper_label'        => 'text',
            'badge_text'           => 'text',
            'custom_css'           => 'css',
            'excluded_products'    => 'array',
            'excluded_categories'  => 'array',
        );
    }

    /**
     * Sanitize a setting value.
     *
     * @param string $key   Setting key.
     * @param mixed  $value Raw value.
     * @return mixed Sanitized value.
     */
    public static function sanitize_setting( $key, $value ) {
        $rules = self::get_sanitization_rules();
        $rule = isset( $rules[ $key ] ) ? $rules[ $key ] : 'text';

        switch ( $rule ) {
            case 'boolean':
                return $value ? '1' : '0';

            case 'integer':
                return absint( $value );

            case 'color':
                return sanitize_hex_color( $value ) ? sanitize_hex_color( $value ) : '#10B981';

            case 'select':
                return sanitize_text_field( $value );

            case 'css':
                return wp_strip_all_tags( $value );

            case 'array':
                if ( is_array( $value ) ) {
                    return array_map( 'absint', $value );
                }
                return array();

            case 'text':
            default:
                return sanitize_text_field( $value );
        }
    }
}
