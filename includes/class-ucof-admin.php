<?php
/**
 * Admin Class
 *
 * Handles all admin functionality including menu, tabs, and settings.
 *
 * @package Unstoppable_COD_Forms
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin uses custom tables
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table queries
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonces verified in form handlers

/**
 * Admin Class.
 */
class UCOF_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ), 20 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'plugin_action_links_' . UCOF_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
    }

    /**
     * Add admin menu.
     */
    public function admin_menu() {
        add_submenu_page(
            'unstoppable-studio',
            __( 'COD Order Forms', 'unstoppable-cod-order-forms-for-woocommerce' ),
            __( 'COD Order Forms', 'unstoppable-cod-order-forms-for-woocommerce' ),
            'manage_woocommerce',
            'ucof-dashboard',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // Only load on our pages.
        if ( strpos( $hook, 'ucof-' ) === false && strpos( $hook, 'ucof_' ) === false ) {
            return;
        }

        // Admin styles.
        wp_enqueue_style(
            'ucof-admin',
            UCOF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            UCOF_VERSION
        );

        // Admin scripts.
        wp_enqueue_script(
            'ucof-admin',
            UCOF_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-util' ),
            UCOF_VERSION,
            true
        );

        wp_localize_script( 'ucof-admin', 'ucof_admin', array(
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'admin_url' => admin_url( 'admin.php?page=ucof-dashboard&tab=forms' ),
            'nonce'     => wp_create_nonce( 'ucof_admin_nonce' ),
            'i18n'      => array(
                'confirm_delete'  => __( 'Are you sure you want to delete this item?', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'saving'          => __( 'Saving...', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'saved'           => __( 'Saved!', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'error'           => __( 'An error occurred.', 'unstoppable-cod-order-forms-for-woocommerce' ),
            ),
        ) );
    }

    /**
     * Plugin action links.
     *
     * @param array $links Action links.
     * @return array Modified links.
     */
    public function plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=ucof-dashboard' ) . '">' . __( 'Settings', 'unstoppable-cod-order-forms-for-woocommerce' ) . '</a>',
        );

        return array_merge( $plugin_links, $links );
    }

    /**
     * Get current tab.
     *
     * @return string Current tab slug.
     */
    private function get_current_tab() {
        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
        $valid_tabs = array_keys( $this->get_tabs() );

        return in_array( $tab, $valid_tabs, true ) ? $tab : 'dashboard';
    }

    /**
     * Get tabs.
     *
     * @return array Tabs configuration.
     */
    private function get_tabs() {
        return array(
            'dashboard'    => array(
                'label' => __( 'Dashboard', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'icon'  => 'dashicons-dashboard',
            ),
            'forms'        => array(
                'label' => __( 'Forms', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'icon'  => 'dashicons-feedback',
            ),
            'orders'       => array(
                'label' => __( 'Orders', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'icon'  => 'dashicons-cart',
            ),
            'zones'        => array(
                'label' => __( 'Delivery Zones', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'icon'  => 'dashicons-location-alt',
            ),
            'integrations' => array(
                'label' => __( 'Integrations', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'icon'  => 'dashicons-admin-plugins',
            ),
            'settings'     => array(
                'label' => __( 'Settings', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'icon'  => 'dashicons-admin-generic',
            ),
        );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        $current_tab = $this->get_current_tab();
        $tabs        = $this->get_tabs();
        ?>
        <div class="wrap ucof-wrap">
            <h1><?php esc_html_e( 'COD Order Forms', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h1>

            <nav class="ucof-tabs-nav">
                <?php foreach ( $tabs as $tab_id => $tab ) :
                    $url       = admin_url( 'admin.php?page=ucof-dashboard&tab=' . $tab_id );
                    $is_active = $current_tab === $tab_id;
                ?>
                    <a href="<?php echo esc_url( $url ); ?>"
                       class="ucof-tab-link <?php echo $is_active ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                        <?php echo esc_html( $tab['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="ucof-tab-content">
                <?php
                switch ( $current_tab ) {
                    case 'forms':
                        $this->render_forms_tab();
                        break;
                    case 'orders':
                        $this->render_orders_tab();
                        break;
                    case 'zones':
                        $this->render_zones_tab();
                        break;
                    case 'integrations':
                        $this->render_integrations_tab();
                        break;
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'dashboard':
                    default:
                        $this->render_dashboard_tab();
                        break;
                }
                ?>
            </div>

            <div class="ucof-footer">
                <p>
                    <?php
                    printf(
                        /* translators: %s: plugin version */
                        esc_html__( 'Unstoppable COD Order Forms v%s', 'unstoppable-cod-order-forms-for-woocommerce' ),
                        esc_html( UCOF_VERSION )
                    );
                    ?>
                    |
                    <a href="https://unstoppablesolutions.app/support" target="_blank">
                        <?php esc_html_e( 'Support', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </a>
                    |
                    <a href="https://unstoppablesolutions.app/docs/cod-forms" target="_blank">
                        <?php esc_html_e( 'Documentation', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Dashboard tab.
     */
    private function render_dashboard_tab() {
        global $wpdb;

        // Get stats.
        $forms_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ucof_forms WHERE status = 'active'" );
        $zones_count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ucof_zones WHERE status = 'active'" );
        $orders_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ucof_orders" );
        $orders_today = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ucof_orders WHERE DATE(created_at) = %s",
            current_time( 'Y-m-d' )
        ) );

        // Get recent orders.
        $recent_orders = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ucof_orders ORDER BY created_at DESC LIMIT 5"
        );
        ?>
        <div class="ucof-dashboard">
            <div class="ucof-stats-grid">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=forms' ) ); ?>" class="ucof-stat-card ucof-stat-card-link">
                    <span class="dashicons dashicons-feedback"></span>
                    <div class="ucof-stat-content">
                        <span class="ucof-stat-number"><?php echo absint( $forms_count ); ?></span>
                        <span class="ucof-stat-label"><?php esc_html_e( 'Active Forms', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                    </div>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=zones' ) ); ?>" class="ucof-stat-card ucof-stat-card-link">
                    <span class="dashicons dashicons-location-alt"></span>
                    <div class="ucof-stat-content">
                        <span class="ucof-stat-number"><?php echo absint( $zones_count ); ?></span>
                        <span class="ucof-stat-label"><?php esc_html_e( 'Delivery Zones', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                    </div>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=orders' ) ); ?>" class="ucof-stat-card ucof-stat-card-link">
                    <span class="dashicons dashicons-cart"></span>
                    <div class="ucof-stat-content">
                        <span class="ucof-stat-number"><?php echo absint( $orders_count ); ?></span>
                        <span class="ucof-stat-label"><?php esc_html_e( 'Total Orders', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                    </div>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=orders' ) ); ?>" class="ucof-stat-card ucof-stat-card-link">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div class="ucof-stat-content">
                        <span class="ucof-stat-number"><?php echo absint( $orders_today ); ?></span>
                        <span class="ucof-stat-label"><?php esc_html_e( 'Orders Today', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                    </div>
                </a>
            </div>

            <div class="ucof-dashboard-grid">
                <div class="ucof-card">
                    <h3><?php esc_html_e( 'Quick Actions', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                    <div class="ucof-quick-actions">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=forms&action=new' ) ); ?>" class="button button-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e( 'Create New Form', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=zones&action=new' ) ); ?>" class="button">
                            <span class="dashicons dashicons-location"></span>
                            <?php esc_html_e( 'Add Delivery Zone', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=integrations' ) ); ?>" class="button">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php esc_html_e( 'Setup Integrations', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </a>
                    </div>
                </div>

                <div class="ucof-card">
                    <h3><?php esc_html_e( 'Recent Orders', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                    <?php if ( empty( $recent_orders ) ) : ?>
                        <p class="ucof-empty-state"><?php esc_html_e( 'No orders yet.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    <?php else : ?>
                        <table class="ucof-mini-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Customer', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                                    <th><?php esc_html_e( 'Total', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $recent_orders as $order ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $order->customer_name ); ?></td>
                                        <td><?php echo wp_kses_post( wc_price( $order->total ) ); ?></td>
                                        <td><span class="ucof-status ucof-status-<?php echo esc_attr( $order->status ); ?>"><?php echo esc_html( ucfirst( $order->status ) ); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=orders' ) ); ?>"><?php esc_html_e( 'View all orders →', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Forms tab.
     */
    private function render_forms_tab() {
        $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

        if ( 'new' === $action || 'edit' === $action ) {
            $this->render_form_editor();
        } else {
            $this->render_forms_list();
        }
    }

    /**
     * Render forms list.
     */
    private function render_forms_list() {
        global $wpdb;
        $forms = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ucof_forms ORDER BY created_at DESC" );
        ?>
        <div class="ucof-card">
            <div class="ucof-card-header">
                <h3><?php esc_html_e( 'Order Forms', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=forms&action=new' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Create Form', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </a>
            </div>

            <?php if ( empty( $forms ) ) : ?>
                <div class="ucof-empty-state">
                    <span class="dashicons dashicons-feedback"></span>
                    <h4><?php esc_html_e( 'No forms yet', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                    <p><?php esc_html_e( 'Create your first COD order form to start receiving orders.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=forms&action=new' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Create Your First Form', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Name', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Shortcode', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Created', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $forms as $form ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $form->name ); ?></strong></td>
                                <td><code>[ucof_form id="<?php echo absint( $form->id ); ?>"]</code></td>
                                <td><span class="ucof-status ucof-status-<?php echo esc_attr( $form->status ); ?>"><?php echo esc_html( ucfirst( $form->status ) ); ?></span></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $form->created_at ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=forms&action=edit&id=' . $form->id ) ); ?>" class="button button-small">
                                        <?php esc_html_e( 'Edit', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                                    </a>
                                    <button type="button" class="button button-small ucof-delete-form" data-id="<?php echo absint( $form->id ); ?>">
                                        <?php esc_html_e( 'Delete', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render form editor.
     */
    private function render_form_editor() {
        $form_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $form    = null;
        $message = '';
        $message_type = '';

        // Handle form submission (fallback when AJAX doesn't work)
        if ( isset( $_POST['ucof_form_submit'] ) && isset( $_POST['ucof_form_nonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ucof_form_nonce'] ) ), 'ucof_save_form' ) && current_user_can( 'manage_woocommerce' ) ) {
                global $wpdb;
                
                $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
                $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
                $slug    = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : sanitize_title( $name );
                $status  = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';
                
                $settings = array();
                if ( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ) {
                    $post_settings = map_deep( wp_unslash( $_POST['settings'] ), 'sanitize_text_field' );
                    foreach ( $post_settings as $key => $value ) {
                        $settings[ sanitize_key( $key ) ] = $value;
                    }
                }
                
                $products = array();
                if ( isset( $_POST['products'] ) && is_array( $_POST['products'] ) ) {
                    $products = array_map( 'absint', wp_unslash( $_POST['products'] ) );
                }
                
                $data = array(
                    'name'       => $name,
                    'slug'       => $slug,
                    'status'     => $status,
                    'settings'   => wp_json_encode( $settings ),
                    'products'   => wp_json_encode( $products ),
                    'updated_at' => current_time( 'mysql' ),
                );
                
                if ( $form_id > 0 ) {
                    // Update existing form
                    $wpdb->update(
                        $wpdb->prefix . 'ucof_forms',
                        $data,
                        array( 'id' => $form_id ),
                        array( '%s', '%s', '%s', '%s', '%s', '%s' ),
                        array( '%d' )
                    );
                    $message = __( 'Form updated successfully!', 'unstoppable-cod-order-forms-for-woocommerce' );
                    $message_type = 'success';
                } else {
                    // Insert new form
                    $data['created_at'] = current_time( 'mysql' );
                    $wpdb->insert(
                        $wpdb->prefix . 'ucof_forms',
                        $data,
                        array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
                    );
                    $form_id = $wpdb->insert_id;
                    $message = __( 'Form created successfully!', 'unstoppable-cod-order-forms-for-woocommerce' );
                    $message_type = 'success';
                    
                    // Redirect to edit page
                    wp_safe_redirect( admin_url( 'admin.php?page=ucof-dashboard&tab=forms&action=edit&id=' . $form_id . '&message=created' ) );
                    exit;
                }
            } else {
                $message = __( 'Security check failed. Please try again.', 'unstoppable-cod-order-forms-for-woocommerce' );
                $message_type = 'error';
            }
        }

        // Check for message from redirect
        if ( isset( $_GET['message'] ) && 'created' === $_GET['message'] ) {
            $message = __( 'Form created successfully!', 'unstoppable-cod-order-forms-for-woocommerce' );
            $message_type = 'success';
        }

        if ( $form_id > 0 ) {
            global $wpdb;
            $form = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ucof_forms WHERE id = %d",
                $form_id
            ) );
        }

        $settings = $form ? json_decode( $form->settings, true ) : array();
        $products = $form ? json_decode( $form->products, true ) : array();
        ?>
        <div class="ucof-card">
            <div class="ucof-card-header">
                <h3><?php echo $form ? esc_html__( 'Edit Form', 'unstoppable-cod-order-forms-for-woocommerce' ) : esc_html__( 'Create Form', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=forms' ) ); ?>" class="button">
                    <?php esc_html_e( '← Back to Forms', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </a>
            </div>

            <?php if ( ! empty( $message ) ) : ?>
                <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible" style="margin: 15px 0;">
                    <p><?php echo esc_html( $message ); ?></p>
                </div>
            <?php endif; ?>

            <form id="ucof-form-editor" class="ucof-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=forms&action=' . ( $form_id ? 'edit&id=' . $form_id : 'new' ) ) ); ?>">
                <?php wp_nonce_field( 'ucof_save_form', 'ucof_form_nonce' ); ?>
                <input type="hidden" name="ucof_form_submit" value="1">
                <input type="hidden" name="form_id" value="<?php echo absint( $form_id ); ?>">

                <div class="ucof-form-section">
                    <h4><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Basic Settings', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>

                    <div class="ucof-field">
                        <label for="form_name"><?php esc_html_e( 'Form Name', 'unstoppable-cod-order-forms-for-woocommerce' ); ?> <span class="required">*</span></label>
                        <input type="text" id="form_name" name="name" value="<?php echo $form ? esc_attr( $form->name ) : ''; ?>" required placeholder="<?php esc_attr_e( 'e.g., Main Product Form', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                        <p class="description"><?php esc_html_e( 'A name to identify this form in your admin.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    </div>

                    <div class="ucof-field">
                        <label for="form_slug"><?php esc_html_e( 'Form Slug', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <input type="text" id="form_slug" name="slug" value="<?php echo $form ? esc_attr( $form->slug ) : ''; ?>" placeholder="<?php esc_attr_e( 'auto-generated-from-name', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                        <p class="description"><?php esc_html_e( 'URL-friendly identifier. Leave blank to auto-generate.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    </div>

                    <div class="ucof-field">
                        <label for="form_status"><?php esc_html_e( 'Status', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <select id="form_status" name="status">
                            <option value="active" <?php selected( $form ? $form->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                            <option value="inactive" <?php selected( $form ? $form->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="ucof-form-section">
                    <h4><span class="dashicons dashicons-products"></span> <?php esc_html_e( 'Products', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                    <p class="description"><?php esc_html_e( 'Choose which products customers can order through this form.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>

                    <div class="ucof-field">
                        <label for="product_source"><?php esc_html_e( 'Product Source', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <select id="product_source" name="settings[product_source]">
                            <option value="all" <?php selected( $settings['product_source'] ?? '', 'all' ); ?>><?php esc_html_e( 'All Products', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                            <option value="category" <?php selected( $settings['product_source'] ?? '', 'category' ); ?>><?php esc_html_e( 'Specific Categories', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                            <option value="selected" <?php selected( $settings['product_source'] ?? '', 'selected' ); ?>><?php esc_html_e( 'Selected Products', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                        </select>
                    </div>

                    <div class="ucof-field ucof-category-select" style="<?php echo ( $settings['product_source'] ?? '' ) === 'category' ? '' : 'display: none;'; ?>">
                        <label for="product_category"><?php esc_html_e( 'Select Category', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <select id="product_category" name="settings[product_category]">
                            <option value=""><?php esc_html_e( '— Select Category —', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                            <?php
                            $categories = get_terms( array(
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => false,
                            ) );
                            if ( ! is_wp_error( $categories ) ) {
                                foreach ( $categories as $category ) {
                                    printf(
                                        '<option value="%d" %s>%s</option>',
                                        absint( $category->term_id ),
                                        selected( $settings['product_category'] ?? '', $category->term_id, false ),
                                        esc_html( $category->name )
                                    );
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="ucof-field ucof-product-search" style="<?php echo ( $settings['product_source'] ?? '' ) === 'selected' ? '' : 'display: none;'; ?>">
                        <label><?php esc_html_e( 'Select Products', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <select id="product_ids" name="products[]" multiple class="ucof-product-select" style="width: 100%;">
                            <?php
                            if ( ! empty( $products ) ) {
                                foreach ( $products as $product_id ) {
                                    $product = wc_get_product( $product_id );
                                    if ( $product ) {
                                        echo '<option value="' . absint( $product_id ) . '" selected>' . esc_html( $product->get_name() ) . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="ucof-form-section">
                    <h4><span class="dashicons dashicons-forms"></span> <?php esc_html_e( 'Form Fields', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                    <p class="description"><?php esc_html_e( 'Customize which fields appear on your order form.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>

                    <div class="ucof-field ucof-checkbox">
                        <label>
                            <input type="checkbox" name="settings[show_quantity]" value="1" <?php checked( $settings['show_quantity'] ?? '1', '1' ); ?>>
                            <?php esc_html_e( 'Show quantity selector', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                    </div>

                    <div class="ucof-field ucof-checkbox">
                        <label>
                            <input type="checkbox" name="settings[show_variations]" value="1" <?php checked( $settings['show_variations'] ?? '1', '1' ); ?>>
                            <?php esc_html_e( 'Show product variations (size, color, etc.)', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                    </div>

                    <div class="ucof-field ucof-checkbox">
                        <label>
                            <input type="checkbox" name="settings[show_images]" value="1" <?php checked( $settings['show_images'] ?? '1', '1' ); ?>>
                            <?php esc_html_e( 'Show product images', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                    </div>

                    <div class="ucof-field ucof-checkbox">
                        <label>
                            <input type="checkbox" name="settings[require_phone]" value="1" <?php checked( $settings['require_phone'] ?? '1', '1' ); ?>>
                            <?php esc_html_e( 'Require phone number', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                    </div>

                    <div class="ucof-field ucof-checkbox">
                        <label>
                            <input type="checkbox" name="settings[require_email]" value="1" <?php checked( $settings['require_email'] ?? '', '1' ); ?>>
                            <?php esc_html_e( 'Require email address', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                    </div>

                    <div class="ucof-field ucof-checkbox">
                        <label>
                            <input type="checkbox" name="settings[require_address]" value="1" <?php checked( $settings['require_address'] ?? '1', '1' ); ?>>
                            <?php esc_html_e( 'Require delivery address', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                    </div>

                    <div class="ucof-field ucof-checkbox">
                        <label>
                            <input type="checkbox" name="settings[show_notes]" value="1" <?php checked( $settings['show_notes'] ?? '', '1' ); ?>>
                            <?php esc_html_e( 'Show order notes field', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                    </div>

                    <div class="ucof-field ucof-checkbox">
                        <label>
                            <input type="checkbox" name="settings[enable_zones]" value="1" <?php checked( $settings['enable_zones'] ?? '', '1' ); ?>>
                            <?php esc_html_e( 'Enable delivery zones (calculate delivery fees by location)', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                    </div>

                    <div class="ucof-field">
                        <label for="button_text"><?php esc_html_e( 'Submit Button Text', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <input type="text" id="button_text" name="settings[button_text]" value="<?php echo esc_attr( $settings['button_text'] ?? __( 'Place Order', 'unstoppable-cod-order-forms-for-woocommerce' ) ); ?>" placeholder="<?php esc_attr_e( 'Place Order', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                    </div>

                    <div class="ucof-field">
                        <label for="success_message"><?php esc_html_e( 'Success Message', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <textarea id="success_message" name="settings[success_message]" rows="2" placeholder="<?php esc_attr_e( 'Order placed successfully!', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"><?php echo esc_textarea( $settings['success_message'] ?? '' ); ?></textarea>
                    </div>

                    <div class="ucof-field">
                        <label for="redirect_url"><?php esc_html_e( 'Redirect URL (after order)', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <input type="url" id="redirect_url" name="settings[redirect_url]" value="<?php echo esc_url( $settings['redirect_url'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'https://example.com/thank-you', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                        <p class="description"><?php esc_html_e( 'Leave empty to show success message on same page', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    </div>
                </div>

                <div class="ucof-form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <?php echo $form ? esc_html__( 'Update Form', 'unstoppable-cod-order-forms-for-woocommerce' ) : esc_html__( 'Create Form', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Orders tab.
     */
    private function render_orders_tab() {
        global $wpdb;

        $per_page     = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset       = ( $current_page - 1 ) * $per_page;

        $total_orders = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ucof_orders" );
        $orders       = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ucof_orders ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ) );
        ?>
        <div class="ucof-card">
            <div class="ucof-card-header">
                <h3><?php esc_html_e( 'COD Orders', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <span class="ucof-count"><?php
                    /* translators: %d: number of orders */
                    printf( esc_html__( '%d orders', 'unstoppable-cod-order-forms-for-woocommerce' ), absint( $total_orders ) );
                ?></span>
            </div>

            <?php if ( empty( $orders ) ) : ?>
                <div class="ucof-empty-state">
                    <span class="dashicons dashicons-cart"></span>
                    <h4><?php esc_html_e( 'No orders yet', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                    <p><?php esc_html_e( 'Orders placed through your COD forms will appear here.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Order', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Customer', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Phone', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Total', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $orders as $order ) : ?>
                            <tr>
                                <td><strong>#<?php echo absint( $order->id ); ?></strong></td>
                                <td><?php echo esc_html( $order->customer_name ); ?></td>
                                <td><?php echo esc_html( $order->customer_phone ); ?></td>
                                <td><?php echo wp_kses_post( wc_price( $order->total ) ); ?></td>
                                <td>
                                    <select class="ucof-order-status" data-id="<?php echo absint( $order->id ); ?>">
                                        <option value="pending" <?php selected( $order->status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                                        <option value="confirmed" <?php selected( $order->status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                                        <option value="shipped" <?php selected( $order->status, 'shipped' ); ?>><?php esc_html_e( 'Shipped', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                                        <option value="delivered" <?php selected( $order->status, 'delivered' ); ?>><?php esc_html_e( 'Delivered', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                                        <option value="cancelled" <?php selected( $order->status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                                    </select>
                                </td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->created_at ) ) ); ?></td>
                                <td>
                                    <button type="button" class="button button-small ucof-view-order" data-id="<?php echo absint( $order->id ); ?>">
                                        <?php esc_html_e( 'View', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                                    </button>
                                    <?php if ( $order->wc_order_id ) : ?>
                                        <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->wc_order_id . '&action=edit' ) ); ?>" class="button button-small">
                                            <?php esc_html_e( 'WC Order', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                $total_pages = ceil( $total_orders / $per_page );
                if ( $total_pages > 1 ) {
                    echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                    echo wp_kses_post( paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $total_pages,
                        'current'   => $current_page,
                    ) ) );
                    echo '</div></div>';
                }
                ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Zones tab.
     */
    private function render_zones_tab() {
        $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

        if ( 'new' === $action || 'edit' === $action ) {
            $this->render_zone_editor();
        } else {
            $this->render_zones_list();
        }
    }

    /**
     * Render zones list.
     */
    private function render_zones_list() {
        global $wpdb;
        $zones = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ucof_zones ORDER BY sort_order ASC, name ASC" );
        ?>
        <div class="ucof-card">
            <div class="ucof-card-header">
                <h3><?php esc_html_e( 'Delivery Zones', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=zones&action=new' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Add Zone', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </a>
            </div>

            <?php if ( empty( $zones ) ) : ?>
                <div class="ucof-empty-state">
                    <span class="dashicons dashicons-location-alt"></span>
                    <h4><?php esc_html_e( 'No delivery zones yet', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                    <p><?php esc_html_e( 'Create delivery zones to calculate shipping fees based on customer location.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=zones&action=new' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Create Your First Zone', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Zone Name', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Regions', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Fee Type', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Fee', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $zones as $zone ) :
                            $regions = json_decode( $zone->regions, true );
                            $region_count = is_array( $regions ) ? count( $regions ) : 0;
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html( $zone->name ); ?></strong></td>
                                <td><?php
                                    /* translators: %d: number of regions */
                                    printf( esc_html__( '%d regions', 'unstoppable-cod-order-forms-for-woocommerce' ), absint( $region_count ) );
                                ?></td>
                                <td><?php echo esc_html( ucfirst( $zone->fee_type ) ); ?></td>
                                <td><?php echo wp_kses_post( wc_price( $zone->fee_amount ) ); ?></td>
                                <td><span class="ucof-status ucof-status-<?php echo esc_attr( $zone->status ); ?>"><?php echo esc_html( ucfirst( $zone->status ) ); ?></span></td>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=zones&action=edit&id=' . $zone->id ) ); ?>" class="button button-small">
                                        <?php esc_html_e( 'Edit', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                                    </a>
                                    <button type="button" class="button button-small ucof-delete-zone" data-id="<?php echo absint( $zone->id ); ?>">
                                        <?php esc_html_e( 'Delete', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render zone editor.
     */
    private function render_zone_editor() {
        $zone_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $zone    = null;
        $message = '';
        $message_type = '';

        // Handle form submission (fallback when AJAX doesn't work)
        if ( isset( $_POST['ucof_zone_submit'] ) && isset( $_POST['ucof_zone_nonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ucof_zone_nonce'] ) ), 'ucof_save_zone' ) && current_user_can( 'manage_woocommerce' ) ) {
                global $wpdb;
                
                $zone_id        = isset( $_POST['zone_id'] ) ? absint( $_POST['zone_id'] ) : 0;
                $name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
                $status         = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';
                $fee_type       = isset( $_POST['fee_type'] ) ? sanitize_key( wp_unslash( $_POST['fee_type'] ) ) : 'flat';
                $fee_amount     = isset( $_POST['fee_amount'] ) ? floatval( $_POST['fee_amount'] ) : 0;
                $free_threshold = isset( $_POST['free_threshold'] ) && '' !== $_POST['free_threshold'] ? floatval( $_POST['free_threshold'] ) : null;
                
                $regions_raw = isset( $_POST['regions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['regions'] ) ) : '';
                $regions     = array_filter( array_map( 'trim', explode( "\n", $regions_raw ) ) );
                
                $data = array(
                    'name'           => $name,
                    'regions'        => wp_json_encode( $regions ),
                    'fee_type'       => $fee_type,
                    'fee_amount'     => $fee_amount,
                    'free_threshold' => $free_threshold,
                    'fee_settings'   => wp_json_encode( array() ),
                    'status'         => $status,
                    'updated_at'     => current_time( 'mysql' ),
                );
                
                if ( $zone_id > 0 ) {
                    // Update existing zone
                    $wpdb->update(
                        $wpdb->prefix . 'ucof_zones',
                        $data,
                        array( 'id' => $zone_id ),
                        array( '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s' ),
                        array( '%d' )
                    );
                    $message = __( 'Zone updated successfully!', 'unstoppable-cod-order-forms-for-woocommerce' );
                    $message_type = 'success';
                } else {
                    // Insert new zone
                    $data['created_at'] = current_time( 'mysql' );
                    $wpdb->insert(
                        $wpdb->prefix . 'ucof_zones',
                        $data,
                        array( '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s' )
                    );
                    $zone_id = $wpdb->insert_id;
                    $message = __( 'Zone created successfully!', 'unstoppable-cod-order-forms-for-woocommerce' );
                    $message_type = 'success';
                    
                    // Redirect to edit page
                    wp_safe_redirect( admin_url( 'admin.php?page=ucof-dashboard&tab=zones&action=edit&id=' . $zone_id . '&message=created' ) );
                    exit;
                }
            } else {
                $message = __( 'Security check failed. Please try again.', 'unstoppable-cod-order-forms-for-woocommerce' );
                $message_type = 'error';
            }
        }

        // Check for message from redirect
        if ( isset( $_GET['message'] ) && 'created' === $_GET['message'] ) {
            $message = __( 'Zone created successfully!', 'unstoppable-cod-order-forms-for-woocommerce' );
            $message_type = 'success';
        }

        if ( $zone_id > 0 ) {
            global $wpdb;
            $zone = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ucof_zones WHERE id = %d",
                $zone_id
            ) );
        }

        $regions      = $zone ? json_decode( $zone->regions, true ) : array();
        $fee_settings = $zone ? json_decode( $zone->fee_settings, true ) : array();
        ?>
        <div class="ucof-card">
            <div class="ucof-card-header">
                <h3><?php echo $zone ? esc_html__( 'Edit Delivery Zone', 'unstoppable-cod-order-forms-for-woocommerce' ) : esc_html__( 'Add Delivery Zone', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=zones' ) ); ?>" class="button">
                    <?php esc_html_e( '← Back to Zones', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </a>
            </div>

            <?php if ( ! empty( $message ) ) : ?>
                <div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible" style="margin: 15px 24px;">
                    <p><?php echo esc_html( $message ); ?></p>
                </div>
            <?php endif; ?>

            <form id="ucof-zone-editor" class="ucof-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=zones&action=' . ( $zone_id ? 'edit&id=' . $zone_id : 'new' ) ) ); ?>">
                <?php wp_nonce_field( 'ucof_save_zone', 'ucof_zone_nonce' ); ?>
                <input type="hidden" name="ucof_zone_submit" value="1">
                <input type="hidden" name="zone_id" value="<?php echo absint( $zone_id ); ?>">

                <div class="ucof-form-section">
                    <h4><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Zone Details', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>

                    <div class="ucof-field">
                        <label for="zone_name"><?php esc_html_e( 'Zone Name', 'unstoppable-cod-order-forms-for-woocommerce' ); ?> <span class="required">*</span></label>
                        <input type="text" id="zone_name" name="name" value="<?php echo $zone ? esc_attr( $zone->name ) : ''; ?>" required placeholder="<?php esc_attr_e( 'e.g., Cairo, Riyadh, Metro Manila', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                        <p class="description"><?php esc_html_e( 'A name to identify this delivery zone.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    </div>

                    <div class="ucof-field">
                        <label for="zone_regions"><?php esc_html_e( 'Regions / Cities', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <textarea id="zone_regions" name="regions" rows="5" placeholder="<?php esc_attr_e( "Cairo\nGiza\nAlexandria\n6th of October", 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"><?php echo esc_textarea( implode( "\n", (array) $regions ) ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Enter one region per line. Can be city names, neighborhoods, state/province names, or postal codes. Customers will select from these when ordering.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    </div>
                </div>

                <div class="ucof-form-section">
                    <h4><span class="dashicons dashicons-money-alt"></span> <?php esc_html_e( 'Delivery Fee', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                    <p class="description"><?php esc_html_e( 'Set the delivery fee for orders shipped to this zone.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>

                    <div class="ucof-field">
                        <label for="fee_type"><?php esc_html_e( 'Fee Type', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <select id="fee_type" name="fee_type">
                            <option value="flat" <?php selected( $zone ? $zone->fee_type : 'flat', 'flat' ); ?>><?php esc_html_e( 'Flat Rate', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                            <option value="percent" <?php selected( $zone ? $zone->fee_type : '', 'percent' ); ?>><?php esc_html_e( 'Percentage of Order', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                            <option value="free" <?php selected( $zone ? $zone->fee_type : '', 'free' ); ?>><?php esc_html_e( 'Free Delivery', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                        </select>
                    </div>

                    <div class="ucof-field ucof-fee-amount">
                        <label for="fee_amount"><?php esc_html_e( 'Fee Amount', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <input type="number" id="fee_amount" name="fee_amount" value="<?php echo $zone ? esc_attr( $zone->fee_amount ) : '0'; ?>" step="0.01" min="0">
                        <p class="description"><?php esc_html_e( 'Enter amount in your store currency. For percentage, enter the % value (e.g., 5 for 5%).', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    </div>

                    <div class="ucof-field">
                        <label for="free_threshold"><?php esc_html_e( 'Free Delivery Threshold', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <input type="number" id="free_threshold" name="free_threshold" value="<?php echo $zone && $zone->free_threshold ? esc_attr( $zone->free_threshold ) : ''; ?>" step="0.01" min="0" placeholder="<?php esc_attr_e( 'Leave empty to disable', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                        <p class="description"><?php esc_html_e( 'Orders above this amount get free delivery automatically.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    </div>
                </div>

                <div class="ucof-form-section">
                    <h4><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Status', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>

                    <div class="ucof-field">
                        <label for="zone_status"><?php esc_html_e( 'Zone Status', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <select id="zone_status" name="status">
                            <option value="active" <?php selected( $zone ? $zone->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                            <option value="inactive" <?php selected( $zone ? $zone->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Inactive zones will not be available for customers to select.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                    </div>
                </div>

                <div class="ucof-form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <?php echo $zone ? esc_html__( 'Update Zone', 'unstoppable-cod-order-forms-for-woocommerce' ) : esc_html__( 'Create Zone', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ucof-dashboard&tab=zones' ) ); ?>" class="button button-large">
                        <?php esc_html_e( 'Cancel', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Integrations tab.
     */
    private function render_integrations_tab() {
        ?>
        <form id="ucof-integrations-form" class="ucof-form" method="post" action="">
            <?php wp_nonce_field( 'ucof_save_integrations', 'ucof_integrations_nonce' ); ?>

            <!-- Webhook / Automation Section -->
            <div class="ucof-card">
                <h3><?php esc_html_e( '🔗 Webhook Integration', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Send order data to any service via webhook URL. Works with Zapier, Make (Integromat), Pabbly, n8n, Google Sheets, and more.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>

                <div class="ucof-field ucof-checkbox">
                    <label>
                        <input type="checkbox" name="enable_webhook" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_enable_webhook', '' ), '1' ); ?>>
                        <?php esc_html_e( 'Enable Webhook', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </label>
                </div>

                <div class="ucof-field">
                    <label for="webhook_url"><?php esc_html_e( 'Webhook URL', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                    <input type="url" id="webhook_url" name="webhook_url" value="<?php echo esc_attr( get_option( UCOF_OPTION_PREFIX . '_webhook_url', '' ) ); ?>" placeholder="<?php esc_attr_e( 'https://hooks.zapier.com/... or https://hook.integromat.com/...', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>" class="large-text">
                    <p class="description"><?php esc_html_e( 'Paste your webhook URL from Zapier, Make, Pabbly, or any automation service.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                </div>

                <div class="ucof-webhook-help">
                    <h4><?php esc_html_e( 'How to get a Webhook URL:', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                    <ul>
                        <li><strong>Zapier:</strong> <?php esc_html_e( 'Create a Zap → Trigger: "Webhooks by Zapier" → "Catch Hook" → Copy URL', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                        <li><strong>Make:</strong> <?php esc_html_e( 'Create Scenario → Add Webhook module → "Custom webhook" → Copy URL', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                        <li><strong>Pabbly:</strong> <?php esc_html_e( 'Create Workflow → Trigger: "Webhook" → Copy URL', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                        <li><strong>Google Sheets:</strong> <?php esc_html_e( 'Use the Google Sheets integration below (easier!)', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                    </ul>
                </div>

                <div class="ucof-field">
                    <button type="button" id="ucof-test-webhook" class="button">
                        <?php esc_html_e( 'Test Webhook', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </button>
                    <span id="ucof-webhook-test-result"></span>
                </div>
            </div>

            <!-- Google Sheets Direct -->
            <div class="ucof-card">
                <h3><?php esc_html_e( '📊 Google Sheets (Direct)', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Send orders directly to Google Sheets using a simple Apps Script. No coding required!', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>

                <div class="ucof-field ucof-checkbox">
                    <label>
                        <input type="checkbox" name="enable_google_sheets" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_enable_google_sheets', '' ), '1' ); ?>>
                        <?php esc_html_e( 'Enable Google Sheets', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </label>
                </div>

                <div class="ucof-field">
                    <label for="google_sheets_url"><?php esc_html_e( 'Google Apps Script URL', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                    <input type="url" id="google_sheets_url" name="google_sheets_url" value="<?php echo esc_attr( get_option( UCOF_OPTION_PREFIX . '_google_sheets_url', '' ) ); ?>" placeholder="<?php esc_attr_e( 'https://script.google.com/macros/s/.../exec', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>" class="large-text">
                </div>

                <div class="ucof-gsheets-setup">
                    <h4><?php esc_html_e( '📋 Quick Setup (5 minutes):', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                    <ol>
                        <li><?php esc_html_e( 'Create a new Google Sheet', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                        <li><?php esc_html_e( 'Go to Extensions → Apps Script', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                        <li><?php esc_html_e( 'Delete any code and paste the script below', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                        <li><?php esc_html_e( 'Click Deploy → New deployment → Web app', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                        <li><?php esc_html_e( 'Set "Who has access" to "Anyone" → Deploy', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                        <li><?php esc_html_e( 'Copy the Web app URL and paste it above', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></li>
                    </ol>
                    
                    <div class="ucof-code-block">
                        <label><?php esc_html_e( 'Apps Script Code (click to copy):', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                        <pre id="ucof-gsheets-code" onclick="navigator.clipboard.writeText(this.innerText); alert('Copied!');">function doPost(e) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var data = JSON.parse(e.postData.contents);
  
  // Add headers if first row is empty
  if (sheet.getLastRow() === 0) {
    sheet.appendRow(['Date', 'Order ID', 'Name', 'Phone', 'Email', 'City', 'Region', 'Address', 'Products', 'Subtotal', 'Delivery', 'Total', 'Status', 'Notes']);
  }
  
  // Add order data
  sheet.appendRow([
    data.date || new Date(),
    data.order_id || '',
    data.customer_name || '',
    data.customer_phone || '',
    data.customer_email || '',
    data.customer_city || '',
    data.customer_region || '',
    data.customer_address || '',
    data.products || '',
    data.subtotal || 0,
    data.delivery_fee || 0,
    data.total || 0,
    data.status || 'Pending',
    data.notes || ''
  ]);
  
  return ContentService.createTextOutput(JSON.stringify({success: true}))
    .setMimeType(ContentService.MimeType.JSON);
}

function doGet(e) {
  return ContentService.createTextOutput('Webhook is working!')
    .setMimeType(ContentService.MimeType.TEXT);
}</pre>
                    </div>
                </div>
            </div>

            <!-- WhatsApp -->
            <div class="ucof-card">
                <h3><?php esc_html_e( '💬 WhatsApp Notifications', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Get instant order notifications on WhatsApp.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>

                <div class="ucof-field ucof-checkbox">
                    <label>
                        <input type="checkbox" name="enable_whatsapp" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_enable_whatsapp', '' ), '1' ); ?>>
                        <?php esc_html_e( 'Enable WhatsApp notifications', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </label>
                </div>

                <div class="ucof-field">
                    <label for="whatsapp_number"><?php esc_html_e( 'Your WhatsApp Number', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                    <input type="text" id="whatsapp_number" name="whatsapp_number" value="<?php echo esc_attr( get_option( UCOF_OPTION_PREFIX . '_whatsapp_number', '' ) ); ?>" placeholder="<?php esc_attr_e( '+966501234567', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                    <p class="description"><?php esc_html_e( 'Include country code (e.g., +966 for Saudi Arabia, +971 for UAE)', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                </div>

                <div class="ucof-field">
                    <label for="whatsapp_template"><?php esc_html_e( 'Message Template', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                    <textarea id="whatsapp_template" name="whatsapp_template" rows="6" class="large-text"><?php echo esc_textarea( get_option( UCOF_OPTION_PREFIX . '_whatsapp_template', $this->get_default_whatsapp_template() ) ); ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'Available placeholders:', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        <code>{order_id}</code> <code>{customer_name}</code> <code>{customer_phone}</code> <code>{products}</code> <code>{total}</code> <code>{address}</code>
                    </p>
                </div>
            </div>

            <!-- Marketing Pixels -->
            <div class="ucof-card">
                <h3><?php esc_html_e( '📈 Marketing Pixels', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <p class="description"><?php esc_html_e( 'Track purchases and conversions. Just paste your Pixel ID - no coding needed.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>

                <div class="ucof-pixels-grid">
                    <div class="ucof-pixel-item">
                        <h4><span class="ucof-pixel-icon">📘</span> <?php esc_html_e( 'Facebook Pixel', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                        <div class="ucof-field ucof-checkbox">
                            <label>
                                <input type="checkbox" name="enable_facebook_pixel" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_enable_facebook_pixel', '' ), '1' ); ?>>
                                <?php esc_html_e( 'Enable', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                            </label>
                        </div>
                        <div class="ucof-field">
                            <input type="text" name="facebook_pixel_id" value="<?php echo esc_attr( get_option( UCOF_OPTION_PREFIX . '_facebook_pixel_id', '' ) ); ?>" placeholder="<?php esc_attr_e( '123456789012345', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                            <p class="description"><?php esc_html_e( 'Find in Facebook Events Manager → Data Sources → Your Pixel', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                        </div>
                    </div>

                    <div class="ucof-pixel-item">
                        <h4><span class="ucof-pixel-icon">🎵</span> <?php esc_html_e( 'TikTok Pixel', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                        <div class="ucof-field ucof-checkbox">
                            <label>
                                <input type="checkbox" name="enable_tiktok_pixel" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_enable_tiktok_pixel', '' ), '1' ); ?>>
                                <?php esc_html_e( 'Enable', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                            </label>
                        </div>
                        <div class="ucof-field">
                            <input type="text" name="tiktok_pixel_id" value="<?php echo esc_attr( get_option( UCOF_OPTION_PREFIX . '_tiktok_pixel_id', '' ) ); ?>" placeholder="<?php esc_attr_e( 'CXXXXXXXXXXXXXXXXX', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                            <p class="description"><?php esc_html_e( 'Find in TikTok Ads Manager → Assets → Events → Web Events', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                        </div>
                    </div>

                    <div class="ucof-pixel-item">
                        <h4><span class="ucof-pixel-icon">📊</span> <?php esc_html_e( 'Google Analytics 4', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                        <div class="ucof-field ucof-checkbox">
                            <label>
                                <input type="checkbox" name="enable_ga4" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_enable_ga4', '' ), '1' ); ?>>
                                <?php esc_html_e( 'Enable', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                            </label>
                        </div>
                        <div class="ucof-field">
                            <input type="text" name="ga4_measurement_id" value="<?php echo esc_attr( get_option( UCOF_OPTION_PREFIX . '_ga4_measurement_id', '' ) ); ?>" placeholder="<?php esc_attr_e( 'G-XXXXXXXXXX', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                            <p class="description"><?php esc_html_e( 'Find in GA4 → Admin → Data Streams → Your Stream', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                        </div>
                    </div>

                    <div class="ucof-pixel-item">
                        <h4><span class="ucof-pixel-icon">📸</span> <?php esc_html_e( 'Snapchat Pixel', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h4>
                        <div class="ucof-field ucof-checkbox">
                            <label>
                                <input type="checkbox" name="enable_snapchat_pixel" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_enable_snapchat_pixel', '' ), '1' ); ?>>
                                <?php esc_html_e( 'Enable', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                            </label>
                        </div>
                        <div class="ucof-field">
                            <input type="text" name="snapchat_pixel_id" value="<?php echo esc_attr( get_option( UCOF_OPTION_PREFIX . '_snapchat_pixel_id', '' ) ); ?>" placeholder="<?php esc_attr_e( 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>">
                            <p class="description"><?php esc_html_e( 'Find in Snapchat Ads Manager → Events Manager', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ucof-form-actions">
                <button type="submit" class="button button-primary button-large">
                    <?php esc_html_e( 'Save Integrations', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </button>
            </div>
        </form>

        <?php
        $integrations_css = '.ucof-webhook-help, .ucof-gsheets-setup { background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin: 15px 0; }
            .ucof-webhook-help ul, .ucof-gsheets-setup ol { margin: 10px 0 0 20px; }
            .ucof-code-block { margin-top: 15px; }
            .ucof-code-block pre { background: #1d2327; color: #50fa7b; padding: 15px; border-radius: 4px; overflow-x: auto; cursor: pointer; font-size: 12px; line-height: 1.4; }
            .ucof-code-block pre:hover { background: #2c3338; }
            .ucof-pixels-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
            .ucof-pixel-item { background: #f6f7f7; border-radius: 4px; padding: 15px; }
            .ucof-pixel-item h4 { margin: 0 0 10px; display: flex; align-items: center; gap: 8px; }
            .ucof-pixel-icon { font-size: 20px; }';
        wp_add_inline_style( 'ucof-admin', $integrations_css );
        ?>
        <?php
    }

    /**
     * Get default WhatsApp template.
     *
     * @return string
     */
    private function get_default_whatsapp_template() {
        return "🛒 *New Order #{order_id}*\n\n" .
               "*Customer:* {customer_name}\n" .
               "*Phone:* {customer_phone}\n" .
               "*Address:* {address}\n\n" .
               "*Products:*\n{products}\n\n" .
               "*Total:* {total}";
    }

    /**
     * Settings tab.
     */
    private function render_settings_tab() {
        ?>
        <form id="ucof-settings-form" class="ucof-form" method="post" action="">
            <?php wp_nonce_field( 'ucof_save_settings', 'ucof_settings_nonce' ); ?>

            <div class="ucof-card">
                <h3><?php esc_html_e( 'General Settings', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>

                <div class="ucof-field ucof-checkbox">
                    <label>
                        <input type="checkbox" name="create_wc_order" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_create_wc_order', 'yes' ), 'yes' ); ?>>
                        <?php esc_html_e( 'Create WooCommerce orders', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'When enabled, a WooCommerce order will be created for each form submission.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></p>
                </div>

                <div class="ucof-field">
                    <label for="default_order_status"><?php esc_html_e( 'Default Order Status', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></label>
                    <select id="default_order_status" name="default_order_status">
                        <option value="on-hold" <?php selected( get_option( UCOF_OPTION_PREFIX . '_default_order_status', 'on-hold' ), 'on-hold' ); ?>><?php esc_html_e( 'On Hold', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                        <option value="processing" <?php selected( get_option( UCOF_OPTION_PREFIX . '_default_order_status', 'on-hold' ), 'processing' ); ?>><?php esc_html_e( 'Processing', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                        <option value="pending" <?php selected( get_option( UCOF_OPTION_PREFIX . '_default_order_status', 'on-hold' ), 'pending' ); ?>><?php esc_html_e( 'Pending Payment', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="ucof-card">
                <h3><?php esc_html_e( 'Form Fields', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>

                <div class="ucof-field ucof-checkbox">
                    <label>
                        <input type="checkbox" name="phone_required" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_phone_required', 'yes' ), 'yes' ); ?>>
                        <?php esc_html_e( 'Require phone number', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </label>
                </div>

                <div class="ucof-field ucof-checkbox">
                    <label>
                        <input type="checkbox" name="email_required" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_email_required', 'no' ), 'yes' ); ?>>
                        <?php esc_html_e( 'Require email address', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </label>
                </div>

                <div class="ucof-field ucof-checkbox">
                    <label>
                        <input type="checkbox" name="address_required" value="1" <?php checked( get_option( UCOF_OPTION_PREFIX . '_address_required', 'yes' ), 'yes' ); ?>>
                        <?php esc_html_e( 'Require delivery address', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </label>
                </div>
            </div>

            <div class="ucof-form-actions">
                <button type="submit" class="button button-primary button-large">
                    <?php esc_html_e( 'Save Settings', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </button>
            </div>
        </form>
        <?php
    }
}
