<?php
/**
 * Widget display handler.
 *
 * Handles rendering the price comparison widget on product pages.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Widget_Display
 */
class CSW_Widget_Display {

    /**
     * Constructor.
     */
    public function __construct() {
        if ( ! CSW_Settings::is_enabled() ) {
            return;
        }

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        $this->register_widget_hook();
    }

    /**
     * Register the widget display hook based on settings.
     */
    private function register_widget_hook() {
        $hook_config = CSW_Settings::get_widget_hook();
        add_action( $hook_config['hook'], array( $this, 'render_widget' ), $hook_config['priority'] );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        if ( ! is_product() ) {
            return;
        }

        global $post;
        if ( CSW_Settings::is_product_excluded( $post->ID ) ) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'csw-widget',
            CSW_PLUGIN_URL . 'public/assets/css/widget.css',
            array(),
            CSW_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'csw-widget',
            CSW_PLUGIN_URL . 'public/assets/js/widget.js',
            array( 'jquery' ),
            CSW_VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'csw-widget', 'cswWidget', array(
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'csw_widget_nonce' ),
            'productId'  => $post->ID,
            'lazyLoad'   => CSW_Settings::get( 'lazy_load', '1' ),
            'animated'   => CSW_Settings::get( 'animation_enabled', '1' ),
            'analytics'  => CSW_Settings::is_analytics_enabled() ? '1' : '0',
            'i18n'       => array(
                'loading'    => esc_html__( 'Checking prices...', 'competitor-spy-widget' ),
                'savings'    => esc_html( CSW_Settings::get( 'savings_label', __( 'You save', 'competitor-spy-widget' ) ) ),
                'cheaper'    => esc_html( CSW_Settings::get( 'cheaper_label', __( 'cheaper here', 'competitor-spy-widget' ) ) ),
                'bestPrice'  => esc_html( CSW_Settings::get( 'badge_text', __( 'Best Price', 'competitor-spy-widget' ) ) ),
                'verified'   => esc_html__( 'Price verified', 'competitor-spy-widget' ),
            ),
        ) );

        // Inline custom CSS
        $custom_css = CSW_Settings::get( 'custom_css', '' );
        if ( ! empty( $custom_css ) ) {
            wp_add_inline_style( 'csw-widget', wp_strip_all_tags( $custom_css ) );
        }

        // Primary color CSS variable
        $primary_color = CSW_Settings::get( 'primary_color', '#10B981' );
        $color_css = ':root { --csw-primary: ' . esc_attr( $primary_color ) . '; --csw-primary-light: ' . esc_attr( $primary_color ) . '1a; }';
        wp_add_inline_style( 'csw-widget', $color_css );
    }

    /**
     * Render the comparison widget.
     */
    public function render_widget() {
        global $product;

        if ( ! $product ) {
            return;
        }

        $product_id = $product->get_id();

        // Check exclusions
        if ( CSW_Settings::is_product_excluded( $product_id ) ) {
            return;
        }

        // Check if lazy loading
        if ( '1' === CSW_Settings::get( 'lazy_load', '1' ) ) {
            $this->render_placeholder( $product_id );
            return;
        }

        // Get comparison data
        $comparison = CSW_Price_Engine::get_comparison( $product_id );

        if ( ! $comparison || ! $comparison['show_widget'] ) {
            return;
        }

        $this->render_comparison_html( $comparison );
    }

    /**
     * Render lazy-load placeholder.
     *
     * @param int $product_id Product ID.
     */
    private function render_placeholder( $product_id ) {
        ?>
        <div class="csw-widget-container" 
             data-product-id="<?php echo esc_attr( $product_id ); ?>"
             data-lazy="true"
             style="display:none;">
            <div class="csw-widget-skeleton">
                <div class="csw-skeleton-header"></div>
                <div class="csw-skeleton-row"></div>
                <div class="csw-skeleton-row"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render comparison HTML.
     *
     * @param array $comparison Comparison data.
     */
    private function render_comparison_html( $comparison ) {
        $widget_style = CSW_Settings::get( 'widget_style', 'modern' );
        $show_savings = '1' === CSW_Settings::get( 'show_savings', '1' );
        $show_logos   = '1' === CSW_Settings::get( 'show_competitor_logo', '1' );
        $animated     = '1' === CSW_Settings::get( 'animation_enabled', '1' );
        $widget_title = CSW_Settings::get( 'widget_title', __( 'Price Comparison', 'competitor-spy-widget' ) );
        $badge_text   = CSW_Settings::get( 'badge_text', __( 'Best Price', 'competitor-spy-widget' ) );
        $savings_label = CSW_Settings::get( 'savings_label', __( 'You save', 'competitor-spy-widget' ) );

        $widget_classes = array(
            'csw-widget-container',
            'csw-style-' . esc_attr( $widget_style ),
        );

        if ( $animated ) {
            $widget_classes[] = 'csw-animated';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $widget_classes ) ); ?>"
             data-product-id="<?php echo esc_attr( $comparison['product_id'] ); ?>"
             role="complementary"
             aria-label="<?php esc_attr_e( 'Price comparison', 'competitor-spy-widget' ); ?>">

            <!-- Widget Header -->
            <div class="csw-widget-header">
                <div class="csw-widget-title">
                    <svg class="csw-icon-compare" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/>
                    </svg>
                    <span><?php echo esc_html( $widget_title ); ?></span>
                </div>
                <?php if ( $comparison['best_savings'] > 0 ) : ?>
                <div class="csw-badge csw-badge-winning">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span><?php echo esc_html( $badge_text ); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Price Comparisons -->
            <div class="csw-comparisons">
                <!-- Our Price Row -->
                <div class="csw-price-row csw-price-ours csw-winning">
                    <div class="csw-row-left">
                        <div class="csw-store-indicator csw-store-ours"></div>
                        <div class="csw-store-info">
                            <span class="csw-store-name"><?php esc_html_e( 'This Store', 'competitor-spy-widget' ); ?></span>
                            <span class="csw-store-tag"><?php esc_html_e( 'You\'re here', 'competitor-spy-widget' ); ?></span>
                        </div>
                    </div>
                    <div class="csw-row-right">
                        <span class="csw-price csw-price-highlight">
                            <?php echo wp_kses_post( wc_price( $comparison['our_price'] ) ); ?>
                        </span>
                    </div>
                </div>

                <!-- Competitor Price Rows -->
                <?php foreach ( $comparison['competitors'] as $index => $competitor ) : ?>
                <div class="csw-price-row csw-price-competitor" data-competitor="<?php echo esc_attr( $competitor['slug'] ); ?>">
                    <div class="csw-row-left">
                        <?php if ( $show_logos && ! empty( $competitor['logo'] ) ) : ?>
                        <img class="csw-competitor-logo" 
                             src="<?php echo esc_url( $competitor['logo'] ); ?>" 
                             alt="<?php echo esc_attr( $competitor['name'] ); ?>"
                             width="24" height="24"
                             loading="lazy" />
                        <?php else : ?>
                        <div class="csw-store-indicator csw-store-competitor"></div>
                        <?php endif; ?>
                        <div class="csw-store-info">
                            <span class="csw-store-name"><?php echo esc_html( $competitor['name'] ); ?></span>
                            <?php if ( $competitor['is_cheaper'] && $show_savings ) : ?>
                            <span class="csw-savings-tag">
                                <?php
                                printf(
                                    /* translators: %s: savings percentage */
                                    esc_html__( '%s%% more expensive', 'competitor-spy-widget' ),
                                    esc_html( number_format( $competitor['savings_percent'], 0 ) )
                                );
                                ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="csw-row-right">
                        <span class="csw-price csw-price-competitor-value">
                            <?php echo wp_kses_post( wc_price( $competitor['price'] ) ); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Savings Summary -->
            <?php if ( $show_savings && $comparison['best_savings'] > 0 ) : ?>
            <div class="csw-savings-summary">
                <div class="csw-savings-icon">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                </div>
                <div class="csw-savings-text">
                    <span class="csw-savings-label"><?php echo esc_html( $savings_label ); ?></span>
                    <span class="csw-savings-amount">
                        <?php
                        $max_diff = 0;
                        foreach ( $comparison['competitors'] as $comp ) {
                            if ( $comp['price_difference'] > $max_diff ) {
                                $max_diff = $comp['price_difference'];
                            }
                        }
                        printf(
                            /* translators: 1: savings amount, 2: savings percentage */
                            esc_html__( 'up to %1$s (%2$s%%)', 'competitor-spy-widget' ),
                            wp_kses_post( wc_price( $max_diff ) ),
                            esc_html( number_format( $comparison['best_savings'], 0 ) )
                        );
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Trust Footer -->
            <div class="csw-widget-footer">
                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span class="csw-last-checked">
                    <?php esc_html_e( 'Prices verified recently', 'competitor-spy-widget' ); ?>
                </span>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for lazy-loaded widget.
     */
    public static function ajax_get_widget() {
        check_ajax_referer( 'csw_widget_nonce', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

        if ( ! $product_id ) {
            wp_send_json_error( array( 'message' => 'Invalid product ID' ) );
        }

        $comparison = CSW_Price_Engine::get_comparison( $product_id );

        if ( ! $comparison || ! $comparison['show_widget'] ) {
            wp_send_json_success( array(
                'show'   => false,
                'html'   => '',
                'reason' => 'no_data',
            ) );
        }

        // Render to buffer using a static method to avoid constructor side effects
        ob_start();
        self::static_render_comparison_html( $comparison );
        $html = ob_get_clean();

        wp_send_json_success( array(
            'show'       => true,
            'html'       => $html,
            'comparison' => $comparison,
        ) );
    }

    /**
     * Static render helper for AJAX context (avoids constructor re-init).
     *
     * @param array $comparison Comparison data.
     */
    private static function static_render_comparison_html( $comparison ) {
        $widget_style  = CSW_Settings::get( 'widget_style', 'modern' );
        $show_savings  = '1' === CSW_Settings::get( 'show_savings', '1' );
        $show_logos    = '1' === CSW_Settings::get( 'show_competitor_logo', '1' );
        $animated      = '1' === CSW_Settings::get( 'animation_enabled', '1' );
        $widget_title  = CSW_Settings::get( 'widget_title', __( 'Price Comparison', 'competitor-spy-widget' ) );
        $badge_text    = CSW_Settings::get( 'badge_text', __( 'Best Price', 'competitor-spy-widget' ) );
        $savings_label = CSW_Settings::get( 'savings_label', __( 'You save', 'competitor-spy-widget' ) );

        $widget_classes = array(
            'csw-widget-container',
            'csw-style-' . esc_attr( $widget_style ),
        );

        if ( $animated ) {
            $widget_classes[] = 'csw-animated';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $widget_classes ) ); ?>"
             data-product-id="<?php echo esc_attr( $comparison['product_id'] ); ?>"
             role="complementary"
             aria-label="<?php esc_attr_e( 'Price comparison', 'competitor-spy-widget' ); ?>">

            <div class="csw-widget-header">
                <div class="csw-widget-title">
                    <svg class="csw-icon-compare" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/>
                    </svg>
                    <span><?php echo esc_html( $widget_title ); ?></span>
                </div>
                <?php if ( $comparison['best_savings'] > 0 ) : ?>
                <div class="csw-badge csw-badge-winning">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <span><?php echo esc_html( $badge_text ); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="csw-comparisons">
                <div class="csw-price-row csw-price-ours csw-winning">
                    <div class="csw-row-left">
                        <div class="csw-store-indicator csw-store-ours"></div>
                        <div class="csw-store-info">
                            <span class="csw-store-name"><?php esc_html_e( 'This Store', 'competitor-spy-widget' ); ?></span>
                            <span class="csw-store-tag"><?php esc_html_e( 'You\'re here', 'competitor-spy-widget' ); ?></span>
                        </div>
                    </div>
                    <div class="csw-row-right">
                        <span class="csw-price csw-price-highlight">
                            <?php echo wp_kses_post( wc_price( $comparison['our_price'] ) ); ?>
                        </span>
                    </div>
                </div>

                <?php foreach ( $comparison['competitors'] as $competitor ) : ?>
                <div class="csw-price-row csw-price-competitor" data-competitor="<?php echo esc_attr( $competitor['slug'] ); ?>">
                    <div class="csw-row-left">
                        <?php if ( $show_logos && ! empty( $competitor['logo'] ) ) : ?>
                        <img class="csw-competitor-logo" 
                             src="<?php echo esc_url( $competitor['logo'] ); ?>" 
                             alt="<?php echo esc_attr( $competitor['name'] ); ?>"
                             width="24" height="24" loading="lazy" />
                        <?php else : ?>
                        <div class="csw-store-indicator csw-store-competitor"></div>
                        <?php endif; ?>
                        <div class="csw-store-info">
                            <span class="csw-store-name"><?php echo esc_html( $competitor['name'] ); ?></span>
                            <?php if ( $competitor['is_cheaper'] && $show_savings ) : ?>
                            <span class="csw-savings-tag">
                                <?php printf( esc_html__( '%s%% more expensive', 'competitor-spy-widget' ), esc_html( number_format( $competitor['savings_percent'], 0 ) ) ); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="csw-row-right">
                        <span class="csw-price csw-price-competitor-value">
                            <?php echo wp_kses_post( wc_price( $competitor['price'] ) ); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ( $show_savings && $comparison['best_savings'] > 0 ) : ?>
            <div class="csw-savings-summary">
                <div class="csw-savings-icon">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                </div>
                <div class="csw-savings-text">
                    <span class="csw-savings-label"><?php echo esc_html( $savings_label ); ?></span>
                    <span class="csw-savings-amount">
                        <?php
                        $max_diff = 0;
                        foreach ( $comparison['competitors'] as $comp ) {
                            if ( $comp['price_difference'] > $max_diff ) {
                                $max_diff = $comp['price_difference'];
                            }
                        }
                        printf(
                            esc_html__( 'up to %1$s (%2$s%%)', 'competitor-spy-widget' ),
                            wp_kses_post( wc_price( $max_diff ) ),
                            esc_html( number_format( $comparison['best_savings'], 0 ) )
                        );
                        ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <div class="csw-widget-footer">
                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span class="csw-last-checked"><?php esc_html_e( 'Prices verified recently', 'competitor-spy-widget' ); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for tracking analytics events.
     */
    public static function ajax_track_event() {
        check_ajax_referer( 'csw_widget_nonce', 'nonce' );

        if ( ! CSW_Settings::is_analytics_enabled() ) {
            wp_send_json_success();
            return;
        }

        $product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $event_type  = isset( $_POST['event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : '';

        $allowed_events = array( 'widget_view', 'widget_impression', 'widget_hidden', 'conversion', 'competitor_click' );

        if ( ! $product_id || ! in_array( $event_type, $allowed_events, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request' ) );
        }

        // Get anonymized visitor data
        $ip_raw = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $ip_hash = hash( 'sha256', $ip_raw . wp_salt() );

        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $device_type = self::detect_device( $user_agent );

        CSW_Database::record_event( array(
            'product_id'    => $product_id,
            'event_type'    => $event_type,
            'competitor_id' => isset( $_POST['competitor_id'] ) ? absint( $_POST['competitor_id'] ) : null,
            'session_id'    => isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '',
            'user_agent'    => $user_agent,
            'ip_hash'       => $ip_hash,
            'device_type'   => $device_type,
            'referrer'      => isset( $_POST['referrer'] ) ? esc_url_raw( wp_unslash( $_POST['referrer'] ) ) : '',
        ) );

        wp_send_json_success();
    }

    /**
     * Detect device type from user agent.
     *
     * @param string $user_agent User agent string.
     * @return string Device type.
     */
    private static function detect_device( $user_agent ) {
        $user_agent = strtolower( $user_agent );

        if ( preg_match( '/mobile|android.*mobile|iphone|ipod/', $user_agent ) ) {
            return 'mobile';
        }

        if ( preg_match( '/tablet|ipad|android(?!.*mobile)/', $user_agent ) ) {
            return 'tablet';
        }

        return 'desktop';
    }
}

// Register AJAX handlers (always available for both admin and frontend AJAX)
add_action( 'wp_ajax_csw_get_widget', array( 'CSW_Widget_Display', 'ajax_get_widget' ) );
add_action( 'wp_ajax_nopriv_csw_get_widget', array( 'CSW_Widget_Display', 'ajax_get_widget' ) );
add_action( 'wp_ajax_csw_track_event', array( 'CSW_Widget_Display', 'ajax_track_event' ) );
add_action( 'wp_ajax_nopriv_csw_track_event', array( 'CSW_Widget_Display', 'ajax_track_event' ) );

// Initialize widget display on frontend via wp_loaded to ensure WooCommerce is ready
add_action( 'wp_loaded', function() {
    if ( ! is_admin() || wp_doing_ajax() ) {
        new CSW_Widget_Display();
    }
} );
