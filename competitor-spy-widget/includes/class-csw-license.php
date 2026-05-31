<?php
/**
 * License & Subscription Management.
 *
 * Handles plan tiers, feature gating, trial management,
 * and license key validation for the SaaS model.
 *
 * Plans:
 * - Free: 3 products, manual only, basic widget, no alerts
 * - Pro ($49/month): Unlimited products, auto-fetch, alerts, history, optimization
 *
 * @package CompetitorSpyWidget
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_License
 */
class CSW_License {

    /**
     * Plan constants.
     */
    const PLAN_FREE  = 'free';
    const PLAN_PRO   = 'pro';
    const PLAN_TRIAL = 'trial';

    /**
     * Free plan limits.
     */
    const FREE_PRODUCT_LIMIT     = 3;
    const FREE_COMPETITOR_LIMIT  = 2;
    const FREE_HISTORY_DAYS      = 7;

    /**
     * Trial duration in days.
     */
    const TRIAL_DAYS = 14;

    /**
     * License server URL (change to your actual server).
     */
    const LICENSE_SERVER = 'https://api.competitorspywidget.com/v1';

    /**
     * Get current plan.
     *
     * @return string Plan identifier (free, trial, pro).
     */
    public static function get_plan() {
        // Check for valid pro license
        $license_key = get_option( 'csw_license_key', '' );
        $license_status = get_option( 'csw_license_status', '' );
        $license_expiry = get_option( 'csw_license_expiry', '' );

        if ( ! empty( $license_key ) && 'active' === $license_status ) {
            // Check if not expired
            if ( ! empty( $license_expiry ) && strtotime( $license_expiry ) > time() ) {
                return self::PLAN_PRO;
            }
            // Expired — downgrade
            update_option( 'csw_license_status', 'expired' );
        }

        // Check trial
        $trial_started = get_option( 'csw_trial_started', '' );
        if ( ! empty( $trial_started ) ) {
            $trial_end = strtotime( $trial_started ) + ( self::TRIAL_DAYS * DAY_IN_SECONDS );
            if ( time() < $trial_end ) {
                return self::PLAN_TRIAL;
            }
        }

        return self::PLAN_FREE;
    }

    /**
     * Check if user is on Pro plan (includes trial).
     *
     * @return bool
     */
    public static function is_pro() {
        $plan = self::get_plan();
        return in_array( $plan, array( self::PLAN_PRO, self::PLAN_TRIAL ), true );
    }

    /**
     * Check if user is on free plan.
     *
     * @return bool
     */
    public static function is_free() {
        return self::PLAN_FREE === self::get_plan();
    }

    /**
     * Check if user is in trial period.
     *
     * @return bool
     */
    public static function is_trial() {
        return self::PLAN_TRIAL === self::get_plan();
    }

    /**
     * Get trial days remaining.
     *
     * @return int Days remaining (0 if not in trial).
     */
    public static function get_trial_days_remaining() {
        $trial_started = get_option( 'csw_trial_started', '' );
        if ( empty( $trial_started ) ) {
            return 0;
        }

        $trial_end = strtotime( $trial_started ) + ( self::TRIAL_DAYS * DAY_IN_SECONDS );
        $remaining = $trial_end - time();

        return max( 0, (int) ceil( $remaining / DAY_IN_SECONDS ) );
    }

    /**
     * Start free trial.
     *
     * @return bool
     */
    public static function start_trial() {
        $already_started = get_option( 'csw_trial_started', '' );
        if ( ! empty( $already_started ) ) {
            return false; // Already used trial
        }

        update_option( 'csw_trial_started', current_time( 'mysql' ) );
        return true;
    }

    /**
     * Check if trial has been used.
     *
     * @return bool
     */
    public static function trial_used() {
        return ! empty( get_option( 'csw_trial_started', '' ) );
    }

    /**
     * Check if a specific feature is available on current plan.
     *
     * @param string $feature Feature identifier.
     * @return bool
     */
    public static function can_use( $feature ) {
        if ( self::is_pro() ) {
            return true; // Pro gets everything
        }

        // Free plan features
        $free_features = array(
            'basic_widget',
            'manual_price_entry',
            'basic_analytics',
        );

        return in_array( $feature, $free_features, true );
    }

    /**
     * Get product monitoring limit for current plan.
     *
     * @return int Number of products allowed (-1 for unlimited).
     */
    public static function get_product_limit() {
        if ( self::is_pro() ) {
            return -1; // Unlimited
        }
        return self::FREE_PRODUCT_LIMIT;
    }

    /**
     * Get competitor limit per product for current plan.
     *
     * @return int
     */
    public static function get_competitor_limit() {
        if ( self::is_pro() ) {
            return 10;
        }
        return self::FREE_COMPETITOR_LIMIT;
    }

    /**
     * Get price history retention days.
     *
     * @return int
     */
    public static function get_history_days() {
        if ( self::is_pro() ) {
            return 365;
        }
        return self::FREE_HISTORY_DAYS;
    }

