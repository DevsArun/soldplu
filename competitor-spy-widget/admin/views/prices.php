<?php
/**
 * Prices management view — URL-based monitoring with fetch status.
 *
 * @package CompetitorSpyWidget
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$prices_table = $wpdb->prefix . 'csw_prices';
$competitors_table = $wpdb->prefix . 'csw_competitors';

$per_page = 20;
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
$offset = ( $current_page - 1 ) * $per_page;

$total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prices_table} WHERE is_active = 1" ); // phpcs:ignore
$total_pages = ceil( $total_items / $per_page );

// Count monitored (with URL) vs manual
$monitored_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prices_table} WHERE is_active = 1 AND competitor_url != '' AND competitor_url IS NOT NULL" ); // phpcs:ignore
$manual_count = $total_items - $monitored_count;

$prices = $wpdb->get_results( $wpdb->prepare(
    "SELECT p.*, c.name as competitor_name, c.logo_url as competitor_logo
     FROM {$prices_table} p
     INNER JOIN {$competitors_table} c ON p.competitor_id = c.id
     WHERE p.is_active = 1
     ORDER BY p.updated_at DESC
     LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $per_page,
    $offset
) );

// Next scheduled refresh
$next_refresh = wp_next_scheduled( 'csw_auto_refresh_prices' );
?>

<div class="csw-admin-wrap">
    <div class="csw-admin-header">
        <div class="csw-header-left">
            <h1 class="csw-page-title"><?php esc_html_e( 'Price Monitor', 'competitor-spy-widget' ); ?></h1>
            <p class="csw-page-subtitle"><?php esc_html_e( 'All monitored competitor prices — auto-refreshed daily from URLs.', 'competitor-spy-widget' ); ?></p>
        </div>
        <div class="csw-header-right" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <button type="button" class="csw-btn csw-btn-outline" id="csw-refresh-all-btn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"/>
                    <path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
                </svg>
                <?php esc_html_e( 'Refresh All Now', 'competitor-spy-widget' ); ?>
            </button>
            <button type="button" class="csw-btn csw-btn-outline" id="csw-import-btn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <?php esc_html_e( 'Import CSV', 'competitor-spy-widget' ); ?>
            </button>
        </div>
    </div>

    <!-- Monitor Stats Bar -->
    <div class="csw-stats-grid" style="margin-bottom: 20px;">
        <div class="csw-stat-card">
            <div class="csw-stat-icon csw-icon-blue">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( $monitored_count ); ?></div>
                <div class="csw-stat-label"><?php esc_html_e( 'Auto-Monitored (URL)', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>
        <div class="csw-stat-card">
            <div class="csw-stat-icon" style="background:rgba(107,114,128,0.1);color:#6B7280;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </div>
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( $manual_count ); ?></div>
                <div class="csw-stat-label"><?php esc_html_e( 'Manual Entries', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>
        <div class="csw-stat-card">
            <div class="csw-stat-icon csw-icon-green">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div class="csw-stat-content">
                <div class="csw-stat-value"><?php echo esc_html( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prices_table} WHERE is_active = 1 AND is_cheaper = 1" ) ); // phpcs:ignore ?></div>
                <div class="csw-stat-label"><?php esc_html_e( 'You\'re Winning', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>
        <div class="csw-stat-card">
            <div class="csw-stat-content">
                <div class="csw-stat-value" style="font-size:14px;">
                    <?php
                    if ( $next_refresh ) {
                        echo esc_html( human_time_diff( time(), $next_refresh ) );
                    } else {
                        esc_html_e( 'Not scheduled', 'competitor-spy-widget' );
                    }
                    ?>
                </div>
                <div class="csw-stat-label"><?php esc_html_e( 'Next Auto-Refresh', 'competitor-spy-widget' ); ?></div>
            </div>
        </div>
    </div>

    <!-- Prices Table -->
    <div class="csw-card">
        <div class="csw-card-header">
            <h3><?php esc_html_e( 'All Monitored Prices', 'competitor-spy-widget' ); ?> <span style="font-weight:400;color:#6B7280;">(<?php echo esc_html( $total_items ); ?>)</span></h3>
            <span id="csw-refresh-status" style="font-size:12px;color:#10B981;display:none;"></span>
        </div>
        <div class="csw-card-body csw-no-padding">
            <?php if ( empty( $prices ) ) : ?>
            <div class="csw-empty-state csw-empty-compact">
                <div class="csw-empty-icon">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
                <h3><?php esc_html_e( 'No Prices Being Monitored', 'competitor-spy-widget' ); ?></h3>
                <p><?php esc_html_e( 'Go to any product → "Competitor Prices" tab → paste a competitor URL to start auto-monitoring.', 'competitor-spy-widget' ); ?></p>
            </div>
            <?php else : ?>
            <table class="csw-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Product', 'competitor-spy-widget' ); ?></th>
                        <th><?php esc_html_e( 'Competitor', 'competitor-spy-widget' ); ?></th>
                        <th><?php esc_html_e( 'Our Price', 'competitor-spy-widget' ); ?></th>
                        <th><?php esc_html_e( 'Their Price', 'competitor-spy-widget' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'competitor-spy-widget' ); ?></th>
                        <th><?php esc_html_e( 'Source', 'competitor-spy-widget' ); ?></th>
                        <th><?php esc_html_e( 'Last Checked', 'competitor-spy-widget' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'competitor-spy-widget' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $prices as $price ) :
                        $product = wc_get_product( $price->product_id );
                        if ( ! $product ) continue;
                        $has_url = ! empty( $price->competitor_url );
                    ?>
                    <tr data-price-id="<?php echo esc_attr( $price->id ); ?>">
                        <td>
                            <div class="csw-product-cell">
                                <?php $img = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ); ?>
                                <?php if ( $img ) : ?><img src="<?php echo esc_url( $img ); ?>" alt="" class="csw-product-thumb" /><?php endif; ?>
                                <div>
                                    <a href="<?php echo esc_url( get_edit_post_link( $price->product_id ) ); ?>" class="csw-product-name"><?php echo esc_html( $product->get_name() ); ?></a>
                                    <span class="csw-product-sku">#<?php echo esc_html( $price->product_id ); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="csw-competitor-cell">
                                <?php if ( ! empty( $price->competitor_logo ) ) : ?>
                                <img src="<?php echo esc_url( $price->competitor_logo ); ?>" alt="" width="20" height="20" />
                                <?php endif; ?>
                                <div>
                                    <span><?php echo esc_html( $price->competitor_name ); ?></span>
                                    <?php if ( $has_url ) : ?>
                                    <a href="<?php echo esc_url( $price->competitor_url ); ?>" target="_blank" style="display:block;font-size:10px;color:#9CA3AF;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr( $price->competitor_url ); ?>">
                                        🔗 <?php echo esc_html( wp_parse_url( $price->competitor_url, PHP_URL_HOST ) ); ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><strong><?php echo wp_kses_post( wc_price( $price->our_price ) ); ?></strong></td>
                        <td><?php echo wp_kses_post( wc_price( $price->competitor_price ) ); ?></td>
                        <td>
                            <?php if ( $price->is_cheaper ) : ?>
                            <span class="csw-badge-sm csw-badge-green"><?php esc_html_e( 'Winning', 'competitor-spy-widget' ); ?></span>
                            <?php else : ?>
                            <span class="csw-badge-sm csw-badge-red"><?php esc_html_e( 'Hidden', 'competitor-spy-widget' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $has_url ) : ?>
                            <span style="font-size:11px;color:#3B82F6;font-weight:500;">🔄 <?php esc_html_e( 'Auto', 'competitor-spy-widget' ); ?></span>
                            <?php else : ?>
                            <span style="font-size:11px;color:#9CA3AF;">✏️ <?php esc_html_e( 'Manual', 'competitor-spy-widget' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="csw-date-cell" title="<?php echo esc_attr( $price->last_checked ); ?>">
                                <?php echo esc_html( human_time_diff( strtotime( $price->last_checked ) ) ); ?> <?php esc_html_e( 'ago', 'competitor-spy-widget' ); ?>
                            </span>
                        </td>
                        <td>
                            <div class="csw-table-actions">
                                <?php if ( $has_url ) : ?>
                                <button type="button" class="csw-btn-icon csw-refresh-single" data-url="<?php echo esc_attr( $price->competitor_url ); ?>" data-id="<?php echo esc_attr( $price->id ); ?>" data-product="<?php echo esc_attr( $price->product_id ); ?>" title="<?php esc_attr_e( 'Refresh', 'competitor-spy-widget' ); ?>">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                                </button>
                                <?php endif; ?>
                                <button type="button" class="csw-btn-icon csw-btn-icon-danger csw-delete-price" data-id="<?php echo esc_attr( $price->id ); ?>" title="<?php esc_attr_e( 'Delete', 'competitor-spy-widget' ); ?>">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ( $total_pages > 1 ) : ?>
            <div class="csw-pagination">
                <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>" class="csw-page-btn <?php echo $i === $current_page ? 'active' : ''; ?>"><?php echo esc_html( $i ); ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- How it works info -->
    <div class="csw-card" style="margin-top:20px;">
        <div class="csw-card-body" style="display:flex;gap:24px;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <h4 style="margin:0 0 8px;font-size:13px;color:#374151;">🔗 <?php esc_html_e( 'Auto-Monitored', 'competitor-spy-widget' ); ?></h4>
                <p style="margin:0;font-size:12px;color:#6B7280;line-height:1.5;">
                    <?php esc_html_e( 'Prices fetched from competitor URLs automatically twice daily. You get email alerts when prices change.', 'competitor-spy-widget' ); ?>
                </p>
            </div>
            <div style="flex:1;min-width:200px;">
                <h4 style="margin:0 0 8px;font-size:13px;color:#374151;">✏️ <?php esc_html_e( 'Manual Entries', 'competitor-spy-widget' ); ?></h4>
                <p style="margin:0;font-size:12px;color:#6B7280;line-height:1.5;">
                    <?php esc_html_e( 'Prices you entered manually. Convert them to auto-monitored by adding the competitor product URL.', 'competitor-spy-widget' ); ?>
                </p>
            </div>
            <div style="flex:1;min-width:200px;">
                <h4 style="margin:0 0 8px;font-size:13px;color:#374151;">📧 <?php esc_html_e( 'Email Alerts', 'competitor-spy-widget' ); ?></h4>
                <p style="margin:0;font-size:12px;color:#6B7280;line-height:1.5;">
                    <?php esc_html_e( 'Get notified when a competitor drops below your price. Widget auto-hides to protect your store.', 'competitor-spy-widget' ); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div class="csw-modal" id="csw-import-modal" style="display:none;">
        <div class="csw-modal-overlay"></div>
        <div class="csw-modal-content">
            <div class="csw-modal-header">
                <h3><?php esc_html_e( 'Import Prices from CSV', 'competitor-spy-widget' ); ?></h3>
                <button type="button" class="csw-modal-close">&times;</button>
            </div>
            <form id="csw-import-form" enctype="multipart/form-data">
                <div class="csw-modal-body">
                    <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:8px;padding:14px;margin-bottom:16px;">
                        <p style="margin:0 0 8px;font-size:13px;font-weight:500;"><?php esc_html_e( 'CSV Format:', 'competitor-spy-widget' ); ?></p>
                        <code style="font-size:12px;">product_id, competitor_name, competitor_price, competitor_url</code>
                        <p style="font-size:11px;color:#6B7280;margin:8px 0 0;"><?php esc_html_e( 'First row = headers. competitor_url is optional but enables auto-monitoring.', 'competitor-spy-widget' ); ?></p>
                    </div>
                    <div class="csw-form-group">
                        <input type="file" id="csw-csv-file" name="csv_file" accept=".csv,.txt" class="csw-form-input" required />
                    </div>
                </div>
                <div class="csw-modal-footer">
                    <button type="button" class="csw-btn csw-btn-outline csw-modal-cancel"><?php esc_html_e( 'Cancel', 'competitor-spy-widget' ); ?></button>
                    <button type="submit" class="csw-btn csw-btn-primary"><?php esc_html_e( 'Import', 'competitor-spy-widget' ); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    // Refresh All
    $('#csw-refresh-all-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('⏳ Refreshing...');
        $('#csw-refresh-status').hide();

        $.ajax({
            url: cswAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'csw_refresh_all_prices', nonce: cswAdmin.nonce },
            timeout: 120000,
            success: function(response) {
                if (response.success) {
                    $('#csw-refresh-status').text('✓ ' + response.data.message).show();
                    setTimeout(function() { location.reload(); }, 2000);
                } else {
                    alert(response.data.message || 'Refresh failed.');
                }
            },
            error: function() { alert('Network error or timeout.'); },
            complete: function() {
                $btn.prop('disabled', false).html('<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg> Refresh All Now');
            }
        });
    });

    // Refresh single
    $(document).on('click', '.csw-refresh-single', function() {
        var $btn = $(this);
        var url = $btn.data('url');
        var productId = $btn.data('product');

        $btn.prop('disabled', true);

        $.ajax({
            url: cswAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'csw_fetch_price_from_url', nonce: cswAdmin.nonce, url: url, product_id: productId },
            success: function(response) {
                if (response.success) {
                    var $row = $btn.closest('tr');
                    $row.find('td:eq(3)').html('<strong>' + response.data.formatted_price + '</strong> <span style="color:#10B981;font-size:10px;">✓</span>');
                }
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    // Delete price
    $(document).on('click', '.csw-delete-price', function() {
        if (!confirm(cswAdmin.i18n.confirm_delete)) return;
        var id = $(this).data('id');
        $.ajax({
            url: cswAdmin.ajaxUrl,
            type: 'POST',
            data: { action: 'csw_delete_price', nonce: cswAdmin.nonce, price_id: id },
            success: function(response) {
                if (response.success) { $('tr[data-price-id="' + id + '"]').fadeOut(300); }
            }
        });
    });
});
</script>
