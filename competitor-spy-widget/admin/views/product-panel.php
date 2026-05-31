<?php
/**
 * WooCommerce product data panel for competitor prices.
 * Redesigned: No need to pre-register competitors. Just type name + price.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get existing competitors for autocomplete suggestions
$all_competitors = CSW_Database::get_competitors( false );
$competitor_names = array();
foreach ( $all_competitors as $c ) {
    $competitor_names[] = $c->name;
}
?>

<div id="csw_prices_panel" class="panel woocommerce_options_panel">
    <?php wp_nonce_field( 'csw_save_product_prices', 'csw_prices_nonce' ); ?>

    <div class="csw-product-panel" style="padding: 12px;">
        <div style="margin-bottom: 12px; padding: 10px 14px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 6px;">
            <strong style="color: #166534;"><?php esc_html_e( '💰 Add Competitor Prices', 'competitor-spy-widget' ); ?></strong>
            <p style="margin: 4px 0 0; font-size: 12px; color: #166534;">
                <?php esc_html_e( 'Just type the competitor name and their price. Widget auto-shows when YOUR price is lower.', 'competitor-spy-widget' ); ?>
            </p>
        </div>

        <table class="widefat" id="csw-inline-prices-table" style="border: 1px solid #E5E7EB; border-radius: 6px;">
            <thead>
                <tr style="background: #F9FAFB;">
                    <th style="width:30%; padding: 10px 12px; font-size: 12px; font-weight: 600;"><?php esc_html_e( 'Competitor Name', 'competitor-spy-widget' ); ?></th>
                    <th style="width:20%; padding: 10px 12px; font-size: 12px; font-weight: 600;"><?php esc_html_e( 'Their Price', 'competitor-spy-widget' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</th>
                    <th style="width:30%; padding: 10px 12px; font-size: 12px; font-weight: 600;"><?php esc_html_e( 'Product URL (optional)', 'competitor-spy-widget' ); ?></th>
                    <th style="width:12%; padding: 10px 12px; font-size: 12px; font-weight: 600;"><?php esc_html_e( 'Status', 'competitor-spy-widget' ); ?></th>
                    <th style="width:8%; padding: 10px 12px;"></th>
                </tr>
            </thead>
            <tbody id="csw-price-rows">
                <?php
                // Show existing prices
                $existing_count = 0;
                if ( ! empty( $prices ) ) :
                    foreach ( $prices as $price ) :
                        $existing_count++;
                        $product_obj = wc_get_product( $product_id );
                        $our_price = $product_obj ? (float) $product_obj->get_price() : 0;
                        $is_winning = $our_price < (float) $price->competitor_price;
                ?>
                <tr class="csw-price-entry-row">
                    <td style="padding: 8px 12px;">
                        <input type="text" 
                               name="csw_prices[<?php echo esc_attr( $existing_count ); ?>][name]" 
                               value="<?php echo esc_attr( $price->competitor_name ); ?>"
                               placeholder="<?php esc_attr_e( 'e.g. Amazon, Flipkart', 'competitor-spy-widget' ); ?>"
                               class="regular-text csw-comp-name-input"
                               list="csw-competitor-suggestions"
                               style="width:100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px;" />
                    </td>
                    <td style="padding: 8px 12px;">
                        <input type="number" 
                               name="csw_prices[<?php echo esc_attr( $existing_count ); ?>][price]" 
                               value="<?php echo esc_attr( $price->competitor_price ); ?>"
                               step="0.01" min="0" 
                               placeholder="0.00"
                               style="width:100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px;" />
                    </td>
                    <td style="padding: 8px 12px;">
                        <input type="url" 
                               name="csw_prices[<?php echo esc_attr( $existing_count ); ?>][url]" 
                               value="<?php echo esc_attr( $price->competitor_url ); ?>"
                               placeholder="https://..."
                               style="width:100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px;" />
                    </td>
                    <td style="padding: 8px 12px; text-align: center;">
                        <?php if ( $is_winning ) : ?>
                        <span style="color: #10B981; font-weight: 600; font-size: 12px;">✓ <?php esc_html_e( 'Win', 'competitor-spy-widget' ); ?></span>
                        <?php else : ?>
                        <span style="color: #EF4444; font-weight: 600; font-size: 12px;">✗ <?php esc_html_e( 'Lose', 'competitor-spy-widget' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 8px 12px; text-align: center;">
                        <button type="button" class="csw-remove-row" style="background:none; border:none; color:#EF4444; cursor:pointer; font-size:18px;" title="<?php esc_attr_e( 'Remove', 'competitor-spy-widget' ); ?>">×</button>
                    </td>
                </tr>
                <?php
                    endforeach;
                endif;

                // Always show at least one empty row for quick add
                $empty_rows = max( 1, 3 - $existing_count );
                for ( $i = 0; $i < $empty_rows; $i++ ) :
                    $row_index = $existing_count + $i + 1;
                ?>
                <tr class="csw-price-entry-row">
                    <td style="padding: 8px 12px;">
                        <input type="text" 
                               name="csw_prices[<?php echo esc_attr( $row_index ); ?>][name]" 
                               value=""
                               placeholder="<?php esc_attr_e( 'e.g. Amazon, Flipkart', 'competitor-spy-widget' ); ?>"
                               class="regular-text csw-comp-name-input"
                               list="csw-competitor-suggestions"
                               style="width:100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px;" />
                    </td>
                    <td style="padding: 8px 12px;">
                        <input type="number" 
                               name="csw_prices[<?php echo esc_attr( $row_index ); ?>][price]" 
                               value=""
                               step="0.01" min="0" 
                               placeholder="0.00"
                               style="width:100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px;" />
                    </td>
                    <td style="padding: 8px 12px;">
                        <input type="url" 
                               name="csw_prices[<?php echo esc_attr( $row_index ); ?>][url]" 
                               value=""
                               placeholder="https://..."
                               style="width:100%; padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px;" />
                    </td>
                    <td style="padding: 8px 12px; text-align: center;">
                        <span style="color: #9CA3AF; font-size: 11px;">—</span>
                    </td>
                    <td style="padding: 8px 12px; text-align: center;">
                        <button type="button" class="csw-remove-row" style="background:none; border:none; color:#EF4444; cursor:pointer; font-size:18px;" title="<?php esc_attr_e( 'Remove', 'competitor-spy-widget' ); ?>">×</button>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- Add More Button -->
        <p style="margin-top: 10px;">
            <button type="button" id="csw-add-price-row" class="button" style="background: #10B981; color: white; border-color: #10B981; border-radius: 4px;">
                + <?php esc_html_e( 'Add Another Competitor', 'competitor-spy-widget' ); ?>
            </button>
        </p>

        <p style="margin-top: 8px; font-size: 11px; color: #6B7280;">
            <?php esc_html_e( '💡 Tip: Just check competitor websites and type their price here. No API keys needed. New competitors are saved automatically.', 'competitor-spy-widget' ); ?>
        </p>
    </div>

    <!-- Autocomplete suggestions from previously added competitors -->
    <datalist id="csw-competitor-suggestions">
        <?php foreach ( $competitor_names as $name ) : ?>
        <option value="<?php echo esc_attr( $name ); ?>">
        <?php endforeach; ?>
        <option value="Amazon">
        <option value="Flipkart">
        <option value="Walmart">
        <option value="eBay">
        <option value="Target">
        <option value="Best Buy">
        <option value="AliExpress">
        <option value="Myntra">
        <option value="Snapdeal">
    </datalist>

    <script>
    jQuery(function($) {
        var rowCounter = <?php echo esc_js( $existing_count + $empty_rows + 1 ); ?>;

        // Add row
        $('#csw-add-price-row').on('click', function() {
            var newRow = '<tr class="csw-price-entry-row">' +
                '<td style="padding:8px 12px;"><input type="text" name="csw_prices[' + rowCounter + '][name]" value="" placeholder="<?php echo esc_js( __( 'e.g. Amazon, Flipkart', 'competitor-spy-widget' ) ); ?>" list="csw-competitor-suggestions" style="width:100%;padding:6px 10px;border:1px solid #D1D5DB;border-radius:4px;"></td>' +
                '<td style="padding:8px 12px;"><input type="number" name="csw_prices[' + rowCounter + '][price]" value="" step="0.01" min="0" placeholder="0.00" style="width:100%;padding:6px 10px;border:1px solid #D1D5DB;border-radius:4px;"></td>' +
                '<td style="padding:8px 12px;"><input type="url" name="csw_prices[' + rowCounter + '][url]" value="" placeholder="https://..." style="width:100%;padding:6px 10px;border:1px solid #D1D5DB;border-radius:4px;"></td>' +
                '<td style="padding:8px 12px;text-align:center;"><span style="color:#9CA3AF;font-size:11px;">—</span></td>' +
                '<td style="padding:8px 12px;text-align:center;"><button type="button" class="csw-remove-row" style="background:none;border:none;color:#EF4444;cursor:pointer;font-size:18px;" title="Remove">×</button></td>' +
                '</tr>';
            $('#csw-price-rows').append(newRow);
            rowCounter++;
        });

        // Remove row
        $(document).on('click', '.csw-remove-row', function() {
            var $row = $(this).closest('tr');
            if ($('#csw-price-rows tr').length > 1) {
                $row.remove();
            } else {
                $row.find('input').val('');
            }
        });
    });
    </script>
</div>
