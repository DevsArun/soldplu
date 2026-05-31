<?php
/**
 * Automated Price Fetcher.
 *
 * Fetches competitor product prices from URLs automatically using
 * multiple extraction strategies: JSON-LD, Open Graph, meta tags,
 * and site-specific CSS patterns.
 *
 * @package CompetitorSpyWidget
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Price_Fetcher
 */
class CSW_Price_Fetcher {

    /**
     * User agents to rotate for requests.
     *
     * @var array
     */
    private static $user_agents = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    );

    /**
     * Fetch price from a competitor product URL.
     *
     * @param string $url         The competitor product page URL.
     * @param string $site_hint   Optional site hint (amazon, flipkart, walmart, etc.).
     * @return array|WP_Error     Array with 'price', 'currency', 'title', 'method' or WP_Error.
     */
    public static function fetch_price( $url, $site_hint = '' ) {
        if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return new WP_Error( 'invalid_url', __( 'Please provide a valid URL.', 'competitor-spy-widget' ) );
        }

        // Detect site from URL if no hint
        if ( empty( $site_hint ) ) {
            $site_hint = self::detect_site( $url );
        }

        // Fetch the page HTML
        $html = self::fetch_page( $url );
        if ( is_wp_error( $html ) ) {
            return $html;
        }

        // Try multiple extraction strategies in order of reliability
        $price = null;
        $method = '';
        $title = '';

        // Strategy 1: JSON-LD Structured Data (most reliable)
        $result = self::extract_from_json_ld( $html );
        if ( $result ) {
            $price = $result['price'];
            $title = isset( $result['title'] ) ? $result['title'] : '';
            $method = 'json_ld';
        }

        // Strategy 2: Open Graph / Meta tags
        if ( ! $price ) {
            $result = self::extract_from_meta_tags( $html );
            if ( $result ) {
                $price = $result['price'];
                $title = isset( $result['title'] ) ? $result['title'] : '';
                $method = 'meta_tags';
            }
        }

        // Strategy 3: Site-specific patterns
        if ( ! $price ) {
            $result = self::extract_site_specific( $html, $site_hint );
            if ( $result ) {
                $price = $result['price'];
                $title = isset( $result['title'] ) ? $result['title'] : '';
                $method = 'site_specific';
            }
        }

        // Strategy 4: Generic price pattern detection
        if ( ! $price ) {
            $result = self::extract_generic_price( $html );
            if ( $result ) {
                $price = $result['price'];
                $method = 'generic_pattern';
            }
        }

        if ( ! $price || $price <= 0 ) {
            return new WP_Error(
                'price_not_found',
                __( 'Could not extract price from this URL. The page might be blocked or use a format we cannot read. You can enter the price manually.', 'competitor-spy-widget' )
            );
        }

        // Get page title if not already found
        if ( empty( $title ) ) {
            if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $title_match ) ) {
                $title = trim( html_entity_decode( strip_tags( $title_match[1] ), ENT_QUOTES, 'UTF-8' ) );
                $title = mb_substr( $title, 0, 200 );
            }
        }

        return array(
            'price'    => round( (float) $price, 2 ),
            'currency' => self::detect_currency( $html, $url ),
            'title'    => sanitize_text_field( $title ),
            'method'   => $method,
            'site'     => $site_hint,
            'fetched'  => current_time( 'mysql' ),
        );
    }

    /**
     * Fetch the HTML of a page.
     *
     * @param string $url URL to fetch.
     * @return string|WP_Error HTML content or error.
     */
    private static function fetch_page( $url ) {
        $user_agent = self::$user_agents[ array_rand( self::$user_agents ) ];

        $args = array(
            'timeout'     => 20,
            'redirection' => 3,
            'httpversion' => '1.1',
            'user-agent'  => $user_agent,
            'headers'     => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'Cache-Control'   => 'no-cache',
                'Connection'      => 'keep-alive',
            ),
            'sslverify'   => true,
        );

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'fetch_failed',
                sprintf(
                    /* translators: %s: error message */
                    __( 'Failed to fetch URL: %s', 'competitor-spy-widget' ),
                    $response->get_error_message()
                )
            );
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status >= 400 ) {
            return new WP_Error(
                'http_error',
                sprintf(
                    /* translators: %d: HTTP status code */
                    __( 'Page returned error status %d. The site may be blocking automated requests.', 'competitor-spy-widget' ),
                    $status
                )
            );
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return new WP_Error( 'empty_response', __( 'The page returned empty content.', 'competitor-spy-widget' ) );
        }

        return $body;
    }

    /**
     * Extract price from JSON-LD structured data.
     *
     * @param string $html Page HTML.
     * @return array|false Array with price and title, or false.
     */
    private static function extract_from_json_ld( $html ) {
        // Find all JSON-LD scripts
        if ( ! preg_match_all( '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches ) ) {
            return false;
        }

        foreach ( $matches[1] as $json_str ) {
            $json_str = trim( $json_str );
            $data = json_decode( $json_str, true );

            if ( ! $data ) {
                continue;
            }

            // Handle @graph array
            if ( isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
                foreach ( $data['@graph'] as $item ) {
                    $result = self::extract_price_from_ld_item( $item );
                    if ( $result ) {
                        return $result;
                    }
                }
            }

            // Handle direct Product type
            $result = self::extract_price_from_ld_item( $data );
            if ( $result ) {
                return $result;
            }

            // Handle array of items
            if ( isset( $data[0] ) && is_array( $data[0] ) ) {
                foreach ( $data as $item ) {
                    $result = self::extract_price_from_ld_item( $item );
                    if ( $result ) {
                        return $result;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Extract price from a single JSON-LD item.
     *
     * @param array $item JSON-LD item.
     * @return array|false
     */
    private static function extract_price_from_ld_item( $item ) {
        if ( ! is_array( $item ) ) {
            return false;
        }

        $type = isset( $item['@type'] ) ? $item['@type'] : '';

        if ( ! in_array( $type, array( 'Product', 'IndividualProduct', 'ProductGroup' ), true ) ) {
            return false;
        }

        $price = null;
        $title = isset( $item['name'] ) ? $item['name'] : '';

        // Check offers
        if ( isset( $item['offers'] ) ) {
            $offers = $item['offers'];

            // Single offer
            if ( isset( $offers['price'] ) ) {
                $price = self::clean_price( $offers['price'] );
            } elseif ( isset( $offers['lowPrice'] ) ) {
                $price = self::clean_price( $offers['lowPrice'] );
            }

            // Array of offers
            if ( ! $price && isset( $offers[0] ) ) {
                foreach ( $offers as $offer ) {
                    if ( isset( $offer['price'] ) ) {
                        $price = self::clean_price( $offer['price'] );
                        break;
                    }
                }
            }

            // AggregateOffer
            if ( ! $price && isset( $offers['@type'] ) && 'AggregateOffer' === $offers['@type'] ) {
                if ( isset( $offers['lowPrice'] ) ) {
                    $price = self::clean_price( $offers['lowPrice'] );
                } elseif ( isset( $offers['price'] ) ) {
                    $price = self::clean_price( $offers['price'] );
                }
            }
        }

        if ( $price && $price > 0 ) {
            return array( 'price' => $price, 'title' => $title );
        }

        return false;
    }

    /**
     * Extract price from meta tags (Open Graph, etc).
     *
     * @param string $html Page HTML.
     * @return array|false
     */
    private static function extract_from_meta_tags( $html ) {
        $price = null;
        $title = '';

        // og:price:amount
        if ( preg_match( '/property=["\']og:price:amount["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m ) ) {
            $price = self::clean_price( $m[1] );
        }
        // product:price:amount
        if ( ! $price && preg_match( '/property=["\']product:price:amount["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m ) ) {
            $price = self::clean_price( $m[1] );
        }
        // twitter price
        if ( ! $price && preg_match( '/name=["\']twitter:data1["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m ) ) {
            $price = self::clean_price( $m[1] );
        }

        // og:title
        if ( preg_match( '/property=["\']og:title["\'][^>]*content=["\']([^"\']+)["\']/i', $html, $m ) ) {
            $title = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
        }

        if ( $price && $price > 0 ) {
            return array( 'price' => $price, 'title' => $title );
        }

        return false;
    }

    /**
     * Extract price using site-specific patterns.
     *
     * @param string $html      Page HTML.
     * @param string $site_hint Site identifier.
     * @return array|false
     */
    private static function extract_site_specific( $html, $site_hint ) {
        $patterns = self::get_site_patterns( $site_hint );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $html, $m ) ) {
                $price = self::clean_price( isset( $m[1] ) ? $m[1] : '' );
                if ( $price && $price > 0 ) {
                    return array( 'price' => $price, 'title' => '' );
                }
            }
        }

        return false;
    }

    /**
     * Get regex patterns for specific sites.
     *
     * @param string $site Site identifier.
     * @return array Array of regex patterns.
     */
    private static function get_site_patterns( $site ) {
        $patterns = array(
            'amazon' => array(
                '/id=["\']priceblock_ourprice["\'][^>]*>[\s]*[^\d]*([0-9,\.]+)/i',
                '/id=["\']priceblock_dealprice["\'][^>]*>[\s]*[^\d]*([0-9,\.]+)/i',
                '/class=["\'][^"\']*a-price-whole[^"\']*["\'][^>]*>([0-9,\.]+)/i',
                '/class=["\'][^"\']*priceToPay[^"\']*["\'][^>]*>.*?([0-9,\.]+)/is',
                '/"priceAmount"\s*:\s*"?([0-9,\.]+)"?/i',
                '/data-a-color=["\']price["\'].*?([0-9,\.]+)/is',
            ),
            'flipkart' => array(
                '/class=["\'][^"\']*_30jeq3[^"\']*["\'][^>]*>[^\d]*([0-9,\.]+)/i',
                '/class=["\'][^"\']*_16Jk6d[^"\']*["\'][^>]*>[^\d]*([0-9,\.]+)/i',
                '/class=["\'][^"\']*CEmiEU[^"\']*["\'][^>]*>.*?([0-9,\.]+)/is',
                '/"selling_price"\s*:\s*([0-9,\.]+)/i',
                '/payable.*?([0-9,]+)/is',
            ),
            'walmart' => array(
                '/itemprop=["\']price["\'][^>]*content=["\']([0-9,\.]+)["\']/i',
                '/class=["\'][^"\']*price-characteristic[^"\']*["\'][^>]*content=["\']([0-9,\.]+)["\']/i',
                '/"currentPrice"\s*:\s*\{[^}]*"price"\s*:\s*([0-9,\.]+)/i',
                '/data-automation=["\']buybox-price["\'].*?([0-9,\.]+)/is',
            ),
            'ebay' => array(
                '/id=["\']prcIsum["\'][^>]*>.*?([0-9,\.]+)/is',
                '/class=["\'][^"\']*x-price-primary[^"\']*["\'][^>]*>.*?([0-9,\.]+)/is',
                '/"price"\s*:\s*"([0-9,\.]+)"/i',
                '/itemprop=["\']price["\'][^>]*content=["\']([0-9,\.]+)["\']/i',
            ),
            'target' => array(
                '/"current_retail"\s*:\s*([0-9,\.]+)/i',
                '/data-test=["\']product-price["\'][^>]*>.*?([0-9,\.]+)/is',
                '/"price"\s*:\s*\{[^}]*"currentRetail"\s*:\s*([0-9,\.]+)/i',
            ),
            'bestbuy' => array(
                '/class=["\'][^"\']*priceView-customer-price[^"\']*["\'][^>]*>.*?([0-9,\.]+)/is',
                '/"currentPrice"\s*:\s*([0-9,\.]+)/i',
                '/data-testid=["\']customer-price["\'].*?([0-9,\.]+)/is',
            ),
            'myntra' => array(
                '/class=["\'][^"\']*pdp-price[^"\']*["\'][^>]*>.*?([0-9,\.]+)/is',
                '/"discountedPrice"\s*:\s*([0-9,\.]+)/i',
                '/"price"\s*:\s*([0-9,\.]+)/i',
            ),
            'snapdeal' => array(
                '/class=["\'][^"\']*payBlkBig[^"\']*["\'][^>]*>.*?([0-9,\.]+)/is',
                '/"selling_price"\s*:\s*"?([0-9,\.]+)"?/i',
            ),
            'aliexpress' => array(
                '/"formattedActivityPrice"\s*:\s*"[^0-9]*([0-9,\.]+)"/i',
                '/"minPrice"\s*:\s*"([0-9,\.]+)"/i',
                '/class=["\'][^"\']*product-price-current[^"\']*["\'][^>]*>.*?([0-9,\.]+)/is',
            ),
        );

        // Return site-specific patterns or generic e-commerce patterns
        if ( isset( $patterns[ $site ] ) ) {
            return $patterns[ $site ];
        }

        // Generic e-commerce patterns (works for many sites)
        return array(
            '/itemprop=["\']price["\'][^>]*content=["\']([0-9,\.]+)["\']/i',
            '/class=["\'][^"\']*price[^"\']*["\'][^>]*>[^\d]*([0-9,\.]+)/i',
            '/id=["\'][^"\']*price[^"\']*["\'][^>]*>[^\d]*([0-9,\.]+)/i',
            '/data-price=["\']([0-9,\.]+)["\']/i',
        );
    }

    /**
     * Generic price extraction as last resort.
     *
     * @param string $html Page HTML.
     * @return array|false
     */
    private static function extract_generic_price( $html ) {
        // Look for common price patterns with currency symbols
        $currency_patterns = array(
            '/[\$£€₹]\s*([0-9,]+\.?\d{0,2})/',           // $99.99, ₹599, €45.00
            '/([0-9,]+\.?\d{0,2})\s*[\$£€₹]/',           // 99.99$, 599₹
            '/(?:Rs|INR|USD|GBP|EUR)\.?\s*([0-9,]+\.?\d{0,2})/i', // Rs. 599, INR 599
            '/price["\']?\s*[:=]\s*["\']?([0-9,]+\.?\d{0,2})/i',  // price: 599
        );

        foreach ( $currency_patterns as $pattern ) {
            if ( preg_match_all( $pattern, $html, $matches ) ) {
                // Pick the most reasonable price (not too small, not too large)
                foreach ( $matches[1] as $match ) {
                    $price = self::clean_price( $match );
                    if ( $price > 0.5 && $price < 1000000 ) {
                        return array( 'price' => $price, 'title' => '' );
                    }
                }
            }
        }

        return false;
    }

    /**
     * Clean and normalize a price string to float.
     *
     * @param mixed $price_str Price string to clean.
     * @return float
     */
    public static function clean_price( $price_str ) {
        if ( is_numeric( $price_str ) ) {
            return (float) $price_str;
        }

        $price_str = (string) $price_str;

        // Remove currency symbols and spaces
        $price_str = preg_replace( '/[^\d,\.]/', '', $price_str );

        if ( empty( $price_str ) ) {
            return 0;
        }

        // Handle Indian format: 1,23,456.78 or 12,345.78
        // Handle European format: 1.234,56
        $last_comma = strrpos( $price_str, ',' );
        $last_dot = strrpos( $price_str, '.' );

        if ( false !== $last_comma && false !== $last_dot ) {
            if ( $last_comma > $last_dot ) {
                // European: 1.234,56 → comma is decimal
                $price_str = str_replace( '.', '', $price_str );
                $price_str = str_replace( ',', '.', $price_str );
            } else {
                // US/India: 1,234.56 → dot is decimal
                $price_str = str_replace( ',', '', $price_str );
            }
        } elseif ( false !== $last_comma ) {
            // Only comma: could be decimal or thousands
            $after_comma = substr( $price_str, $last_comma + 1 );
            if ( strlen( $after_comma ) === 2 ) {
                // Likely decimal: 123,45
                $price_str = str_replace( ',', '.', $price_str );
            } else {
                // Likely thousands: 1,234 or 12,345
                $price_str = str_replace( ',', '', $price_str );
            }
        }

        return (float) $price_str;
    }

    /**
     * Detect the site/platform from URL.
     *
     * @param string $url URL to detect.
     * @return string Site identifier.
     */
    public static function detect_site( $url ) {
        $host = wp_parse_url( $url, PHP_URL_HOST );
        if ( ! $host ) {
            return 'unknown';
        }

        $host = strtolower( $host );

        $site_map = array(
            'amazon'      => array( 'amazon.com', 'amazon.in', 'amazon.co.uk', 'amazon.de', 'amazon.ca', 'amazon.co.jp', 'amzn.to' ),
            'flipkart'    => array( 'flipkart.com', 'dl.flipkart.com' ),
            'walmart'     => array( 'walmart.com', 'walmart.ca' ),
            'ebay'        => array( 'ebay.com', 'ebay.co.uk', 'ebay.de', 'ebay.in' ),
            'target'      => array( 'target.com' ),
            'bestbuy'     => array( 'bestbuy.com', 'bestbuy.ca' ),
            'myntra'      => array( 'myntra.com' ),
            'snapdeal'    => array( 'snapdeal.com' ),
            'aliexpress'  => array( 'aliexpress.com', 'aliexpress.us' ),
            'etsy'        => array( 'etsy.com' ),
            'shopify'     => array( 'myshopify.com' ),
        );

        foreach ( $site_map as $site => $domains ) {
            foreach ( $domains as $domain ) {
                if ( false !== strpos( $host, $domain ) ) {
                    return $site;
                }
            }
        }

        return 'generic';
    }

    /**
     * Detect currency from page content.
     *
     * @param string $html Page HTML.
     * @param string $url  Page URL.
     * @return string Currency code.
     */
    private static function detect_currency( $html, $url ) {
        // Check JSON-LD for currency
        if ( preg_match( '/"priceCurrency"\s*:\s*"([A-Z]{3})"/i', $html, $m ) ) {
            return strtoupper( $m[1] );
        }

        // Check meta tag
        if ( preg_match( '/property=["\']og:price:currency["\'][^>]*content=["\']([A-Z]{3})["\']/i', $html, $m ) ) {
            return strtoupper( $m[1] );
        }

        // Guess from URL domain
        $host = wp_parse_url( $url, PHP_URL_HOST );
        if ( $host ) {
            if ( strpos( $host, '.in' ) !== false || strpos( $host, 'flipkart' ) !== false || strpos( $host, 'myntra' ) !== false ) {
                return 'INR';
            }
            if ( strpos( $host, '.co.uk' ) !== false ) {
                return 'GBP';
            }
            if ( strpos( $host, '.de' ) !== false || strpos( $host, '.fr' ) !== false ) {
                return 'EUR';
            }
        }

        // Check for currency symbols in HTML
        if ( preg_match( '/₹/', $html ) ) {
            return 'INR';
        }
        if ( preg_match( '/£/', $html ) ) {
            return 'GBP';
        }
        if ( preg_match( '/€/', $html ) ) {
            return 'EUR';
        }

        return 'USD';
    }

    /**
     * Batch fetch prices for multiple URLs.
     *
     * @param array $urls Array of arrays with 'url' and optional 'site_hint'.
     * @return array Results indexed by URL.
     */
    public static function batch_fetch( $urls ) {
        $results = array();

        foreach ( $urls as $item ) {
            $url = is_array( $item ) ? $item['url'] : $item;
            $hint = is_array( $item ) && isset( $item['site_hint'] ) ? $item['site_hint'] : '';

            $results[ $url ] = self::fetch_price( $url, $hint );

            // Small delay between requests to be polite
            usleep( 500000 ); // 0.5 seconds
        }

        return $results;
    }

    /**
     * Check if a URL is fetchable (basic validation).
     *
     * @param string $url URL to validate.
     * @return bool
     */
    public static function is_fetchable_url( $url ) {
        if ( empty( $url ) ) {
            return false;
        }

        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return false;
        }

        $scheme = wp_parse_url( $url, PHP_URL_SCHEME );
        if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
            return false;
        }

        return true;
    }

    /**
     * Get human-readable site name from URL.
     *
     * @param string $url URL.
     * @return string Site display name.
     */
    public static function get_site_display_name( $url ) {
        $site = self::detect_site( $url );

        $names = array(
            'amazon'     => 'Amazon',
            'flipkart'   => 'Flipkart',
            'walmart'    => 'Walmart',
            'ebay'       => 'eBay',
            'target'     => 'Target',
            'bestbuy'    => 'Best Buy',
            'myntra'     => 'Myntra',
            'snapdeal'   => 'Snapdeal',
            'aliexpress' => 'AliExpress',
            'etsy'       => 'Etsy',
        );

        if ( isset( $names[ $site ] ) ) {
            return $names[ $site ];
        }

        // Return domain name
        $host = wp_parse_url( $url, PHP_URL_HOST );
        return $host ? ucfirst( str_replace( 'www.', '', $host ) ) : __( 'Competitor', 'competitor-spy-widget' );
    }
}
