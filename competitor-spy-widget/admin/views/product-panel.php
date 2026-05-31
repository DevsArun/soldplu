<?php
/**
 * WooCommerce product data panel — URL-based auto-fetch approach.
 *
 * NEW WORKFLOW: Paste competitor product URL → Plugin auto-fetches price.
 * Manual price entry still available as fallback.
 *
 * @package CompetitorSpyWidget
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$product_obj = wc_get_product( $product_id );
$our_price = $product_obj ? (float) $product_obj->get_price() : 0;
?>

<div id="csw_prices_panel" class="panel woocommerce_options_panel">
    <?php wp_nonce_field( 'csw_save_product_prices', 'csw_prices_nonce' ); ?>

    <div class="csw-product-panel" style="padding: 12px;">

        <!-- Header Info Box -->
        <div style="margin-bottom: 16px; padding: 12px 16px; background: linear-gradient(135deg, #F0FDF4, #ECFDF5); border: 1px solid #BBF7D0; border-radius: 8px;">
            <strong style="color: #166534; font-size: 13px;">🔍 <?php esc_html_e( 'Auto Price Monitoring', 'competitor-spy-widget' ); ?></strong>
            <p style="margin: 6px 0 0; font-size: 12px; color: #166534; line-height: 1.5;">
                <?php esc_html_e( 'Paste the competitor product URL below — we\'ll automatically fetch their price and keep it updated daily. Widget only shows when YOUR price wins.', 'competitor-spy-widget' ); ?>
            </p>
        </div>

        <!-- Your Price Display -->
        <div style="margin-bottom: 16px; padding: 10px 14px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 12px; color: #6B7280;"><?php esc_html_e( 'Your price for this product:', 'competitor-spy-widget' ); ?></span>
            <strong style="font-size: 15px; color: #10B981;"><?php echo wp_kses_post( wc_price( $our_price ) ); ?></strong>
        </div>

        <!-- Existing Price Entries -->
        <?php if ( ! empty( $prices ) ) : ?>
        <div style="margin-bottom: 16px;">
            <h4 style="margin: 0 0 10px; font-size: 13px; font-weight: 600; color: #374151;"><?php esc_html_e( 'Active Competitor Monitoring', 'competitor-spy-widget' ); ?></h4>
            <?php foreach ( $prices as $idx => $price ) :
                $is_winning = $our_price < (float) $price->competitor_price;
                $has_url = ! empty( $price->competitor_url );
                $site_name = $has_url ? CSW_Price_Fetcher::get_site_display_name( $price->competitor_url ) : $price->competitor_name;
            ?>
            <div class="csw-monitor-entry" style="margin-bottom: 8px; padding: 12px 14px; border: 1px solid <?php echo $is_winning ? '#BBF7D0' : '#FECACA'; ?>; border-radius: 8px; background: <?php echo $is_winning ? '#F0FDF4' : '#FEF2F2'; ?>;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <strong style="font-size: 13px; color: #1F2937;"><?php echo esc_html( $site_name ); ?></strong>
                            <?php if ( $is_winning ) : ?>
                            <span style="font-size: 10px; padding: 2px 6px; background: #10B981; color: white; border-radius: 10px; font-weight: 600;">✓ <?php esc_html_e( 'YOU WIN', 'competitor-spy-widget' ); ?></span>
                            <?php else : ?>
                            <span style="font-size: 10px; padding: 2px 6px; background: #EF4444; color: white; border-radius: 10px; font-weight: 600;"><?php esc_html_e( 'HIDDEN', 'competitor-spy-widget' ); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ( $has_url ) : ?>
                        <div style="margin-top: 4px; font-size: 11px; color: #6B7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 350px;">
                            🔗 <?php echo esc_html( $price->competitor_url ); ?>
                        </div>
                        <?php endif; ?>
                        <div style="margin-top: 4px; font-size: 11px; color: #9CA3AF;">
                            <?php
                            printf(
                                /* translators: %s: time ago */
                                esc_html__( 'Last checked: %s ago', 'competitor-spy-widget' ),
                                esc_html( human_time_diff( strtotime( $price->last_checked ) ) )
                            );
                            ?>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 16px; font-weight: 700; color: #1F2937;">
                            <?php echo wp_kses_post( wc_price( $price->competitor_price ) ); ?>
                        </div>
                        <?php if ( $is_winning ) : ?>
                        <div style="font-size: 11px; color: #10B981; font-weight: 500;">
                            <?php
                            $diff = (float) $price->competitor_price - $our_price;
                            printf(
                                /* translators: %s: savings amount */
                                esc_html__( 'Customer saves %s', 'competitor-spy-widget' ),
                                wp_kses_post( wc_price( $diff ) )
                            );
                            ?>
                        </div>
                        <?php else : ?>
                        <div style="font-size: 11px; color: #EF4444; font-weight: 500;">
                            <?php esc_html_e( 'Widget auto-hidden', 'competitor-spy-widget' ); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Hidden inputs for saving -->
                <input type="hidden" name="csw_prices[<?php echo esc_attr( $idx ); ?>][name]" value="<?php echo esc_attr( $price->competitor_name ); ?>" />
                <input type="hidden" name="csw_prices[<?php echo esc_attr( $idx ); ?>][price]" value="<?php echo esc_attr( $price->competitor_price ); ?>" />
                <input type="hidden" name="csw_prices[<?php echo esc_attr( $idx ); ?>][url]" value="<?php echo esc_attr( $price->competitor_url ); ?>" />
                <!-- Refresh + Delete buttons -->
                <div style="margin-top: 8px; display: flex; gap: 8px; align-items: center;">
                    <?php if ( $has_url ) : ?>
                    <button type="button" class="button csw-refresh-price-btn" data-url="<?php echo esc_attr( $price->competitor_url ); ?>" data-index="<?php echo esc_attr( $idx ); ?>" style="font-size: 11px; padding: 2px 8px; height: auto;">
                        🔄 <?php esc_html_e( 'Refresh Price Now', 'competitor-spy-widget' ); ?>
                    </button>
                    <?php endif; ?>
                    <button type="button" class="csw-remove-entry-btn" data-index="<?php echo esc_attr( $idx ); ?>" style="background:none; border:none; color:#EF4444; cursor:pointer; font-size: 11px; text-decoration: underline;">
                        <?php esc_html_e( 'Remove', 'competitor-spy-widget' ); ?>
                    </button>
                    <span class="csw-refresh-status" data-index="<?php echo esc_attr( $idx ); ?>" style="font-size: 11px; color: #6B7280; display: none;"></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Add New Competitor URL Section -->
        <div style="border: 2px dashed #D1D5DB; border-radius: 8px; padding: 16px; background: #FAFAFA;">
            <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 600; color: #374151;">
                ➕ <?php esc_html_e( 'Add Competitor Product URL', 'competitor-spy-widget' ); ?>
            </h4>

            <div id="csw-add-url-rows">
                <div class="csw-new-url-row" style="margin-bottom: 10px; padding: 10px; background: white; border: 1px solid #E5E7EB; border-radius: 6px;">
                    <div style="display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
                        <div style="flex: 2; min-width: 250px;">
                            <label style="font-size: 11px; font-weight: 500; color: #6B7280; display: block; margin-bottom: 4px;"><?php esc_html_e( 'Competitor Product URL', 'competitor-spy-widget' ); ?></label>
                            <input type="url" 
                                   class="csw-new-url-input"
                                   placeholder="<?php esc_attr_e( 'https://www.amazon.in/product-name/dp/B0XXXXX', 'competitor-spy-widget' ); ?>"
                                   style="width:100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px;" />
                        </div>
                        <div style="flex: 0 0 auto;">
                            <button type="button" class="button button-primary csw-fetch-url-btn" style="background: #10B981; border-color: #059669; padding: 6px 14px; height: auto; font-size: 12px; border-radius: 6px;">
                                🔍 <?php esc_html_e( 'Fetch Price', 'competitor-spy-widget' ); ?>
                            </button>
                        </div>
                    </div>
                    <!-- Result area (hidden initially) -->
                    <div class="csw-fetch-result" style="display: none; margin-top: 10px; padding: 10px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 6px;">
                    </div>
                    <!-- Manual override -->
                    <div style="margin-top: 8px;">
                        <details style="font-size: 11px; color: #6B7280;">
                            <summary style="cursor: pointer;"><?php esc_html_e( 'Or enter price manually (no URL needed)', 'competitor-spy-widget' ); ?></summary>
                            <div style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                                <input type="text" class="csw-manual-name" placeholder="<?php esc_attr_e( 'Competitor name', 'competitor-spy-widget' ); ?>" list="csw-competitor-suggestions" style="padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px; font-size: 12px; width: 150px;" />
                                <input type="number" class="csw-manual-price" placeholder="<?php esc_attr_e( 'Price', 'competitor-spy-widget' ); ?>" step="0.01" min="0" style="padding: 6px 10px; border: 1px solid #D1D5DB; border-radius: 4px; font-size: 12px; width: 100px;" />
                                <button type="button" class="button csw-add-manual-btn" style="font-size: 11px; padding: 4px 10px; height: auto;">
                                    <?php esc_html_e( 'Add', 'competitor-spy-widget' ); ?>
                                </button>
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            <!-- Hidden container for new entries to be saved -->
            <div id="csw-new-entries-container"></div>
        </div>

        <p style="margin-top: 12px; font-size: 11px; color: #9CA3AF; line-height: 1.5;">
            <?php esc_html_e( '🔄 Prices are automatically refreshed daily. You\'ll get an email alert if a competitor drops below your price.', 'competitor-spy-widget' ); ?>
        </p>
    </div>

    <!-- Autocomplete suggestions -->
    <datalist id="csw-competitor-suggestions">
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
        var newEntryCount = <?php echo esc_js( count( $prices ) ); ?>;

        // Fetch price from URL
        $(document).on('click', '.csw-fetch-url-btn', function() {
            var $btn = $(this);
            var $row = $btn.closest('.csw-new-url-row');
            var url = $row.find('.csw-new-url-input').val().trim();
            var $result = $row.find('.csw-fetch-result');

            if (!url) {
                alert('<?php echo esc_js( __( 'Please paste a competitor product URL first.', 'competitor-spy-widget' ) ); ?>');
                return;
            }

            $btn.prop('disabled', true).text('⏳ Fetching...');
            $result.hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'csw_fetch_price_from_url',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'csw_admin_nonce' ) ); ?>',
                    url: url,
                    product_id: <?php echo esc_js( $product_id ); ?>
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var ourPrice = <?php echo esc_js( $our_price ); ?>;
                        var isWinning = ourPrice < data.price;
                        var statusHtml = isWinning
                            ? '<span style="color:#10B981;font-weight:600;">✓ YOU WIN — Widget will show!</span>'
                            : '<span style="color:#EF4444;font-weight:600;">✗ They\'re cheaper — Widget will hide</span>';

                        $result.html(
                            '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">' +
                            '<div><strong>' + (data.site_name || 'Competitor') + '</strong>' +
                            (data.title ? '<br><span style="font-size:11px;color:#6B7280;">' + data.title.substring(0, 60) + '...</span>' : '') +
                            '</div>' +
                            '<div style="text-align:right;">' +
                            '<div style="font-size:16px;font-weight:700;">' + data.formatted_price + '</div>' +
                            '<div style="font-size:11px;">' + statusHtml + '</div>' +
                            '</div></div>' +
                            '<div style="margin-top:8px;text-align:right;">' +
                            '<button type="button" class="button button-primary csw-confirm-add-btn" style="font-size:11px;padding:4px 12px;height:auto;background:#10B981;border-color:#059669;" ' +
                            'data-name="' + (data.site_name || 'Competitor') + '" ' +
                            'data-price="' + data.price + '" ' +
                            'data-url="' + url + '">' +
                            '✓ Add This Competitor</button></div>'
                        ).show();
                    } else {
                        $result.html(
                            '<div style="color:#EF4444;font-size:12px;">❌ ' + (response.data.message || 'Could not fetch price.') + '</div>' +
                            '<div style="margin-top:6px;font-size:11px;color:#6B7280;">You can still enter the price manually using the option below.</div>'
                        ).show();
                    }
                },
                error: function() {
                    $result.html('<div style="color:#EF4444;font-size:12px;">❌ Network error. Please try again.</div>').show();
                },
                complete: function() {
                    $btn.prop('disabled', false).html('🔍 <?php echo esc_js( __( 'Fetch Price', 'competitor-spy-widget' ) ); ?>');
                }
            });
        });

        // Confirm add fetched price
        $(document).on('click', '.csw-confirm-add-btn', function() {
            var $btn = $(this);
            var name = $btn.data('name');
            var price = $btn.data('price');
            var url = $btn.data('url');

            newEntryCount++;
            var hiddenInputs = '<div class="csw-added-entry" data-index="' + newEntryCount + '">' +
                '<input type="hidden" name="csw_prices[' + newEntryCount + '][name]" value="' + name + '">' +
                '<input type="hidden" name="csw_prices[' + newEntryCount + '][price]" value="' + price + '">' +
                '<input type="hidden" name="csw_prices[' + newEntryCount + '][url]" value="' + url + '">' +
                '</div>';
            $('#csw-new-entries-container').append(hiddenInputs);

            // Show confirmation
            $btn.closest('.csw-fetch-result').html(
                '<div style="color:#10B981;font-weight:500;">✓ Added! Click "Update" to save this product.</div>'
            );

            // Clear the URL input for next entry
            $btn.closest('.csw-new-url-row').find('.csw-new-url-input').val('');
        });

        // Add manual entry
        $(document).on('click', '.csw-add-manual-btn', function() {
            var $row = $(this).closest('.csw-new-url-row');
            var name = $row.find('.csw-manual-name').val().trim();
            var price = $row.find('.csw-manual-price').val().trim();

            if (!name || !price || parseFloat(price) <= 0) {
                alert('<?php echo esc_js( __( 'Please enter competitor name and price.', 'competitor-spy-widget' ) ); ?>');
                return;
            }

            newEntryCount++;
            var hiddenInputs = '<div class="csw-added-entry" data-index="' + newEntryCount + '">' +
                '<input type="hidden" name="csw_prices[' + newEntryCount + '][name]" value="' + name + '">' +
                '<input type="hidden" name="csw_prices[' + newEntryCount + '][price]" value="' + price + '">' +
                '<input type="hidden" name="csw_prices[' + newEntryCount + '][url]" value="">' +
                '</div>';
            $('#csw-new-entries-container').append(hiddenInputs);

            // Show confirmation
            $row.find('.csw-manual-name').val('');
            $row.find('.csw-manual-price').val('');
            $row.find('.csw-fetch-result').html(
                '<div style="color:#10B981;font-weight:500;">✓ ' + name + ' @ ' + price + ' added! Click "Update" to save.</div>'
            ).show();
        });

        // Refresh single price
        $(document).on('click', '.csw-refresh-price-btn', function() {
            var $btn = $(this);
            var url = $btn.data('url');
            var index = $btn.data('index');
            var $status = $('.csw-refresh-status[data-index="' + index + '"]');

            $btn.prop('disabled', true).text('⏳...');
            $status.text('').hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'csw_fetch_price_from_url',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'csw_admin_nonce' ) ); ?>',
                    url: url,
                    product_id: <?php echo esc_js( $product_id ); ?>
                },
                success: function(response) {
                    if (response.success) {
                        // Update the hidden price input
                        $btn.closest('.csw-monitor-entry').find('input[name$="[price]"]').val(response.data.price);
                        $status.text('✓ Updated to ' + response.data.formatted_price + ' — Click Update to save').css('color', '#10B981').show();
                    } else {
                        $status.text('❌ ' + (response.data.message || 'Fetch failed')).css('color', '#EF4444').show();
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).html('🔄 <?php echo esc_js( __( 'Refresh Price Now', 'competitor-spy-widget' ) ); ?>');
                }
            });
        });

        // Remove entry
        $(document).on('click', '.csw-remove-entry-btn', function() {
            var index = $(this).data('index');
            var $entry = $(this).closest('.csw-monitor-entry');
            $entry.find('input').each(function() {
                $(this).val('').attr('name', '');
            });
            $entry.slideUp(200, function() { $(this).remove(); });
        });
    });
    </script>
</div>
