<?php
/**
 * Prices management view — redesigned with bulk entry.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$competitors = CSW_Database::get_competitors( true );

global $wpdb;
$prices_table = $wpdb->prefix . 'csw_prices';
$competitors_table = $wpdb->prefix . 'csw_competitors';

$per_page = 20;
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
$offset = ( $current_page - 1 ) * $per_page;

$total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prices_table} WHERE is_active = 1" ); // phpcs:ignore
$total_pages = ceil( $total_items / $per_page );

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

// Get competitor names for autocomplete
$comp_names = array();
foreach ( $competitors as $c ) {
    $comp_names[] = $c->name;
}
?>

<div class="csw-admin-wrap">
    <div class="csw-admin-header">
        <div class="csw-header-left">
            <h1 class="csw-page-title"><?php esc_html_e( 'Price Comparisons', 'competitor-spy-widget' ); ?></h1>
            <p class="csw-page-subtitle"><?php esc_html_e( 'Add competitor prices for multiple products at once. No API needed — just type!', 'competitor-spy-widget' ); ?></p>
        </div>
        <div class="csw-header-right" style="display:flex;align-items:center;gap:10px;">
            <button type="button" class="csw-btn csw-btn-outline" id="csw-import-btn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <?php esc_html_e( 'Import CSV', 'competitor-spy-widget' ); ?>
            </button>
            <button type="button" class="csw-btn csw-btn-primary" id="csw-show-bulk-add">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e( 'Bulk Add Prices', 'competitor-spy-widget' ); ?>
            </button>
        </div>
    </div>

    <!-- BULK ADD SECTION -->
    <div class="csw-card" id="csw-bulk-add-section" style="margin-bottom:20px; display:none;">
        <div class="csw-card-header">
            <h3><?php esc_html_e( 'Quick Bulk Add — No API Needed', 'competitor-spy-widget' ); ?></h3>
            <button type="button" class="csw-btn csw-btn-sm csw-btn-outline" id="csw-hide-bulk-add"><?php esc_html_e( 'Close', 'competitor-spy-widget' ); ?></button>
        </div>
        <div class="csw-card-body">
            <p style="font-size:13px; color:#6B7280; margin:0 0 16px;">
                <?php esc_html_e( 'Search for a product, type the competitor name and price. Click "Save All" when done. Competitors are created automatically.', 'competitor-spy-widget' ); ?>
            </p>

            <form id="csw-bulk-price-form">
                <table class="csw-table" id="csw-bulk-table" style="border:1px solid #E5E7EB;">
                    <thead>
                        <tr>
                            <th style="width:30%;"><?php esc_html_e( 'Product', 'competitor-spy-widget' ); ?></th>
                            <th style="width:20%;"><?php esc_html_e( 'Competitor Name', 'competitor-spy-widget' ); ?></th>
                            <th style="width:15%;"><?php esc_html_e( 'Their Price', 'competitor-spy-widget' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</th>
                            <th style="width:25%;"><?php esc_html_e( 'URL (optional)', 'competitor-spy-widget' ); ?></th>
                            <th style="width:10%;"></th>
                        </tr>
                    </thead>
                    <tbody id="csw-bulk-rows">
                        <?php for ( $i = 0; $i < 5; $i++ ) : ?>
                        <tr class="csw-bulk-row">
                            <td style="padding:8px;">
                                <select name="bulk_prices[<?php echo esc_attr( $i ); ?>][product_id]" class="csw-form-select csw-bulk-product-search" data-placeholder="<?php esc_attr_e( 'Search product...', 'competitor-spy-widget' ); ?>">
                                </select>
                            </td>
                            <td style="padding:8px;">
                                <input type="text" name="bulk_prices[<?php echo esc_attr( $i ); ?>][competitor_name]" placeholder="<?php esc_attr_e( 'e.g. Amazon', 'competitor-spy-widget' ); ?>" list="csw-bulk-comp-list" class="csw-form-input" />
                            </td>
                            <td style="padding:8px;">
                                <input type="number" name="bulk_prices[<?php echo esc_attr( $i ); ?>][competitor_price]" step="0.01" min="0" placeholder="0.00" class="csw-form-input" />
                            </td>
                            <td style="padding:8px;">
                                <input type="url" name="bulk_prices[<?php echo esc_attr( $i ); ?>][competitor_url]" placeholder="https://..." class="csw-form-input" />
                            </td>
                            <td style="padding:8px; text-align:center;">
                                <button type="button" class="csw-btn-icon csw-remove-bulk-row" title="<?php esc_attr_e( 'Remove', 'competitor-spy-widget' ); ?>">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <datalist id="csw-bulk-comp-list">
                    <?php foreach ( $comp_names as $name ) : ?>
                    <option value="<?php echo esc_attr( $name ); ?>">
                    <?php endforeach; ?>
                    <option value="Amazon">
                    <option value="Flipkart">
                    <option value="Walmart">
                    <option value="eBay">
                    <option value="Target">
                    <option value="Best Buy">
                    <option value="AliExpress">
                </datalist>

                <div style="margin-top:12px; display:flex; gap:10px; align-items:center;">
                    <button type="button" class="csw-btn csw-btn-outline" id="csw-add-bulk-row">
                        + <?php esc_html_e( 'Add More Rows', 'competitor-spy-widget' ); ?>
                    </button>
                    <button type="submit" class="csw-btn csw-btn-primary" id="csw-save-bulk-prices">
                        <span class="csw-btn-text"><?php esc_html_e( 'Save All Prices', 'competitor-spy-widget' ); ?></span>
                        <span class="csw-btn-loading" style="display:none;"><?php esc_html_e( 'Saving...', 'competitor-spy-widget' ); ?></span>
                    </button>
                    <span id="csw-bulk-status" style="font-size:13px; color:#10B981; display:none;">✓ <?php esc_html_e( 'Saved!', 'competitor-spy-widget' ); ?></span>
                </div>
            </form>
        </div>
    </div>

    <!-- EXISTING PRICES TABLE -->
    <div class="csw-card">
        <div class="csw-card-header">
            <h3><?php esc_html_e( 'All Price Entries', 'competitor-spy-widget' ); ?> <span style="font-weight:400; color:#6B7280;">(<?php echo esc_html( $total_items ); ?>)</span></h3>
        </div>
        <div class="csw-card-body csw-no-padding">
            <?php if ( empty( $prices ) ) : ?>
            <div class="csw-empty-state csw-empty-compact">
                <div class="csw-empty-icon">
                    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                </div>
                <h3><?php esc_html_e( 'No Price Entries Yet', 'competitor-spy-widget' ); ?></h3>
                <p><?php esc_html_e( 'Use "Bulk Add Prices" above or go to any product → "Competitor Prices" tab to add prices.', 'competitor-spy-widget' ); ?></p>
                <button type="button" class="csw-btn csw-btn-primary" onclick="document.getElementById('csw-show-bulk-add').click();">
                    <?php esc_html_e( 'Add Your First Prices', 'competitor-spy-widget' ); ?>
                </button>
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
                        <th><?php esc_html_e( 'Savings', 'competitor-spy-widget' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'competitor-spy-widget' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $prices as $price ) :
                        $product = wc_get_product( $price->product_id );
                        if ( ! $product ) continue;
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
                                <span><?php echo esc_html( $price->competitor_name ); ?></span>
                            </div>
                        </td>
                        <td><strong><?php echo wp_kses_post( wc_price( $price->our_price ) ); ?></strong></td>
                        <td><?php echo wp_kses_post( wc_price( $price->competitor_price ) ); ?></td>
                        <td>
                            <?php if ( $price->is_cheaper ) : ?>
                            <span class="csw-badge-sm csw-badge-green"><?php esc_html_e( 'Winning', 'competitor-spy-widget' ); ?></span>
                            <?php else : ?>
                            <span class="csw-badge-sm csw-badge-red"><?php esc_html_e( 'Losing', 'competitor-spy-widget' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( $price->is_cheaper ) : ?>
                            <span class="csw-text-green"><?php echo esc_html( $price->savings_percent ); ?>%</span>
                            <?php else : ?>
                            <span class="csw-text-red">-<?php echo esc_html( abs( $price->savings_percent ) ); ?>%</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="csw-btn-icon csw-btn-icon-danger csw-delete-price" data-id="<?php echo esc_attr( $price->id ); ?>" title="<?php esc_attr_e( 'Delete', 'competitor-spy-widget' ); ?>">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                </svg>
                            </button>
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

    <!-- Import Modal -->
    <div class="csw-modal" id="csw-import-modal" style="display:none;">
        <div class="csw-modal-overlay"></div>
        <div class="csw-modal-content">
            <div class="csw-modal-header">
                <h3><?php esc_html_e( 'Import Prices from CSV', 'competitor-spy-widget' ); ?></h3>
                <button type="button" class="csw-modal-close">&times;</button>
            </div>
            <form id="csw-import-form" enctype="multipart/form-data">
                <div class="csw-modal-body">
                    <div style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:8px; padding:14px; margin-bottom:16px;">
                        <p style="margin:0 0 8px; font-size:13px; font-weight:500;"><?php esc_html_e( 'CSV Format (4 columns):', 'competitor-spy-widget' ); ?></p>
                        <code style="font-size:12px;">product_id, competitor_name, competitor_price, competitor_url</code>
                        <p style="font-size:11px; color:#6B7280; margin:8px 0 0;"><?php esc_html_e( 'First row = headers. competitor_url is optional. Competitors are created automatically.', 'competitor-spy-widget' ); ?></p>
                    </div>
                    <div class="csw-form-group">
                        <label for="csw-csv-file" class="csw-form-label"><?php esc_html_e( 'CSV File', 'competitor-spy-widget' ); ?></label>
                        <input type="file" id="csw-csv-file" name="csv_file" accept=".csv,.txt" class="csw-form-input" required />
                    </div>
                </div>
                <div class="csw-modal-footer">
                    <button type="button" class="csw-btn csw-btn-outline csw-modal-cancel"><?php esc_html_e( 'Cancel', 'competitor-spy-widget' ); ?></button>
                    <button type="submit" class="csw-btn csw-btn-primary">
                        <span class="csw-btn-text"><?php esc_html_e( 'Import', 'competitor-spy-widget' ); ?></span>
                        <span class="csw-btn-loading" style="display:none;"><?php esc_html_e( 'Importing...', 'competitor-spy-widget' ); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var bulkRowCount = 5;

    // Toggle bulk add
    $('#csw-show-bulk-add').on('click', function() {
        $('#csw-bulk-add-section').slideDown(200);
    });
    $('#csw-hide-bulk-add').on('click', function() {
        $('#csw-bulk-add-section').slideUp(200);
    });

    // Add more bulk rows
    $('#csw-add-bulk-row').on('click', function() {
        for (var i = 0; i < 3; i++) {
            var row = '<tr class="csw-bulk-row">' +
                '<td style="padding:8px;"><select name="bulk_prices[' + bulkRowCount + '][product_id]" class="csw-form-select csw-bulk-product-search" data-placeholder="Search product..."></select></td>' +
                '<td style="padding:8px;"><input type="text" name="bulk_prices[' + bulkRowCount + '][competitor_name]" placeholder="e.g. Amazon" list="csw-bulk-comp-list" class="csw-form-input"></td>' +
                '<td style="padding:8px;"><input type="number" name="bulk_prices[' + bulkRowCount + '][competitor_price]" step="0.01" min="0" placeholder="0.00" class="csw-form-input"></td>' +
                '<td style="padding:8px;"><input type="url" name="bulk_prices[' + bulkRowCount + '][competitor_url]" placeholder="https://..." class="csw-form-input"></td>' +
                '<td style="padding:8px;text-align:center;"><button type="button" class="csw-btn-icon csw-remove-bulk-row"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></td>' +
                '</tr>';
            $('#csw-bulk-rows').append(row);
            bulkRowCount++;
        }
        initProductSearch();
    });

    // Remove bulk row
    $(document).on('click', '.csw-remove-bulk-row', function() {
        $(this).closest('tr').remove();
    });

    // Init Select2 for product search
    function initProductSearch() {
        if ($.fn.select2) {
            $('.csw-bulk-product-search:not(.select2-hidden-accessible)').select2({
                ajax: {
                    url: cswAdmin.ajaxUrl,
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return { action: 'csw_search_products', nonce: cswAdmin.nonce, term: params.term };
                    },
                    processResults: function(data) { return { results: data }; }
                },
                minimumInputLength: 2,
                placeholder: 'Search product...',
                allowClear: true,
                width: '100%'
            });
        }
    }
    initProductSearch();

    // Save bulk prices
    $('#csw-bulk-price-form').on('submit', function(e) {
        e.preventDefault();

        var $btn = $('#csw-save-bulk-prices');
        $btn.find('.csw-btn-text').hide();
        $btn.find('.csw-btn-loading').show();
        $('#csw-bulk-status').hide();

        var entries = [];
        $('.csw-bulk-row').each(function() {
            var $row = $(this);
            var productId = $row.find('[name$="[product_id]"]').val();
            var compName = $row.find('[name$="[competitor_name]"]').val();
            var compPrice = $row.find('[name$="[competitor_price]"]').val();
            var compUrl = $row.find('[name$="[competitor_url]"]').val();

            if (productId && compName && compPrice && parseFloat(compPrice) > 0) {
                entries.push({
                    product_id: productId,
                    competitor_name: compName,
                    competitor_price: compPrice,
                    competitor_url: compUrl || ''
                });
            }
        });

        if (entries.length === 0) {
            alert('<?php echo esc_js( __( 'Please fill at least one complete row (product + competitor + price).', 'competitor-spy-widget' ) ); ?>');
            $btn.find('.csw-btn-text').show();
            $btn.find('.csw-btn-loading').hide();
            return;
        }

        $.ajax({
            url: cswAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'csw_bulk_save_prices',
                nonce: cswAdmin.nonce,
                entries: entries
            },
            success: function(response) {
                if (response.success) {
                    $('#csw-bulk-status').show();
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    alert(response.data.message || 'Error saving prices.');
                }
            },
            error: function() {
                alert('<?php echo esc_js( __( 'Network error. Please try again.', 'competitor-spy-widget' ) ); ?>');
            },
            complete: function() {
                $btn.find('.csw-btn-text').show();
                $btn.find('.csw-btn-loading').hide();
            }
        });
    });
});
</script>
