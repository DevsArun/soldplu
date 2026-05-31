<?php
/**
 * Dashboard admin view.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$counts = CSW_Database::get_dashboard_counts();
$stats = CSW_Price_Engine::get_overall_stats();
$analytics = CSW_Analytics::get_conversion_stats( '30days' );
?>

<div class="csw-admin-wrap">
    <div class="csw-admin-header">
        <div class="csw-header-left">
            <h1 class="csw-page-title">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/>
                </svg>
                <?php esc_html_e( 'Competitor Spy Widget', 'competitor-spy-widget' ); ?>
            </h1>
            <span class="csw-version">v<?php echo esc_html( CSW_VERSION ); ?></span>
        </div>
        <div class="csw-header-right">
            <span class="csw-status-badge <?php echo CSW_Settings::is_enabled() ? 'csw-status-active' : 'csw-status-inactive'; ?>">
                <?php echo CSW_Settings::is_enabled() ? esc_html__( 'Active', 'competitor-spy-widget' ) : esc_html__( 'Inactive', 'competitor-spy-widget' ); ?>
            </span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="csw-stats-grid">
        <div class="csw-stat-card csw-stat-primary">
            <div class="csw-stat-icon">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( $stats['win_rate'] ); ?>%</div>
                <div class="csw-stat-label"><?php esc_html_e( 'Price Win Rate', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>

        <div class="csw-stat-card">
            <div class="csw-stat-icon csw-icon-green">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                    <polyline points="17 6 23 6 23 12"/>
                </svg>
            </div>
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( $analytics['conversion_rate'] ); ?>%</div>
                <div class="csw-stat-label"><?php esc_html_e( 'Conversion Rate', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>

        <div class="csw-stat-card">
            <div class="csw-stat-icon csw-icon-blue">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="3" width="22" height="18" rx="2"/>
                    <line x1="1" y1="9" x2="23" y2="9"/>
                </svg>
            </div>
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( number_format( $analytics['impressions'] ) ); ?></div>
                <div class="csw-stat-label"><?php esc_html_e( 'Widget Impressions', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>

        <div class="csw-stat-card">
            <div class="csw-stat-icon csw-icon-purple">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( $counts['total_competitors'] ); ?></div>
                <div class="csw-stat-label"><?php esc_html_e( 'Active Competitors', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="csw-dashboard-grid">
        <!-- Chart Section -->
        <div class="csw-card csw-card-chart">
            <div class="csw-card-header">
                <h3><?php esc_html_e( 'Performance Overview', 'competitor-spy-widget' ); ?></h3>
                <div class="csw-chart-period">
                    <button class="csw-period-btn active" data-period="7days"><?php esc_html_e( '7D', 'competitor-spy-widget' ); ?></button>
                    <button class="csw-period-btn" data-period="30days"><?php esc_html_e( '30D', 'competitor-spy-widget' ); ?></button>
                    <button class="csw-period-btn" data-period="90days"><?php esc_html_e( '90D', 'competitor-spy-widget' ); ?></button>
                </div>
            </div>
            <div class="csw-card-body">
                <canvas id="csw-performance-chart" height="280"></canvas>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="csw-card csw-card-actions">
            <div class="csw-card-header">
                <h3><?php esc_html_e( 'Quick Actions', 'competitor-spy-widget' ); ?></h3>
            </div>
            <div class="csw-card-body">
                <div class="csw-quick-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=csw-competitors' ) ); ?>" class="csw-action-item">
                        <div class="csw-action-icon csw-bg-blue">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                        </div>
                        <span><?php esc_html_e( 'Add Competitor', 'competitor-spy-widget' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=csw-prices' ) ); ?>" class="csw-action-item">
                        <div class="csw-action-icon csw-bg-green">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                            </svg>
                        </div>
                        <span><?php esc_html_e( 'Add Price', 'competitor-spy-widget' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=csw-analytics' ) ); ?>" class="csw-action-item">
                        <div class="csw-action-icon csw-bg-purple">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="14"/>
                            </svg>
                        </div>
                        <span><?php esc_html_e( 'View Analytics', 'competitor-spy-widget' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=csw-settings' ) ); ?>" class="csw-action-item">
                        <div class="csw-action-icon csw-bg-gray">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                            </svg>
                        </div>
                        <span><?php esc_html_e( 'Settings', 'competitor-spy-widget' ); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Price Summary -->
        <div class="csw-card">
            <div class="csw-card-header">
                <h3><?php esc_html_e( 'Price Advantage Summary', 'competitor-spy-widget' ); ?></h3>
            </div>
            <div class="csw-card-body">
                <div class="csw-summary-stats">
                    <div class="csw-summary-item">
                        <div class="csw-summary-circle csw-circle-green">
                            <span><?php echo esc_html( $counts['winning_products'] ); ?></span>
                        </div>
                        <div class="csw-summary-label"><?php esc_html_e( 'Products Winning', 'competitor-spy-widget' ); ?></div>
                    </div>
                    <div class="csw-summary-item">
                        <div class="csw-summary-circle csw-circle-red">
                            <span><?php echo esc_html( $counts['losing_products'] ); ?></span>
                        </div>
                        <div class="csw-summary-label"><?php esc_html_e( 'Products Losing', 'competitor-spy-widget' ); ?></div>
                    </div>
                    <div class="csw-summary-item">
                        <div class="csw-summary-circle csw-circle-blue">
                            <span><?php echo esc_html( $stats['avg_savings'] ); ?>%</span>
                        </div>
                        <div class="csw-summary-label"><?php esc_html_e( 'Avg. Savings Shown', 'competitor-spy-widget' ); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="csw-card">
            <div class="csw-card-header">
                <h3><?php esc_html_e( 'Getting Started', 'competitor-spy-widget' ); ?></h3>
            </div>
            <div class="csw-card-body">
                <div class="csw-checklist">
                    <div class="csw-checklist-item <?php echo $counts['total_competitors'] > 0 ? 'csw-check-done' : ''; ?>">
                        <div class="csw-check-icon">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <span><?php esc_html_e( 'Add at least one competitor', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <div class="csw-checklist-item <?php echo $counts['total_price_entries'] > 0 ? 'csw-check-done' : ''; ?>">
                        <div class="csw-check-icon">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <span><?php esc_html_e( 'Add competitor prices for products', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <div class="csw-checklist-item <?php echo CSW_Settings::is_enabled() ? 'csw-check-done' : ''; ?>">
                        <div class="csw-check-icon">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <span><?php esc_html_e( 'Enable the widget', 'competitor-spy-widget' ); ?></span>
                    </div>
                    <div class="csw-checklist-item <?php echo $analytics['impressions'] > 0 ? 'csw-check-done' : ''; ?>">
                        <div class="csw-check-icon">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <span><?php esc_html_e( 'Get your first widget impression', 'competitor-spy-widget' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
