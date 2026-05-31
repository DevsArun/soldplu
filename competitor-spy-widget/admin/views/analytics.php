<?php
/**
 * Analytics view.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$period = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : '30days'; // phpcs:ignore WordPress.Security.NonceVerification
$stats = CSW_Analytics::get_conversion_stats( $period );
$performance = CSW_Analytics::get_widget_performance();
?>

<div class="csw-admin-wrap">
    <div class="csw-admin-header">
        <div class="csw-header-left">
            <h1 class="csw-page-title"><?php esc_html_e( 'Analytics', 'competitor-spy-widget' ); ?></h1>
            <p class="csw-page-subtitle"><?php esc_html_e( 'Track how the price comparison widget impacts your conversions.', 'competitor-spy-widget' ); ?></p>
        </div>
        <div class="csw-header-right">
            <div class="csw-period-selector">
                <a href="<?php echo esc_url( add_query_arg( 'period', '7days' ) ); ?>" class="csw-period-btn <?php echo '7days' === $period ? 'active' : ''; ?>"><?php esc_html_e( '7 Days', 'competitor-spy-widget' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( 'period', '30days' ) ); ?>" class="csw-period-btn <?php echo '30days' === $period ? 'active' : ''; ?>"><?php esc_html_e( '30 Days', 'competitor-spy-widget' ); ?></a>
                <a href="<?php echo esc_url( add_query_arg( 'period', '90days' ) ); ?>" class="csw-period-btn <?php echo '90days' === $period ? 'active' : ''; ?>"><?php esc_html_e( '90 Days', 'competitor-spy-widget' ); ?></a>
            </div>
            <button type="button" class="csw-btn csw-btn-outline" id="csw-export-analytics">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <?php esc_html_e( 'Export', 'competitor-spy-widget' ); ?>
            </button>
        </div>
    </div>

    <!-- Analytics Stats -->
    <div class="csw-stats-grid csw-stats-4">
        <div class="csw-stat-card">
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( number_format( $stats['impressions'] ) ); ?></div>
                <div class="csw-stat-label"><?php esc_html_e( 'Widget Impressions', 'competitor-spy-widget' ); ?></div>
            </div>
            <div class="csw-stat-trend csw-trend-up">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                </svg>
            </div>
        </div>

        <div class="csw-stat-card">
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( number_format( $stats['conversions'] ) ); ?></div>
                <div class="csw-stat-label"><?php esc_html_e( 'Conversions', 'competitor-spy-widget' ); ?></div>
            </div>
            <div class="csw-stat-trend csw-trend-up">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                </svg>
            </div>
        </div>

        <div class="csw-stat-card">
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( $stats['conversion_rate'] ); ?>%</div>
                <div class="csw-stat-label"><?php esc_html_e( 'Conversion Rate', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>

        <div class="csw-stat-card">
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( $performance['impression_rate'] ); ?>%</div>
                <div class="csw-stat-label"><?php esc_html_e( 'Show Rate', 'competitor-spy-widget' ); ?></div>
                <div class="csw-stat-help"><?php esc_html_e( 'of product views show widget', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>
    </div>

    <div class="csw-dashboard-grid">
        <!-- Conversions Chart -->
        <div class="csw-card csw-card-chart">
            <div class="csw-card-header">
                <h3><?php esc_html_e( 'Impressions & Conversions', 'competitor-spy-widget' ); ?></h3>
            </div>
            <div class="csw-card-body">
                <canvas id="csw-analytics-chart" height="300"></canvas>
            </div>
        </div>

        <!-- Device Breakdown -->
        <div class="csw-card">
            <div class="csw-card-header">
                <h3><?php esc_html_e( 'Device Breakdown', 'competitor-spy-widget' ); ?></h3>
            </div>
            <div class="csw-card-body">
                <canvas id="csw-device-chart" height="200"></canvas>
                <div class="csw-device-legend">
                    <?php
                    $device_totals = array( 'desktop' => 0, 'mobile' => 0, 'tablet' => 0 );
                    foreach ( $performance['device_breakdown'] as $device ) {
                        $device_totals[ $device->device_type ] = (int) $device->count;
                    }
                    $total_devices = array_sum( $device_totals );
                    ?>
                    <div class="csw-device-item">
                        <span class="csw-device-dot csw-dot-blue"></span>
                        <span><?php esc_html_e( 'Desktop', 'competitor-spy-widget' ); ?></span>
                        <span class="csw-device-percent"><?php echo $total_devices > 0 ? esc_html( round( ( $device_totals['desktop'] / $total_devices ) * 100 ) ) : 0; ?>%</span>
                    </div>
                    <div class="csw-device-item">
                        <span class="csw-device-dot csw-dot-green"></span>
                        <span><?php esc_html_e( 'Mobile', 'competitor-spy-widget' ); ?></span>
                        <span class="csw-device-percent"><?php echo $total_devices > 0 ? esc_html( round( ( $device_totals['mobile'] / $total_devices ) * 100 ) ) : 0; ?>%</span>
                    </div>
                    <div class="csw-device-item">
                        <span class="csw-device-dot csw-dot-purple"></span>
                        <span><?php esc_html_e( 'Tablet', 'competitor-spy-widget' ); ?></span>
                        <span class="csw-device-percent"><?php echo $total_devices > 0 ? esc_html( round( ( $device_totals['tablet'] / $total_devices ) * 100 ) ) : 0; ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="csw-card csw-card-full">
            <div class="csw-card-header">
                <h3><?php esc_html_e( 'Top Performing Products', 'competitor-spy-widget' ); ?></h3>
            </div>
            <div class="csw-card-body csw-no-padding">
                <?php if ( ! empty( $stats['top_products'] ) ) : ?>
                <table class="csw-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Product', 'competitor-spy-widget' ); ?></th>
                            <th><?php esc_html_e( 'Widget Views', 'competitor-spy-widget' ); ?></th>
                            <th><?php esc_html_e( 'Price', 'competitor-spy-widget' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $stats['top_products'] as $product_item ) : ?>
                        <tr>
                            <td>
                                <div class="csw-product-cell">
                                    <?php if ( ! empty( $product_item['image'] ) ) : ?>
                                    <img src="<?php echo esc_url( $product_item['image'] ); ?>" alt="" class="csw-product-thumb" />
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url( $product_item['permalink'] ); ?>" target="_blank">
                                        <?php echo esc_html( $product_item['name'] ); ?>
                                    </a>
                                </div>
                            </td>
                            <td><strong><?php echo esc_html( number_format( $product_item['views'] ) ); ?></strong></td>
                            <td><?php echo wp_kses_post( wc_price( $product_item['price'] ) ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div class="csw-empty-state csw-empty-compact">
                    <p><?php esc_html_e( 'No data yet. Analytics will appear once visitors start viewing your products.', 'competitor-spy-widget' ); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Pass analytics data to JS for charts
    var cswAnalyticsData = <?php echo wp_json_encode( array(
        'daily_stats'      => $stats['daily_stats'],
        'device_breakdown' => $performance['device_breakdown'],
    ) ); ?>;
</script>
