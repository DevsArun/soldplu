<?php
/**
 * Onboarding wizard handler.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Onboarding
 */
class CSW_Onboarding {

    /**
     * Get onboarding steps.
     *
     * @return array
     */
    public static function get_steps() {
        return array(
            array(
                'id'          => 'welcome',
                'title'       => __( 'Welcome to Competitor Spy Widget', 'competitor-spy-widget' ),
                'description' => __( 'Let\'s set up your price comparison widget in under 2 minutes. Your customers will never leave to check competitor prices again.', 'competitor-spy-widget' ),
            ),
            array(
                'id'          => 'competitors',
                'title'       => __( 'Add Your Competitors', 'competitor-spy-widget' ),
                'description' => __( 'Select the competitors you want to compare prices against. You can always add more later.', 'competitor-spy-widget' ),
            ),
            array(
                'id'          => 'display',
                'title'       => __( 'Configure Display', 'competitor-spy-widget' ),
                'description' => __( 'Choose how and where the price comparison widget appears on your product pages.', 'competitor-spy-widget' ),
            ),
            array(
                'id'          => 'complete',
                'title'       => __( 'You\'re All Set!', 'competitor-spy-widget' ),
                'description' => __( 'Your competitor spy widget is ready. Start adding competitor prices to your products and watch conversions grow.', 'competitor-spy-widget' ),
            ),
        );
    }

    /**
     * Get competitor templates for onboarding.
     *
     * @return array
     */
    public static function get_competitor_options() {
        return array(
            array(
                'key'         => 'amazon',
                'name'        => 'Amazon',
                'icon'        => CSW_PLUGIN_URL . 'public/assets/images/competitors/amazon.svg',
                'description' => __( 'World\'s largest online marketplace', 'competitor-spy-widget' ),
            ),
            array(
                'key'         => 'walmart',
                'name'        => 'Walmart',
                'icon'        => CSW_PLUGIN_URL . 'public/assets/images/competitors/walmart.svg',
                'description' => __( 'Major retail chain', 'competitor-spy-widget' ),
            ),
            array(
                'key'         => 'ebay',
                'name'        => 'eBay',
                'icon'        => CSW_PLUGIN_URL . 'public/assets/images/competitors/ebay.svg',
                'description' => __( 'Online auction and shopping', 'competitor-spy-widget' ),
            ),
            array(
                'key'         => 'target',
                'name'        => 'Target',
                'icon'        => CSW_PLUGIN_URL . 'public/assets/images/competitors/target.svg',
                'description' => __( 'General merchandise retailer', 'competitor-spy-widget' ),
            ),
            array(
                'key'         => 'bestbuy',
                'name'        => 'Best Buy',
                'icon'        => CSW_PLUGIN_URL . 'public/assets/images/competitors/bestbuy.svg',
                'description' => __( 'Electronics retailer', 'competitor-spy-widget' ),
            ),
        );
    }

    /**
     * Get display position options.
     *
     * @return array
     */
    public static function get_position_options() {
        return array(
            'after_price' => array(
                'label'       => __( 'After Price', 'competitor-spy-widget' ),
                'description' => __( 'Show immediately after the product price (Recommended)', 'competitor-spy-widget' ),
                'recommended' => true,
            ),
            'before_price' => array(
                'label'       => __( 'Before Price', 'competitor-spy-widget' ),
                'description' => __( 'Show just before the product price', 'competitor-spy-widget' ),
                'recommended' => false,
            ),
            'after_add_to_cart' => array(
                'label'       => __( 'After Add to Cart', 'competitor-spy-widget' ),
                'description' => __( 'Show below the add to cart button', 'competitor-spy-widget' ),
                'recommended' => false,
            ),
            'after_meta' => array(
                'label'       => __( 'After Product Meta', 'competitor-spy-widget' ),
                'description' => __( 'Show after categories and tags', 'competitor-spy-widget' ),
                'recommended' => false,
            ),
            'after_summary' => array(
                'label'       => __( 'After Product Summary', 'competitor-spy-widget' ),
                'description' => __( 'Show below the entire product summary section', 'competitor-spy-widget' ),
                'recommended' => false,
            ),
        );
    }
}
