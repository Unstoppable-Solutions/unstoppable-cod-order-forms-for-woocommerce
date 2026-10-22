<?php
/**
 * Zones Class
 *
 * Handles delivery zones and fee calculations.
 *
 * @package Unstoppable_COD_Forms
 */

defined( 'ABSPATH' ) || exit;

/**
 * Zones Class.
 */
class UCOF_Zones {

    /**
     * Constructor.
     */
    public function __construct() {
        // Initialize zones functionality.
    }

    /**
     * Get zone by ID.
     *
     * @param int $zone_id Zone ID.
     * @return object|null Zone object or null.
     */
    public function get_zone( $zone_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ucof_zones WHERE id = %d",
            $zone_id
        ) );
    }

    /**
     * Get all active zones.
     *
     * @return array Zones.
     */
    public function get_active_zones() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ucof_zones WHERE status = 'active' ORDER BY sort_order ASC, name ASC"
        );
    }

    /**
     * Get all zones.
     *
     * @return array Zones.
     */
    public function get_all_zones() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ucof_zones ORDER BY sort_order ASC, name ASC"
        );
    }

    /**
     * Create a new zone.
     *
     * @param array $data Zone data.
     * @return int|false Zone ID on success, false on failure.
     */
    public function create( $data ) {
        global $wpdb;

        $name           = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $regions        = isset( $data['regions'] ) ? $data['regions'] : array();
        $fee_type       = isset( $data['fee_type'] ) ? sanitize_key( $data['fee_type'] ) : 'flat';
        $fee_amount     = isset( $data['fee_amount'] ) ? floatval( $data['fee_amount'] ) : 0;
        $free_threshold = isset( $data['free_threshold'] ) && '' !== $data['free_threshold'] ? floatval( $data['free_threshold'] ) : null;
        $status         = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';
        $sort_order     = isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0;
        $fee_settings   = isset( $data['fee_settings'] ) ? $data['fee_settings'] : array();

        if ( empty( $name ) ) {
            return false;
        }

        // Parse regions if string
        if ( is_string( $regions ) ) {
            $regions = array_filter( array_map( 'trim', explode( "\n", $regions ) ) );
        }
        $regions = array_map( 'sanitize_text_field', (array) $regions );

        $zone_data = array(
            'name'           => $name,
            'regions'        => wp_json_encode( array_values( $regions ) ),
            'fee_type'       => $fee_type,
            'fee_amount'     => $fee_amount,
            'fee_settings'   => wp_json_encode( (array) $fee_settings ),
            'free_threshold' => $free_threshold,
            'status'         => $status,
            'sort_order'     => $sort_order,
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'ucof_zones',
            $zone_data,
            array( '%s', '%s', '%s', '%f', '%s', '%f', '%s', '%d' )
        );

        if ( false === $result ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            }
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update an existing zone.
     *
     * @param int   $zone_id Zone ID.
     * @param array $data    Zone data.
     * @return bool Success.
     */
    public function update( $zone_id, $data ) {
        global $wpdb;

        $name           = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $regions        = isset( $data['regions'] ) ? $data['regions'] : array();
        $fee_type       = isset( $data['fee_type'] ) ? sanitize_key( $data['fee_type'] ) : 'flat';
        $fee_amount     = isset( $data['fee_amount'] ) ? floatval( $data['fee_amount'] ) : 0;
        $free_threshold = isset( $data['free_threshold'] ) && '' !== $data['free_threshold'] ? floatval( $data['free_threshold'] ) : null;
        $status         = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active';
        $sort_order     = isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0;
        $fee_settings   = isset( $data['fee_settings'] ) ? $data['fee_settings'] : array();

        if ( empty( $name ) ) {
            return false;
        }

        // Parse regions if string
        if ( is_string( $regions ) ) {
            $regions = array_filter( array_map( 'trim', explode( "\n", $regions ) ) );
        }
        $regions = array_map( 'sanitize_text_field', (array) $regions );

        $zone_data = array(
            'name'           => $name,
            'regions'        => wp_json_encode( array_values( $regions ) ),
            'fee_type'       => $fee_type,
            'fee_amount'     => $fee_amount,
            'fee_settings'   => wp_json_encode( (array) $fee_settings ),
            'free_threshold' => $free_threshold,
            'status'         => $status,
            'sort_order'     => $sort_order,
            'updated_at'     => current_time( 'mysql' ),
        );

        $result = $wpdb->update(
            $wpdb->prefix . 'ucof_zones',
            $zone_data,
            array( 'id' => $zone_id ),
            array( '%s', '%s', '%s', '%f', '%s', '%f', '%s', '%d', '%s' ),
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Save zone (legacy method).
     *
     * @param array $data Zone data.
     * @return int|false Zone ID on success, false on failure.
     */
    public function save_zone( $data ) {
        $zone_id = isset( $data['zone_id'] ) ? absint( $data['zone_id'] ) : 0;
        
        if ( $zone_id > 0 ) {
            return $this->update( $zone_id, $data ) ? $zone_id : false;
        } else {
            return $this->create( $data );
        }
    }

    /**
     * Delete zone.
     *
     * @param int $zone_id Zone ID.
     * @return bool Success.
     */
    public function delete_zone( $zone_id ) {
        global $wpdb;
        return false !== $wpdb->delete(
            $wpdb->prefix . 'ucof_zones',
            array( 'id' => $zone_id ),
            array( '%d' )
        );
    }

    /**
     * Find matching zone for a location.
     *
     * @param string $city    City name.
     * @param string $region  Region/state name.
     * @param string $postal  Postal code.
     * @return object|null Matching zone or null.
     */
    public function find_zone( $city = '', $region = '', $postal = '' ) {
        $zones = $this->get_active_zones();

        foreach ( $zones as $zone ) {
            $zone_regions = json_decode( $zone->regions, true ) ?: array();

            foreach ( $zone_regions as $zone_region ) {
                $zone_region = strtolower( trim( $zone_region ) );

                // Check city.
                if ( ! empty( $city ) && strtolower( $city ) === $zone_region ) {
                    return $zone;
                }

                // Check region/state.
                if ( ! empty( $region ) && strtolower( $region ) === $zone_region ) {
                    return $zone;
                }

                // Check postal code (prefix match).
                if ( ! empty( $postal ) ) {
                    $postal_lower = strtolower( $postal );
                    if ( $postal_lower === $zone_region || strpos( $postal_lower, $zone_region ) === 0 ) {
                        return $zone;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Calculate delivery fee.
     *
     * @param object $zone       Zone object.
     * @param float  $order_total Order subtotal.
     * @param float  $weight     Total weight.
     * @return float Delivery fee.
     */
    public function calculate_fee( $zone, $order_total = 0, $weight = 0 ) {
        if ( ! $zone ) {
            return 0;
        }

        // Check free threshold.
        if ( ! empty( $zone->free_threshold ) && $order_total >= $zone->free_threshold ) {
            return 0;
        }

        $fee_settings = json_decode( $zone->fee_settings, true ) ?: array();

        switch ( $zone->fee_type ) {
            case 'flat':
                return floatval( $zone->fee_amount );

            case 'percent':
                return $order_total * ( floatval( $zone->fee_amount ) / 100 );

            case 'free':
                return 0;

            default:
                return floatval( $zone->fee_amount );
        }
    }

    /**
     * Get zones as options for select field.
     *
     * @return array Zone options.
     */
    public function get_zone_options() {
        $zones   = $this->get_active_zones();
        $options = array();

        foreach ( $zones as $zone ) {
            $regions = json_decode( $zone->regions, true ) ?: array();
            $options[ $zone->id ] = array(
                'name'    => $zone->name,
                'regions' => $regions,
                'fee'     => $this->format_fee_display( $zone ),
            );
        }

        return $options;
    }

    /**
     * Format fee for display.
     *
     * @param object $zone Zone object.
     * @return string Formatted fee.
     */
    public function format_fee_display( $zone ) {
        switch ( $zone->fee_type ) {
            case 'free':
                return __( 'Free', 'unstoppable-cod-order-forms-for-woocommerce' );

            case 'percent':
                return $zone->fee_amount . '%';

            case 'flat':
            default:
                return wc_price( $zone->fee_amount );
        }
    }
}
