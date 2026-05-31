<?php
/**
 * Competitors management view.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$competitors = CSW_Competitor::get_with_stats();
$templates = CSW_Competitor::get_templates();
?>

<div class="csw-admin-wrap">
    <div class="csw-admin-header">
        <div class="csw-header-left">
            <h1 class="csw-page-title"><?php esc_html_e( 'Competitors', 'competitor-spy-widget' ); ?></h1>
            <p class="csw-page-subtitle"><?php esc_html_e( 'Manage the competitors you want to compare prices against.', 'competitor-spy-widget' ); ?></p>
        </div>
        <div class="csw-header-right">
            <button type="button" class="csw-btn csw-btn-primary" id="csw-add-competitor-btn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e( 'Add Competitor', 'competitor-spy-widget' ); ?>
            </button>
        </div>
    </div>

    <!-- Competitors Grid -->
    <div class="csw-competitors-grid" id="csw-competitors-list">
        <?php if ( empty( $competitors ) ) : ?>
        <div class="csw-empty-state">
            <div class="csw-empty-icon">
                <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <h3><?php esc_html_e( 'No Competitors Yet', 'competitor-spy-widget' ); ?></h3>
            <p><?php esc_html_e( 'Add your first competitor to start comparing prices.', 'competitor-spy-widget' ); ?></p>
            <button type="button" class="csw-btn csw-btn-primary" id="csw-add-first-competitor">
                <?php esc_html_e( 'Add Your First Competitor', 'competitor-spy-widget' ); ?>
            </button>
        </div>
        <?php else : ?>
            <?php foreach ( $competitors as $competitor ) : ?>
            <div class="csw-competitor-card" data-id="<?php echo esc_attr( $competitor->id ); ?>">
                <div class="csw-competitor-header">
                    <div class="csw-competitor-info">
                        <?php if ( ! empty( $competitor->logo_url ) ) : ?>
                        <img src="<?php echo esc_url( $competitor->logo_url ); ?>" alt="<?php echo esc_attr( $competitor->name ); ?>" class="csw-competitor-logo-img" />
                        <?php else : ?>
                        <div class="csw-competitor-avatar">
                            <?php echo esc_html( strtoupper( substr( $competitor->name, 0, 2 ) ) ); ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="csw-competitor-name"><?php echo esc_html( $competitor->name ); ?></h4>
                            <a href="<?php echo esc_url( $competitor->website_url ); ?>" target="_blank" class="csw-competitor-url">
                                <?php echo esc_html( wp_parse_url( $competitor->website_url, PHP_URL_HOST ) ); ?>
                            </a>
                        </div>
                    </div>
                    <div class="csw-competitor-toggle">
                        <label class="csw-switch">
                            <input type="checkbox" class="csw-toggle-active" data-id="<?php echo esc_attr( $competitor->id ); ?>" <?php checked( $competitor->is_active, 1 ); ?>>
                            <span class="csw-switch-slider"></span>
                        </label>
                    </div>
                </div>
                <div class="csw-competitor-stats">
                    <div class="csw-comp-stat">
                        <span class="csw-comp-stat-value"><?php echo esc_html( $competitor->total_products ); ?></span>
                        <span class="csw-comp-stat-label"><?php esc_html_e( 'Products', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <div class="csw-comp-stat">
                        <span class="csw-comp-stat-value csw-text-green"><?php echo esc_html( $competitor->winning_count ); ?></span>
                        <span class="csw-comp-stat-label"><?php esc_html_e( 'Winning', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <div class="csw-comp-stat">
                        <span class="csw-comp-stat-value csw-text-red"><?php echo esc_html( $competitor->losing_count ); ?></span>
                        <span class="csw-comp-stat-label"><?php esc_html_e( 'Losing', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <div class="csw-comp-stat">
                        <span class="csw-comp-stat-value"><?php echo esc_html( $competitor->avg_savings ); ?>%</span>
                        <span class="csw-comp-stat-label"><?php esc_html_e( 'Avg Savings', 'competitor-spy-widget' ); ?></span>
                    </div>
                </div>
                <div class="csw-competitor-actions">
                    <button type="button" class="csw-btn csw-btn-sm csw-btn-outline csw-edit-competitor" data-id="<?php echo esc_attr( $competitor->id ); ?>">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <?php esc_html_e( 'Edit', 'competitor-spy-widget' ); ?>
                    </button>
                    <button type="button" class="csw-btn csw-btn-sm csw-btn-danger csw-delete-competitor" data-id="<?php echo esc_attr( $competitor->id ); ?>">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                        </svg>
                        <?php esc_html_e( 'Delete', 'competitor-spy-widget' ); ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Competitor Modal -->
    <div class="csw-modal" id="csw-competitor-modal" style="display:none;">
        <div class="csw-modal-overlay"></div>
        <div class="csw-modal-content">
            <div class="csw-modal-header">
                <h3 id="csw-modal-title"><?php esc_html_e( 'Add Competitor', 'competitor-spy-widget' ); ?></h3>
                <button type="button" class="csw-modal-close">&times;</button>
            </div>
            <form id="csw-competitor-form">
                <div class="csw-modal-body">
                    <!-- Quick Templates -->
                    <div class="csw-form-section" id="csw-templates-section">
                        <label class="csw-form-label"><?php esc_html_e( 'Quick Add from Template', 'competitor-spy-widget' ); ?></label>
                        <div class="csw-template-grid">
                            <?php foreach ( $templates as $key => $template ) :
                                if ( 'custom' === $key ) continue;
                            ?>
                            <button type="button" class="csw-template-btn" data-template="<?php echo esc_attr( $key ); ?>">
                                <img src="<?php echo esc_url( $template['logo_url'] ); ?>" alt="<?php echo esc_attr( $template['name'] ); ?>" width="24" height="24" />
                                <span><?php echo esc_html( $template['name'] ); ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="csw-divider"><span><?php esc_html_e( 'or add custom', 'competitor-spy-widget' ); ?></span></div>
                    </div>

                    <input type="hidden" id="csw-competitor-id" name="competitor_id" value="0" />

                    <div class="csw-form-group">
                        <label for="csw-comp-name" class="csw-form-label"><?php esc_html_e( 'Competitor Name', 'competitor-spy-widget' ); ?> <span class="required">*</span></label>
                        <input type="text" id="csw-comp-name" name="name" class="csw-form-input" placeholder="<?php esc_attr_e( 'e.g., Amazon, Best Buy', 'competitor-spy-widget' ); ?>" required />
                    </div>

                    <div class="csw-form-group">
                        <label for="csw-comp-url" class="csw-form-label"><?php esc_html_e( 'Website URL', 'competitor-spy-widget' ); ?> <span class="required">*</span></label>
                        <input type="url" id="csw-comp-url" name="website_url" class="csw-form-input" placeholder="https://www.example.com" required />
                    </div>

                    <div class="csw-form-group">
                        <label for="csw-comp-logo" class="csw-form-label"><?php esc_html_e( 'Logo URL', 'competitor-spy-widget' ); ?></label>
                        <input type="url" id="csw-comp-logo" name="logo_url" class="csw-form-input" placeholder="https://www.example.com/logo.png" />
                        <p class="csw-form-help"><?php esc_html_e( 'Optional. URL to the competitor\'s logo image.', 'competitor-spy-widget' ); ?></p>
                    </div>

                    <div class="csw-form-row">
                        <div class="csw-form-group csw-form-half">
                            <label for="csw-comp-api-type" class="csw-form-label"><?php esc_html_e( 'Price Source', 'competitor-spy-widget' ); ?></label>
                            <select id="csw-comp-api-type" name="api_type" class="csw-form-select">
                                <option value="manual"><?php esc_html_e( 'Manual Entry', 'competitor-spy-widget' ); ?></option>
                                <option value="api"><?php esc_html_e( 'API Integration', 'competitor-spy-widget' ); ?></option>
                            </select>
                        </div>
                        <div class="csw-form-group csw-form-half">
                            <label for="csw-comp-priority" class="csw-form-label"><?php esc_html_e( 'Display Priority', 'competitor-spy-widget' ); ?></label>
                            <input type="number" id="csw-comp-priority" name="priority" class="csw-form-input" value="0" min="0" max="100" />
                        </div>
                    </div>

                    <div class="csw-api-fields" style="display:none;">
                        <div class="csw-form-group">
                            <label for="csw-comp-api-endpoint" class="csw-form-label"><?php esc_html_e( 'API Endpoint', 'competitor-spy-widget' ); ?></label>
                            <input type="url" id="csw-comp-api-endpoint" name="api_endpoint" class="csw-form-input" placeholder="https://api.example.com/prices" />
                        </div>
                        <div class="csw-form-group">
                            <label for="csw-comp-api-key" class="csw-form-label"><?php esc_html_e( 'API Key', 'competitor-spy-widget' ); ?></label>
                            <input type="password" id="csw-comp-api-key" name="api_key" class="csw-form-input" placeholder="<?php esc_attr_e( 'Enter API key', 'competitor-spy-widget' ); ?>" />
                        </div>
                    </div>
                </div>
                <div class="csw-modal-footer">
                    <button type="button" class="csw-btn csw-btn-outline csw-modal-cancel"><?php esc_html_e( 'Cancel', 'competitor-spy-widget' ); ?></button>
                    <button type="submit" class="csw-btn csw-btn-primary" id="csw-save-competitor-btn">
                        <span class="csw-btn-text"><?php esc_html_e( 'Save Competitor', 'competitor-spy-widget' ); ?></span>
                        <span class="csw-btn-loading" style="display:none;"><?php esc_html_e( 'Saving...', 'competitor-spy-widget' ); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
