/**
 * Unstoppable COD Order Forms - Admin JavaScript
 * @package Unstoppable_COD_Forms
 */
(function($) {
    'use strict';

    var UCOF_Admin = {
        init: function() {
            this.bindEvents();
            this.initProductSourceToggle();
        },

        bindEvents: function() {
            // Form save
            $(document).on('submit', '#ucof-form-editor', this.saveForm);
            $(document).on('click', '.ucof-delete-form', this.deleteForm);
            
            // Zone save
            $(document).on('submit', '#ucof-zone-editor', this.saveZone);
            $(document).on('click', '.ucof-delete-zone', this.deleteZone);
            
            // Order status
            $(document).on('change', '.ucof-order-status-select', this.updateOrderStatus);
            
            // Settings save
            $(document).on('submit', '#ucof-settings-form', this.saveSettings);
            
            // Integration save
            $(document).on('submit', '#ucof-integrations-form', this.saveIntegrations);
            
            // Test webhook
            $(document).on('click', '#ucof-test-webhook', this.testWebhook);
            
            // Product source toggle
            $(document).on('change', '#product_source', this.handleProductSourceChange);
            
            // Product search
            this.initProductSearch();
        },

        saveForm: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            
            $btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ucof_admin.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=ucof_save_form&nonce=' + ucof_admin.nonce,
                success: function(response) {
                    $btn.prop('disabled', false).text('Save Form');
                    if (response.success) {
                        UCOF_Admin.showNotice('success', response.data.message);
                        if (response.data.form_id && !$form.find('input[name="form_id"]').val()) {
                            window.location.href = ucof_admin.admin_url + '&action=edit&id=' + response.data.form_id;
                        }
                    } else {
                        UCOF_Admin.showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Save Form');
                    UCOF_Admin.showNotice('error', 'Connection error.');
                }
            });
        },

        deleteForm: function(e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this form?')) return;
            
            var formId = $(this).data('id');
            
            $.ajax({
                url: ucof_admin.ajax_url,
                type: 'POST',
                data: { action: 'ucof_delete_form', form_id: formId, nonce: ucof_admin.nonce },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        UCOF_Admin.showNotice('error', response.data.message);
                    }
                }
            });
        },

        saveZone: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            
            $btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ucof_admin.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=ucof_save_zone&nonce=' + ucof_admin.nonce,
                success: function(response) {
                    $btn.prop('disabled', false).text('Save Zone');
                    if (response.success) {
                        UCOF_Admin.showNotice('success', response.data.message);
                        // Redirect to edit page for new zones
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        }
                    } else {
                        UCOF_Admin.showNotice('error', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false).text('Save Zone');
                    UCOF_Admin.showNotice('error', 'Connection error: ' + error);
                    console.error('Zone save error:', xhr.responseText);
                }
            });
        },

        deleteZone: function(e) {
            e.preventDefault();
            if (!confirm('Delete this zone?')) return;
            
            var zoneId = $(this).data('id');
            
            $.ajax({
                url: ucof_admin.ajax_url,
                type: 'POST',
                data: { action: 'ucof_delete_zone', zone_id: zoneId, nonce: ucof_admin.nonce },
                success: function(response) {
                    if (response.success) window.location.reload();
                }
            });
        },

        updateOrderStatus: function() {
            var $select = $(this);
            var orderId = $select.data('order-id');
            var status = $select.val();
            
            $.ajax({
                url: ucof_admin.ajax_url,
                type: 'POST',
                data: { action: 'ucof_update_order_status', order_id: orderId, status: status, nonce: ucof_admin.nonce },
                success: function(response) {
                    if (response.success) {
                        UCOF_Admin.showNotice('success', 'Status updated.');
                    }
                }
            });
        },

        saveSettings: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            
            $btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ucof_admin.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=ucof_save_settings&nonce=' + ucof_admin.nonce,
                success: function(response) {
                    $btn.prop('disabled', false).text('Save Settings');
                    UCOF_Admin.showNotice(response.success ? 'success' : 'error', response.data.message);
                }
            });
        },

        saveIntegrations: function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            
            $btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ucof_admin.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=ucof_save_integrations&nonce=' + ucof_admin.nonce,
                success: function(response) {
                    $btn.prop('disabled', false).text('Save Integrations');
                    UCOF_Admin.showNotice(response.success ? 'success' : 'error', response.data.message);
                },
                error: function() {
                    $btn.prop('disabled', false).text('Save Integrations');
                    UCOF_Admin.showNotice('error', 'Connection error. Please try again.');
                }
            });
        },

        testWebhook: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $result = $('#ucof-webhook-test-result');
            var webhookUrl = $('#webhook_url').val();
            
            if (!webhookUrl) {
                $result.html('<span style="color:#d63638;">Please enter a webhook URL first.</span>');
                return;
            }
            
            $btn.prop('disabled', true);
            $result.html('<span class="spinner is-active" style="float:none; margin:0 5px;"></span> Testing...');
            
            $.ajax({
                url: ucof_admin.ajax_url,
                type: 'POST',
                data: { 
                    action: 'ucof_test_webhook', 
                    webhook_url: webhookUrl,
                    nonce: ucof_admin.nonce 
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $result.html('<span style="color:#00a32a;">✓ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color:#d63638;">✗ ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    $result.html('<span style="color:#d63638;">✗ Connection error.</span>');
                }
            });
        },

        initProductSearch: function() {
            if ($('.ucof-product-search select').length && $.fn.select2) {
                $('.ucof-product-search select').select2({
                    ajax: {
                        url: ucof_admin.ajax_url,
                        type: 'POST',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return { action: 'ucof_search_products', search: params.term, nonce: ucof_admin.nonce };
                        },
                        processResults: function(response) {
                            return { results: response.data.products || [] };
                        }
                    },
                    minimumInputLength: 2,
                    placeholder: 'Search products...'
                });
            }
        },

        initProductSourceToggle: function() {
            // Set initial state based on current value
            var source = $('#product_source').val();
            this.toggleProductSourceFields(source);
        },

        handleProductSourceChange: function() {
            var source = $(this).val();
            UCOF_Admin.toggleProductSourceFields(source);
        },

        toggleProductSourceFields: function(source) {
            $('.ucof-category-select').hide();
            $('.ucof-product-search').hide();
            
            if (source === 'category') {
                $('.ucof-category-select').show();
            } else if (source === 'selected') {
                $('.ucof-product-search').show();
            }
        },

        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.ucof-admin-wrap h1').after($notice);
            setTimeout(function() { $notice.fadeOut(); }, 4000);
        }
    };

    $(document).ready(function() { UCOF_Admin.init(); });

})(jQuery);
