<?php
/**
 * Upgrade/Pricing page — Stripe-style design.
 *
 * @package CompetitorSpyWidget
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$license_info = CSW_License::get_license_info();
$features = CSW_License::get_feature_comparison();
$is_pro = CSW_License::is_pro();
?>

<div class="csw-admin-wrap">
    <div style="max-width: 900px; margin: 0 auto; padding: 40px 20px;">

        <!-- Header -->
        <div style="text-align: center; margin-bottom: 48px;">
            <h1 style="font-size: 32px; font-weight: 800; color: #1F2937; margin: 0 0 12px; letter-spacing: -0.5px;">
                <?php if ( $is_pro ) : ?>
                    <?php esc_html_e( 'You\'re on Pro!', 'competitor-spy-widget' ); ?> 🎉
                <?php else : ?>
                    <?php esc_html_e( 'Unlock Full Price Intelligence', 'competitor-spy-widget' ); ?>
                <?php endif; ?>
            </h1>
            <p style="font-size: 16px; color: #6B7280; max-width: 500px; margin: 0 auto; line-height: 1.6;">
                <?php if ( $is_pro ) : ?>
                    <?php esc_html_e( 'All premium features are active. Thank you for being a Pro customer.', 'competitor-spy-widget' ); ?>
                <?php else : ?>
                    <?php esc_html_e( 'Auto-monitor competitor prices, get smart optimization suggestions, and never miss a price change.', 'competitor-spy-widget' ); ?>
                <?php endif; ?>
            </p>
        </div>

        <?php if ( ! $is_pro ) : ?>
        <!-- Pricing Cards -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 48px;">

            <!-- Free Plan -->
            <div style="background: white; border: 1px solid #E5E7EB; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="margin-bottom: 24px;">
                    <span style="font-size: 12px; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Free', 'competitor-spy-widget' ); ?></span>
                    <div style="margin-top: 8px;">
                        <span style="font-size: 36px; font-weight: 800; color: #1F2937;">$0</span>
                        <span style="font-size: 14px; color: #9CA3AF;">/<?php esc_html_e( 'forever', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <p style="font-size: 13px; color: #6B7280; margin: 8px 0 0;"><?php esc_html_e( 'Basic price comparison widget for small stores.', 'competitor-spy-widget' ); ?></p>
                </div>

                <div style="border-top: 1px solid #E5E7EB; padding-top: 20px;">
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px; color: #374151;">
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #10B981;">✓</span> <?php esc_html_e( '3 products monitored', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #10B981;">✓</span> <?php esc_html_e( '2 competitors per product', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #10B981;">✓</span> <?php esc_html_e( 'Manual price entry', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #10B981;">✓</span> <?php esc_html_e( 'Basic widget (1 style)', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #D1D5DB;">✗</span> <span style="color: #9CA3AF;"><?php esc_html_e( 'Auto price fetching', 'competitor-spy-widget' ); ?></span>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #D1D5DB;">✗</span> <span style="color: #9CA3AF;"><?php esc_html_e( 'Price alerts', 'competitor-spy-widget' ); ?></span>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #D1D5DB;">✗</span> <span style="color: #9CA3AF;"><?php esc_html_e( 'Optimization suggestions', 'competitor-spy-widget' ); ?></span>
                        </li>
                    </ul>
                </div>

                <div style="margin-top: 24px;">
                    <span style="display: block; text-align: center; padding: 10px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; color: #6B7280; font-weight: 500;">
                        <?php echo 'free' === $license_info['plan'] ? esc_html__( 'Current Plan', 'competitor-spy-widget' ) : esc_html__( 'Downgrade', 'competitor-spy-widget' ); ?>
                    </span>
                </div>
            </div>

            <!-- Pro Plan -->
            <div style="background: white; border: 2px solid #4F46E5; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(79,70,229,0.12); position: relative;">
                <div style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #4F46E5, #7C3AED); color: white; font-size: 11px; font-weight: 700; padding: 4px 14px; border-radius: 100px; text-transform: uppercase; letter-spacing: 0.05em;">
                    <?php esc_html_e( 'Most Popular', 'competitor-spy-widget' ); ?>
                </div>

                <div style="margin-bottom: 24px;">
                    <span style="font-size: 12px; font-weight: 600; color: #4F46E5; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Pro', 'competitor-spy-widget' ); ?></span>
                    <div style="margin-top: 8px;">
                        <span style="font-size: 36px; font-weight: 800; color: #1F2937;">$49</span>
                        <span style="font-size: 14px; color: #9CA3AF;">/<?php esc_html_e( 'month', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <p style="font-size: 13px; color: #6B7280; margin: 8px 0 0;"><?php esc_html_e( 'Full price intelligence for growing stores.', 'competitor-spy-widget' ); ?></p>
                </div>

                <div style="border-top: 1px solid #E0E7FF; padding-top: 20px;">
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px; color: #374151;">
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <strong><?php esc_html_e( 'Unlimited products', 'competitor-spy-widget' ); ?></strong>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <?php esc_html_e( '10 competitors per product', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <strong><?php esc_html_e( 'Auto price fetching from URLs', 'competitor-spy-widget' ); ?></strong>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <?php esc_html_e( 'Daily auto-refresh', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <strong><?php esc_html_e( 'Email price alerts', 'competitor-spy-widget' ); ?></strong>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <?php esc_html_e( 'Price optimization suggestions', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <?php esc_html_e( '365 days price history + charts', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <?php esc_html_e( 'All 4 widget styles', 'competitor-spy-widget' ); ?>
                        </li>
                        <li style="padding: 6px 0; display: flex; align-items: center; gap: 8px;">
                            <span style="color: #4F46E5;">✓</span> <?php esc_html_e( 'Priority support', 'competitor-spy-widget' ); ?>
                        </li>
                    </ul>
                </div>

                <div style="margin-top: 24px;">
                    <?php if ( ! CSW_License::trial_used() ) : ?>
                    <button type="button" id="csw-start-trial-btn" style="display: block; width: 100%; padding: 12px; background: linear-gradient(135deg, #4F46E5, #7C3AED); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        <?php esc_html_e( 'Start 14-Day Free Trial', 'competitor-spy-widget' ); ?>
                    </button>
                    <p style="text-align: center; font-size: 11px; color: #9CA3AF; margin: 8px 0 0;">
                        <?php esc_html_e( 'No credit card required. Full Pro features for 14 days.', 'competitor-spy-widget' ); ?>
                    </p>
                    <?php else : ?>
                    <a href="<?php echo esc_url( CSW_License::get_upgrade_url() ); ?>" target="_blank" style="display: block; width: 100%; padding: 12px; background: linear-gradient(135deg, #4F46E5, #7C3AED); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center;">
                        <?php esc_html_e( 'Upgrade to Pro — $49/month', 'competitor-spy-widget' ); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- License Key Section -->
        <div style="background: white; border: 1px solid #E5E7EB; border-radius: 12px; padding: 24px; margin-bottom: 32px;">
            <h3 style="margin: 0 0 16px; font-size: 15px; font-weight: 600; color: #1F2937;">
                <?php esc_html_e( 'License Key', 'competitor-spy-widget' ); ?>
            </h3>

            <?php if ( ! empty( $license_info['license_key'] ) ) : ?>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; margin-bottom: 12px;">
                <span style="font-size: 13px; color: #166534; font-weight: 500;">✓ <?php echo esc_html( $license_info['license_key'] ); ?></span>
                <span style="font-size: 11px; color: #166534; margin-left: auto;">
                    <?php
                    if ( $license_info['license_expiry'] ) {
                        printf(
                            /* translators: %s: expiry date */
                            esc_html__( 'Expires: %s', 'competitor-spy-widget' ),
                            esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license_info['license_expiry'] ) ) )
                        );
                    }
                    ?>
                </span>
            </div>
            <button type="button" id="csw-deactivate-btn" class="csw-btn csw-btn-sm csw-btn-outline" style="color: #EF4444; border-color: #EF4444;">
                <?php esc_html_e( 'Deactivate License', 'competitor-spy-widget' ); ?>
            </button>
            <?php else : ?>
            <div style="display: flex; gap: 10px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 12px; color: #6B7280; margin-bottom: 4px;"><?php esc_html_e( 'Enter your license key:', 'competitor-spy-widget' ); ?></label>
                    <input type="text" id="csw-license-key-input" class="csw-form-input" placeholder="CSW-XXXX-XXXX-XXXX-XXXX" style="font-family: monospace; letter-spacing: 0.5px;" />
                </div>
                <button type="button" id="csw-activate-btn" class="csw-btn csw-btn-primary">
                    <?php esc_html_e( 'Activate', 'competitor-spy-widget' ); ?>
                </button>
            </div>
            <p style="font-size: 11px; color: #9CA3AF; margin: 8px 0 0;">
                <?php esc_html_e( 'Purchase a license at competitorspywidget.com to unlock Pro features.', 'competitor-spy-widget' ); ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Current Plan Info -->
        <div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px; text-align: center;">
            <p style="margin: 0; font-size: 13px; color: #6B7280;">
                <?php esc_html_e( 'Current Plan:', 'competitor-spy-widget' ); ?>
                <strong style="color: #1F2937;"><?php echo esc_html( $license_info['plan_label'] ); ?></strong>
                <?php if ( $license_info['is_trial'] ) : ?>
                    <span style="color: #F59E0B;"> — <?php printf( esc_html__( '%d days remaining', 'competitor-spy-widget' ), $license_info['trial_remaining'] ); ?></span>
                <?php endif; ?>
                &nbsp;|&nbsp;
                <?php esc_html_e( 'Products:', 'competitor-spy-widget' ); ?>
                <strong><?php echo esc_html( $license_info['products_used'] ); ?>/<?php echo -1 === $license_info['product_limit'] ? '∞' : esc_html( $license_info['product_limit'] ); ?></strong>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    // Start trial
    $('#csw-start-trial-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Starting...');

        $.ajax({
            url: cswAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'csw_start_trial', nonce: cswAdmin.nonce },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error starting trial.');
                    $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Start 14-Day Free Trial', 'competitor-spy-widget' ) ); ?>');
                }
            }
        });
    });

    // Activate license
    $('#csw-activate-btn').on('click', function() {
        var key = $('#csw-license-key-input').val().trim();
        if (!key) { alert('Please enter a license key.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Activating...');

        $.ajax({
            url: cswAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'csw_activate_license', nonce: cswAdmin.nonce, license_key: key },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'Activation failed.');
                    $btn.prop('disabled', false).text('Activate');
                }
            }
        });
    });

    // Deactivate
    $('#csw-deactivate-btn').on('click', function() {
        if (!confirm('Are you sure? Pro features will be disabled.')) return;

        $.ajax({
            url: cswAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'csw_deactivate_license', nonce: cswAdmin.nonce },
            success: function() { location.reload(); }
        });
    });
});
</script>
