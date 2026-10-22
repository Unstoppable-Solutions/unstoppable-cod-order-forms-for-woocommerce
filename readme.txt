=== Unstoppable COD Order Forms for WooCommerce ===
Contributors: paramedic192
Tags: woocommerce, cod, cash-on-delivery, order-forms, landing-page
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.1.5
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 11.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create high-converting COD (Cash on Delivery) order forms for WooCommerce with delivery zones, Google Sheets sync, and marketing pixel tracking.

== Description ==

**Unstoppable COD Order Forms** is designed for markets where Cash on Delivery is the dominant payment method. Create standalone order forms that convert visitors into customers without the complexity of a traditional checkout.

= Key Features =

* **Standalone Order Forms** - Create beautiful, mobile-optimized order forms
* **Delivery Zone Calculator** - Set up regions with flat rate, percentage, or weight-based fees
* **Free Delivery Thresholds** - Encourage larger orders with free delivery offers
* **Google Sheets Sync** - Automatically send orders to Google Sheets for fulfillment
* **WhatsApp Notifications** - Send order confirmations via WhatsApp
* **Pixel Tracking** - Built-in support for Facebook Pixel, TikTok Pixel, and GA4
* **WooCommerce Integration** - Optionally create WooCommerce orders for inventory tracking

= Perfect For =

* Middle East markets (Saudi Arabia, UAE, Egypt)
* Southeast Asia (Indonesia, Thailand, Philippines)
* South Asia (India, Pakistan, Bangladesh)
* South America (Brazil, Colombia, Argentina)
* Africa (Nigeria, Kenya, South Africa)

= Shortcode Usage =

Display a form anywhere using:
`[ucof_form id="1"]`
or
`[ucof_form slug="my-form"]`

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/unstoppable-cod-order-forms-for-woocommerce/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Options Studio → COD Forms to configure
4. Create your first form and delivery zones
5. Use the shortcode `[ucof_form id="X"]` to display forms on any page

== External Services ==

This plugin optionally connects to third-party services when you enable specific integrations. No data is sent to external services unless you explicitly configure and enable them.

= Google Analytics 4 (GA4) =

When enabled in Integrations settings, the plugin loads Google's gtag.js script to track page views and purchase events.

* **What it does:** Tracks visitor behavior and purchase conversions on your forms
* **Data sent:** Page URL, purchase value, product IDs, currency (only when a purchase is completed)
* **When:** Script loads on pages with your forms; purchase data sent on order completion
* **Service provider:** Google LLC
* **Terms of Service:** https://marketingplatform.google.com/about/analytics/terms/us/
* **Privacy Policy:** https://policies.google.com/privacy

= Facebook Pixel =

When enabled in Integrations settings, the plugin loads Facebook's tracking pixel to track page views and purchase events.

* **What it does:** Tracks visitor behavior and purchase conversions for Facebook Ads optimization
* **Data sent:** Page URL, purchase value, product IDs, currency, content type
* **When:** Script loads on pages with your forms; purchase data sent on order completion
* **Service provider:** Meta Platforms, Inc.
* **Terms of Service:** https://www.facebook.com/legal/terms
* **Privacy Policy:** https://www.facebook.com/privacy/policy/

= TikTok Pixel =

When enabled in Integrations settings, the plugin loads TikTok's tracking pixel.

* **What it does:** Tracks visitor behavior and purchase conversions for TikTok Ads optimization
* **Data sent:** Page URL, purchase value, product IDs, currency
* **When:** Script loads on pages with your forms; purchase data sent on order completion
* **Service provider:** TikTok Inc.
* **Terms of Service:** https://www.tiktok.com/legal/terms-of-service
* **Privacy Policy:** https://www.tiktok.com/legal/privacy-policy

= Snapchat Pixel =

When enabled in Integrations settings, the plugin loads Snapchat's tracking pixel.

* **What it does:** Tracks purchase conversions for Snapchat Ads optimization
* **Data sent:** Purchase value, currency, transaction ID
* **When:** Purchase data sent on order completion only
* **Service provider:** Snap Inc.
* **Terms of Service:** https://snap.com/en-US/terms
* **Privacy Policy:** https://snap.com/en-US/privacy/privacy-policy

= Webhook Integration =

When you configure a webhook URL, order data is sent to your specified endpoint.

* **What it does:** Sends order details to any URL you configure
* **Data sent:** Customer name, phone, email, address, order items, totals
* **When:** Immediately after order submission
* **Service provider:** User-configured (your own endpoint)

= Google Sheets Integration =

