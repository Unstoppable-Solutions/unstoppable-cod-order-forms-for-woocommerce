# Unstoppable COD Order Forms for WooCommerce

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/unstoppable-cod-order-forms-for-woocommerce.svg)](https://wordpress.org/plugins/unstoppable-cod-order-forms-for-woocommerce/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/unstoppable-cod-order-forms-for-woocommerce.svg)](https://wordpress.org/plugins/unstoppable-cod-order-forms-for-woocommerce/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

Cash-on-Delivery order forms for WooCommerce. One-page order forms where customers skip the whole cart/checkout flow and place a COD order from a dedicated landing page — the pattern that works for high-converting single-product campaigns, especially in regions where COD is the primary payment method.

> **End users:** install from the WordPress Plugin Directory — [wordpress.org/plugins/unstoppable-cod-order-forms-for-woocommerce](https://wordpress.org/plugins/unstoppable-cod-order-forms-for-woocommerce/)

## Features

- **One-page COD order form** — product, quantity, customer details, place order, all on one screen
- **Landing-page friendly** — drop the form onto any page as a shortcode or block
- **Variation + quantity picker** — handles variable products and quantity selection inline
- **Order bump + upsells** — optional add-ons shown inside the same form
- **Thank-you page** — redirect to a dedicated thank-you URL after order
- **COD-first flow** — Cash on Delivery is the default; no card form, no payment-gateway friction

## Who it's for

Stores that run paid-ad campaigns where asking the visitor to go through the full WooCommerce cart kills conversion, and stores in markets where COD is how most orders get paid. Especially useful for single-product campaigns, dropshipping stores, and any store built around a specific landing page.

## Development

### Release workflow

Releases are deployed to WordPress.org automatically by GitHub Actions:

1. Finish the feature work on `main`
2. Bump the version in `unstoppable-cod-forms.php` (header + any constant), `readme.txt` (Stable tag), and add a Changelog entry
3. Commit and push `main`
4. On GitHub, create a new **Release** tagged `vX.Y.Z` — the deploy workflow fires and pushes to WordPress.org SVN

### CI

Every push and pull request runs the official WordPress Plugin Check against the codebase.

## License

GPL-2.0-or-later. See the `License` header in `unstoppable-cod-forms.php`.

## Author

Developed and maintained by [Unstoppable Solutions](https://unstoppablesolutions.app).
