<?php
/**
 * Admin Dashboard Notifications.
 *
 * Shows real-time notifications in the admin bar and dashboard
 * for price changes, alerts, and optimization opportunities.
 *
 * @package CompetitorSpyWidget
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CSW_Notifications
 */
class CSW_Notifications {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_notification' ), 100 );
        add_action( 'wp_ajax_csw_get_notifications', array( $this, 'ajax_get_notifications' ) );
        add_action( 'wp_ajax_csw_dismiss_notification', array( $this, 'ajax_dismiss_notification' ) );
        add_action( 'wp_ajax_csw_mark_all_read', array( $this, 'ajax_mark_all_read' ) );
    }

    /**
     * Add notification bell to admin bar.
     *
     * @param WP_Admin_Bar $admin_bar Admin bar instance.
     */
    public function add_admin_bar_notification( $admin_bar ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $unread = self::get_unread_count();

        $title = '<span class="csw-admin-bar-icon">';
        $title .= '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>';
        if ( $unread > 0 ) {
            $title .= '<span class="csw-notif-badge" style="background:#EF4444;color:white;font-size:10px;padding:1px 5px;border-radius:10px;margin-left:3px;font-weight:700;">' . esc_html( $unread ) . '</span>';
        }
        $title .= '</span>';

        $admin_bar->add_node( array(
            'id'    => 'csw-notifications',
            'title' => $title,
            'href'  => admin_url( 'admin.php?page=competitor-spy-widget' ),
            'meta'  => array(
                'title' => sprintf(
                    /* translators: %d: notification count */
                    __( 'Competitor Spy: %d alerts', 'competitor-spy-widget' ),
                    $unread
                ),
            ),
        ) );
    }

    /**
     * Create a notification.
     *
     * @param array $data Notification data.
     * @return bool
     */
    public static function create( $data ) {
        $notifications = get_option( 'csw_notifications', array() );

        $notification = array(
            'id'         => 'csw_' . time() . '_' . wp_rand( 100, 999 ),
            'type'       => isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : 'info',
            'title'      => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
            'message'    => isset( $data['message'] ) ? sanitize_text_field( $data['message'] ) : '',
            'product_id' => isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0,
            'link'       => isset( $data['link'] ) ? esc_url_raw( $data['link'] ) : '',
            'read'       => false,
            'created_at' => current_time( 'mysql' ),
        );

        // Prepend (newest first)
        array_unshift( $notifications, $notification );

        // Keep only last 50
        $notifications = array_slice( $notifications, 0, 50 );

        update_option( 'csw_notifications', $notifications );

        return true;
    }

    /**
     * Get all notifications.
     *
     * @param int $limit Limit results.
     * @return array
     */
    public static function get_all( $limit = 20 ) {
        $notifications = get_option( 'csw_notifications', array() );
        return array_slice( $notifications, 0, $limit );
    }

    /**
     * Get unread notification count.
     *
     * @return int
     */
    public static function get_unread_count() {
        $notifications = get_option( 'csw_notifications', array() );
        $unread = 0;

        foreach ( $notifications as $n ) {
            if ( empty( $n['read'] ) ) {
                $unread++;
            }
        }

        return $unread;
    }

    /**
     * Mark a notification as read.
     *
     * @param string $notification_id Notification ID.
     * @return bool
     */
    public static function mark_read( $notification_id ) {
        $notifications = get_option( 'csw_notifications', array() );

        foreach ( $notifications as &$n ) {
            if ( $n['id'] === $notification_id ) {
                $n['read'] = true;
                break;
            }
        }

        update_option( 'csw_notifications', $notifications );
        return true;
    }

    /**
     * Mark all notifications as read.
     *
     * @return bool
     */
    public static function mark_all_read() {
        $notifications = get_option( 'csw_notifications', array() );

        foreach ( $notifications as &$n ) {
            $n['read'] = true;
        }

        update_option( 'csw_notifications', $notifications );
        return true;
    }

    /**
     * Dismiss (delete) a notification.
     *
     * @param string $notification_id Notification ID.
     * @return bool
     */
    public static function dismiss( $notification_id ) {
        $notifications = get_option( 'csw_notifications', array() );

        $notifications = array_filter( $notifications, function( $n ) use ( $notification_id ) {
            return $n['id'] !== $notification_id;
        } );

        update_option( 'csw_notifications', array_values( $notifications ) );
        return true;
    }

    /**
     * Create price change notifications (called from cron).
     *
     * @param array $changes Price changes data.
     */
    public static function create_price_alerts( $changes ) {
        foreach ( $changes as $change ) {
            $type = 'lost' === $change['status'] ? 'price_lost' : 'price_won';
            $icon = 'lost' === $change['status'] ? '🔴' : '🟢';

            if ( 'lost' === $change['status'] ) {
                $title = sprintf(
                    /* translators: %s: competitor name */
                    __( '%s is now cheaper!', 'competitor-spy-widget' ),
                    $change['competitor_name']
                );
                $message = sprintf(
                    /* translators: 1: product name, 2: new price, 3: our price */
                    __( '%1$s dropped to %2$s (you: %3$s). Widget auto-hidden.', 'competitor-spy-widget' ),
                    $change['product_name'],
                    wp_strip_all_tags( wc_price( $change['new_price'] ) ),
                    wp_strip_all_tags( wc_price( $change['our_price'] ) )
                );
            } else {
                $title = sprintf(
                    /* translators: %s: product name */
                    __( 'You\'re winning on %s!', 'competitor-spy-widget' ),
                    $change['product_name']
                );
                $message = sprintf(
                    /* translators: 1: competitor name, 2: new price */
                    __( '%1$s raised to %2$s. Widget is now showing.', 'competitor-spy-widget' ),
                    $change['competitor_name'],
                    wp_strip_all_tags( wc_price( $change['new_price'] ) )
                );
            }

            self::create( array(
                'type'       => $type,
                'title'      => $icon . ' ' . $title,
                'message'    => $message,
                'product_id' => $change['product_id'],
                'link'       => get_edit_post_link( $change['product_id'], 'raw' ),
            ) );
        }
    }

    /**
     * AJAX: Get notifications.
     */
    public function ajax_get_notifications() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error();
        }

        wp_send_json_success( array(
            'notifications' => self::get_all( 20 ),
            'unread_count'  => self::get_unread_count(),
        ) );
    }

    /**
     * AJAX: Dismiss notification.
     */
    public function ajax_dismiss_notification() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error();
        }

        $id = isset( $_POST['notification_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notification_id'] ) ) : '';
        self::dismiss( $id );

        wp_send_json_success();
    }

    /**
     * AJAX: Mark all read.
     */
    public function ajax_mark_all_read() {
        check_ajax_referer( 'csw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error();
        }

        self::mark_all_read();
        wp_send_json_success();
    }
}

// Initialize
new CSW_Notifications();
