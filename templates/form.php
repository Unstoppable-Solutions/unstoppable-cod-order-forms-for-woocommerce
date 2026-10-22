<?php
/**
 * COD Order Form Template
 *
 * This template can be overridden by copying it to yourtheme/ucof/form.php.
 *
 * @package Unstoppable_COD_Forms
 * @version 1.0.0
 *
 * Variables available:
 * @var object $form     Form object with properties: id, name, slug, settings, products, status
 * @var array  $settings Form settings array
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get zone options if zones are enabled
$ucof_zones = new UCOF_Zones();
$ucof_zone_options = ! empty( $settings['enable_zones'] ) ? $ucof_zones->get_zone_options() : array();
$ucof_show_images = ! empty( $settings['show_images'] );
$ucof_show_quantity = ! empty( $settings['show_quantity'] );
?>

<div class="ucof-form-wrapper" data-form-id="<?php echo esc_attr( $form->id ); ?>">
    
    <form class="ucof-order-form" id="ucof-form-<?php echo esc_attr( $form->id ); ?>" novalidate>
        <?php wp_nonce_field( 'ucof_submit_order', 'ucof_nonce' ); ?>
        <input type="hidden" name="form_id" value="<?php echo esc_attr( $form->id ); ?>">
        <input type="hidden" name="action" value="ucof_submit_order">

        <!-- Step 1: Product Selection -->
        <div class="ucof-section ucof-products-section">
            <div class="ucof-section-header">
                <span class="ucof-step-number">1</span>
                <h3><?php esc_html_e( 'Select Your Products', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
            </div>
            
            <div class="ucof-products-grid" data-loading="true" data-show-images="<?php echo $ucof_show_images ? 'true' : 'false'; ?>" data-show-quantity="<?php echo $ucof_show_quantity ? 'true' : 'false'; ?>">
                <div class="ucof-loading">
                    <span class="ucof-spinner"></span>
                    <span><?php esc_html_e( 'Loading products...', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                </div>
            </div>
        </div>

        <!-- Step 2: Cart / Order Summary -->
        <div class="ucof-section ucof-cart-section" style="display: none;">
            <div class="ucof-section-header">
                <span class="ucof-step-number">2</span>
                <h3><?php esc_html_e( 'Your Order', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
            </div>
            
            <div class="ucof-cart-container">
                <div class="ucof-cart-items"></div>
                
                <div class="ucof-cart-summary">
                    <div class="ucof-summary-row ucof-subtotal">
                        <span class="ucof-label"><?php esc_html_e( 'Subtotal', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                        <span class="ucof-value" data-subtotal="0">-</span>
                    </div>
                    
                    <?php if ( ! empty( $settings['enable_zones'] ) ) : ?>
                    <div class="ucof-summary-row ucof-delivery">
                        <span class="ucof-label"><?php esc_html_e( 'Delivery Fee', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                        <span class="ucof-value" data-delivery="0">
                            <span class="ucof-delivery-pending"><?php esc_html_e( 'Select location below', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="ucof-summary-row ucof-total">
                        <span class="ucof-label"><?php esc_html_e( 'Total', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                        <span class="ucof-value" data-total="0">-</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Customer Details -->
        <div class="ucof-section ucof-customer-section">
            <div class="ucof-section-header">
                <span class="ucof-step-number">3</span>
                <h3><?php esc_html_e( 'Your Details', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h3>
            </div>
            
            <div class="ucof-fields-grid">
                <!-- Full Name -->
                <div class="ucof-field ucof-field-full">
                    <label for="ucof-name-<?php echo esc_attr( $form->id ); ?>">
                        <?php esc_html_e( 'Full Name', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        <span class="ucof-required">*</span>
                    </label>
                    <input type="text" 
                           id="ucof-name-<?php echo esc_attr( $form->id ); ?>" 
                           name="customer_name"
                           placeholder="<?php esc_attr_e( 'Enter your full name', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"
                           required>
                    <span class="ucof-field-error"></span>
                </div>

                <!-- Phone Number -->
                <div class="ucof-field ucof-field-half">
                    <label for="ucof-phone-<?php echo esc_attr( $form->id ); ?>">
                        <?php esc_html_e( 'Phone Number', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        <?php if ( ! empty( $settings['require_phone'] ) ) : ?><span class="ucof-required">*</span><?php endif; ?>
                    </label>
                    <input type="tel" 
                           id="ucof-phone-<?php echo esc_attr( $form->id ); ?>" 
                           name="customer_phone"
                           placeholder="<?php esc_attr_e( 'Your phone number', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"
                           <?php echo ! empty( $settings['require_phone'] ) ? 'required' : ''; ?>>
                    <span class="ucof-field-error"></span>
                </div>

                <!-- Email (optional based on settings) -->
                <div class="ucof-field ucof-field-half">
                    <label for="ucof-email-<?php echo esc_attr( $form->id ); ?>">
                        <?php esc_html_e( 'Email Address', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        <?php if ( ! empty( $settings['require_email'] ) ) : ?><span class="ucof-required">*</span><?php endif; ?>
                    </label>
                    <input type="email" 
                           id="ucof-email-<?php echo esc_attr( $form->id ); ?>" 
                           name="customer_email"
                           placeholder="<?php esc_attr_e( 'your@email.com', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"
                           <?php echo ! empty( $settings['require_email'] ) ? 'required' : ''; ?>>
                    <span class="ucof-field-error"></span>
                </div>

                <?php if ( ! empty( $settings['enable_zones'] ) ) : ?>
                <!-- Region/Zone Selection -->
                <div class="ucof-field ucof-field-half">
                    <label for="ucof-region-<?php echo esc_attr( $form->id ); ?>">
                        <?php esc_html_e( 'Region / State', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        <span class="ucof-required">*</span>
                    </label>
                    <select id="ucof-region-<?php echo esc_attr( $form->id ); ?>" 
                            name="customer_region"
                            class="ucof-zone-select"
                            required>
                        <option value=""><?php esc_html_e( '— Select Region —', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></option>
                        <?php foreach ( $ucof_zone_options as $ucof_option ) : ?>
                            <option value="<?php echo esc_attr( $ucof_option['value'] ); ?>" data-zone-id="<?php echo esc_attr( $ucof_option['zone_id'] ?? '' ); ?>">
                                <?php echo esc_html( $ucof_option['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="ucof-field-error"></span>
                </div>

                <!-- City -->
                <div class="ucof-field ucof-field-half">
                    <label for="ucof-city-<?php echo esc_attr( $form->id ); ?>">
                        <?php esc_html_e( 'City', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        <span class="ucof-required">*</span>
                    </label>
                    <input type="text" 
                           id="ucof-city-<?php echo esc_attr( $form->id ); ?>" 
                           name="customer_city"
                           placeholder="<?php esc_attr_e( 'Your city', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"
                           required>
                    <span class="ucof-field-error"></span>
                </div>
                <?php endif; ?>

                <!-- Full Address -->
                <div class="ucof-field ucof-field-full">
                    <label for="ucof-address-<?php echo esc_attr( $form->id ); ?>">
                        <?php esc_html_e( 'Delivery Address', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        <?php if ( ! empty( $settings['require_address'] ) ) : ?><span class="ucof-required">*</span><?php endif; ?>
                    </label>
                    <textarea id="ucof-address-<?php echo esc_attr( $form->id ); ?>" 
                              name="customer_address"
                              rows="3"
                              placeholder="<?php esc_attr_e( 'Street address, building, floor, etc.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"
                              <?php echo ! empty( $settings['require_address'] ) ? 'required' : ''; ?>></textarea>
                    <span class="ucof-field-error"></span>
                </div>

                <?php if ( ! empty( $settings['show_notes'] ) ) : ?>
                <!-- Order Notes -->
                <div class="ucof-field ucof-field-full">
                    <label for="ucof-notes-<?php echo esc_attr( $form->id ); ?>">
                        <?php esc_html_e( 'Order Notes', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                        <span class="ucof-optional"><?php esc_html_e( '(optional)', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
                    </label>
                    <textarea id="ucof-notes-<?php echo esc_attr( $form->id ); ?>" 
                              name="notes"
                              rows="2"
                              placeholder="<?php esc_attr_e( 'Any special instructions or notes...', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>"></textarea>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="ucof-section ucof-submit-section">
            <div class="ucof-form-messages" role="alert" aria-live="polite"></div>
            
            <button type="submit" class="ucof-submit-btn" disabled>
                <span class="ucof-btn-text"><?php echo esc_html( $settings['button_text'] ?? __( 'Place Order', 'unstoppable-cod-order-forms-for-woocommerce' ) ); ?></span>
                <span class="ucof-btn-loading" style="display: none;">
                    <span class="ucof-spinner"></span>
                    <?php esc_html_e( 'Processing...', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
                </span>
            </button>
            
            <div class="ucof-payment-notice">
                <span class="ucof-cod-icon">💵</span>
                <span><?php esc_html_e( 'Cash on Delivery — Pay when you receive your order', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
            </div>
        </div>

    </form>

    <!-- Success State -->
    <div class="ucof-success-state" style="display: none;">
        <div class="ucof-success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <h2 class="ucof-success-title"><?php esc_html_e( 'Order Placed Successfully!', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></h2>
        <p class="ucof-success-message"></p>
        <div class="ucof-order-reference">
            <span class="ucof-label"><?php esc_html_e( 'Order Reference:', 'unstoppable-cod-order-forms-for-woocommerce' ); ?></span>
            <span class="ucof-ref-number"></span>
        </div>
        <p class="ucof-success-note">
            <?php esc_html_e( 'We will contact you shortly to confirm your order.', 'unstoppable-cod-order-forms-for-woocommerce' ); ?>
        </p>
    </div>

</div>
