<?php
/**
 * Plugin Name: Unstoppable COD Order Forms for WooCommerce
 * Plugin URI: https://unstoppablesolutions.app/plugins/unstoppable-cod-forms
 * Description: Create high-converting COD order forms with delivery zone calculators, Google Sheets sync, and marketing pixel integration for cash-on-delivery markets.
 * Version: 1.1.4
 * Author: Unstoppable Solutions
 * Author URI: https://unstoppablesolutions.app
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: unstoppable-cod-order-forms-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.5
 * Requires Plugins: woocommerce
 *
 * @package Unstoppable_COD_Forms
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'UCOF_VERSION', '1.1.4' );
define( 'UCOF_PLUGIN_FILE', __FILE__ );
define( 'UCOF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UCOF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UCOF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'UCOF_PLUGIN_SLUG', 'unstoppable-cod-order-forms-for-woocommerce' );
define( 'UCOF_OPTION_PREFIX', 'ucof' );

/*
 * ============================================================================
 * WOOCOMMERCE HPOS COMPATIBILITY
 * Declare compatibility with High-Performance Order Storage
 * ============================================================================
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

/*
 * ============================================================================
 * UNSTOPPABLE OPTIONS STUDIO - SHARED SHELL SYSTEM
 * ============================================================================
 */

