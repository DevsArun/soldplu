<?php
/**
 * Prices management view.
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
?>

<div class="csw-admin-wrap">
    <div class="csw-admin-header">
        <div class="csw-header-left">
            <h1 class="csw-page-title"><?php esc_html_e( 'Price Comparisons', 'competitor-spy-widget' ); ?></h1>
            <p class="csw-page-subtitle"><?php esc_html_e( 'Manage competitor prices for your products.', 'competitor-spy-widget' ); ?></p>
        </div>
        <div class="csw-header-right">
            <button type="button" class="csw-btn csw-btn-outline" id="csw-import-btn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <?php esc_html_e( 'Import CSV', 'competitor-spy-widget' ); ?>
            </button>
            <button type="button" class="csw-btn csw-btn-primary" id="csw-add-price-btn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e( 'Add Price', 'competitor-spy-widget' ); ?>
            </button>
        </div>
    </div>

    <?php if ( empty( $competitors ) ) : ?>
    <div class="csw-notice csw-notice-warning">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <p>
            <?php
            printf(
                /* translators: %s: link to competitors page */
                esc_html__( 'You need to add at least one competitor first. %s', 'competitor-spy-widget' ),
                '<a href="' . esc_url( admin_url( 'admin.php?page=csw-competitors' ) ) . '">' . esc_html__( 'Add a competitor', 'competitor-spy-widget' ) . '</a>'
            );
            ?>
        </p>
    </div>
    <?php endif; ?>

    <!-- Prices Table -->
    <div class="csw-card">
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
                <p><?php esc_html_e( 'Add competitor prices for your products to enable the comparison widget.', 'competitor-spy-widget' ); ?></p>
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
                        <th><?php esc_html_e( 'Last Updated', 'competitor-spy-widget' ); ?></th>
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
                                <?php
                                $image_url = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
                                if ( $image_url ) :
                                ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="" class="csw-product-thumb" />
                                <?php endif; ?>
                                <div>
                                    <a href="<?php echo esc_url( get_edit_post_link( $price->product_id ) ); ?>" class="csw-product-name">
                                        <?php echo esc_html( $product->get_name() ); ?>
                                    </a>
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
                            <span class="csw-date-cell"><?php echo esc_html( human_time_diff( strtotime( $price->updated_at ) ) ); ?> <?php esc_html_e( 'ago', 'competitor-spy-widget' ); ?></span>
                        </td>
                        <td>
                            <div class="csw-table-actions">
                                <button type="button" class="csw-btn-icon csw-edit-price" data-id="<?php echo esc_attr( $price->id ); ?>" title="<?php esc_attr_e( 'Edit', 'competitor-spy-widget' ); ?>">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button type="button" class="csw-btn-icon csw-btn-icon-danger csw-delete-price" data-id="<?php echo esc_attr( $price->id ); ?>" title="<?php esc_attr_e( 'Delete', 'competitor-spy-widget' ); ?>">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    </svg>
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
                <a href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>" class="csw-page-btn <?php echo $i === $current_page ? 'active' : ''; ?>">
                    <?php echo esc_html( $i ); ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Price Modal -->
    <div class="csw-modal" id="csw-price-modal" style="display:none;">
        <div class="csw-modal-overlay"></div>
        <div class="csw-modal-content">
            <div class="csw-modal-header">
                <h3><?php esc_html_e( 'Add Competitor Price', 'competitor-spy-widget' ); ?></h3>
                <button type="button" class="csw-modal-close">&times;</button>
            </div>
            <form id="csw-price-form">
                <div class="csw-modal-body">
                    <div class="csw-form-group">
                        <label for="csw-price-product" class="csw-form-label"><?php esc_html_e( 'Product', 'competitor-spy-widget' ); ?> <span class="required">*</span></label>
                        <select id="csw-price-product" name="product_id" class="csw-form-select csw-product-search" data-placeholder="<?php esc_attr_e( 'Search for a product...', 'competitor-spy-widget' ); ?>">
                        </select>
                    </div>

                    <div class="csw-form-group">
                        <label for="csw-price-competitor" class="csw-form-label"><?php esc_html_e( 'Competitor', 'competitor-spy-widget' ); ?> <span class="required">*</span></label>
                        <select id="csw-price-competitor" name="competitor_id" class="csw-form-select">
                            <option value=""><?php esc_html_e( 'Select a competitor', 'competitor-spy-widget' ); ?></option>
                            <?php foreach ( $competitors as $comp ) : ?>
                            <option value="<?php echo esc_attr( $comp->id ); ?>"><?php echo esc_html( $comp->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="csw-form-group">
                        <label for="csw-price-amount" class="csw-form-label"><?php esc_html_e( 'Competitor Price', 'competitor-spy-widget' ); ?> <span class="required">*</span></label>
                        <div class="csw-input-group">
                            <span class="csw-input-prefix"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
                            <input type="number" id="csw-price-amount" name="competitor_price" class="csw-form-input" step="0.01" min="0" placeholder="0.00" required />
                        </div>
                    </div>

                    <div class="csw-form-group">
                        <label for="csw-price-url" class="csw-form-label"><?php esc_html_e( 'Product URL at Competitor', 'competitor-spy-widget' ); ?></label>
                        <input type="url" id="csw-price-url" name="competitor_url" class="csw-form-input" placeholder="https://www.competitor.com/product-page" />
                        <p class="csw-form-help"><?php esc_html_e( 'Optional. Link to the product on the competitor\'s website.', 'competitor-spy-widget' ); ?></p>
                    </div>
                </div>
                <div class="csw-modal-footer">
                    <button type="button" class="csw-btn csw-btn-outline csw-modal-cancel"><?php esc_html_e( 'Cancel', 'competitor-spy-widget' ); ?></button>
                    <button type="submit" class="csw-btn csw-btn-primary">
                        <span class="csw-btn-text"><?php esc_html_e( 'Save Price', 'competitor-spy-widget' ); ?></span>
                        <span class="csw-btn-loading" style="display:none;"><?php esc_html_e( 'Saving...', 'competitor-spy-widget' ); ?></span>
                    </button>
                </div>
            </form>
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
                    <div class="csw-import-info">
                        <p><?php esc_html_e( 'Upload a CSV file with the following columns:', 'competitor-spy-widget' ); ?></p>
                        <code>product_id, competitor_id, competitor_price, competitor_url</code>
                        <p class="csw-form-help"><?php esc_html_e( 'The first row should be headers. competitor_url is optional.', 'competitor-spy-widget' ); ?></p>
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
