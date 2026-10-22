<?php
/**
 * Admin Notices Class
 *
 * Handles all admin dashboard notices for the plugin.
 *
 * @package Unstoppable_COD_Forms
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin Notices Class.
 */
class UCOF_Admin_Notices {

    /**
     * Plugin prefix.
     *
     * @var string
     */
    private $prefix = 'ucof';

    /**
     * Plugin name.
     *
     * @var string
     */
    private $plugin_name = 'Unstoppable COD Order Forms';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_notices', array( $this, 'display_notices' ) );
        add_action( 'admin_notices', array( $this, 'display_flash_notices' ) );
        add_action( 'wp_ajax_' . $this->prefix . '_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Enqueue scripts.
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        // Only load on our pages or dashboard.
        if ( strpos( $screen->id, $this->prefix ) === false &&
             strpos( $screen->id, 'unstoppable-studio' ) === false &&
             $screen->id !== 'dashboard' ) {
            return;
        }

        wp_enqueue_script(
            $this->prefix . '-admin-notices',
            UCOF_PLUGIN_URL . 'assets/js/admin-notices.js',
            array( 'jquery' ),
            UCOF_VERSION,
            true
        );

        wp_localize_script(
            $this->prefix . '-admin-notices',
            $this->prefix . '_notices',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( $this->prefix . '_dismiss_notice' ),
            )
        );
    }

    /**
     * Display notices.
     */
    public function display_notices() {
        $this->maybe_show_welcome_notice();
    }

    /**
     * Welcome notice.
     */
    private function maybe_show_welcome_notice() {
        if ( get_option( $this->prefix . '_welcome_notice_dismissed' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        $allowed = $screen->id === 'dashboard' ||
                   strpos( $screen->id, $this->prefix ) !== false ||
                   strpos( $screen->id, 'unstoppable-studio' ) !== false ||
                   strpos( $screen->id, 'woocommerce' ) !== false;

        if ( ! $allowed ) {
            return;
        }

        ?>
        <div class="notice notice-success is-dismissible <?php echo esc_attr( $this->prefix ); ?>-notice" data-notice="welcome">
            <p>
                <strong><?php echo esc_html( $this->plugin_name ); ?></strong> —
                <?php esc_html_e( 'Thanks for installing! Get started by creating your first COD order form.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard' ) ); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php esc_html_e( 'Get Started', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX dismiss notice.
     */
    public function ajax_dismiss_notice() {
        check_ajax_referer( $this->prefix . '_dismiss_notice', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Permission denied.' );
        }

        $notice = isset( $_POST['notice'] ) ? sanitize_key( $_POST['notice'] ) : '';

        if ( empty( $notice ) ) {
            wp_send_json_error( 'Invalid notice.' );
        }

        update_option( $this->prefix . '_' . $notice . '_notice_dismissed', true );

        wp_send_json_success();
    }

    /**
     * Display flash notices.
     */
    public function display_flash_notices() {
        $notices = get_transient( $this->prefix . '_flash_notices' );

        if ( empty( $notices ) || ! is_array( $notices ) ) {
            return;
        }

        foreach ( $notices as $notice ) {
            $type    = isset( $notice['type'] ) ? $notice['type'] : 'info';
            $message = isset( $notice['message'] ) ? $notice['message'] : '';

            if ( empty( $message ) ) {
                continue;
            }

            $allowed_types = array( 'success', 'error', 'warning', 'info' );
            if ( ! in_array( $type, $allowed_types, true ) ) {
                $type = 'info';
            }

            ?>
            <div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
                <p><?php echo wp_kses_post( $message ); ?></p>
            </div>
            <?php
        }

        delete_transient( $this->prefix . '_flash_notices' );
    }

    /**
     * Add flash notice.
     *
     * @param string $message Message.
     * @param string $type    Type.
     */
    public static function add_flash_notice( $message, $type = 'success' ) {
        $prefix  = 'ucof';
        $notices = get_transient( $prefix . '_flash_notices' );

        if ( ! is_array( $notices ) ) {
            $notices = array();
        }

        $notices[] = array(
            'message' => $message,
            'type'    => $type,
        );

        set_transient( $prefix . '_flash_notices', $notices, 60 );
    }
}
