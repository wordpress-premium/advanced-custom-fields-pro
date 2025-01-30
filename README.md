# Advanced Custom Fields PRO

[![Version](https://img.shields.io/badge/version-6.3.11-blue)](https://github.com/wordpress-premium/advanced-custom-fields-pro) ![GitHub followers](https://img.shields.io/github/followers/wordpress-premium?style=flat&color=lightblue) ![GitHub Repo stars](https://img.shields.io/github/stars/wordpress-premium/advanced-custom-fields-pro?style=flat&color=orange) ![GitHub forks](https://img.shields.io/github/forks/wordpress-premium/advanced-custom-fields-pro?style=flat) ![GitHub last commit](https://img.shields.io/github/last-commit/wordpress-premium/advanced-custom-fields-pro) [![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)](https://github.com/wordpress-premium/advanced-custom-fields-pro/blob/main/LICENSE)

[**Advanced Custom Fields PRO**](https://www.advancedcustomfields.com/) (or **ACF**)is a powerful plugin for WordPress that allows you to customize your website with professional and intuitive fields. ACF PRO provides tools to take full control of your WordPress edit screens, custom field data, and more, making it a favorite among developers.

## Usage

This is the **fully activated premium version** of the plugin, provided by [wordpress-premium.net](https://www.wordpress-premium.net). It has been scanned for security issues and is intended **for evaluation purposes only**. To use Advanced Custom Fields PRO on a live website, please [purchase a license](https://www.advancedcustomfields.com/pro/) directly from the official website.

**Important:** Unlicensed ("nulled") usage may violate the developer's terms and will not include official updates or support.

### Using Premium Features

Advanced Custom Fields PRO comes with a range of advanced features, including:

- **Repeater Field:** Create a set of subfields that can be repeated as many times as needed.
- **Flexible Content Field:** Define, create, and manage content with multiple layouts and subfield options.
- **Options Page:** Add custom admin pages to edit ACF fields.
- **Gallery Field:** Build fully customizable image galleries.
- **Clone Field:** Reuse existing fields and field groups to streamline your workflow.

To access these features, simply activate the plugin and navigate to the **ACF** section in your WordPress dashboard.

## Donate

If [WordPress Premium](https://www.wordpress-premium.net/) helps you access premium plugins safely, consider [supporting us via PayPal](https://www.paypal.com/paypalme/thaikolja) to keep the service running.

---

Here are the last three updates from the Advanced Custom Fields PRO changelog to keep you in the loop.

## Changelog

### v6.3.11

**Release Date:** November 12, 2024  

- **Enhancement:** Field Group keys are now copyable on click.  
- **Fix:** Repeater tables with fields hidden by conditional logic now render correctly.  
- **Fix:** ACF Blocks now behave correctly in React StrictMode.  
- **Fix:** Edit mode is no longer available to ACF Blocks with a WordPress Block API version of 3 as field editing is not supported in the iframe.  

### v6.3.10.2

**Release Date:** October 29, 2024  
*(Free release only)*

- **Fix:** ACF Free no longer causes a fatal error when any unsupported legacy ACF addons are active.  

### v6.3.10.1

**Release Date:** October 29, 2024  
*(Free release only)*

- **Fix:** ACF Free no longer causes a fatal error when WPML is active.  

### v6.3.10

**Release Date:** October 29, 2024  

- **Security:** Setting a metabox callback for custom post types and taxonomies now requires being an admin, or super admin for multisite installs.  
- **Security:** Field-specific ACF nonces are now prefixed, resolving an issue where third-party nonces could be treated as valid for AJAX calls.  
- **Enhancement:** A new “Close and Add Field” option is now available when editing a field group, inserting a new field inline after the field being edited.  
- **Enhancement:** ACF and ACF PRO now share the same plugin updater for improved reliability and performance.  
- **Fix:** Exporting post types and taxonomies containing metabox callbacks now correctly exports the user-defined callback.  

---

For the full changelog, visit [Advanced Custom Fields PRO Changelog](https://www.advancedcustomfields.com/changelog/).