if ( ! function_exists( 'unstoppable_options_studio_init' ) ) {
    /**
     * Initialize the Options Studio shell menu.
     */
    function unstoppable_options_studio_init() {
        add_action( 'admin_menu', 'unstoppable_options_studio_create_menu', 9 );
        add_action( 'admin_head', 'unstoppable_options_studio_menu_icon_css' );
        add_action( 'admin_bar_menu', 'unstoppable_options_studio_admin_bar', 100 );
        add_action( 'wp_head', 'unstoppable_options_studio_admin_bar_css' );
        add_action( 'admin_head', 'unstoppable_options_studio_admin_bar_css' );
    }

    /**
     * Create the Options Studio parent menu.
     */
    function unstoppable_options_studio_create_menu() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        global $menu;
        foreach ( $menu as $item ) {
            if ( isset( $item[2] ) && $item[2] === 'unstoppable-cod-order-forms-for-woocommerce' ) {
                return;
            }
        }

        add_menu_page(
            __( 'Options Studio', 'unstoppable-cod-order-forms-for-woocommerce' ),
            __( 'Options Studio', 'unstoppable-cod-order-forms-for-woocommerce' ),
            'manage_woocommerce',
            'unstoppable-cod-order-forms-for-woocommerce',
            'unstoppable_options_studio_dashboard',
            'dashicons-screenoptions',
            56
        );

        add_submenu_page(
            'unstoppable-cod-order-forms-for-woocommerce',
            __( 'Dashboard', 'unstoppable-cod-order-forms-for-woocommerce' ),
            __( 'Dashboard', 'unstoppable-cod-order-forms-for-woocommerce' ),
            'manage_woocommerce',
            'unstoppable-cod-order-forms-for-woocommerce',
            'unstoppable_options_studio_dashboard'
        );
    }

    /**
     * Options Studio Dashboard page.
     */
    function unstoppable_options_studio_dashboard() {
        $plugins = apply_filters( 'unstoppable_studio_registered_plugins', array() );
        ?>
        <div class="wrap unstoppable-studio-dashboard">
            <h1><?php esc_html_e( 'Options Studio', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Your suite of WooCommerce enhancement tools by Unstoppable Solutions.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>

            <div class="unstoppable-studio-plugins" style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;">
                <?php if ( empty( $plugins ) ) : ?>
                    <p><?php esc_html_e( 'No plugins registered yet.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $plugins as $plugin ) : ?>
                        <div class="unstoppable-plugin-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; width: 300px;">
                            <h3 style="margin-top: 0;"><?php echo esc_html( $plugin['name'] ); ?></h3>
                            <p style="color: #666;"><?php echo esc_html( $plugin['description'] ); ?></p>
                            <?php if ( ! empty( $plugin['menu_slug'] ) ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $plugin['menu_slug'] ) ); ?>" class="button button-primary">
                                    <?php esc_html_e( 'Open', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="unstoppable-studio-footer" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
                <p>
                    <a href="https://unstoppablesolutions.app" target="_blank"><?php esc_html_e( 'Visit Unstoppable Solutions', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></a> |
                    <a href="https://unstoppablesolutions.app/support" target="_blank"><?php esc_html_e( 'Get Support', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Menu icon CSS.
     */
    function unstoppable_options_studio_menu_icon_css() {
        $css = '#adminmenu .toplevel_page_unstoppable-studio .wp-menu-image:before { color: #f0b849; }
            #adminmenu .toplevel_page_unstoppable-studio:hover .wp-menu-image:before,
            #adminmenu .toplevel_page_unstoppable-studio.current .wp-menu-image:before { color: #fff; }';
        wp_register_style( 'ucof-admin-menu', false );
        wp_enqueue_style( 'ucof-admin-menu' );
        wp_add_inline_style( 'ucof-admin-menu', $css );
    }

    /**
     * Admin bar integration.
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
     */
    function unstoppable_options_studio_admin_bar( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $wp_admin_bar->add_node( array(
            'id'    => 'unstoppable-cod-order-forms-for-woocommerce',
            'title' => '<span class="ab-icon dashicons dashicons-screenoptions"></span>' . __( 'Options Studio', 'unstoppable-cod-order-forms-for-woocommerce' ),
            'href'  => admin_url( 'admin.php?page=unstoppable-studio' ),
        ) );

        $plugins = apply_filters( 'unstoppable_studio_registered_plugins', array() );
        foreach ( $plugins as $plugin_id => $plugin ) {
            if ( ! empty( $plugin['menu_slug'] ) ) {
                $wp_admin_bar->add_node( array(
                    'id'     => 'unstoppable-studio-' . sanitize_key( $plugin_id ),
                    'parent' => 'unstoppable-cod-order-forms-for-woocommerce',
                    'title'  => $plugin['name'],
                    'href'   => admin_url( 'admin.php?page=' . $plugin['menu_slug'] ),
                ) );
            }
        }
    }

    /**
     * Admin bar CSS.
     */
    function unstoppable_options_studio_admin_bar_css() {
        if ( ! is_admin_bar_showing() ) {
            return;
        }
        $css = '#wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item .ab-icon:before { content: "\\f180"; top: 2px; }
            #wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item .ab-icon { margin-right: 6px; }
            #wpadminbar #wp-admin-bar-unstoppable-studio > .ab-item { color: #f0b849 !important; }
            #wpadminbar #wp-admin-bar-unstoppable-studio:hover > .ab-item { color: #fff !important; background: #32373c !important; }';
        wp_register_style( 'ucof-admin-bar', false );
        wp_enqueue_style( 'ucof-admin-bar' );
        wp_add_inline_style( 'ucof-admin-bar', $css );
    }
}

unstoppable_options_studio_init();

/* ============================================================================
 * END SHARED SHELL SYSTEM
 * ============================================================================ */

/**
 * Main plugin class.
 */
final class UCOF_Main {

    /**
     * Single instance.
     *
     * @var Unstoppable_COD_Forms
     */
    private static $instance = null;

    /**
     * Admin instance.
     *
     * @var UCOF_Admin
     */
    public $admin;

    /**
     * Forms handler.
     *
     * @var UCOF_Forms
     */
    public $forms;

    /**
     * Zones handler.
     *
     * @var UCOF_Zones
     */
    public $zones;

    /**
     * Integrations handler.
     *
     * @var UCOF_Integrations
     */
    public $integrations;

    /**
     * Get instance.
     *
     * @return UCOF_Main
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->check_requirements();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Check requirements.
     */
    private function check_requirements() {
        add_action( 'admin_init', array( $this, 'check_woocommerce' ) );
    }

    /**
     * Check WooCommerce.
     *
     * @return bool
     */
    public function check_woocommerce() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return false;
        }
        return true;
    }

    /**
     * WooCommerce missing notice.
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'Unstoppable COD Order Forms', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></strong>
                <?php esc_html_e( 'requires WooCommerce to be installed and active.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                <a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ); ?>">
                    <?php esc_html_e( 'Install WooCommerce', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Include files.
     */
    private function includes() {
        require_once UCOF_PLUGIN_DIR . 'includes/class-ucof-admin-notices.php';
        require_once UCOF_PLUGIN_DIR . 'includes/class-ucof-admin.php';
        require_once UCOF_PLUGIN_DIR . 'includes/class-ucof-forms.php';
        require_once UCOF_PLUGIN_DIR . 'includes/class-ucof-zones.php';
        require_once UCOF_PLUGIN_DIR . 'includes/class-ucof-integrations.php';
        require_once UCOF_PLUGIN_DIR . 'includes/class-ucof-frontend.php';
        require_once UCOF_PLUGIN_DIR . 'includes/class-ucof-ajax.php';
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
        add_filter( 'unstoppable_studio_registered_plugins', array( $this, 'register_with_studio' ) );
        register_activation_hook( UCOF_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( UCOF_PLUGIN_FILE, array( $this, 'deactivate' ) );
    }

    /**
     * Initialize components.
     */
    public function init() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        if ( is_admin() ) {
            new UCOF_Admin_Notices();
            $this->admin = new UCOF_Admin();
        }

        $this->forms        = new UCOF_Forms();
        $this->zones        = new UCOF_Zones();
        $this->integrations = new UCOF_Integrations();

        new UCOF_Frontend();
        new UCOF_Ajax();
    }

    /**
     * Register with Options Studio.
     *
     * @param array $plugins Registered plugins.
     * @return array
     */
    public function register_with_studio( $plugins ) {
        $plugins['ucof'] = array(
            'name'        => __( 'COD Order Forms', 'unstoppable-cod-order-forms-for-woocommerce' ),
            'description' => __( 'Create high-converting COD order forms with delivery calculators.', 'unstoppable-cod-order-forms-for-woocommerce' ),
            'menu_slug'   => 'ucof-dashboard',
            'version'     => UCOF_VERSION,
        );
        return $plugins;
    }

    /**
     * Activation.
     */
    public function activate() {
        if ( ! get_option( UCOF_OPTION_PREFIX . '_install_date' ) ) {
            update_option( UCOF_OPTION_PREFIX . '_install_date', time() );
        }
        $this->create_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }

    /**
     * Deactivation.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create database tables.
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_forms = $wpdb->prefix . 'ucof_forms';
        $sql_forms = "CREATE TABLE IF NOT EXISTS {$table_forms} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            settings longtext,
            products longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status)
        ) {$charset_collate};";

        $table_zones = $wpdb->prefix . 'ucof_zones';
        $sql_zones = "CREATE TABLE IF NOT EXISTS {$table_zones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            regions longtext,
            fee_type varchar(50) DEFAULT 'flat',
            fee_amount decimal(10,2) DEFAULT 0.00,
            fee_settings longtext,
            free_threshold decimal(10,2) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY sort_order (sort_order)
        ) {$charset_collate};";

        $table_orders = $wpdb->prefix . 'ucof_orders';
        $sql_orders = "CREATE TABLE IF NOT EXISTS {$table_orders} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            wc_order_id bigint(20) unsigned DEFAULT NULL,
            form_id bigint(20) unsigned NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_phone varchar(50) NOT NULL,
            customer_email varchar(255) DEFAULT NULL,
            customer_address text,
            customer_city varchar(100) DEFAULT NULL,
            customer_region varchar(100) DEFAULT NULL,
            zone_id bigint(20) unsigned DEFAULT NULL,
            products longtext,
            subtotal decimal(10,2) DEFAULT 0.00,
            delivery_fee decimal(10,2) DEFAULT 0.00,
            total decimal(10,2) DEFAULT 0.00,
            status varchar(50) DEFAULT 'pending',
            notes text,
            tracking_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wc_order_id (wc_order_id),
            KEY form_id (form_id),
            KEY status (status),
            KEY customer_phone (customer_phone),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_forms );
        dbDelta( $sql_zones );
        dbDelta( $sql_orders );

        update_option( UCOF_OPTION_PREFIX . '_db_version', '1.0.0' );
    }

    /**
     * Set default options.
     */
    private function set_default_options() {
        $defaults = array(
            'currency'              => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
            'phone_required'        => 'yes',
            'email_required'        => 'no',
            'address_required'      => 'yes',
            'create_wc_order'       => 'yes',
            'default_order_status'  => 'on-hold',
            'enable_whatsapp'       => 'no',
            'whatsapp_number'       => '',
            'enable_google_sheets'  => 'no',
            'google_sheets_id'      => '',
            'enable_facebook_pixel' => 'no',
            'facebook_pixel_id'     => '',
            'enable_tiktok_pixel'   => 'no',
            'tiktok_pixel_id'       => '',
            'enable_ga4'            => 'no',
            'ga4_measurement_id'    => '',
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( UCOF_OPTION_PREFIX . '_' . $key ) ) {
                update_option( UCOF_OPTION_PREFIX . '_' . $key, $value );
            }
        }
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserializing.
     *
     * @throws Exception When attempting to unserialize.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton.' );
    }
}

/**
 * Returns main plugin instance.
 *
 * @return UCOF_Main
 */
function UCOF() {
    return UCOF_Main::instance();
}

// Initialize.
UCOF();
