<?php
/**
 * AJAX Handler Class
 *
 * Handles all AJAX operations for the plugin.
 *
 * @package Unstoppable_COD_Forms
 */

// phpcs:disable WordPress.Security.NonceVerification.Missing -- All public methods verify nonces via verify_admin_request() or verify_frontend_request()
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Plugin uses custom tables
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- Caching not needed for admin AJAX operations

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX Handler Class.
 */
class UCOF_Ajax {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Admin AJAX actions
        add_action( 'wp_ajax_ucof_save_form', array( $this, 'save_form' ) );
        add_action( 'wp_ajax_ucof_delete_form', array( $this, 'delete_form' ) );
        add_action( 'wp_ajax_ucof_duplicate_form', array( $this, 'duplicate_form' ) );
        add_action( 'wp_ajax_ucof_save_zone', array( $this, 'save_zone' ) );
        add_action( 'wp_ajax_ucof_delete_zone', array( $this, 'delete_zone' ) );
        add_action( 'wp_ajax_ucof_update_order_status', array( $this, 'update_order_status' ) );
        add_action( 'wp_ajax_ucof_save_settings', array( $this, 'save_settings' ) );
        add_action( 'wp_ajax_ucof_save_integrations', array( $this, 'save_integrations' ) );
        add_action( 'wp_ajax_ucof_search_products', array( $this, 'search_products' ) );
        add_action( 'wp_ajax_ucof_get_product_categories', array( $this, 'get_product_categories' ) );
        add_action( 'wp_ajax_ucof_test_google_sheets', array( $this, 'test_google_sheets' ) );
        add_action( 'wp_ajax_ucof_test_webhook', array( $this, 'test_webhook' ) );
        add_action( 'wp_ajax_ucof_dismiss_notice', array( $this, 'dismiss_notice' ) );