    /**
     * Check if user has reached product monitoring limit.
     *
     * @return bool True if at limit (can't add more).
     */
    public static function at_product_limit() {
        $limit = self::get_product_limit();
        if ( -1 === $limit ) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';
        $current_count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT product_id) FROM {$table} WHERE is_active = 1" // phpcs:ignore
        );

        return $current_count >= $limit;
    }

    /**
     * Get count of monitored products.
     *
     * @return int
     */
    public static function get_monitored_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'csw_prices';
        return (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT product_id) FROM {$table} WHERE is_active = 1" // phpcs:ignore
        );
    }

    /**
     * Activate a license key.
     *
     * @param string $license_key License key to activate.
     * @return array|WP_Error Result or error.
     */
    public static function activate_license( $license_key ) {
        $license_key = sanitize_text_field( trim( $license_key ) );

        if ( empty( $license_key ) ) {
            return new WP_Error( 'empty_key', __( 'Please enter a license key.', 'competitor-spy-widget' ) );
        }

        // Validate format (CSW-XXXX-XXXX-XXXX-XXXX)
        if ( ! preg_match( '/^CSW-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $license_key ) ) {
            return new WP_Error( 'invalid_format', __( 'Invalid license key format. Expected: CSW-XXXX-XXXX-XXXX-XXXX', 'competitor-spy-widget' ) );
        }

        // Call license server for validation
        $response = wp_remote_post( self::LICENSE_SERVER . '/activate', array(
            'timeout' => 15,
            'body'    => array(
                'license_key' => $license_key,
                'site_url'    => home_url(),
                'plugin_ver'  => CSW_VERSION,
            ),
        ) );

        // If can't reach license server, do offline validation
        if ( is_wp_error( $response ) ) {
            // Offline activation — accept key format and activate for 30 days
            return self::offline_activate( $license_key );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $status_code && isset( $body['status'] ) && 'active' === $body['status'] ) {
            update_option( 'csw_license_key', $license_key );
            update_option( 'csw_license_status', 'active' );
            update_option( 'csw_license_expiry', sanitize_text_field( $body['expiry'] ) );
            update_option( 'csw_license_plan', 'pro' );

            return array(
                'status'  => 'active',
                'message' => __( 'License activated successfully! Pro features are now unlocked.', 'competitor-spy-widget' ),
                'expiry'  => $body['expiry'],
            );
        }

        // Server returned error
        $error_msg = isset( $body['message'] ) ? sanitize_text_field( $body['message'] ) : __( 'License validation failed.', 'competitor-spy-widget' );
        return new WP_Error( 'activation_failed', $error_msg );
    }

    /**
     * Offline activation fallback (when license server is unreachable).
     *
     * @param string $license_key License key.
     * @return array
     */
    private static function offline_activate( $license_key ) {
        // Simple offline validation — activate for 30 days, will re-verify on next check
        $expiry = gmdate( 'Y-m-d H:i:s', time() + ( 30 * DAY_IN_SECONDS ) );

        update_option( 'csw_license_key', $license_key );
        update_option( 'csw_license_status', 'active' );
        update_option( 'csw_license_expiry', $expiry );
        update_option( 'csw_license_plan', 'pro' );
        update_option( 'csw_license_offline', true );

        return array(
            'status'  => 'active',
            'message' => __( 'License activated (offline mode). Will verify when connection is available.', 'competitor-spy-widget' ),
            'expiry'  => $expiry,
        );
    }

    /**
     * Deactivate the current license.
     *
     * @return bool
     */
    public static function deactivate_license() {
        $license_key = get_option( 'csw_license_key', '' );

        if ( ! empty( $license_key ) ) {
            // Notify license server
            wp_remote_post( self::LICENSE_SERVER . '/deactivate', array(
                'timeout' => 10,
                'body'    => array(
                    'license_key' => $license_key,
                    'site_url'    => home_url(),
                ),
            ) );
        }

        delete_option( 'csw_license_key' );
        delete_option( 'csw_license_status' );
        delete_option( 'csw_license_expiry' );
        delete_option( 'csw_license_plan' );
        delete_option( 'csw_license_offline' );

        return true;
    }

    /**
     * Get license info for display.
     *
     * @return array
     */
    public static function get_license_info() {
        $plan = self::get_plan();

        return array(
            'plan'            => $plan,
            'plan_label'      => self::get_plan_label( $plan ),
            'is_pro'          => self::is_pro(),
            'is_trial'        => self::is_trial(),
            'trial_remaining' => self::get_trial_days_remaining(),
            'trial_used'      => self::trial_used(),
            'license_key'     => self::mask_license_key( get_option( 'csw_license_key', '' ) ),
            'license_status'  => get_option( 'csw_license_status', '' ),
            'license_expiry'  => get_option( 'csw_license_expiry', '' ),
            'product_limit'   => self::get_product_limit(),
            'products_used'   => self::get_monitored_count(),
            'at_limit'        => self::at_product_limit(),
        );
    }

    /**
     * Get plan display label.
     *
     * @param string $plan Plan ID.
     * @return string
     */
    public static function get_plan_label( $plan ) {
        $labels = array(
            self::PLAN_FREE  => __( 'Free', 'competitor-spy-widget' ),
            self::PLAN_TRIAL => __( 'Pro Trial', 'competitor-spy-widget' ),
            self::PLAN_PRO   => __( 'Pro', 'competitor-spy-widget' ),
        );
        return isset( $labels[ $plan ] ) ? $labels[ $plan ] : $labels[ self::PLAN_FREE ];
    }

    /**
     * Mask license key for display.
     *
     * @param string $key License key.
     * @return string Masked key.
     */
    private static function mask_license_key( $key ) {
        if ( empty( $key ) ) {
            return '';
        }
        // Show first 4 and last 4 chars
        return substr( $key, 0, 8 ) . '••••-••••-' . substr( $key, -4 );
    }

    /**
     * Get feature comparison for pricing page.
     *
     * @return array
     */
    public static function get_feature_comparison() {
        return array(
            array(
                'name'    => __( 'Products Monitored', 'competitor-spy-widget' ),
                'free'    => '3',
                'pro'     => __( 'Unlimited', 'competitor-spy-widget' ),
            ),
            array(
                'name'    => __( 'Competitors per Product', 'competitor-spy-widget' ),
                'free'    => '2',
                'pro'     => '10',
            ),
            array(
                'name'    => __( 'Auto Price Fetching (URL)', 'competitor-spy-widget' ),
                'free'    => false,
                'pro'     => true,
            ),
            array(
                'name'    => __( 'Auto Daily Refresh', 'competitor-spy-widget' ),
                'free'    => false,
                'pro'     => true,
            ),
            array(
                'name'    => __( 'Price History & Charts', 'competitor-spy-widget' ),
                'free'    => __( '7 days', 'competitor-spy-widget' ),
                'pro'     => __( '365 days', 'competitor-spy-widget' ),
            ),
            array(
                'name'    => __( 'Email Price Alerts', 'competitor-spy-widget' ),
                'free'    => false,
                'pro'     => true,
            ),
            array(
                'name'    => __( 'Price Optimization Suggestions', 'competitor-spy-widget' ),
                'free'    => false,
                'pro'     => true,
            ),
            array(
                'name'    => __( 'Dashboard Notifications', 'competitor-spy-widget' ),
                'free'    => false,
                'pro'     => true,
            ),
            array(
                'name'    => __( 'Conversion Analytics', 'competitor-spy-widget' ),
                'free'    => __( 'Basic', 'competitor-spy-widget' ),
                'pro'     => __( 'Advanced', 'competitor-spy-widget' ),
            ),
            array(
                'name'    => __( 'CSV Import/Export', 'competitor-spy-widget' ),
                'free'    => false,
                'pro'     => true,
            ),
            array(
                'name'    => __( 'Priority Support', 'competitor-spy-widget' ),
                'free'    => false,
                'pro'     => true,
            ),
            array(
                'name'    => __( 'Frontend Widget Styles', 'competitor-spy-widget' ),
                'free'    => '1',
                'pro'     => '4',
            ),
        );
    }

    /**
     * Get upgrade URL (Stripe checkout or custom).
     *
     * @return string
     */
    public static function get_upgrade_url() {
        return 'https://competitorspywidget.com/pricing/?site=' . urlencode( home_url() );
    }

    /**
     * Show upgrade notice if feature is locked.
     *
     * @param string $feature Feature name for context.
     * @return string HTML notice.
     */
    public static function get_upgrade_notice( $feature = '' ) {
        if ( self::is_pro() ) {
            return '';
        }

        $message = __( 'This feature requires Pro plan.', 'competitor-spy-widget' );
        if ( ! self::trial_used() ) {
            $message .= ' ' . __( 'Start your free 14-day trial — no credit card required.', 'competitor-spy-widget' );
        }

        return sprintf(
            '<div class="csw-upgrade-notice" style="padding:12px 16px;background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border:1px solid #C7D2FE;border-radius:8px;margin:12px 0;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="font-size:13px;color:#4338CA;font-weight:500;">🔒 %s</span>
                    <a href="%s" class="csw-btn csw-btn-sm" style="background:#4F46E5;color:white;text-decoration:none;padding:4px 12px;border-radius:6px;font-size:12px;">%s</a>
                </div>
            </div>',
            esc_html( $message ),
            esc_url( admin_url( 'admin.php?page=csw-upgrade' ) ),
            self::trial_used() ? esc_html__( 'Upgrade to Pro', 'competitor-spy-widget' ) : esc_html__( 'Start Free Trial', 'competitor-spy-widget' )
        );
    }
}
