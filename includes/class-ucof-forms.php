<?php
/**
 * Forms Class
 *
 * Handles form creation, management, and rendering.
 *
 * @package Unstoppable_COD_Forms
 */

defined( 'ABSPATH' ) || exit;

/**
 * Forms Class.
 */
class UCOF_Forms {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'ucof_form', array( $this, 'render_shortcode' ) );
    }

    /**
     * Get form by ID.
     *
     * @param int $form_id Form ID.
     * @return object|null Form object or null.
     */
    public function get_form( $form_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ucof_forms WHERE id = %d",
            $form_id
        ) );
    }

    /**
     * Get form by slug.
     *
     * @param string $slug Form slug.
     * @return object|null Form object or null.
     */
    public function get_form_by_slug( $slug ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ucof_forms WHERE slug = %s",
            $slug
        ) );
    }

    /**
     * Get all active forms.
     *
     * @return array Forms.
     */
    public function get_active_forms() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ucof_forms WHERE status = 'active' ORDER BY name ASC"
        );
    }

    /**
     * Create a new form.
     *
     * @param array $data Form data.
     * @return int|false Form ID on success, false on failure.
     */
    public function create( $data ) {
        global $wpdb;

        $name     = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $slug     = isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
        $status   = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';
        $settings = isset( $data['settings'] ) ? $data['settings'] : array();
        $products = isset( $data['products'] ) ? array_map( 'absint', (array) $data['products'] ) : array();

        if ( empty( $name ) ) {
            return false;
        }

        // Make slug unique if it already exists
        $original_slug = $slug;
        $counter = 1;
        while ( $this->get_form_by_slug( $slug ) ) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        $form_data = array(
            'name'       => $name,
            'slug'       => $slug,
            'status'     => $status,
            'settings'   => wp_json_encode( $settings ),
            'products'   => wp_json_encode( $products ),
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'ucof_forms',
            $form_data,
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            // Log error for debugging
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            }
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update an existing form.
     *
     * @param int   $form_id Form ID.
     * @param array $data    Form data.
     * @return bool Success.
     */
    public function update( $form_id, $data ) {
        global $wpdb;

        $name     = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $slug     = isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $name );
        $status   = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';
        $settings = isset( $data['settings'] ) ? $data['settings'] : array();
        $products = isset( $data['products'] ) ? array_map( 'absint', (array) $data['products'] ) : array();

        if ( empty( $name ) ) {
            return false;
        }

        $form_data = array(
            'name'       => $name,
            'slug'       => $slug,
            'status'     => $status,
            'settings'   => wp_json_encode( $settings ),
            'products'   => wp_json_encode( $products ),
            'updated_at' => current_time( 'mysql' ),
        );

        $result = $wpdb->update(
            $wpdb->prefix . 'ucof_forms',
            $form_data,
            array( 'id' => $form_id ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Save form (legacy method).
     *
     * @param array $data Form data.
     * @return int|false Form ID on success, false on failure.
     */
    public function save_form( $data ) {
        $form_id = isset( $data['form_id'] ) ? absint( $data['form_id'] ) : 0;
        
        if ( $form_id > 0 ) {
            return $this->update( $form_id, $data ) ? $form_id : false;
        } else {
            return $this->create( $data );
        }
    }

    /**
     * Delete form.
     *
     * @param int $form_id Form ID.
     * @return bool Success.
     */
    public function delete_form( $form_id ) {
        global $wpdb;
        return false !== $wpdb->delete(
            $wpdb->prefix . 'ucof_forms',
            array( 'id' => $form_id ),
            array( '%d' )
        );
    }

    /**
     * Render form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Form HTML.
     */
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id'   => 0,
            'slug' => '',
        ), $atts, 'ucof_form' );

        if ( ! empty( $atts['id'] ) ) {
            $form = $this->get_form( absint( $atts['id'] ) );
        } elseif ( ! empty( $atts['slug'] ) ) {
            $form = $this->get_form_by_slug( sanitize_title( $atts['slug'] ) );
        } else {
            return '<p>' . esc_html__( 'Form not specified.', 'unstoppable-cod-order-forms-for-woocommerce' ) . '</p>';
        }

        if ( ! $form || 'active' !== $form->status ) {
            return '<p>' . esc_html__( 'Form not found or inactive.', 'unstoppable-cod-order-forms-for-woocommerce' ) . '</p>';
        }

        return $this->render_form( $form );
    }

    /**
     * Render form HTML.
     *
     * @param object $form Form object.
     * @return string Form HTML.
     */
    public function render_form( $form ) {
        $settings = json_decode( $form->settings, true ) ?: array();
        $products = $this->get_form_products( $form );

        ob_start();
        include UCOF_PLUGIN_DIR . 'templates/form.php';
        return ob_get_clean();
    }

    /**
     * Get products for form.
     *
     * @param object $form Form object.
     * @return array Products.
     */
    public function get_form_products( $form ) {
        $settings = json_decode( $form->settings, true ) ?: array();
        $product_ids = json_decode( $form->products, true ) ?: array();
        $source = $settings['product_source'] ?? 'all';

        $args = array(
            'status' => 'publish',
            'limit'  => -1,
        );

        if ( 'selected' === $source && ! empty( $product_ids ) ) {
            $args['include'] = $product_ids;
        } elseif ( 'category' === $source && ! empty( $settings['product_categories'] ) ) {
            $args['category'] = $settings['product_categories'];
        }

        return wc_get_products( $args );
    }
}