        // Frontend AJAX actions (logged in and non-logged in)
        add_action( 'wp_ajax_ucof_submit_order', array( $this, 'submit_order' ) );
        add_action( 'wp_ajax_nopriv_ucof_submit_order', array( $this, 'submit_order' ) );
        add_action( 'wp_ajax_ucof_calculate_delivery', array( $this, 'calculate_delivery' ) );
        add_action( 'wp_ajax_nopriv_ucof_calculate_delivery', array( $this, 'calculate_delivery' ) );
        add_action( 'wp_ajax_ucof_get_products', array( $this, 'get_products' ) );
        add_action( 'wp_ajax_nopriv_ucof_get_products', array( $this, 'get_products' ) );
    }

    /**
     * Verify admin request.
     *
     * @param string $nonce_action Nonce action name.
     * @return bool
     */
    private function verify_admin_request( $nonce_action = 'ucof_admin_nonce' ) {
        if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
            return false;
        }

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
            return false;
        }

        return true;
    }

    /**
     * Verify frontend request.
     *
     * @param string $nonce_action Nonce action name.
     * @return bool
     */
    private function verify_frontend_request( $nonce_action = 'ucof_frontend_nonce' ) {
        if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
            return false;
        }

        return true;
    }

    /**
     * Save form.
     */
    public function save_form() {
        $this->verify_admin_request();

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_admin_request()
        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
        $status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => __( 'Form name is required.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        // Auto-generate slug if empty
        if ( empty( $slug ) ) {
            $slug = sanitize_title( $name );
        }

        // Get settings from nested array or direct POST
        $post_settings = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? $_POST['settings'] : array();

        // Settings - check both nested and direct POST values
        $settings = array(
            'product_source'    => isset( $post_settings['product_source'] ) ? sanitize_key( $post_settings['product_source'] ) : ( isset( $_POST['product_source'] ) ? sanitize_key( $_POST['product_source'] ) : 'all' ),
            'product_category'  => isset( $post_settings['product_category'] ) ? absint( $post_settings['product_category'] ) : ( isset( $_POST['product_category'] ) ? absint( $_POST['product_category'] ) : 0 ),
            'show_quantity'     => isset( $post_settings['show_quantity'] ) || isset( $_POST['show_quantity'] ) ? 1 : 0,
            'show_variations'   => isset( $post_settings['show_variations'] ) || isset( $_POST['show_variations'] ) ? 1 : 0,
            'show_images'       => isset( $post_settings['show_images'] ) || isset( $_POST['show_images'] ) ? 1 : 0,
            'require_phone'     => isset( $post_settings['require_phone'] ) || isset( $_POST['require_phone'] ) ? 1 : 0,
            'require_email'     => isset( $post_settings['require_email'] ) || isset( $_POST['require_email'] ) ? 1 : 0,
            'require_address'   => isset( $post_settings['require_address'] ) || isset( $_POST['require_address'] ) ? 1 : 0,
            'show_notes'        => isset( $post_settings['show_notes'] ) || isset( $_POST['show_notes'] ) ? 1 : 0,
            'button_text'       => isset( $post_settings['button_text'] ) ? sanitize_text_field( $post_settings['button_text'] ) : ( isset( $_POST['button_text'] ) ? sanitize_text_field( $_POST['button_text'] ) : __( 'Place Order', 'unstoppable-cod-order-forms-for-woocommerce' ) ),
            'success_message'   => isset( $post_settings['success_message'] ) ? sanitize_textarea_field( $post_settings['success_message'] ) : ( isset( $_POST['success_message'] ) ? sanitize_textarea_field( $_POST['success_message'] ) : '' ),
            'redirect_url'      => isset( $post_settings['redirect_url'] ) ? esc_url_raw( $post_settings['redirect_url'] ) : ( isset( $_POST['redirect_url'] ) ? esc_url_raw( $_POST['redirect_url'] ) : '' ),
            'enable_zones'      => isset( $post_settings['enable_zones'] ) || isset( $_POST['enable_zones'] ) ? 1 : 0,
        );

        // Selected products (check both 'products' and 'selected_products')
        $products = array();
        if ( isset( $_POST['products'] ) && is_array( $_POST['products'] ) ) {
            $products = array_map( 'absint', $_POST['products'] );
        } elseif ( isset( $_POST['selected_products'] ) && is_array( $_POST['selected_products'] ) ) {
            $products = array_map( 'absint', $_POST['selected_products'] );
        }

        $forms = new UCOF_Forms();

        $data = array(
            'name'     => $name,
            'slug'     => $slug,
            'settings' => $settings,
            'products' => $products,
            'status'   => $status,
        );

        if ( $form_id > 0 ) {
            // Update existing form
            $result = $forms->update( $form_id, $data );
            if ( $result ) {
                wp_send_json_success( array(
                    'message' => __( 'Form updated successfully.', 'unstoppable-cod-order-forms-for-woocommerce' ),
                    'form_id' => $form_id,
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Failed to update form. Please try again.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
            }
        } else {
            // Create new form
            $new_id = $forms->create( $data );
            if ( $new_id ) {
                wp_send_json_success( array(
                    'message' => __( 'Form created successfully.', 'unstoppable-cod-order-forms-for-woocommerce' ),
                    'form_id' => $new_id,
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Failed to create form. Please check the form name is not empty.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
            }
        }

        wp_send_json_error( array( 'message' => __( 'Unknown error occurred.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Delete form.
     */
    public function delete_form() {
        $this->verify_admin_request();

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $forms = new UCOF_Forms();
        $result = $forms->delete( $form_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Form deleted successfully.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to delete form.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Duplicate form.
     */
    public function duplicate_form() {
        $this->verify_admin_request();

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $forms = new UCOF_Forms();
        $form = $forms->get( $form_id );

        if ( ! $form ) {
            wp_send_json_error( array( 'message' => __( 'Form not found.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $new_data = array(
            'name'     => $form->name . ' ' . __( '(Copy)', 'unstoppable-cod-order-forms-for-woocommerce' ),
            'slug'     => $form->slug . '-copy-' . time(),
            'settings' => maybe_unserialize( $form->settings ),
            'products' => maybe_unserialize( $form->products ),
            'status'   => 'draft',
        );

        $new_id = $forms->create( $new_data );

        if ( $new_id ) {
            wp_send_json_success( array(
                'message' => __( 'Form duplicated successfully.', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'form_id' => $new_id,
            ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to duplicate form.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Save zone.
     */
    public function save_zone() {
        $this->verify_admin_request();

        $zone_id = isset( $_POST['zone_id'] ) ? absint( $_POST['zone_id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $status = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : 'active';
        $fee_type = isset( $_POST['fee_type'] ) ? sanitize_key( $_POST['fee_type'] ) : 'flat';
        $fee_amount = isset( $_POST['fee_amount'] ) ? floatval( $_POST['fee_amount'] ) : 0;
        $free_threshold = isset( $_POST['free_threshold'] ) ? floatval( $_POST['free_threshold'] ) : 0;
        $sort_order = isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0;

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => __( 'Zone name is required.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        // Regions - handle both textarea string and array input
        $regions = array();
        $regions_debug = 'not set';
        
        if ( isset( $_POST['regions'] ) ) {
            $regions_debug = 'isset, type: ' . gettype( $_POST['regions'] );
            
            if ( is_array( $_POST['regions'] ) ) {
                // Array format (legacy)
                foreach ( $_POST['regions'] as $region ) {
                    if ( is_array( $region ) ) {
                        $regions[] = isset( $region['value'] ) ? sanitize_text_field( $region['value'] ) : '';
                    } else {
                        $regions[] = sanitize_text_field( $region );
                    }
                }
                $regions_debug .= ', array count: ' . count( $_POST['regions'] );
            } else {
                // String format (textarea with newlines)
                $regions_raw = sanitize_textarea_field( $_POST['regions'] );
                $regions = array_filter( array_map( 'trim', explode( "\n", $regions_raw ) ) );
                $regions_debug .= ', string value: "' . substr( $regions_raw, 0, 50 ) . '"';
            }
        }
        // Remove empty values
        $regions = array_filter( $regions );
        $regions_debug .= ', final count: ' . count( $regions );
        
        // Log for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        }

        $zones = new UCOF_Zones();

        $data = array(
            'name'           => $name,
            'regions'        => $regions,
            'fee_type'       => $fee_type,
            'fee_amount'     => $fee_amount,
            'fee_settings'   => $fee_settings,
            'free_threshold' => $free_threshold,
            'status'         => $status,
            'sort_order'     => $sort_order,
        );

        if ( $zone_id > 0 ) {
            // Update existing zone
            $result = $zones->update( $zone_id, $data );
            if ( $result ) {
                $region_count = count( $regions );
                wp_send_json_success( array(
                    'message' => sprintf( 
                        /* translators: %d: number of regions saved */
                        __( 'Zone updated successfully! %d region(s) saved.', 'unstoppable-cod-order-forms-for-woocommerce' ), 
                        $region_count 
                    ),
                    'zone_id' => $zone_id,
                    'regions_count' => $region_count,
                ) );
            }
        } else {
            // Create new zone
            $new_id = $zones->create( $data );
            if ( $new_id ) {
                $region_count = count( $regions );
                wp_send_json_success( array(
                    'message' => sprintf( 
                        /* translators: %d: number of regions saved */
                        __( 'Zone created successfully! %d region(s) saved.', 'unstoppable-cod-order-forms-for-woocommerce' ), 
                        $region_count 
                    ),
                    'zone_id' => $new_id,
                    'regions_count' => $region_count,
                    'redirect' => admin_url( 'admin.php?page=ucof-dashboard&tab=zones&action=edit&id=' . $new_id ),
                ) );
            }
        }

        wp_send_json_error( array( 'message' => __( 'Failed to save zone. Please try again.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Delete zone.
     */
    public function delete_zone() {
        $this->verify_admin_request();

        $zone_id = isset( $_POST['zone_id'] ) ? absint( $_POST['zone_id'] ) : 0;

        if ( ! $zone_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid zone ID.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $zones = new UCOF_Zones();
        $result = $zones->delete( $zone_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Zone deleted successfully.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to delete zone.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Update order status.
     */
    public function update_order_status() {
        $this->verify_admin_request();

        global $wpdb;

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $status = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';

        if ( ! $order_id || empty( $status ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order ID or status.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $allowed_statuses = array( 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned' );
        if ( ! in_array( $status, $allowed_statuses, true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid status.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $table = $wpdb->prefix . 'ucof_orders';
        $result = $wpdb->update(
            $table,
            array(
                'status'     => $status,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $order_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( false !== $result ) {
            // Also update WooCommerce order if linked
            $order = $wpdb->get_row( $wpdb->prepare( "SELECT wc_order_id FROM {$table} WHERE id = %d", $order_id ) );
            if ( $order && $order->wc_order_id ) {
                $wc_order = wc_get_order( $order->wc_order_id );
                if ( $wc_order ) {
                    $wc_status_map = array(
                        'pending'    => 'pending',
                        'confirmed'  => 'processing',
                        'processing' => 'processing',
                        'shipped'    => 'completed',
                        'delivered'  => 'completed',
                        'cancelled'  => 'cancelled',
                        'returned'   => 'refunded',
                    );
                    if ( isset( $wc_status_map[ $status ] ) ) {
                        $wc_order->update_status( $wc_status_map[ $status ], __( 'Status updated via COD Forms.', 'unstoppable-cod-order-forms-for-woocommerce' ) );
                    }
                }
            }

            wp_send_json_success( array( 'message' => __( 'Order status updated.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Failed to update order status.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Save settings.
     */
    public function save_settings() {
        $this->verify_admin_request();

        $settings = array(
            'create_wc_order'     => isset( $_POST['create_wc_order'] ) ? 1 : 0,
            'default_order_status'=> isset( $_POST['default_order_status'] ) ? sanitize_key( $_POST['default_order_status'] ) : 'pending',
            'phone_required'      => isset( $_POST['phone_required'] ) ? 1 : 0,
            'email_required'      => isset( $_POST['email_required'] ) ? 1 : 0,
            'address_required'    => isset( $_POST['address_required'] ) ? 1 : 0,
            'enable_stock_check'  => isset( $_POST['enable_stock_check'] ) ? 1 : 0,
            'currency_position'   => isset( $_POST['currency_position'] ) ? sanitize_key( $_POST['currency_position'] ) : 'left',
            'thousand_separator'  => isset( $_POST['thousand_separator'] ) ? sanitize_text_field( $_POST['thousand_separator'] ) : ',',
            'decimal_separator'   => isset( $_POST['decimal_separator'] ) ? sanitize_text_field( $_POST['decimal_separator'] ) : '.',
            'decimals'            => isset( $_POST['decimals'] ) ? absint( $_POST['decimals'] ) : 2,
        );

        update_option( 'ucof_settings', $settings );

        wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Save integrations.
     */
    public function save_integrations() {
        $this->verify_admin_request();

        // Webhook Integration
        update_option( UCOF_OPTION_PREFIX . '_enable_webhook', isset( $_POST['enable_webhook'] ) ? '1' : '' );
        update_option( UCOF_OPTION_PREFIX . '_webhook_url', isset( $_POST['webhook_url'] ) ? esc_url_raw( $_POST['webhook_url'] ) : '' );

        // Google Sheets
        update_option( UCOF_OPTION_PREFIX . '_enable_google_sheets', isset( $_POST['enable_google_sheets'] ) ? '1' : '' );
        update_option( UCOF_OPTION_PREFIX . '_google_sheets_url', isset( $_POST['google_sheets_url'] ) ? esc_url_raw( $_POST['google_sheets_url'] ) : '' );

        // WhatsApp
        update_option( UCOF_OPTION_PREFIX . '_enable_whatsapp', isset( $_POST['enable_whatsapp'] ) ? '1' : '' );
        update_option( UCOF_OPTION_PREFIX . '_whatsapp_number', isset( $_POST['whatsapp_number'] ) ? sanitize_text_field( $_POST['whatsapp_number'] ) : '' );
        update_option( UCOF_OPTION_PREFIX . '_whatsapp_template', isset( $_POST['whatsapp_template'] ) ? sanitize_textarea_field( $_POST['whatsapp_template'] ) : '' );

        // Facebook Pixel
        update_option( UCOF_OPTION_PREFIX . '_enable_facebook_pixel', isset( $_POST['enable_facebook_pixel'] ) ? '1' : '' );
        update_option( UCOF_OPTION_PREFIX . '_facebook_pixel_id', isset( $_POST['facebook_pixel_id'] ) ? sanitize_text_field( $_POST['facebook_pixel_id'] ) : '' );

        // TikTok Pixel
        update_option( UCOF_OPTION_PREFIX . '_enable_tiktok_pixel', isset( $_POST['enable_tiktok_pixel'] ) ? '1' : '' );
        update_option( UCOF_OPTION_PREFIX . '_tiktok_pixel_id', isset( $_POST['tiktok_pixel_id'] ) ? sanitize_text_field( $_POST['tiktok_pixel_id'] ) : '' );

        // Google Analytics 4
        update_option( UCOF_OPTION_PREFIX . '_enable_ga4', isset( $_POST['enable_ga4'] ) ? '1' : '' );
        update_option( UCOF_OPTION_PREFIX . '_ga4_measurement_id', isset( $_POST['ga4_measurement_id'] ) ? sanitize_text_field( $_POST['ga4_measurement_id'] ) : '' );

        // Snapchat Pixel
        update_option( UCOF_OPTION_PREFIX . '_enable_snapchat_pixel', isset( $_POST['enable_snapchat_pixel'] ) ? '1' : '' );
        update_option( UCOF_OPTION_PREFIX . '_snapchat_pixel_id', isset( $_POST['snapchat_pixel_id'] ) ? sanitize_text_field( $_POST['snapchat_pixel_id'] ) : '' );

        wp_send_json_success( array( 'message' => __( 'Integrations saved successfully!', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Test webhook connection.
     */
    public function test_webhook() {
        $this->verify_admin_request();

        $url = isset( $_POST['webhook_url'] ) ? esc_url_raw( $_POST['webhook_url'] ) : '';

        if ( empty( $url ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a webhook URL.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        // Send test data
        $test_data = array(
            'test'          => true,
            'date'          => current_time( 'mysql' ),
            'order_id'      => 'TEST-' . wp_rand( 1000, 9999 ),
            'customer_name' => 'Test Customer',
            'customer_phone'=> '+1234567890',
            'customer_email'=> 'test@example.com',
            'products'      => 'Test Product x 1',
            'subtotal'      => 100,
            'delivery_fee'  => 10,
            'total'         => 110,
            'status'        => 'Test',
            'notes'         => 'This is a test webhook from Unstoppable COD Order Forms',
        );

        $response = wp_remote_post( $url, array(
            'timeout'   => 30,
            'headers'   => array( 'Content-Type' => 'application/json' ),
            'body'      => wp_json_encode( $test_data ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
        }

        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 200 && $code < 300 ) {
            wp_send_json_success( array( 'message' => __( 'Webhook test successful! Check your destination.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        } else {
            /* translators: %d: HTTP status code */
            wp_send_json_error( array( 'message' => sprintf( __( 'Webhook returned status code: %d', 'unstoppable-cod-order-forms-for-woocommerce' ), $code ) ) );
        }
    }

    /**
     * Search products.
     */
    public function search_products() {
        $this->verify_admin_request();

        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            's'              => $search,
        );

        $products = get_posts( $args );
        $results = array();

        foreach ( $products as $product ) {
            $wc_product = wc_get_product( $product->ID );
            $results[] = array(
                'id'    => $product->ID,
                'text'  => $product->post_title,
                'price' => $wc_product ? $wc_product->get_price() : 0,
            );
        }

        wp_send_json_success( array( 'products' => $results ) );
    }

    /**
     * Get product categories.
     */
    public function get_product_categories() {
        $this->verify_admin_request();

        $terms = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        ) );

        $categories = array();
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $categories[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                );
            }
        }

        wp_send_json_success( array( 'categories' => $categories ) );
    }

    /**
     * Test Google Sheets connection.
     */
    public function test_google_sheets() {
        $this->verify_admin_request();

        $integrations = new UCOF_Integrations();
        $result = $integrations->test_google_sheets();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Connection successful!', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
    }

    /**
     * Dismiss notice.
     */
    public function dismiss_notice() {
        check_ajax_referer( 'ucof_dismiss_notice', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( 'Permission denied.' );
        }

        $notice = isset( $_POST['notice'] ) ? sanitize_key( $_POST['notice'] ) : '';

        if ( empty( $notice ) ) {
            wp_send_json_error( 'Invalid notice.' );
        }

        update_option( 'ucof_' . $notice . '_notice_dismissed', true );

        if ( in_array( $notice, array( 'upgrade' ), true ) ) {
            update_option( 'ucof_' . $notice . '_notice_dismissed_date', time() );
        }

        wp_send_json_success();
    }

    /**
     * Submit order (frontend).
     */
    public function submit_order() {
        $this->verify_frontend_request( 'ucof_submit_order' );

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $forms = new UCOF_Forms();
        $form = $forms->get( $form_id );

        if ( ! $form || $form->status !== 'active' ) {
            wp_send_json_error( array( 'message' => __( 'Form not found or inactive.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $settings = maybe_unserialize( $form->settings );

        // Customer data
        $customer_name = isset( $_POST['customer_name'] ) ? sanitize_text_field( $_POST['customer_name'] ) : '';
        $customer_phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( $_POST['customer_phone'] ) : '';
        $customer_email = isset( $_POST['customer_email'] ) ? sanitize_email( $_POST['customer_email'] ) : '';
        $customer_address = isset( $_POST['customer_address'] ) ? sanitize_textarea_field( $_POST['customer_address'] ) : '';
        $customer_city = isset( $_POST['customer_city'] ) ? sanitize_text_field( $_POST['customer_city'] ) : '';
        $customer_region = isset( $_POST['customer_region'] ) ? sanitize_text_field( $_POST['customer_region'] ) : '';
        $notes = isset( $_POST['notes'] ) ? sanitize_textarea_field( $_POST['notes'] ) : '';

        // Validation
        if ( empty( $customer_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Name is required.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        if ( ! empty( $settings['require_phone'] ) && empty( $customer_phone ) ) {
            wp_send_json_error( array( 'message' => __( 'Phone number is required.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        if ( ! empty( $settings['require_email'] ) && ( empty( $customer_email ) || ! is_email( $customer_email ) ) ) {
            wp_send_json_error( array( 'message' => __( 'Valid email is required.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        if ( ! empty( $settings['require_address'] ) && empty( $customer_address ) ) {
            wp_send_json_error( array( 'message' => __( 'Address is required.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        // Products
        $products = isset( $_POST['products'] ) && is_array( $_POST['products'] ) ? $_POST['products'] : array();

        if ( empty( $products ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select at least one product.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        // Process products
        $order_products = array();
        $subtotal = 0;

        foreach ( $products as $item ) {
            $product_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
            $quantity = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;

            if ( ! $product_id || $quantity < 1 ) {
                continue;
            }

            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            // Check stock
            $global_settings = get_option( 'ucof_settings', array() );
            if ( ! empty( $global_settings['enable_stock_check'] ) && ! $product->is_in_stock() ) {
                wp_send_json_error( array(
                    /* translators: %s: product name */
                    'message' => sprintf( __( '%s is out of stock.', 'unstoppable-cod-order-forms-for-woocommerce' ), $product->get_name() ),
                ) );
            }

            $price = floatval( $product->get_price() );
            $line_total = $price * $quantity;

            $order_products[] = array(
                'id'         => $product_id,
                'name'       => $product->get_name(),
                'price'      => $price,
                'quantity'   => $quantity,
                'line_total' => $line_total,
            );

            $subtotal += $line_total;
        }

        if ( empty( $order_products ) ) {
            wp_send_json_error( array( 'message' => __( 'No valid products selected.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        // Calculate delivery fee
        $delivery_fee = 0;
        $zone_id = 0;

        if ( ! empty( $settings['enable_zones'] ) ) {
            $zones = new UCOF_Zones();
            $zone = $zones->find_zone( $customer_city, $customer_region );

            if ( $zone ) {
                $zone_id = $zone->id;
                $delivery_fee = $zones->calculate_fee( $zone, $subtotal );
            }
        }

        $total = $subtotal + $delivery_fee;

        // Create order in our table
        global $wpdb;
        $orders_table = $wpdb->prefix . 'ucof_orders';

        $order_data = array(
            'form_id'          => $form_id,
            'customer_name'    => $customer_name,
            'customer_phone'   => $customer_phone,
            'customer_email'   => $customer_email,
            'customer_address' => $customer_address,
            'customer_city'    => $customer_city,
            'customer_region'  => $customer_region,
            'zone_id'          => $zone_id,
            'products'         => maybe_serialize( $order_products ),
            'subtotal'         => $subtotal,
            'delivery_fee'     => $delivery_fee,
            'total'            => $total,
            'status'           => 'pending',
            'notes'            => $notes,
            'tracking_data'    => maybe_serialize( array(
                'ip'         => $this->get_client_ip(),
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                'referrer'   => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
            ) ),
            'created_at'       => current_time( 'mysql' ),
            'updated_at'       => current_time( 'mysql' ),
        );

        $result = $wpdb->insert( $orders_table, $order_data );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => __( 'Failed to create order.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $order_id = $wpdb->insert_id;

        // Create WooCommerce order if enabled
        $wc_order_id = 0;
        $global_settings = get_option( 'ucof_settings', array() );

        if ( ! empty( $global_settings['create_wc_order'] ) ) {
            $wc_order_id = $this->create_wc_order( $order_id, $order_data, $order_products, $delivery_fee );

            if ( $wc_order_id ) {
                $wpdb->update(
                    $orders_table,
                    array( 'wc_order_id' => $wc_order_id ),
                    array( 'id' => $order_id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }

        // Trigger integrations
        $integrations = new UCOF_Integrations();
        $integrations->process_new_order( $order_id, $order_data, $order_products );

        // Success response
        $response = array(
            'message'   => $settings['success_message'] ?? __( 'Order placed successfully!', 'unstoppable-cod-order-forms-for-woocommerce' ),
            'order_id'  => $order_id,
            'order_ref' => 'COD-' . str_pad( $order_id, 6, '0', STR_PAD_LEFT ),
        );

        if ( ! empty( $settings['redirect_url'] ) ) {
            $response['redirect_url'] = $settings['redirect_url'];
        }

        wp_send_json_success( $response );
    }

    /**
     * Calculate delivery fee (frontend).
     */
    public function calculate_delivery() {
        $this->verify_frontend_request( 'ucof_calculate_delivery' );

        $city = isset( $_POST['city'] ) ? sanitize_text_field( $_POST['city'] ) : '';
        $region = isset( $_POST['region'] ) ? sanitize_text_field( $_POST['region'] ) : '';
        $subtotal = isset( $_POST['subtotal'] ) ? floatval( $_POST['subtotal'] ) : 0;

        $zones = new UCOF_Zones();
        $zone = $zones->find_zone( $city, $region );

        if ( ! $zone ) {
            wp_send_json_success( array(
                'found'        => false,
                'delivery_fee' => 0,
                'message'      => __( 'Delivery not available for this location.', 'unstoppable-cod-order-forms-for-woocommerce' ),
            ) );
        }

        $delivery_fee = $zones->calculate_fee( $zone, $subtotal );

        wp_send_json_success( array(
            'found'        => true,
            'zone_name'    => $zone->name,
            'delivery_fee' => $delivery_fee,
            'fee_type'     => $zone->fee_type,
        ) );
    }

    /**
     * Get products for form (frontend).
     */
    public function get_products() {
        $this->verify_frontend_request( 'ucof_get_products' );

        $form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

        if ( ! $form_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid form.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $forms = new UCOF_Forms();
        $form = $forms->get( $form_id );

        if ( ! $form ) {
            wp_send_json_error( array( 'message' => __( 'Form not found.', 'unstoppable-cod-order-forms-for-woocommerce' ) ) );
        }

        $settings = maybe_unserialize( $form->settings );
        $selected_products = maybe_unserialize( $form->products );

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        switch ( $settings['product_source'] ?? 'all' ) {
            case 'category':
                if ( ! empty( $settings['product_category'] ) ) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $settings['product_category'],
                        ),
                    );
                }
                break;

            case 'selected':
                if ( ! empty( $selected_products ) ) {
                    $args['post__in'] = $selected_products;
                    $args['orderby'] = 'post__in';
                } else {
                    wp_send_json_success( array( 'products' => array() ) );
                }
                break;
        }

        $products = get_posts( $args );
        $results = array();

        foreach ( $products as $product ) {
            $wc_product = wc_get_product( $product->ID );
            if ( ! $wc_product ) {
                continue;
            }

            $image_id = $wc_product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );

            $results[] = array(
                'id'          => $product->ID,
                'name'        => $wc_product->get_name(),
                'price'       => floatval( $wc_product->get_price() ),
                'price_html'  => $wc_product->get_price_html(),
                'image'       => $image_url,
                'in_stock'    => $wc_product->is_in_stock(),
                'weight'      => floatval( $wc_product->get_weight() ),
            );
        }

        wp_send_json_success( array( 'products' => $results ) );
    }

    /**
     * Create WooCommerce order.
     *
     * @param int   $ucof_order_id    Our order ID.
     * @param array $order_data       Order data.
     * @param array $products         Products array.
     * @param float $delivery_fee     Delivery fee.
     * @return int|false WC order ID or false.
     */
    private function create_wc_order( $ucof_order_id, $order_data, $products, $delivery_fee ) {
        $order = wc_create_order();

        if ( is_wp_error( $order ) ) {
            return false;
        }

        // Add products
        foreach ( $products as $item ) {
            $product = wc_get_product( $item['id'] );
            if ( $product ) {
                $order->add_product( $product, $item['quantity'] );
            }
        }

        // Add delivery fee as a fee line
        if ( $delivery_fee > 0 ) {
            $fee = new WC_Order_Item_Fee();
            $fee->set_name( __( 'Delivery Fee', 'unstoppable-cod-order-forms-for-woocommerce' ) );
            $fee->set_amount( $delivery_fee );
            $fee->set_total( $delivery_fee );
            $order->add_item( $fee );
        }

        // Set billing address
        $order->set_billing_first_name( $order_data['customer_name'] );
        $order->set_billing_phone( $order_data['customer_phone'] );
        $order->set_billing_email( $order_data['customer_email'] );
        $order->set_billing_address_1( $order_data['customer_address'] );
        $order->set_billing_city( $order_data['customer_city'] );
        $order->set_billing_state( $order_data['customer_region'] );

        // Set shipping address (same as billing)
        $order->set_shipping_first_name( $order_data['customer_name'] );
        $order->set_shipping_address_1( $order_data['customer_address'] );
        $order->set_shipping_city( $order_data['customer_city'] );
        $order->set_shipping_state( $order_data['customer_region'] );

        // Set payment method
        $order->set_payment_method( 'cod' );
        $order->set_payment_method_title( __( 'Cash on Delivery', 'unstoppable-cod-order-forms-for-woocommerce' ) );

        // Add meta
        $order->update_meta_data( '_ucof_order_id', $ucof_order_id );
        $order->update_meta_data( '_ucof_form_id', $order_data['form_id'] );

        // Add customer note
        if ( ! empty( $order_data['notes'] ) ) {
            $order->add_order_note( $order_data['notes'], true );
        }

        // Calculate totals and save
        $order->calculate_totals();

        // Set status
        $global_settings = get_option( 'ucof_settings', array() );
        $default_status = $global_settings['default_order_status'] ?? 'pending';
        $order->set_status( $default_status );

        $order->save();

        return $order->get_id();
    }

    /**
     * Get client IP address.
     *
     * @return string IP address.
     */
    private function get_client_ip() {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return $ip;
    }
}
