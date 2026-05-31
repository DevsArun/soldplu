<?php
/**
 * WooCommerce product data panel for competitor prices.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="csw_prices_panel" class="panel woocommerce_options_panel">
    <?php wp_nonce_field( 'csw_save_product_prices', 'csw_prices_nonce' ); ?>

    <div class="csw-product-panel">
        <p class="form-field">
            <strong><?php esc_html_e( 'Competitor Prices', 'competitor-spy-widget' ); ?></strong><br>
            <span class="description"><?php esc_html_e( 'Enter competitor prices for this product. The widget will only display when your price is better.', 'competitor-spy-widget' ); ?></span>
        </p>

        <?php if ( empty( $competitors ) ) : ?>
        <p class="form-field">
            <em>
                <?php
                printf(
                    /* translators: %s: link to competitors page */
                    esc_html__( 'No competitors configured. %s to get started.', 'competitor-spy-widget' ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=csw-competitors' ) ) . '">' . esc_html__( 'Add competitors', 'competitor-spy-widget' ) . '</a>'
                );
                ?>
            </em>
        </p>
        <?php else : ?>

        <table class="csw-product-prices-table widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Competitor', 'competitor-spy-widget' ); ?></th>
                    <th><?php esc_html_e( 'Price', 'competitor-spy-widget' ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</th>
                    <th><?php esc_html_e( 'URL (optional)', 'competitor-spy-widget' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'competitor-spy-widget' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $competitors as $competitor ) :
                    // Find existing price for this competitor
                    $existing_price = '';
                    $existing_url = '';
                    $is_winning = false;

                    foreach ( $prices as $price ) {
                        if ( (int) $price->competitor_id === (int) $competitor->id ) {
                            $existing_price = $price->competitor_price;
                            $existing_url = $price->competitor_url;
                            $is_winning = (bool) $price->is_cheaper;
                            break;
                        }
                    }
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html( $competitor->name ); ?></strong>
                    </td>
                    <td>
                        <input type="number" 
                               name="csw_competitor_prices[<?php echo esc_attr( $competitor->id ); ?>]" 
                               value="<?php echo esc_attr( $existing_price ); ?>" 
                               step="0.01" 
                               min="0" 
                               placeholder="0.00"
                               class="short" />
                    </td>
                    <td>
                        <input type="url" 
                               name="csw_competitor_urls[<?php echo esc_attr( $competitor->id ); ?>]" 
                               value="<?php echo esc_attr( $existing_url ); ?>" 
                               placeholder="https://"
                               class="short" />
                    </td>
                    <td>
                        <?php if ( ! empty( $existing_price ) ) : ?>
                            <?php if ( $is_winning ) : ?>
                            <span style="color: #10B981; font-weight: 600;">&#10003; <?php esc_html_e( 'Winning', 'competitor-spy-widget' ); ?></span>
                            <?php else : ?>
                            <span style="color: #EF4444; font-weight: 600;">&#10007; <?php esc_html_e( 'Losing', 'competitor-spy-widget' ); ?></span>
                            <?php endif; ?>
                        <?php else : ?>
                        <span style="color: #9CA3AF;"><?php esc_html_e( 'Not set', 'competitor-spy-widget' ); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="form-field">
            <span class="description">
                <?php esc_html_e( 'Tip: Leave price empty to skip a competitor for this product.', 'competitor-spy-widget' ); ?>
            </span>
        </p>

        <?php endif; ?>
    </div>
</div>