When configured with a Google Apps Script URL, order data is sent to your Google Sheet.

* **What it does:** Automatically logs orders to a Google Sheet for fulfillment tracking
* **Data sent:** Order ID, customer details, products, totals, timestamps
* **When:** Immediately after order submission
* **Service provider:** Google LLC (via user's own Google Apps Script)
* **Terms of Service:** https://developers.google.com/apps-script/terms
* **Privacy Policy:** https://policies.google.com/privacy

== Changelog ==

= 1.1.5 =
* Updated: WooCommerce tested up to 11.2

= 1.1.4 =
* Added: Category picker dropdown in form editor
* Added: All form settings now exposed in admin UI
* Fixed: Inline scripts properly enqueued via wp_add_inline_script
* Fixed: Inline styles properly enqueued via wp_add_inline_style
* Fixed: Main class renamed to UCOF_Main for proper prefixing

= 1.1.1 =
* Removed: Custom CSS feature (not permitted per WordPress.org guidelines)
* Removed: Weight-based delivery fee code (hidden functionality)
* All features now fully accessible in admin UI

= 1.1.0 =
* Fixed: Replaced session_start() with cookies for cache compatibility
* Fixed: Added complete External Services documentation in readme
* Fixed: Removed license key reference from installation steps
* Added: Terms of Service and Privacy Policy links for all external services

= 1.0.9 =
* Removed: All remaining license activation code for WordPress.org compliance

= 1.0.8 =
* Fixed: Added PHPCS ignore comments for false-positive warnings
* Fixed: Changed wp_redirect to wp_safe_redirect
* Fixed: Added wp_unslash to POST data sanitization

= 1.0.7 =
* Fixed: Added missing translators comments for all placeholder strings

= 1.0.6 =
* Fixed: All output escaping errors (UCOF_VERSION, wc_price, paginate_links)
* Fixed: Added translators comments for placeholder strings
* Fixed: Properly enqueued GA4 script in frontend
* Fixed: Custom CSS now uses wp_add_inline_style
* Removed: Debug error_log calls from forms and zones classes

= 1.0.5 =
* Changed: Plugin slug and text domain to 'unstoppable-cod-order-forms-for-woocommerce'
* Changed: Folder name matches new slug for WordPress.org compliance

= 1.0.4 =
* Fixed: Tested up to header now matches in both plugin file and readme (7.0)
* Fixed: Options Studio shell now uses plugin text domain

= 1.0.3 =
* Fixed Plugin Check warnings and errors
* Properly enqueued GA4 tracking script
* Added variable prefixes for WP.org compliance
* Removed deprecated load_plugin_textdomain
* Fixed wp_unslash on $_SERVER variables
* Removed debug error_log calls

= 1.0.2 =
* WordPress.org Lite version - removed license system

= 1.1.1 =
* Removed: Custom CSS feature (not permitted per WordPress.org guidelines)
* Removed: Weight-based delivery fee code (hidden functionality)
* All features now fully accessible in admin UI

= 1.1.0 =
* Fixed: Replaced session_start() with cookies for cache compatibility
* Fixed: Added complete External Services documentation in readme
* Fixed: Removed license key reference from installation steps
* Added: Terms of Service and Privacy Policy links for all external services

= 1.0.9 =
* Removed: All remaining license activation code for WordPress.org compliance

= 1.0.8 =
* Made dashboard stat cards clickable - now link to Forms, Zones, and Orders tabs
* Improved stat card hover effects with smooth animation
* Better visual feedback on card interaction
* CSS improvements for stat cards display

= 1.1.1 =
* Removed: Custom CSS feature (not permitted per WordPress.org guidelines)
* Removed: Weight-based delivery fee code (hidden functionality)
* All features now fully accessible in admin UI

= 1.1.0 =
* Fixed: Replaced session_start() with cookies for cache compatibility
* Fixed: Added complete External Services documentation in readme
* Fixed: Removed license key reference from installation steps
* Added: Terms of Service and Privacy Policy links for all external services

= 1.0.9 =
* Removed: All remaining license activation code for WordPress.org compliance

= 1.0.8 =
* Fixed: Added PHPCS ignore comments for false-positive warnings
* Fixed: Changed wp_redirect to wp_safe_redirect
* Fixed: Added wp_unslash to POST data sanitization

= 1.0.7 =
* Improved regions/cities saving with better feedback
* Success message now shows how many regions were saved
* Auto-redirect to edit page after creating a new zone
* Added debugging for troubleshooting zone saves
* Better error handling in JavaScript

= 1.1.1 =
* Removed: Custom CSS feature (not permitted per WordPress.org guidelines)
* Removed: Weight-based delivery fee code (hidden functionality)
* All features now fully accessible in admin UI

= 1.1.0 =
* Fixed: Replaced session_start() with cookies for cache compatibility
* Fixed: Added complete External Services documentation in readme
* Fixed: Removed license key reference from installation steps
* Added: Terms of Service and Privacy Policy links for all external services

= 1.0.9 =
* Removed: All remaining license activation code for WordPress.org compliance

= 1.0.8 =
* Fixed: Added PHPCS ignore comments for false-positive warnings
* Fixed: Changed wp_redirect to wp_safe_redirect
* Fixed: Added wp_unslash to POST data sanitization

= 1.0.7 =
* Fixed: Added missing translators comments for all placeholder strings

= 1.0.6 =
* Fixed PHP syntax error (extra closing brace in zones class)

= 1.1.1 =
* Removed: Custom CSS feature (not permitted per WordPress.org guidelines)
* Removed: Weight-based delivery fee code (hidden functionality)
* All features now fully accessible in admin UI

= 1.1.0 =
* Fixed: Replaced session_start() with cookies for cache compatibility
* Fixed: Added complete External Services documentation in readme
* Fixed: Removed license key reference from installation steps
* Added: Terms of Service and Privacy Policy links for all external services

= 1.0.9 =
* Removed: All remaining license activation code for WordPress.org compliance

= 1.0.8 =
* Fixed: Added PHPCS ignore comments for false-positive warnings
* Fixed: Changed wp_redirect to wp_safe_redirect
* Fixed: Added wp_unslash to POST data sanitization

= 1.0.7 =
* Fixed: Added missing translators comments for all placeholder strings

= 1.0.6 =
* Fixed: All output escaping errors (UCOF_VERSION, wc_price, paginate_links)
* Fixed: Added translators comments for placeholder strings
* Fixed: Properly enqueued GA4 script in frontend
* Fixed: Custom CSS now uses wp_add_inline_style
* Removed: Debug error_log calls from forms and zones classes

= 1.0.5 =
* CRITICAL FIX: Added missing create() and update() methods to Forms and Zones classes
* Fixed form/zone saving not working via AJAX
* Fixed settings not being saved properly (settings array parsing)
* Added automatic slug uniqueness (appends number if slug exists)
* Improved error handling with better user feedback
* Added WP_DEBUG logging for troubleshooting

= 1.1.1 =
* Removed: Custom CSS feature (not permitted per WordPress.org guidelines)
* Removed: Weight-based delivery fee code (hidden functionality)
* All features now fully accessible in admin UI

= 1.1.0 =
* Fixed: Replaced session_start() with cookies for cache compatibility
* Fixed: Added complete External Services documentation in readme
* Fixed: Removed license key reference from installation steps
* Added: Terms of Service and Privacy Policy links for all external services

= 1.0.9 =
* Removed: All remaining license activation code for WordPress.org compliance

= 1.0.8 =
* Fixed: Added PHPCS ignore comments for false-positive warnings
* Fixed: Changed wp_redirect to wp_safe_redirect
* Fixed: Added wp_unslash to POST data sanitization

= 1.0.7 =
* Fixed: Added missing translators comments for all placeholder strings

= 1.0.6 =
* Fixed: All output escaping errors (UCOF_VERSION, wc_price, paginate_links)
* Fixed: Added translators comments for placeholder strings
* Fixed: Properly enqueued GA4 script in frontend
* Fixed: Custom CSS now uses wp_add_inline_style
* Removed: Debug error_log calls from forms and zones classes

= 1.0.5 =
* Changed: Plugin slug and text domain to 'unstoppable-cod-order-forms-for-woocommerce'
* Changed: Folder name matches new slug for WordPress.org compliance

= 1.0.4 =
* Improved admin UI with modern card-based design
* Added icons to form section headers for better visual organization
* Added PHP fallback for zone editor when JavaScript fails
* Improved form fields with better descriptions and placeholders
* Enhanced checkbox styling with hover effects
* Better responsive design for mobile devices
* Added Cancel button to form/zone editors

= 1.1.1 =
* Removed: Custom CSS feature (not permitted per WordPress.org guidelines)
* Removed: Weight-based delivery fee code (hidden functionality)
* All features now fully accessible in admin UI

= 1.1.0 =
* Fixed: Replaced session_start() with cookies for cache compatibility
* Fixed: Added complete External Services documentation in readme
* Fixed: Removed license key reference from installation steps
* Added: Terms of Service and Privacy Policy links for all external services

= 1.0.9 =
* Removed: All remaining license activation code for WordPress.org compliance

= 1.0.8 =
* Fixed: Added PHPCS ignore comments for false-positive warnings
* Fixed: Changed wp_redirect to wp_safe_redirect
* Fixed: Added wp_unslash to POST data sanitization

= 1.0.7 =
* Fixed: Added missing translators comments for all placeholder strings

= 1.0.6 =
* Fixed: All output escaping errors (UCOF_VERSION, wc_price, paginate_links)
* Fixed: Added translators comments for placeholder strings
* Fixed: Properly enqueued GA4 script in frontend
* Fixed: Custom CSS now uses wp_add_inline_style
* Removed: Debug error_log calls from forms and zones classes

= 1.0.5 =
* Changed: Plugin slug and text domain to 'unstoppable-cod-order-forms-for-woocommerce'
* Changed: Folder name matches new slug for WordPress.org compliance

= 1.0.4 =
* Fixed: Tested up to header now matches in both plugin file and readme (7.0)
* Fixed: Options Studio shell now uses plugin text domain

= 1.0.3 =
* Fixed form editor JavaScript not binding correctly (was using class selector instead of ID)
* Fixed white screen when creating new forms
* Added PHP fallback for form submission when JavaScript fails
* Added admin_url to JavaScript for proper redirects

= 1.1.1 =
* Removed: Custom CSS feature (not permitted per WordPress.org guidelines)
* Removed: Weight-based delivery fee code (hidden functionality)
* All features now fully accessible in admin UI

= 1.1.0 =
* Fixed: Replaced session_start() with cookies for cache compatibility
* Fixed: Added complete External Services documentation in readme
* Fixed: Removed license key reference from installation steps
* Added: Terms of Service and Privacy Policy links for all external services

= 1.0.9 =
* Removed: All remaining license activation code for WordPress.org compliance

= 1.0.8 =
* Fixed: Added PHPCS ignore comments for false-positive warnings
* Fixed: Changed wp_redirect to wp_safe_redirect
* Fixed: Added wp_unslash to POST data sanitization

= 1.0.7 =
* Fixed: Added missing translators comments for all placeholder strings

= 1.0.6 =
* Fixed: All output escaping errors (UCOF_VERSION, wc_price, paginate_links)
* Fixed: Added translators comments for placeholder strings
* Fixed: Properly enqueued GA4 script in frontend
* Fixed: Custom CSS now uses wp_add_inline_style
* Removed: Debug error_log calls from forms and zones classes

= 1.0.5 =
* Changed: Plugin slug and text domain to 'unstoppable-cod-order-forms-for-woocommerce'
* Changed: Folder name matches new slug for WordPress.org compliance

= 1.0.4 =
* Fixed: Tested up to header now matches in both plugin file and readme (7.0)
* Fixed: Options Studio shell now uses plugin text domain

= 1.0.3 =
* Fixed Plugin Check warnings and errors
* Properly enqueued GA4 tracking script
* Added variable prefixes for WP.org compliance
* Removed deprecated load_plugin_textdomain
* Fixed wp_unslash on $_SERVER variables
* Removed debug error_log calls

= 1.0.2 =
* Simplified integrations - now uses webhook URLs instead of complex API credentials
* Added universal webhook integration (works with Zapier, Make, Pabbly, n8n)
* Added Google Sheets setup with copy-paste Apps Script code
* Added Snapchat Pixel support
* Added webhook test button
* Improved WhatsApp message templates with more placeholders
* Better UI with step-by-step setup instructions

= 1.0.1 =
* Added WooCommerce HPOS (High-Performance Order Storage) compatibility
* Added Cart/Checkout Blocks compatibility
* Updated minimum WooCommerce version to 8.0
* Updated minimum WordPress version to 6.0

= 1.0.0 =
* Initial release

== Frequently Asked Questions ==

= Does this work without WooCommerce? =

WooCommerce is required for product data. However, you can disable WooCommerce order creation and use Google Sheets for fulfillment instead.

= Can I customize the form appearance? =

Yes! Each form has a custom CSS field, and the template can be overridden in your theme.

= How do delivery zones work? =

Create zones with regions/cities, set fee types (flat, percentage, weight-based, or free), and the form automatically calculates delivery based on customer location.

== Screenshots ==

1. Form builder interface
2. Delivery zones configuration
3. Orders management
4. Frontend order form
