/**
 * Unstoppable COD Order Forms - Frontend JavaScript
 *
 * @package Unstoppable_COD_Forms
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * UCOF Form Controller
     */
    var UCOFForm = function(wrapper) {
        this.$wrapper = $(wrapper);
        this.$form = this.$wrapper.find('.ucof-order-form');
        this.formId = this.$wrapper.data('form-id');
        this.cart = [];
        this.products = [];
        this.deliveryFee = 0;
        this.config = window.ucof_frontend || {};
        
        this.init();
    };

    UCOFForm.prototype = {
        
        /**
         * Initialize the form
         */
        init: function() {
            this.bindEvents();
            this.loadProducts();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Product selection
            this.$wrapper.on('click', '.ucof-product-card:not(.out-of-stock)', function(e) {
                if (!$(e.target).closest('.ucof-quantity-control, .ucof-add-btn').length) {
                    self.toggleProduct($(this));
                }
            });

            // Add to cart button
            this.$wrapper.on('click', '.ucof-add-btn', function(e) {
                e.stopPropagation();
                self.addToCart($(this).closest('.ucof-product-card'));
            });

            // Quantity controls
            this.$wrapper.on('click', '.ucof-qty-btn', function(e) {
                e.stopPropagation();
                var $card = $(this).closest('.ucof-product-card');
                var $input = $card.find('.ucof-qty-input');
                var current = parseInt($input.val()) || 1;
                
                if ($(this).hasClass('ucof-qty-minus')) {
                    $input.val(Math.max(1, current - 1));
                } else {
                    $input.val(current + 1);
                }
            });

            // Cart item remove
            this.$wrapper.on('click', '.ucof-cart-item-remove', function() {
                var productId = $(this).closest('.ucof-cart-item').data('product-id');
                self.removeFromCart(productId);
            });

            // Zone/region change
            this.$wrapper.on('change', '.ucof-zone-select', function() {
                self.calculateDelivery();
            });

            // City input blur
            this.$wrapper.on('blur', 'input[name="customer_city"]', function() {
                self.calculateDelivery();
            });

            // Form submission
            this.$form.on('submit', function(e) {
                e.preventDefault();
                self.submitOrder();
            });

            // Real-time validation
            this.$form.on('blur', 'input[required], select[required], textarea[required]', function() {
                self.validateField($(this));
            });

            // Enable submit button when cart has items
            this.updateSubmitState();
        },

        /**
         * Load products from server
         */
        loadProducts: function() {
            var self = this;
            var $grid = this.$wrapper.find('.ucof-products-grid');

            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'ucof_get_products',
                    nonce: this.config.products_nonce,
                    form_id: this.formId
                },
                success: function(response) {
                    if (response.success && response.data.products) {
                        self.products = response.data.products;
                        self.renderProducts($grid);
                    } else {
                        $grid.html('<p class="ucof-error">' + self.config.i18n.error + '</p>');
                    }
                },
                error: function() {
                    $grid.html('<p class="ucof-error">' + self.config.i18n.error + '</p>');
                }
            });
        },

        /**
         * Render products in the grid
         */
        renderProducts: function($grid) {
            var self = this;
            var showImages = $grid.data('show-images') !== false;
            var showQuantity = $grid.data('show-quantity') !== false;
            var html = '';

            $.each(this.products, function(i, product) {
                var outOfStock = !product.in_stock;
                
                html += '<div class="ucof-product-card' + (outOfStock ? ' out-of-stock' : '') + '" data-product-id="' + product.id + '">';
                
                if (showImages) {
                    html += '<img class="ucof-product-image" src="' + self.escapeHtml(product.image) + '" alt="' + self.escapeHtml(product.name) + '">';
                }
                
                html += '<div class="ucof-product-info">';
                html += '<div class="ucof-product-name">' + self.escapeHtml(product.name) + '</div>';
                html += '<div class="ucof-product-price">' + self.formatPrice(product.price) + '</div>';
                html += '</div>';

                if (!outOfStock) {
                    html += '<div class="ucof-product-actions">';
                    
                    if (showQuantity) {
                        html += '<div class="ucof-quantity-control">';
                        html += '<button type="button" class="ucof-qty-btn ucof-qty-minus">−</button>';
                        html += '<input type="number" class="ucof-qty-input" value="1" min="1">';
                        html += '<button type="button" class="ucof-qty-btn ucof-qty-plus">+</button>';
                        html += '</div>';
                    }
                    
                    html += '<button type="button" class="ucof-add-btn">' + self.config.i18n.add_to_order + '</button>';
                    html += '</div>';
                } else {
                    html += '<span class="ucof-out-of-stock-badge">' + self.config.i18n.out_of_stock + '</span>';
                }

                html += '</div>';
            });

            $grid.html(html);
            $grid.attr('data-loading', 'false');
        },

        /**
         * Toggle product selection
         */
        toggleProduct: function($card) {
            if ($card.hasClass('selected')) {
                this.removeFromCart($card.data('product-id'));
            } else {
                this.addToCart($card);
            }
        },

        /**
         * Add product to cart
         */
        addToCart: function($card) {
            var productId = $card.data('product-id');
            var quantity = parseInt($card.find('.ucof-qty-input').val()) || 1;
            var product = this.getProductById(productId);

            if (!product) return;

            // Check if already in cart
            var existing = this.getCartItem(productId);
            if (existing) {
                existing.quantity += quantity;
            } else {
                this.cart.push({
                    id: productId,
                    name: product.name,
                    price: product.price,
                    image: product.image,
                    quantity: quantity
                });
            }

            $card.addClass('selected');
            this.updateCartDisplay();
            this.updateSubmitState();
        },

        /**
         * Remove product from cart
         */
        removeFromCart: function(productId) {
            this.cart = this.cart.filter(function(item) {
                return item.id !== productId;
            });

            this.$wrapper.find('.ucof-product-card[data-product-id="' + productId + '"]').removeClass('selected');
            this.updateCartDisplay();
            this.updateSubmitState();
        },

        /**
         * Update cart display
         */
        updateCartDisplay: function() {
            var self = this;
            var $section = this.$wrapper.find('.ucof-cart-section');
            var $items = $section.find('.ucof-cart-items');
            var subtotal = 0;

            if (this.cart.length === 0) {
                $section.hide();
                return;
            }

            $section.show();
            var html = '';

            $.each(this.cart, function(i, item) {
                var lineTotal = item.price * item.quantity;
                subtotal += lineTotal;

                html += '<div class="ucof-cart-item" data-product-id="' + item.id + '">';
                html += '<img class="ucof-cart-item-image" src="' + self.escapeHtml(item.image) + '" alt="">';
                html += '<div class="ucof-cart-item-info">';
                html += '<div class="ucof-cart-item-name">' + self.escapeHtml(item.name) + '</div>';
                html += '<div class="ucof-cart-item-meta">' + self.formatPrice(item.price) + ' × ' + item.quantity + '</div>';
                html += '</div>';
                html += '<div class="ucof-cart-item-total">' + self.formatPrice(lineTotal) + '</div>';
                html += '<button type="button" class="ucof-cart-item-remove" title="' + self.config.i18n.remove + '">×</button>';
                html += '</div>';
            });

            $items.html(html);

            // Update totals
            $section.find('.ucof-subtotal .ucof-value').text(this.formatPrice(subtotal)).attr('data-subtotal', subtotal);
            this.updateTotal();

            // Recalculate delivery if location is set
            this.calculateDelivery();
        },

        /**
         * Calculate delivery fee based on location
         */
        calculateDelivery: function() {
            var self = this;
            var $deliveryRow = this.$wrapper.find('.ucof-delivery');
            
            if ($deliveryRow.length === 0) {
                this.updateTotal();
                return;
            }

            var city = this.$form.find('input[name="customer_city"]').val();
            var region = this.$form.find('select[name="customer_region"]').val();
            var subtotal = parseFloat(this.$wrapper.find('.ucof-subtotal .ucof-value').attr('data-subtotal')) || 0;

            if (!region || this.cart.length === 0) {
                $deliveryRow.find('.ucof-value').html('<span class="ucof-delivery-pending">' + this.config.i18n.calculating + '</span>');
                this.deliveryFee = 0;
                this.updateTotal();
                return;
            }

            // Calculate total weight
            var totalWeight = 0;
            $.each(this.cart, function(i, item) {
                var product = self.getProductById(item.id);
                if (product && product.weight) {
                    totalWeight += product.weight * item.quantity;
                }
            });

            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: {
                    action: 'ucof_calculate_delivery',
                    nonce: this.config.delivery_nonce,
                    city: city,
                    region: region,
                    subtotal: subtotal,
                    weight: totalWeight
                },
                success: function(response) {
                    if (response.success) {
                        self.deliveryFee = response.data.delivery_fee || 0;
                        
                        if (self.deliveryFee === 0 && response.data.found) {
                            $deliveryRow.find('.ucof-value').html('<span class="ucof-free-delivery">' + self.config.i18n.free_delivery + '</span>');
                        } else if (response.data.found) {
                            $deliveryRow.find('.ucof-value').text(self.formatPrice(self.deliveryFee));
                        } else {
                            $deliveryRow.find('.ucof-value').html('<span class="ucof-delivery-pending">' + response.data.message + '</span>');
                        }
                    }
                    self.updateTotal();
                },
                error: function() {
                    $deliveryRow.find('.ucof-value').text('-');
                    self.deliveryFee = 0;
                    self.updateTotal();
                }
            });
        },

        /**
         * Update total display
         */
        updateTotal: function() {
            var subtotal = parseFloat(this.$wrapper.find('.ucof-subtotal .ucof-value').attr('data-subtotal')) || 0;
            var total = subtotal + this.deliveryFee;
            
            this.$wrapper.find('.ucof-total .ucof-value').text(this.formatPrice(total)).attr('data-total', total);
        },

        /**
         * Update submit button state
         */
        updateSubmitState: function() {
            var $btn = this.$wrapper.find('.ucof-submit-btn');
            $btn.prop('disabled', this.cart.length === 0);
        },

        /**
         * Validate a single field
         */
        validateField: function($field) {
            var $wrapper = $field.closest('.ucof-field');
            var value = $field.val().trim();
            var isValid = true;
            var errorMsg = '';

            // Required check
            if ($field.prop('required') && !value) {
                isValid = false;
                errorMsg = 'This field is required.';
            }

            // Email validation
            if (isValid && $field.attr('type') === 'email' && value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMsg = 'Please enter a valid email address.';
                }
            }

            // Phone validation (basic)
            if (isValid && $field.attr('type') === 'tel' && value) {
                var phoneRegex = /^[\d\s\+\-\(\)]{7,20}$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    errorMsg = 'Please enter a valid phone number.';
                }
            }

            // Update field state
            if (isValid) {
                $wrapper.removeClass('has-error');
                $field.removeClass('error');
                $wrapper.find('.ucof-field-error').text('');
            } else {
                $wrapper.addClass('has-error');
                $field.addClass('error');
                $wrapper.find('.ucof-field-error').text(errorMsg);
            }

            return isValid;
        },

        /**
         * Validate entire form
         */
        validateForm: function() {
            var self = this;
            var isValid = true;

            // Validate all required fields
            this.$form.find('input[required], select[required], textarea[required]').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });

            // Check if cart has items
            if (this.cart.length === 0) {
                this.showMessage('error', this.config.i18n.select_products);
                isValid = false;
            }

            return isValid;
        },

        /**
         * Submit the order
         */
        submitOrder: function() {
            var self = this;

            // Validate
            if (!this.validateForm()) {
                // Scroll to first error
                var $firstError = this.$form.find('.has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
                return;
            }

            var $btn = this.$wrapper.find('.ucof-submit-btn');
            $btn.addClass('loading').prop('disabled', true);
            this.hideMessage();

            // Prepare data
            var formData = this.$form.serializeArray();
            var data = {
                action: 'ucof_submit_order',
                nonce: this.config.submit_nonce
            };

            $.each(formData, function(i, field) {
                data[field.name] = field.value;
            });

            // Add cart products
            data.products = this.cart.map(function(item) {
                return {
                    id: item.id,
                    quantity: item.quantity
                };
            });

            $.ajax({
                url: this.config.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    $btn.removeClass('loading').prop('disabled', false);

                    if (response.success) {
                        self.handleSuccess(response.data);
                    } else {
                        self.showMessage('error', response.data.message || self.config.i18n.error);
                    }
                },
                error: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    self.showMessage('error', self.config.i18n.error);
                }
            });
        },

        /**
         * Handle successful order
         */
        handleSuccess: function(data) {
            // Hide form, show success
            this.$form.hide();
            
            var $success = this.$wrapper.find('.ucof-success-state');
            $success.find('.ucof-success-message').text(data.message || '');
            $success.find('.ucof-ref-number').text(data.order_ref || '');
            $success.show();

            // Scroll to success message
            $('html, body').animate({
                scrollTop: this.$wrapper.offset().top - 50
            }, 300);

            // Fire tracking events
            this.fireTrackingEvents(data);

            // Redirect if URL provided
            if (data.redirect_url) {
                setTimeout(function() {
                    window.location.href = data.redirect_url;
                }, 2000);
            }
        },

        /**
         * Fire tracking events
         */
        fireTrackingEvents: function(data) {
            var self = this;
            var total = parseFloat(this.$wrapper.find('.ucof-total .ucof-value').attr('data-total')) || 0;
            var currency = 'USD'; // Get from WC settings if available

            // Prepare items
            var items = this.cart.map(function(item) {
                return {
                    id: String(item.id),
                    name: item.name,
                    price: item.price,
                    quantity: item.quantity
                };
            });

            // Facebook Pixel
            if (typeof fbq === 'function') {
                fbq('track', 'Purchase', {
                    value: total,
                    currency: currency,
                    content_ids: items.map(function(i) { return i.id; }),
                    content_type: 'product',
                    order_id: data.order_ref
                });
            }

            // TikTok Pixel
            if (typeof ttq === 'object') {
                ttq.track('CompletePayment', {
                    value: total,
                    currency: currency,
                    contents: items.map(function(i) {
                        return {
                            content_id: i.id,
                            content_name: i.name,
                            quantity: i.quantity,
                            price: i.price
                        };
                    }),
                    content_type: 'product'
                });
            }

            // GA4
            if (typeof gtag === 'function') {
                gtag('event', 'purchase', {
                    transaction_id: data.order_ref,
                    value: total,
                    currency: currency,
                    items: items.map(function(i) {
                        return {
                            item_id: i.id,
                            item_name: i.name,
                            price: i.price,
                            quantity: i.quantity
                        };
                    })
                });
            }
        },

        /**
         * Show message
         */
        showMessage: function(type, message) {
            var $messages = this.$wrapper.find('.ucof-form-messages');
            $messages.removeClass('success error').addClass(type).text(message).show();
        },

        /**
         * Hide message
         */
        hideMessage: function() {
            this.$wrapper.find('.ucof-form-messages').hide().removeClass('success error').text('');
        },

        /**
         * Get product by ID
         */
        getProductById: function(id) {
            for (var i = 0; i < this.products.length; i++) {
                if (this.products[i].id === id) {
                    return this.products[i];
                }
            }
            return null;
        },

        /**
         * Get cart item by product ID
         */
        getCartItem: function(productId) {
            for (var i = 0; i < this.cart.length; i++) {
                if (this.cart[i].id === productId) {
                    return this.cart[i];
                }
            }
            return null;
        },

        /**
         * Format price
         */
        formatPrice: function(price) {
            var formatted = parseFloat(price).toFixed(this.config.decimals || 2);
            
            // Apply thousand separator
            var parts = formatted.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.config.thousand_separator || ',');
            formatted = parts.join(this.config.decimal_separator || '.');

            // Position currency symbol
            switch (this.config.currency_position) {
                case 'right':
                    return formatted + this.config.currency_symbol;
                case 'left_space':
                    return this.config.currency_symbol + ' ' + formatted;
                case 'right_space':
                    return formatted + ' ' + this.config.currency_symbol;
                case 'left':
                default:
                    return this.config.currency_symbol + formatted;
            }
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    /**
     * Initialize all forms on page
     */
    $(document).ready(function() {
        $('.ucof-form-wrapper').each(function() {
            new UCOFForm(this);
        });
    });

})(jQuery);
