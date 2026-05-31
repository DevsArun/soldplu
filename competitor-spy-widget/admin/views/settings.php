<?php
/**
 * Settings view.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = CSW_Settings::get_all();
?>

<div class="csw-admin-wrap">
    <div class="csw-admin-header">
        <div class="csw-header-left">
            <h1 class="csw-page-title"><?php esc_html_e( 'Settings', 'competitor-spy-widget' ); ?></h1>
            <p class="csw-page-subtitle"><?php esc_html_e( 'Configure how the price comparison widget looks and behaves.', 'competitor-spy-widget' ); ?></p>
        </div>
    </div>

    <form id="csw-settings-form">
        <div class="csw-settings-layout">
            <!-- Settings Navigation -->
            <div class="csw-settings-nav">
                <a href="#general" class="csw-nav-item active" data-tab="general">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                    <?php esc_html_e( 'General', 'competitor-spy-widget' ); ?>
                </a>
                <a href="#display" class="csw-nav-item" data-tab="display">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                        <line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                    <?php esc_html_e( 'Display', 'competitor-spy-widget' ); ?>
                </a>
                <a href="#style" class="csw-nav-item" data-tab="style">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="13.5" cy="6.5" r="2.5"/>
                        <circle cx="17.5" cy="10.5" r="2.5"/>
                        <circle cx="8.5" cy="7.5" r="2.5"/>
                        <circle cx="6.5" cy="12.5" r="2.5"/>
                        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 011.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>
                    </svg>
                    <?php esc_html_e( 'Style', 'competitor-spy-widget' ); ?>
                </a>
                <a href="#labels" class="csw-nav-item" data-tab="labels">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 7V4h16v3M9 20h6M12 4v16"/>
                    </svg>
                    <?php esc_html_e( 'Labels', 'competitor-spy-widget' ); ?>
                </a>
                <a href="#performance" class="csw-nav-item" data-tab="performance">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    <?php esc_html_e( 'Performance', 'competitor-spy-widget' ); ?>
                </a>
                <a href="#advanced" class="csw-nav-item" data-tab="advanced">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                    <?php esc_html_e( 'Advanced', 'competitor-spy-widget' ); ?>
                </a>
            </div>

            <!-- Settings Content -->
            <div class="csw-settings-content">
                <!-- General Tab -->
                <div class="csw-settings-tab active" id="csw-tab-general">
                    <div class="csw-card">
                        <div class="csw-card-header">
                            <h3><?php esc_html_e( 'General Settings', 'competitor-spy-widget' ); ?></h3>
                        </div>
                        <div class="csw-card-body">
                            <div class="csw-form-group csw-form-toggle">
                                <div class="csw-toggle-info">
                                    <label class="csw-form-label"><?php esc_html_e( 'Enable Widget', 'competitor-spy-widget' ); ?></label>
                                    <p class="csw-form-help"><?php esc_html_e( 'Show the price comparison widget on product pages.', 'competitor-spy-widget' ); ?></p>
                                </div>
                                <label class="csw-switch">
                                    <input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'], '1' ); ?> />
                                    <span class="csw-switch-slider"></span>
                                </label>
                            </div>

                            <div class="csw-form-group csw-form-toggle">
                                <div class="csw-toggle-info">
                                    <label class="csw-form-label"><?php esc_html_e( 'Smart Display Mode', 'competitor-spy-widget' ); ?></label>
                                    <p class="csw-form-help"><?php esc_html_e( 'Only show widget when your price is lower than competitors. Hides automatically when you\'re more expensive.', 'competitor-spy-widget' ); ?></p>
                                </div>
                                <label class="csw-switch">
                                    <input type="checkbox" name="hide_when_expensive" value="1" <?php checked( $settings['hide_when_expensive'], '1' ); ?> />
                                    <span class="csw-switch-slider"></span>
                                </label>
                            </div>

                            <div class="csw-form-group csw-form-toggle">
                                <div class="csw-toggle-info">
                                    <label class="csw-form-label"><?php esc_html_e( 'Enable Analytics', 'competitor-spy-widget' ); ?></label>
                                    <p class="csw-form-help"><?php esc_html_e( 'Track widget impressions, conversions, and performance metrics.', 'competitor-spy-widget' ); ?></p>
                                </div>
                                <label class="csw-switch">
                                    <input type="checkbox" name="analytics_enabled" value="1" <?php checked( $settings['analytics_enabled'], '1' ); ?> />
                                    <span class="csw-switch-slider"></span>
                                </label>
                            </div>

                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Maximum Competitors Shown', 'competitor-spy-widget' ); ?></label>
                                <select name="max_competitors" class="csw-form-select">
                                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $settings['max_competitors'], $i ); ?>><?php echo esc_html( $i ); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <p class="csw-form-help"><?php esc_html_e( 'How many competitor prices to display at once.', 'competitor-spy-widget' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Display Tab -->
                <div class="csw-settings-tab" id="csw-tab-display">
                    <div class="csw-card">
                        <div class="csw-card-header">
                            <h3><?php esc_html_e( 'Widget Position', 'competitor-spy-widget' ); ?></h3>
                        </div>
                        <div class="csw-card-body">
                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Display Position', 'competitor-spy-widget' ); ?></label>
                                <div class="csw-radio-cards">
                                    <?php
                                    $positions = CSW_Onboarding::get_position_options();
                                    foreach ( $positions as $key => $pos ) :
                                    ?>
                                    <label class="csw-radio-card <?php echo $key === $settings['widget_position'] ? 'active' : ''; ?>">
                                        <input type="radio" name="widget_position" value="<?php echo esc_attr( $key ); ?>" <?php checked( $settings['widget_position'], $key ); ?> />
                                        <div class="csw-radio-card-content">
                                            <span class="csw-radio-card-title"><?php echo esc_html( $pos['label'] ); ?></span>
                                            <span class="csw-radio-card-desc"><?php echo esc_html( $pos['description'] ); ?></span>
                                        </div>
                                        <?php if ( ! empty( $pos['recommended'] ) ) : ?>
                                        <span class="csw-badge-sm csw-badge-green"><?php esc_html_e( 'Recommended', 'competitor-spy-widget' ); ?></span>
                                        <?php endif; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="csw-form-group csw-form-toggle">
                                <div class="csw-toggle-info">
                                    <label class="csw-form-label"><?php esc_html_e( 'Show Savings Amount', 'competitor-spy-widget' ); ?></label>
                                    <p class="csw-form-help"><?php esc_html_e( 'Display how much the customer saves compared to competitors.', 'competitor-spy-widget' ); ?></p>
                                </div>
                                <label class="csw-switch">
                                    <input type="checkbox" name="show_savings" value="1" <?php checked( $settings['show_savings'], '1' ); ?> />
                                    <span class="csw-switch-slider"></span>
                                </label>
                            </div>

                            <div class="csw-form-group csw-form-toggle">
                                <div class="csw-toggle-info">
                                    <label class="csw-form-label"><?php esc_html_e( 'Show Competitor Logos', 'competitor-spy-widget' ); ?></label>
                                    <p class="csw-form-help"><?php esc_html_e( 'Display competitor logos next to their names for better recognition.', 'competitor-spy-widget' ); ?></p>
                                </div>
                                <label class="csw-switch">
                                    <input type="checkbox" name="show_competitor_logo" value="1" <?php checked( $settings['show_competitor_logo'], '1' ); ?> />
                                    <span class="csw-switch-slider"></span>
                                </label>
                            </div>

                            <div class="csw-form-group csw-form-toggle">
                                <div class="csw-toggle-info">
                                    <label class="csw-form-label"><?php esc_html_e( 'Enable Animations', 'competitor-spy-widget' ); ?></label>
                                    <p class="csw-form-help"><?php esc_html_e( 'Smooth entrance animations for the widget.', 'competitor-spy-widget' ); ?></p>
                                </div>
                                <label class="csw-switch">
                                    <input type="checkbox" name="animation_enabled" value="1" <?php checked( $settings['animation_enabled'], '1' ); ?> />
                                    <span class="csw-switch-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Style Tab -->
                <div class="csw-settings-tab" id="csw-tab-style">
                    <div class="csw-card">
                        <div class="csw-card-header">
                            <h3><?php esc_html_e( 'Visual Style', 'competitor-spy-widget' ); ?></h3>
                        </div>
                        <div class="csw-card-body">
                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Widget Style', 'competitor-spy-widget' ); ?></label>
                                <select name="widget_style" class="csw-form-select">
                                    <option value="modern" <?php selected( $settings['widget_style'], 'modern' ); ?>><?php esc_html_e( 'Modern (Recommended)', 'competitor-spy-widget' ); ?></option>
                                    <option value="minimal" <?php selected( $settings['widget_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'competitor-spy-widget' ); ?></option>
                                    <option value="compact" <?php selected( $settings['widget_style'], 'compact' ); ?>><?php esc_html_e( 'Compact', 'competitor-spy-widget' ); ?></option>
                                    <option value="detailed" <?php selected( $settings['widget_style'], 'detailed' ); ?>><?php esc_html_e( 'Detailed', 'competitor-spy-widget' ); ?></option>
                                </select>
                            </div>

                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Color Scheme', 'competitor-spy-widget' ); ?></label>
                                <select name="color_scheme" class="csw-form-select">
                                    <option value="auto" <?php selected( $settings['color_scheme'], 'auto' ); ?>><?php esc_html_e( 'Auto (Match Theme)', 'competitor-spy-widget' ); ?></option>
                                    <option value="light" <?php selected( $settings['color_scheme'], 'light' ); ?>><?php esc_html_e( 'Light', 'competitor-spy-widget' ); ?></option>
                                    <option value="dark" <?php selected( $settings['color_scheme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'competitor-spy-widget' ); ?></option>
                                </select>
                            </div>

                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Primary Color', 'competitor-spy-widget' ); ?></label>
                                <div class="csw-color-picker-wrap">
                                    <input type="color" name="primary_color" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" class="csw-color-input" />
                                    <input type="text" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" class="csw-form-input csw-color-text" readonly />
                                </div>
                                <p class="csw-form-help"><?php esc_html_e( 'Used for the "winning" indicator and badges.', 'competitor-spy-widget' ); ?></p>
                            </div>

                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Custom CSS', 'competitor-spy-widget' ); ?></label>
                                <textarea name="custom_css" class="csw-form-textarea csw-code-editor" rows="6" placeholder="<?php esc_attr_e( '/* Add custom styles here */', 'competitor-spy-widget' ); ?>"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
                                <p class="csw-form-help"><?php esc_html_e( 'Add custom CSS to further customize the widget appearance.', 'competitor-spy-widget' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Labels Tab -->
                <div class="csw-settings-tab" id="csw-tab-labels">
                    <div class="csw-card">
                        <div class="csw-card-header">
                            <h3><?php esc_html_e( 'Text & Labels', 'competitor-spy-widget' ); ?></h3>
                        </div>
                        <div class="csw-card-body">
                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Widget Title', 'competitor-spy-widget' ); ?></label>
                                <input type="text" name="widget_title" class="csw-form-input" value="<?php echo esc_attr( $settings['widget_title'] ); ?>" />
                            </div>
                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Badge Text', 'competitor-spy-widget' ); ?></label>
                                <input type="text" name="badge_text" class="csw-form-input" value="<?php echo esc_attr( $settings['badge_text'] ); ?>" />
                                <p class="csw-form-help"><?php esc_html_e( 'Text shown in the "best price" badge.', 'competitor-spy-widget' ); ?></p>
                            </div>
                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Savings Label', 'competitor-spy-widget' ); ?></label>
                                <input type="text" name="savings_label" class="csw-form-input" value="<?php echo esc_attr( $settings['savings_label'] ); ?>" />
                            </div>
                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Cheaper Label', 'competitor-spy-widget' ); ?></label>
                                <input type="text" name="cheaper_label" class="csw-form-input" value="<?php echo esc_attr( $settings['cheaper_label'] ); ?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Tab -->
                <div class="csw-settings-tab" id="csw-tab-performance">
                    <div class="csw-card">
                        <div class="csw-card-header">
                            <h3><?php esc_html_e( 'Performance & Caching', 'competitor-spy-widget' ); ?></h3>
                        </div>
                        <div class="csw-card-body">
                            <div class="csw-form-group csw-form-toggle">
                                <div class="csw-toggle-info">
                                    <label class="csw-form-label"><?php esc_html_e( 'Lazy Loading', 'competitor-spy-widget' ); ?></label>
                                    <p class="csw-form-help"><?php esc_html_e( 'Load widget data via AJAX after page load. Better for page speed.', 'competitor-spy-widget' ); ?></p>
                                </div>
                                <label class="csw-switch">
                                    <input type="checkbox" name="lazy_load" value="1" <?php checked( $settings['lazy_load'], '1' ); ?> />
                                    <span class="csw-switch-slider"></span>
                                </label>
                            </div>

                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Cache Duration', 'competitor-spy-widget' ); ?></label>
                                <select name="cache_duration" class="csw-form-select">
                                    <option value="1800" <?php selected( $settings['cache_duration'], '1800' ); ?>><?php esc_html_e( '30 Minutes', 'competitor-spy-widget' ); ?></option>
                                    <option value="3600" <?php selected( $settings['cache_duration'], '3600' ); ?>><?php esc_html_e( '1 Hour (Recommended)', 'competitor-spy-widget' ); ?></option>
                                    <option value="7200" <?php selected( $settings['cache_duration'], '7200' ); ?>><?php esc_html_e( '2 Hours', 'competitor-spy-widget' ); ?></option>
                                    <option value="14400" <?php selected( $settings['cache_duration'], '14400' ); ?>><?php esc_html_e( '4 Hours', 'competitor-spy-widget' ); ?></option>
                                    <option value="43200" <?php selected( $settings['cache_duration'], '43200' ); ?>><?php esc_html_e( '12 Hours', 'competitor-spy-widget' ); ?></option>
                                    <option value="86400" <?php selected( $settings['cache_duration'], '86400' ); ?>><?php esc_html_e( '24 Hours', 'competitor-spy-widget' ); ?></option>
                                </select>
                                <p class="csw-form-help"><?php esc_html_e( 'How long to cache comparison data. Lower values mean fresher data but more database queries.', 'competitor-spy-widget' ); ?></p>
                            </div>

                            <div class="csw-form-group">
                                <button type="button" class="csw-btn csw-btn-outline" id="csw-flush-cache-btn">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="1 4 1 10 7 10"/>
                                        <path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
                                    </svg>
                                    <?php esc_html_e( 'Flush Cache Now', 'competitor-spy-widget' ); ?>
                                </button>
                                <p class="csw-form-help"><?php esc_html_e( 'Clear all cached comparison data. New data will be generated on next page load.', 'competitor-spy-widget' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Tab -->
                <div class="csw-settings-tab" id="csw-tab-advanced">
                    <div class="csw-card">
                        <div class="csw-card-header">
                            <h3><?php esc_html_e( 'Advanced Settings', 'competitor-spy-widget' ); ?></h3>
                        </div>
                        <div class="csw-card-body">
                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Excluded Product IDs', 'competitor-spy-widget' ); ?></label>
                                <input type="text" name="excluded_products_text" class="csw-form-input" value="<?php echo esc_attr( is_array( $settings['excluded_products'] ) ? implode( ', ', $settings['excluded_products'] ) : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., 123, 456, 789', 'competitor-spy-widget' ); ?>" />
                                <p class="csw-form-help"><?php esc_html_e( 'Comma-separated product IDs to exclude from showing the widget.', 'competitor-spy-widget' ); ?></p>
                            </div>

                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'Shortcode', 'competitor-spy-widget' ); ?></label>
                                <div class="csw-code-block">
                                    <code>[competitor_spy_widget product_id="123"]</code>
                                </div>
                                <p class="csw-form-help"><?php esc_html_e( 'Use this shortcode to manually place the widget anywhere.', 'competitor-spy-widget' ); ?></p>
                            </div>

                            <div class="csw-form-group">
                                <label class="csw-form-label"><?php esc_html_e( 'System Info', 'competitor-spy-widget' ); ?></label>
                                <div class="csw-system-info">
                                    <div class="csw-info-row">
                                        <span><?php esc_html_e( 'Plugin Version:', 'competitor-spy-widget' ); ?></span>
                                        <span><?php echo esc_html( CSW_VERSION ); ?></span>
                                    </div>
                                    <div class="csw-info-row">
                                        <span><?php esc_html_e( 'DB Version:', 'competitor-spy-widget' ); ?></span>
                                        <span><?php echo esc_html( get_option( 'csw_db_version', 'N/A' ) ); ?></span>
                                    </div>
                                    <div class="csw-info-row">
                                        <span><?php esc_html_e( 'Object Cache:', 'competitor-spy-widget' ); ?></span>
                                        <span><?php echo wp_using_ext_object_cache() ? esc_html__( 'Active', 'competitor-spy-widget' ) : esc_html__( 'Not Active', 'competitor-spy-widget' ); ?></span>
                                    </div>
                                    <div class="csw-info-row">
                                        <span><?php esc_html_e( 'WooCommerce:', 'competitor-spy-widget' ); ?></span>
                                        <span><?php echo defined( 'WC_VERSION' ) ? esc_html( WC_VERSION ) : esc_html__( 'N/A', 'competitor-spy-widget' ); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button (Fixed) -->
                <div class="csw-settings-save">
                    <button type="submit" class="csw-btn csw-btn-primary csw-btn-lg" id="csw-save-settings-btn">
                        <span class="csw-btn-text"><?php esc_html_e( 'Save Settings', 'competitor-spy-widget' ); ?></span>
                        <span class="csw-btn-loading" style="display:none;"><?php esc_html_e( 'Saving...', 'competitor-spy-widget' ); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
