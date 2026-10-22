<?php
/**
 * Frontend Handler Class
 *
 * Handles frontend form rendering, assets, and display.
 *
 * @package Unstoppable_COD_Forms
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend Handler Class.
 */
class UCOF_Frontend {

    /**
     * Forms instance.
     *
     * @var UCOF_Forms
     */
    private $forms;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->forms = new UCOF_Forms();
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_head', array( $this, 'output_tracking_pixels' ) );
    }

    /**
     * Enqueue frontend scripts and styles.
     */
    public function enqueue_scripts() {
        // Only enqueue if shortcode is present
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'ucof_form' ) ) {
            return;
        }

        // Main frontend CSS
        wp_enqueue_style(
            'ucof-frontend',
            UCOF_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            UCOF_VERSION
        );

        // Main frontend JS
        wp_enqueue_script(
            'ucof-frontend',
            UCOF_PLUGIN_URL . 'assets/js/frontend.js',
            array( 'jquery' ),
            UCOF_VERSION,
            true
        );

        // Get WooCommerce currency settings
        $currency_symbol = get_woocommerce_currency_symbol();
        $settings = get_option( 'ucof_settings', array() );

        wp_localize_script( 'ucof-frontend', 'ucof_frontend', array(
            'ajax_url'            => admin_url( 'admin-ajax.php' ),
            'nonce'               => wp_create_nonce( 'ucof_frontend_nonce' ),
            'submit_nonce'        => wp_create_nonce( 'ucof_submit_order' ),
            'delivery_nonce'      => wp_create_nonce( 'ucof_calculate_delivery' ),
            'products_nonce'      => wp_create_nonce( 'ucof_get_products' ),
            'currency_symbol'     => $currency_symbol,
            'currency_position'   => $settings['currency_position'] ?? 'left',
            'thousand_separator'  => $settings['thousand_separator'] ?? ',',
            'decimal_separator'   => $settings['decimal_separator'] ?? '.',
            'decimals'            => $settings['decimals'] ?? 2,
            'i18n'                => array(
                'loading'          => __( 'Loading...', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'submitting'       => __( 'Submitting...', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'error'            => __( 'An error occurred. Please try again.', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'select_products'  => __( 'Please select at least one product.', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'out_of_stock'     => __( 'Out of Stock', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'add_to_order'     => __( 'Add to Order', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'remove'           => __( 'Remove', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'calculating'      => __( 'Calculating...', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'free_delivery'    => __( 'Free Delivery', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'delivery_fee'     => __( 'Delivery Fee', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'subtotal'         => __( 'Subtotal', 'unstoppable-cod-order-forms-for-woocommerce' ),
                'total'            => __( 'Total', 'unstoppable-cod-order-forms-for-woocommerce' ),
            ),
        ) );
    }

    /**
     * Output tracking pixels in head.
     */
    public function output_tracking_pixels() {
        // Facebook Pixel
        $fb_pixel = get_option( 'ucof_facebook_pixel', array() );
        if ( ! empty( $fb_pixel['enabled'] ) && ! empty( $fb_pixel['pixel_id'] ) ) {
            $this->output_facebook_pixel( $fb_pixel['pixel_id'] );
        }

        // TikTok Pixel
        $tt_pixel = get_option( 'ucof_tiktok_pixel', array() );
        if ( ! empty( $tt_pixel['enabled'] ) && ! empty( $tt_pixel['pixel_id'] ) ) {
            $this->output_tiktok_pixel( $tt_pixel['pixel_id'] );
        }

        // GA4
        $ga4 = get_option( 'ucof_ga4', array() );
        if ( ! empty( $ga4['enabled'] ) && ! empty( $ga4['measurement_id'] ) ) {
            $this->output_ga4( $ga4['measurement_id'] );
        }
    }

    /**
     * Output Facebook Pixel code.
     *
     * @param string $pixel_id Pixel ID.
     */
    private function output_facebook_pixel( $pixel_id ) {
        $fb_script = "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','" . esc_js( $pixel_id ) . "');fbq('track','PageView');";
        wp_register_script( 'ucof-fb-pixel-form', false, array(), UCOF_VERSION, false );
        wp_enqueue_script( 'ucof-fb-pixel-form' );
        wp_add_inline_script( 'ucof-fb-pixel-form', $fb_script );
    }

    /**
     * Output TikTok Pixel code.
     *
     * @param string $pixel_id Pixel ID.
     */
    private function output_tiktok_pixel( $pixel_id ) {
        $tt_script = "!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript',o.async=!0,o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};ttq.load('" . esc_js( $pixel_id ) . "');ttq.page();}(window,document,'ttq');";
        wp_register_script( 'ucof-tt-pixel-form', false, array(), UCOF_VERSION, false );
        wp_enqueue_script( 'ucof-tt-pixel-form' );
        wp_add_inline_script( 'ucof-tt-pixel-form', $tt_script );
    }

    /**
     * Output GA4 code.
     *
     * @param string $measurement_id Measurement ID.
     */
    private function output_ga4( $measurement_id ) {
        wp_enqueue_script(
            'ucof-ga4-frontend',
            'https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $measurement_id ),
            array(),
            null,
            false
        );
        
        $inline_script = "window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '" . esc_js( $measurement_id ) . "');";
        wp_add_inline_script( 'ucof-ga4-frontend', $inline_script );
    }

    /**
     * Render form.
     *
     * @param array $atts Shortcode attributes.
     * @return string Form HTML.
     */
    public function render_form( $atts ) {
        $atts = shortcode_atts( array(
            'id'   => 0,
            'slug' => '',
        ), $atts );

        // Get form by ID or slug
        $form = null;
        if ( ! empty( $atts['id'] ) ) {
            $form = $this->forms->get( absint( $atts['id'] ) );
        } elseif ( ! empty( $atts['slug'] ) ) {
            $form = $this->forms->get_by_slug( sanitize_key( $atts['slug'] ) );
        }

        if ( ! $form || $form->status !== 'active' ) {
            return '<p class="ucof-error">' . esc_html__( 'Form not found or inactive.', 'unstoppable-cod-order-forms-for-woocommerce' ) . '</p>';
        }

        $settings = maybe_unserialize( $form->settings );

        // Start output buffering
        ob_start();

        // Load template
        $template = UCOF_PLUGIN_DIR . 'templates/form.php';
        if ( file_exists( $template ) ) {
            include $template;
        } else {
            $this->render_default_form( $form, $settings );
        }

        return ob_get_clean();
    }

    /**
     * Render default form (fallback if template missing).
     *
     * @param object $form     Form object.
     * @param array  $settings Form settings.
     */
    private function render_default_form( $form, $settings ) {
        $zones = new UCOF_Zones();
        $zone_options = $zones->get_zone_options();
        ?>
        <div class="ucof-form-wrapper" data-form-id="<?php echo esc_attr( $form->id ); ?>">
            <form class="ucof-order-form" id="ucof-form-<?php echo esc_attr( $form->id ); ?>">
                <?php wp_nonce_field( 'ucof_submit_order', 'ucof_nonce' ); ?>
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $form->id ); ?>">

                <!-- Products Section -->
                <div class="ucof-section ucof-products-section">
                    <h3><?php esc_html_e( 'Select Products', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                    <div class="ucof-products-list" data-loading="true">
                        <div class="ucof-loading"><?php esc_html_e( 'Loading products...', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></div>
                    </div>
                </div>

                <!-- Selected Products / Cart -->
                <div class="ucof-section ucof-cart-section" style="display: none;">
                    <h3><?php esc_html_e( 'Your Order', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                    <div class="ucof-cart-items"></div>
                    <div class="ucof-cart-totals">
                        <div class="ucof-subtotal">
                            <span class="ucof-label"><?php esc_html_e( 'Subtotal:', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                            <span class="ucof-value">0</span>
                        </div>
                        <?php if ( ! empty( $settings['enable_zones'] ) ) : ?>
                        <div class="ucof-delivery-fee">
                            <span class="ucof-label"><?php esc_html_e( 'Delivery Fee:', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                            <span class="ucof-value">-</span>
                        </div>
                        <?php endif; ?>
                        <div class="ucof-total">
                            <span class="ucof-label"><?php esc_html_e( 'Total:', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                            <span class="ucof-value">0</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Section -->
                <div class="ucof-section ucof-customer-section">
                    <h3><?php esc_html_e( 'Your Details', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>

                    <div class="ucof-field">
                        <label for="ucof-name-<?php echo esc_attr( $form->id ); ?>">
                            <?php esc_html_e( 'Full Name', 'unstoppable-cod-order-forms-for-woocommerce' ); ?> <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="ucof-name-<?php echo esc_attr( $form->id ); ?>" 
                               name="customer_name" 
                               required>
                    </div>

                    <div class="ucof-field">
                        <label for="ucof-phone-<?php echo esc_attr( $form->id ); ?>">
                            <?php esc_html_e( 'Phone Number', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                            <?php if ( ! empty( $settings['require_phone'] ) ) : ?><span class="required">*</span><?php endif; ?>
                        </label>
                        <input type="tel" 
                               id="ucof-phone-<?php echo esc_attr( $form->id ); ?>" 
                               name="customer_phone"
                               <?php echo ! empty( $settings['require_phone'] ) ? 'required' : ''; ?>>
                    </div>

                    <?php if ( ! empty( $settings['require_email'] ) || empty( $settings['require_email'] ) ) : ?>
                    <div class="ucof-field">
                        <label for="ucof-email-<?php echo esc_attr( $form->id ); ?>">
                            <?php esc_html_e( 'Email Address', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                            <?php if ( ! empty( $settings['require_email'] ) ) : ?><span class="required">*</span><?php endif; ?>
                        </label>
                        <input type="email" 
                               id="ucof-email-<?php echo esc_attr( $form->id ); ?>" 
                               name="customer_email"
                               <?php echo ! empty( $settings['require_email'] ) ? 'required' : ''; ?>>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $settings['enable_zones'] ) && ! empty( $zone_options ) ) : ?>
                    <div class="ucof-field">
                        <label for="ucof-region-<?php echo esc_attr( $form->id ); ?>">
                            <?php esc_html_e( 'Region/State', 'unstoppable-cod-order-forms-for-woocommerce' ); ?> <span class="required">*</span>
                        </label>
                        <select id="ucof-region-<?php echo esc_attr( $form->id ); ?>" 
                                name="customer_region" 
                                class="ucof-zone-select"
                                required>
                            <option value=""><?php esc_html_e( 'Select Region', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                            <?php foreach ( $zone_options as $option ) : ?>
                                <option value="<?php echo esc_attr( $option['value'] ); ?>">
                                    <?php echo esc_html( $option['label'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ucof-field">
                        <label for="ucof-city-<?php echo esc_attr( $form->id ); ?>">
                            <?php esc_html_e( 'City', 'unstoppable-cod-order-forms-for-woocommerce' ); ?> <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="ucof-city-<?php echo esc_attr( $form->id ); ?>" 
                               name="customer_city"
                               required>
                    </div>
                    <?php endif; ?>

                    <div class="ucof-field">
                        <label for="ucof-address-<?php echo esc_attr( $form->id ); ?>">
                            <?php esc_html_e( 'Delivery Address', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                            <?php if ( ! empty( $settings['require_address'] ) ) : ?><span class="required">*</span><?php endif; ?>
                        </label>
                        <textarea id="ucof-address-<?php echo esc_attr( $form->id ); ?>" 
                                  name="customer_address"
                                  rows="3"
                                  <?php echo ! empty( $settings['require_address'] ) ? 'required' : ''; ?>></textarea>
                    </div>

                    <?php if ( ! empty( $settings['show_notes'] ) ) : ?>
                    <div class="ucof-field">
                        <label for="ucof-notes-<?php echo esc_attr( $form->id ); ?>">
                            <?php esc_html_e( 'Order Notes', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        </label>
                        <textarea id="ucof-notes-<?php echo esc_attr( $form->id ); ?>" 
                                  name="notes"
                                  rows="2"
                                  placeholder="<?php esc_attr_e( 'Any special instructions...', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"></textarea>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Submit Button -->
                <div class="ucof-section ucof-submit-section">
                    <button type="submit" class="ucof-submit-btn">
                        <?php echo esc_html( $settings['button_text'] ?? __( 'Place Order', 'unstoppable-cod-order-forms-for-woocommerce' ) ); ?>
                    </button>
                    <p class="ucof-cod-notice">
                        <?php esc_html_e( 'Cash on Delivery - Pay when you receive your order', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                    </p>
                </div>

                <!-- Messages -->
                <div class="ucof-messages" style="display: none;"></div>
            </form>

            <!-- Success Message (hidden by default) -->
            <div class="ucof-success-message" style="display: none;">
                <div class="ucof-success-icon">✓</div>
                <h3><?php esc_html_e( 'Order Placed Successfully!', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
                <p class="ucof-success-text"></p>
                <p class="ucof-order-ref"></p>
            </div>
        </div>
        <?php
    }

    /**
     * Format price for display.
     *
     * @param float $price Price value.
     * @return string Formatted price.
     */
    public static function format_price( $price ) {
        $settings = get_option( 'ucof_settings', array() );
        $currency_symbol = get_woocommerce_currency_symbol();

        $formatted = number_format(
            floatval( $price ),
            $settings['decimals'] ?? 2,
            $settings['decimal_separator'] ?? '.',
            $settings['thousand_separator'] ?? ','
        );

        $position = $settings['currency_position'] ?? 'left';

        switch ( $position ) {
            case 'left':
                return $currency_symbol . $formatted;
            case 'right':
                return $formatted . $currency_symbol;
            case 'left_space':
                return $currency_symbol . ' ' . $formatted;
            case 'right_space':
                return $formatted . ' ' . $currency_symbol;
            default:
                return $currency_symbol . $formatted;
        }
    }
}
