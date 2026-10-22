<?php
/**
 * Integrations Handler Class
 *
 * Handles third-party integrations: Webhooks, Google Sheets, WhatsApp, Pixels.
 *
 * @package Unstoppable_COD_Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integrations Handler Class.
 */
class UCOF_Integrations {

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
        // Enqueue tracking scripts properly
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tracking_scripts' ) );
        // Add tracking pixels to frontend (for pixels that must be inline)
        add_action( 'wp_head', array( $this, 'output_tracking_pixels' ), 1 );
        add_action( 'wp_footer', array( $this, 'output_pixel_events' ) );
    }

    /**
     * Enqueue tracking scripts properly.
     */
    public function enqueue_tracking_scripts() {
        // Google Analytics 4
        if ( get_option( UCOF_OPTION_PREFIX . '_enable_ga4' ) ) {
            $measurement_id = get_option( UCOF_OPTION_PREFIX . '_ga4_measurement_id', '' );
            if ( ! empty( $measurement_id ) ) {
                wp_enqueue_script(
                    'ucof-gtag',
                    'https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $measurement_id ),
                    array(),
                    null,
                    array( 'strategy' => 'async', 'in_footer' => false )
                );
                
                $inline_script = "window.dataLayer = window.dataLayer || [];
                    function gtag(){dataLayer.push(arguments);}
                    gtag('js', new Date());
                    gtag('config', '" . esc_js( $measurement_id ) . "');";
                wp_add_inline_script( 'ucof-gtag', $inline_script );
            }
        }
    }

    /**
     * Process a new order through all integrations.
     *
     * @param int   $order_id   Order ID.
     * @param array $order_data Order data.
     * @param array $products   Products array.
     */
    public function process_new_order( $order_id, $order_data, $products ) {
        // Prepare order payload
        $payload = $this->prepare_order_payload( $order_id, $order_data, $products );

        // Generic Webhook
        $this->send_to_webhook( $payload );

        // Google Sheets
        $this->send_to_google_sheets( $payload );

        // Store pixel event data for frontend tracking
        $this->store_purchase_event( $order_id, $order_data, $products );
    }

    /**
     * Prepare order payload for webhooks.
     *
     * @param int   $order_id   Order ID.
     * @param array $order_data Order data.
     * @param array $products   Products array.
     * @return array
     */
    private function prepare_order_payload( $order_id, $order_data, $products ) {
        // Format products list
        $product_lines = array();
        foreach ( $products as $item ) {
            $product_lines[] = $item['name'] . ' x' . $item['quantity'] . ' (' . wc_price( $item['total'] ) . ')';
        }

        return array(
            'date'             => current_time( 'Y-m-d H:i:s' ),
            'order_id'         => 'COD-' . str_pad( $order_id, 6, '0', STR_PAD_LEFT ),
            'order_id_raw'     => $order_id,
            'customer_name'    => $order_data['customer_name'] ?? '',
            'customer_phone'   => $order_data['customer_phone'] ?? '',
            'customer_email'   => $order_data['customer_email'] ?? '',
            'customer_city'    => $order_data['customer_city'] ?? '',
            'customer_region'  => $order_data['customer_region'] ?? '',
            'customer_address' => $order_data['customer_address'] ?? '',
            'products'         => implode( "\n", $product_lines ),
            'products_array'   => $products,
            'subtotal'         => floatval( $order_data['subtotal'] ?? 0 ),
            'delivery_fee'     => floatval( $order_data['delivery_fee'] ?? 0 ),
            'total'            => floatval( $order_data['total'] ?? 0 ),
            'currency'         => get_woocommerce_currency(),
            'status'           => 'Pending',
            'notes'            => $order_data['notes'] ?? '',
            'form_id'          => $order_data['form_id'] ?? 0,
            'site_url'         => home_url(),
            'site_name'        => get_bloginfo( 'name' ),
        );
    }

    /**
     * Send order to generic webhook URL.
     *
     * @param array $payload Order payload.
     * @return bool|WP_Error
     */
    public function send_to_webhook( $payload ) {
        if ( ! get_option( UCOF_OPTION_PREFIX . '_enable_webhook' ) ) {
            return false;
        }

        $webhook_url = get_option( UCOF_OPTION_PREFIX . '_webhook_url', '' );

        if ( empty( $webhook_url ) ) {
            return false;
        }

        $response = wp_remote_post( $webhook_url, array(
            'timeout'   => 30,
            'headers'   => array( 'Content-Type' => 'application/json' ),
            'body'      => wp_json_encode( $payload ),
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
        }

        return true;
    }

    /**
     * Send order to Google Sheets via Apps Script URL.
     *
     * @param array $payload Order payload.
     * @return bool|WP_Error
     */
    public function send_to_google_sheets( $payload ) {
        if ( ! get_option( UCOF_OPTION_PREFIX . '_enable_google_sheets' ) ) {
            return false;
        }

        $sheets_url = get_option( UCOF_OPTION_PREFIX . '_google_sheets_url', '' );

        if ( empty( $sheets_url ) ) {
            // Store for manual export if no URL configured
            $this->store_order_for_export( $payload );
            return false;
        }

        $response = wp_remote_post( $sheets_url, array(
            'timeout'   => 30,
            'headers'   => array( 'Content-Type' => 'application/json' ),
            'body'      => wp_json_encode( $payload ),
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            $this->store_order_for_export( $payload );
            return $response;
        }

        return true;
    }

    /**
     * Store order for manual export if webhooks fail.
     *
     * @param array $payload Order payload.
     */
    private function store_order_for_export( $payload ) {
        $pending = get_option( UCOF_OPTION_PREFIX . '_pending_exports', array() );
        $pending[] = array(
            'payload'    => $payload,
            'created_at' => current_time( 'mysql' ),
        );
        // Keep only last 100 pending exports
        if ( count( $pending ) > 100 ) {
            $pending = array_slice( $pending, -100 );
        }
        update_option( UCOF_OPTION_PREFIX . '_pending_exports', $pending );
    }

    /**
     * Generate WhatsApp notification link for admin.
     *
     * @param int   $order_id   Order ID.
     * @param array $order_data Order data.
     * @param array $products   Products array.
     * @return string|false WhatsApp link or false if disabled.
     */
    public function get_whatsapp_admin_link( $order_id, $order_data, $products ) {
        if ( ! get_option( UCOF_OPTION_PREFIX . '_enable_whatsapp' ) ) {
            return false;
        }

        $phone = get_option( UCOF_OPTION_PREFIX . '_whatsapp_number', '' );
        if ( empty( $phone ) ) {
            return false;
        }

        // Clean phone number
        $phone = preg_replace( '/[^0-9+]/', '', $phone );
        $phone = ltrim( $phone, '+' );

        // Get template
        $template = get_option( UCOF_OPTION_PREFIX . '_whatsapp_template', '' );
        if ( empty( $template ) ) {
            $template = $this->get_default_whatsapp_template();
        }

        // Format products
        $product_lines = array();
        foreach ( $products as $item ) {
            $product_lines[] = '• ' . $item['name'] . ' x' . $item['quantity'];
        }

        // Replace placeholders
        $message = str_replace(
            array(
                '{order_id}',
                '{customer_name}',
                '{customer_phone}',
                '{customer_email}',
                '{products}',
                '{subtotal}',
                '{delivery_fee}',
                '{total}',
                '{address}',
                '{city}',
                '{region}',
                '{notes}',
            ),
            array(
                'COD-' . str_pad( $order_id, 6, '0', STR_PAD_LEFT ),
                $order_data['customer_name'] ?? '',
                $order_data['customer_phone'] ?? '',
                $order_data['customer_email'] ?? '',
                implode( "\n", $product_lines ),
                wc_price( $order_data['subtotal'] ?? 0 ),
                wc_price( $order_data['delivery_fee'] ?? 0 ),
                wc_price( $order_data['total'] ?? 0 ),
                $order_data['customer_address'] ?? '',
                $order_data['customer_city'] ?? '',
                $order_data['customer_region'] ?? '',
                $order_data['notes'] ?? '',
            ),
            $template
        );

        return 'https://wa.me/' . $phone . '?text=' . rawurlencode( $message );
    }

    /**
     * Get default WhatsApp message template.
     *
     * @return string
     */
    private function get_default_whatsapp_template() {
        return "🛒 *New Order #{order_id}*\n\n" .
               "*Customer:* {customer_name}\n" .
               "*Phone:* {customer_phone}\n" .
               "*Address:* {address}, {city}\n\n" .
               "*Products:*\n{products}\n\n" .
               "*Total:* {total}";
    }

    /**
     * Output tracking pixel base code in head.
     */
    public function output_tracking_pixels() {
        // Facebook Pixel
        if ( get_option( UCOF_OPTION_PREFIX . '_enable_facebook_pixel' ) ) {
            $pixel_id = get_option( UCOF_OPTION_PREFIX . '_facebook_pixel_id', '' );
            if ( ! empty( $pixel_id ) ) {
                $fb_script = "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','" . esc_js( $pixel_id ) . "');fbq('track','PageView');";
                wp_register_script( 'ucof-fb-pixel', false, array(), UCOF_VERSION, false );
                wp_enqueue_script( 'ucof-fb-pixel' );
                wp_add_inline_script( 'ucof-fb-pixel', $fb_script );
            }
        }

        // TikTok Pixel
        if ( get_option( UCOF_OPTION_PREFIX . '_enable_tiktok_pixel' ) ) {
            $pixel_id = get_option( UCOF_OPTION_PREFIX . '_tiktok_pixel_id', '' );
            if ( ! empty( $pixel_id ) ) {
                $tt_script = "!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript',o.async=!0,o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};ttq.load('" . esc_js( $pixel_id ) . "');ttq.page();}(window,document,'ttq');";
                wp_register_script( 'ucof-tt-pixel', false, array(), UCOF_VERSION, false );
                wp_enqueue_script( 'ucof-tt-pixel' );
                wp_add_inline_script( 'ucof-tt-pixel', $tt_script );
            }
        }

        // Snapchat Pixel
        if ( get_option( UCOF_OPTION_PREFIX . '_enable_snapchat_pixel' ) ) {
            $pixel_id = get_option( UCOF_OPTION_PREFIX . '_snapchat_pixel_id', '' );
            if ( ! empty( $pixel_id ) ) {
                $snap_script = "(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function(){a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};a.queue=[];var s='script';r=t.createElement(s);r.async=!0;r.src=n;var u=t.getElementsByTagName(s)[0];u.parentNode.insertBefore(r,u);})(window,document,'https://sc-static.net/scevent.min.js');snaptr('init','" . esc_js( $pixel_id ) . "');snaptr('track','PAGE_VIEW');";
                wp_register_script( 'ucof-snap-pixel', false, array(), UCOF_VERSION, false );
                wp_enqueue_script( 'ucof-snap-pixel' );
                wp_add_inline_script( 'ucof-snap-pixel', $snap_script );
            }
        }
    }

    /**
     * Store purchase event for pixel tracking.
     *
     * @param int   $order_id   Order ID.
     * @param array $order_data Order data.
     * @param array $products   Products array.
     */
    public function store_purchase_event( $order_id, $order_data, $products ) {
        // Prepare event data
        $event_data = array(
            'order_id'    => $order_id,
            'value'       => floatval( $order_data['total'] ?? 0 ),
            'currency'    => get_woocommerce_currency(),
            'content_ids' => array(),
            'contents'    => array(),
            'num_items'   => 0,
        );

        foreach ( $products as $item ) {
            $event_data['content_ids'][] = (string) $item['product_id'];
            $event_data['contents'][] = array(
                'id'       => (string) $item['product_id'],
                'quantity' => intval( $item['quantity'] ),
                'price'    => floatval( $item['price'] ),
            );
            $event_data['num_items'] += intval( $item['quantity'] );
        }

        // Store in transient for frontend output
        set_transient( 'ucof_purchase_event_' . $order_id, $event_data, HOUR_IN_SECONDS );

        // Store order ID in cookie for retrieval (avoid session_start for caching compatibility)
        if ( ! headers_sent() ) {
            setcookie( 'ucof_completed_order', $order_id, time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }
    }

    /**
     * Output pixel purchase events in footer.
     */
    public function output_pixel_events() {
        // Check for completed order via cookie (no session_start needed)
        if ( empty( $_COOKIE['ucof_completed_order'] ) ) {
            return;
        }

        $order_id = intval( $_COOKIE['ucof_completed_order'] );
        $event_data = get_transient( 'ucof_purchase_event_' . $order_id );

        if ( empty( $event_data ) ) {
            return;
        }

        // Clear cookie and transient to prevent duplicate tracking
        if ( ! headers_sent() ) {
            setcookie( 'ucof_completed_order', '', time() - HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }
        delete_transient( 'ucof_purchase_event_' . $order_id );

        $event_json = wp_json_encode( $event_data );
        $purchase_script = "(function(){var eventData={$event_json};if(typeof fbq!=='undefined'){fbq('track','Purchase',{value:eventData.value,currency:eventData.currency,content_ids:eventData.content_ids,content_type:'product',num_items:eventData.num_items});}if(typeof ttq!=='undefined'){ttq.track('CompletePayment',{value:eventData.value,currency:eventData.currency,contents:eventData.contents,content_type:'product'});}if(typeof gtag!=='undefined'){var items=[];eventData.contents.forEach(function(item){items.push({item_id:item.id,quantity:item.quantity,price:item.price});});gtag('event','purchase',{transaction_id:'COD-'+eventData.order_id,value:eventData.value,currency:eventData.currency,items:items});}if(typeof snaptr!=='undefined'){snaptr('track','PURCHASE',{price:eventData.value,currency:eventData.currency,transaction_id:'COD-'+eventData.order_id,item_ids:eventData.content_ids});}})();";
        
        wp_register_script( 'ucof-purchase-event', false, array(), UCOF_VERSION, true );
        wp_enqueue_script( 'ucof-purchase-event' );
        wp_add_inline_script( 'ucof-purchase-event', $purchase_script );
    }

    /**
     * Get pending exports for manual download.
     *
     * @return array
     */
    public function get_pending_exports() {
        return get_option( UCOF_OPTION_PREFIX . '_pending_exports', array() );
    }

    /**
     * Clear pending exports.
     */
    public function clear_pending_exports() {
        delete_option( UCOF_OPTION_PREFIX . '_pending_exports' );
    }

    /**
     * Export pending orders as CSV.
     *
     * @return string CSV content.
     */
    public function export_pending_as_csv() {
        $pending = $this->get_pending_exports();

        if ( empty( $pending ) ) {
            return '';
        }

        $csv = array();
        $csv[] = array( 'Date', 'Order ID', 'Name', 'Phone', 'Email', 'City', 'Region', 'Address', 'Products', 'Subtotal', 'Delivery', 'Total', 'Status', 'Notes' );

        foreach ( $pending as $item ) {
            $p = $item['payload'];
            $csv[] = array(
                $p['date'],
                $p['order_id'],
                $p['customer_name'],
                $p['customer_phone'],
                $p['customer_email'],
                $p['customer_city'],
                $p['customer_region'],
                $p['customer_address'],
                str_replace( "\n", '; ', $p['products'] ),
                $p['subtotal'],
                $p['delivery_fee'],
                $p['total'],
                $p['status'],
                $p['notes'],
            );
        }

        // Convert to CSV string
        $output = '';
        foreach ( $csv as $row ) {
            $output .= '"' . implode( '","', array_map( function( $cell ) {
                return str_replace( '"', '""', $cell );
            }, $row ) ) . '"' . "\n";
        }

        return $output;
    }

    /**
     * Get integration status summary.
     *
     * @return array
     */
    public function get_status_summary() {
        return array(
            'webhook' => array(
                'enabled' => (bool) get_option( UCOF_OPTION_PREFIX . '_enable_webhook' ),
                'configured' => ! empty( get_option( UCOF_OPTION_PREFIX . '_webhook_url' ) ),
            ),
            'google_sheets' => array(
                'enabled' => (bool) get_option( UCOF_OPTION_PREFIX . '_enable_google_sheets' ),
                'configured' => ! empty( get_option( UCOF_OPTION_PREFIX . '_google_sheets_url' ) ),
            ),
            'whatsapp' => array(
                'enabled' => (bool) get_option( UCOF_OPTION_PREFIX . '_enable_whatsapp' ),
                'configured' => ! empty( get_option( UCOF_OPTION_PREFIX . '_whatsapp_number' ) ),
            ),
            'facebook_pixel' => array(
                'enabled' => (bool) get_option( UCOF_OPTION_PREFIX . '_enable_facebook_pixel' ),
                'configured' => ! empty( get_option( UCOF_OPTION_PREFIX . '_facebook_pixel_id' ) ),
            ),
            'tiktok_pixel' => array(
                'enabled' => (bool) get_option( UCOF_OPTION_PREFIX . '_enable_tiktok_pixel' ),
                'configured' => ! empty( get_option( UCOF_OPTION_PREFIX . '_tiktok_pixel_id' ) ),
            ),
            'ga4' => array(
                'enabled' => (bool) get_option( UCOF_OPTION_PREFIX . '_enable_ga4' ),
                'configured' => ! empty( get_option( UCOF_OPTION_PREFIX . '_ga4_measurement_id' ) ),
            ),
            'snapchat_pixel' => array(
                'enabled' => (bool) get_option( UCOF_OPTION_PREFIX . '_enable_snapchat_pixel' ),
                'configured' => ! empty( get_option( UCOF_OPTION_PREFIX . '_snapchat_pixel_id' ) ),
            ),
        );
    }
}
