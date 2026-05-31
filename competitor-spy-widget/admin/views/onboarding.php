<?php
/**
 * Onboarding wizard view.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$steps = CSW_Onboarding::get_steps();
$competitor_options = CSW_Onboarding::get_competitor_options();
$position_options = CSW_Onboarding::get_position_options();
?>

<div class="csw-onboarding-wrap">
    <div class="csw-onboarding-container">
        <!-- Progress Bar -->
        <div class="csw-onboarding-progress">
            <div class="csw-progress-bar">
                <div class="csw-progress-fill" style="width: 25%;"></div>
            </div>
            <div class="csw-progress-steps">
                <?php foreach ( $steps as $index => $step ) : ?>
                <div class="csw-progress-step <?php echo 0 === $index ? 'active' : ''; ?>" data-step="<?php echo esc_attr( $index ); ?>">
                    <div class="csw-step-dot"></div>
                    <span class="csw-step-label"><?php echo esc_html( $step['title'] ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Step 1: Welcome -->
        <div class="csw-onboarding-step active" data-step="0">
            <div class="csw-step-content csw-text-center">
                <div class="csw-welcome-icon">
                    <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/>
                    </svg>
                </div>
                <h2><?php esc_html_e( 'Welcome to Competitor Spy Widget', 'competitor-spy-widget' ); ?></h2>
                <p class="csw-step-desc">
                    <?php esc_html_e( 'Let\'s set up your price comparison widget in under 2 minutes. Your customers will never leave to check competitor prices again.', 'competitor-spy-widget' ); ?>
                </p>
                <div class="csw-welcome-features">
                    <div class="csw-feature-item">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><?php esc_html_e( 'Show price comparisons on product pages', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <div class="csw-feature-item">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><?php esc_html_e( 'Auto-hide when competitors are cheaper', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <div class="csw-feature-item">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><?php esc_html_e( 'Track conversion impact with analytics', 'competitor-spy-widget' ); ?></span>
                    </div>
                </div>
                <button type="button" class="csw-btn csw-btn-primary csw-btn-lg csw-next-step">
                    <?php esc_html_e( 'Get Started', 'competitor-spy-widget' ); ?>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>

        <!-- Step 2: Add Competitors -->
        <div class="csw-onboarding-step" data-step="1">
            <div class="csw-step-content">
                <h2><?php esc_html_e( 'Add Your Competitors', 'competitor-spy-widget' ); ?></h2>
                <p class="csw-step-desc"><?php esc_html_e( 'Select the competitors you want to compare prices against. You can add more later.', 'competitor-spy-widget' ); ?></p>

                <div class="csw-competitor-select-grid">
                    <?php foreach ( $competitor_options as $option ) : ?>
                    <label class="csw-competitor-option">
                        <input type="checkbox" name="onboarding_competitors[]" value="<?php echo esc_attr( $option['key'] ); ?>" />
                        <div class="csw-option-card">
                            <img src="<?php echo esc_url( $option['icon'] ); ?>" alt="<?php echo esc_attr( $option['name'] ); ?>" width="32" height="32" />
                            <div class="csw-option-info">
                                <span class="csw-option-name"><?php echo esc_html( $option['name'] ); ?></span>
                                <span class="csw-option-desc"><?php echo esc_html( $option['description'] ); ?></span>
                            </div>
                            <div class="csw-option-check">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="csw-custom-competitor">
                    <p class="csw-form-label"><?php esc_html_e( 'Or add a custom competitor:', 'competitor-spy-widget' ); ?></p>
                    <div class="csw-form-row">
                        <input type="text" id="csw-custom-comp-name" class="csw-form-input" placeholder="<?php esc_attr_e( 'Competitor name', 'competitor-spy-widget' ); ?>" />
                        <input type="url" id="csw-custom-comp-url" class="csw-form-input" placeholder="https://www.competitor.com" />
                    </div>
                </div>

                <div class="csw-step-actions">
                    <button type="button" class="csw-btn csw-btn-outline csw-prev-step"><?php esc_html_e( 'Back', 'competitor-spy-widget' ); ?></button>
                    <button type="button" class="csw-btn csw-btn-primary csw-next-step"><?php esc_html_e( 'Continue', 'competitor-spy-widget' ); ?></button>
                </div>
            </div>
        </div>

        <!-- Step 3: Configure Display -->
        <div class="csw-onboarding-step" data-step="2">
            <div class="csw-step-content">
                <h2><?php esc_html_e( 'Configure Display', 'competitor-spy-widget' ); ?></h2>
                <p class="csw-step-desc"><?php esc_html_e( 'Choose where the widget appears on your product pages.', 'competitor-spy-widget' ); ?></p>

                <div class="csw-position-options">
                    <?php foreach ( $position_options as $key => $pos ) : ?>
                    <label class="csw-position-option <?php echo 'after_price' === $key ? 'selected' : ''; ?>">
                        <input type="radio" name="onboarding_position" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, 'after_price' ); ?> />
                        <div class="csw-position-card">
                            <span class="csw-position-name"><?php echo esc_html( $pos['label'] ); ?></span>
                            <span class="csw-position-desc"><?php echo esc_html( $pos['description'] ); ?></span>
                            <?php if ( ! empty( $pos['recommended'] ) ) : ?>
                            <span class="csw-badge-sm csw-badge-green"><?php esc_html_e( 'Recommended', 'competitor-spy-widget' ); ?></span>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="csw-step-actions">
                    <button type="button" class="csw-btn csw-btn-outline csw-prev-step"><?php esc_html_e( 'Back', 'competitor-spy-widget' ); ?></button>
                    <button type="button" class="csw-btn csw-btn-primary csw-next-step"><?php esc_html_e( 'Continue', 'competitor-spy-widget' ); ?></button>
                </div>
            </div>
        </div>

        <!-- Step 4: Complete -->
        <div class="csw-onboarding-step" data-step="3">
            <div class="csw-step-content csw-text-center">
                <div class="csw-complete-icon">
                    <svg viewBox="0 0 24 24" width="64" height="64" fill="none" stroke="#10B981" stroke-width="1.5">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h2><?php esc_html_e( 'You\'re All Set!', 'competitor-spy-widget' ); ?></h2>
                <p class="csw-step-desc">
                    <?php esc_html_e( 'Your competitor spy widget is configured. Now add competitor prices to your products to activate the widget.', 'competitor-spy-widget' ); ?>
                </p>

                <div class="csw-next-steps">
                    <h4><?php esc_html_e( 'Next Steps:', 'competitor-spy-widget' ); ?></h4>
                    <ol class="csw-next-steps-list">
                        <li><?php esc_html_e( 'Go to any product and add competitor prices in the "Competitor Prices" tab', 'competitor-spy-widget' ); ?></li>
                        <li><?php esc_html_e( 'Or use the "Prices" page to bulk-add competitor prices', 'competitor-spy-widget' ); ?></li>
                        <li><?php esc_html_e( 'Visit a product page to see the widget in action', 'competitor-spy-widget' ); ?></li>
                    </ol>
                </div>

                <button type="button" class="csw-btn csw-btn-primary csw-btn-lg" id="csw-complete-onboarding">
                    <?php esc_html_e( 'Go to Dashboard', 'competitor-spy-widget' ); ?>
                </button>
            </div>
        </div>
    </div>
</div>
