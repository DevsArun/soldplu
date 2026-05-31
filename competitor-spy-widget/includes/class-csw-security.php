<?php
/**
 * Security layer for the plugin.
 *
 * Handles all security-related functionality including input validation,
 * capability checks, rate limiting, and data encryption.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Security
 */
class CSW_Security {

    /**
     * Required capabilities for admin actions.
     *
     * @var array
     */
    private static $capabilities = array(
        'manage_competitors' => 'manage_woocommerce',
        'manage_prices'      => 'manage_woocommerce',
        'view_analytics'     => 'manage_woocommerce',
        'manage_settings'    => 'manage_options',
        'export_data'        => 'manage_woocommerce',
    );

    /**
     * Check if current user has required capability.
     *
     * @param string $action Action to check.
     * @return bool
     */
    public static function can( $action ) {
        $capability = isset( self::$capabilities[ $action ] ) ? self::$capabilities[ $action ] : 'manage_options';
        return current_user_can( $capability );
    }

    /**
     * Verify nonce for admin actions.
     *
     * @param string $nonce_field  Nonce field name.
     * @param string $nonce_action Nonce action.
     * @return bool
     */
    public static function verify_nonce( $nonce_field = '_csw_nonce', $nonce_action = 'csw_admin_action' ) {
        $nonce = '';

        if ( isset( $_REQUEST[ $nonce_field ] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_field ] ) );
        }

        return wp_verify_nonce( $nonce, $nonce_action );
    }

    /**
     * Sanitize and validate input data.
     *
     * @param array $data   Raw input data.
     * @param array $schema Validation schema.
     * @return array|WP_Error Sanitized data or error.
     */
    public static function validate_input( $data, $schema ) {
        $sanitized = array();
        $errors = array();

        foreach ( $schema as $field => $rules ) {
            $value = isset( $data[ $field ] ) ? $data[ $field ] : null;

            // Required check
            if ( ! empty( $rules['required'] ) && ( null === $value || '' === $value ) ) {
                $errors[] = sprintf(
                    /* translators: %s: field name */
                    __( 'Field "%s" is required.', 'competitor-spy-widget' ),
                    $field
                );
                continue;
            }

            if ( null === $value ) {
                $sanitized[ $field ] = isset( $rules['default'] ) ? $rules['default'] : null;
                continue;
            }

            // Type validation and sanitization
            switch ( $rules['type'] ) {
                case 'string':
                    $sanitized[ $field ] = sanitize_text_field( $value );
                    if ( ! empty( $rules['max_length'] ) && strlen( $sanitized[ $field ] ) > $rules['max_length'] ) {
                        $sanitized[ $field ] = substr( $sanitized[ $field ], 0, $rules['max_length'] );
                    }
                    break;

                case 'url':
                    $sanitized[ $field ] = esc_url_raw( $value );
                    if ( ! empty( $value ) && ! filter_var( $sanitized[ $field ], FILTER_VALIDATE_URL ) ) {
                        $errors[] = sprintf(
                            /* translators: %s: field name */
                            __( 'Field "%s" must be a valid URL.', 'competitor-spy-widget' ),
                            $field
                        );
                    }
                    break;

                case 'email':
                    $sanitized[ $field ] = sanitize_email( $value );
                    if ( ! is_email( $sanitized[ $field ] ) ) {
                        $errors[] = sprintf(
                            /* translators: %s: field name */
                            __( 'Field "%s" must be a valid email.', 'competitor-spy-widget' ),
                            $field
                        );
                    }
                    break;

                case 'integer':
                    $sanitized[ $field ] = absint( $value );
                    if ( ! empty( $rules['min'] ) && $sanitized[ $field ] < $rules['min'] ) {
                        $sanitized[ $field ] = $rules['min'];
                    }
                    if ( ! empty( $rules['max'] ) && $sanitized[ $field ] > $rules['max'] ) {
                        $sanitized[ $field ] = $rules['max'];
                    }
                    break;

                case 'float':
                    $sanitized[ $field ] = floatval( $value );
                    if ( ! empty( $rules['min'] ) && $sanitized[ $field ] < $rules['min'] ) {
                        $sanitized[ $field ] = $rules['min'];
                    }
                    if ( ! empty( $rules['max'] ) && $sanitized[ $field ] > $rules['max'] ) {
                        $sanitized[ $field ] = $rules['max'];
                    }
                    break;

                case 'boolean':
                    $sanitized[ $field ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? 1 : 0;
                    break;

                case 'select':
                    $sanitized[ $field ] = sanitize_text_field( $value );
                    if ( ! empty( $rules['options'] ) && ! in_array( $sanitized[ $field ], $rules['options'], true ) ) {
                        $sanitized[ $field ] = isset( $rules['default'] ) ? $rules['default'] : $rules['options'][0];
                    }
                    break;

                case 'array':
                    if ( is_array( $value ) ) {
                        $sanitized[ $field ] = array_map( 'sanitize_text_field', $value );
                    } else {
                        $sanitized[ $field ] = array();
                    }
                    break;

                case 'html':
                    $sanitized[ $field ] = wp_kses_post( $value );
                    break;

                case 'css':
                    $sanitized[ $field ] = wp_strip_all_tags( $value );
                    break;

                default:
                    $sanitized[ $field ] = sanitize_text_field( $value );
                    break;
            }
        }

        if ( ! empty( $errors ) ) {
            return new WP_Error( 'validation_error', implode( ' ', $errors ), $errors );
        }

        return $sanitized;
    }

    /**
     * Rate limiting check.
     *
     * @param string $action    Action identifier.
     * @param int    $limit     Maximum number of requests.
     * @param int    $window    Time window in seconds.
     * @param string $identifier Unique identifier (IP hash, user ID, etc).
     * @return bool True if within rate limit.
     */
    public static function rate_limit_check( $action, $limit = 60, $window = 60, $identifier = '' ) {
        if ( empty( $identifier ) ) {
            $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
            $identifier = md5( $ip );
        }

        $transient_key = 'csw_rate_' . md5( $action . $identifier );
        $current = get_transient( $transient_key );

        if ( false === $current ) {
            set_transient( $transient_key, 1, $window );
            return true;
        }

        if ( (int) $current >= $limit ) {
            return false;
        }

        set_transient( $transient_key, (int) $current + 1, $window );
        return true;
    }

    /**
     * Encrypt sensitive data.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data.
     */
    public static function encrypt( $data ) {
        if ( empty( $data ) ) {
            return '';
        }

        $key = self::get_encryption_key();

        if ( function_exists( 'openssl_encrypt' ) ) {
            $iv = openssl_random_pseudo_bytes( 16 );
            $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
            return base64_encode( $iv . $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        }

        // Fallback: base64 with salt (not true encryption)
        return base64_encode( $key . '|' . $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
    }

    /**
     * Decrypt sensitive data.
     *
     * @param string $data Encrypted data.
     * @return string Decrypted data.
     */
    public static function decrypt( $data ) {
        if ( empty( $data ) ) {
            return '';
        }

        $key = self::get_encryption_key();

        if ( function_exists( 'openssl_decrypt' ) ) {
            $decoded = base64_decode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
            $iv = substr( $decoded, 0, 16 );
            $encrypted = substr( $decoded, 16 );
            return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
        }

        // Fallback
        $decoded = base64_decode( $data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $parts = explode( '|', $decoded, 2 );
        return isset( $parts[1] ) ? $parts[1] : '';
    }

    /**
     * Get encryption key.
     *
     * @return string
     */
    private static function get_encryption_key() {
        if ( defined( 'CSW_ENCRYPTION_KEY' ) ) {
            return CSW_ENCRYPTION_KEY;
        }

        return hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY );
    }

    /**
     * Sanitize output for safe display.
     *
     * @param string $content Content to sanitize.
     * @param string $context Context (html, attr, url, js).
     * @return string
     */
    public static function escape_output( $content, $context = 'html' ) {
        switch ( $context ) {
            case 'attr':
                return esc_attr( $content );
            case 'url':
                return esc_url( $content );
            case 'js':
                return esc_js( $content );
            case 'html':
            default:
                return esc_html( $content );
        }
    }

    /**
     * Check admin referer with custom action.
     *
     * @param string $action Nonce action.
     * @param string $field  Nonce field name.
     */
    public static function check_admin_referer( $action, $field = '_csw_nonce' ) {
        if ( ! self::verify_nonce( $field, $action ) ) {
            wp_die(
                esc_html__( 'Security check failed. Please try again.', 'competitor-spy-widget' ),
                esc_html__( 'Security Error', 'competitor-spy-widget' ),
                array( 'response' => 403, 'back_link' => true )
            );
        }
    }
}
