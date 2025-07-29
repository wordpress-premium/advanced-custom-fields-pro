# Advanced Custom Fields PRO

![GitLab Release](https://img.shields.io/gitlab/v/release/wordpress-premium%2Fadvanced-custom-fields-pro) ![GitHub followers](https://img.shields.io/github/followers/wordpress-premium?style=flat&color=lightblue) ![GitHub Repo stars](https://img.shields.io/github/stars/wordpress-premium/advanced-custom-fields-pro?style=flat&color=orange) ![GitHub forks](https://img.shields.io/github/forks/wordpress-premium/advanced-custom-fields-pro?style=flat) ![GitHub last commit](https://img.shields.io/github/last-commit/wordpress-premium/advanced-custom-fields-pro)

[**Advanced Custom Fields PRO**](https://www.advancedcustomfields.com/) (or **ACF**) is a powerful plugin for WordPress that allows you to customize your website with professional and intuitive fields. ACF PRO provides tools to take full control of your WordPress edit screens, custom field data, and more, making it a favorite among developers.

## Usage

This is the **fully activated premium version** of the plugin, provided by [wordpress-premium.net](https://www.wordpress-premium.net). It has been scanned for security issues and is intended **for evaluation purposes only**. To use Advanced Custom Fields PRO on a live website, please [purchase a license](https://www.advancedcustomfields.com/pro/) directly from the official website.

**Important:** Unlicensed ("nulled") usage may violate the developer's terms and will not include official updates or support.

> [!TIP]
>
> ## Donate
>
> If [WordPress Premium](https://www.wordpress-premium.net/) helps you access premium plugins safely, consider [supporting us via PayPal](https://www.paypal.com/paypalme/thaikolja) or by [cryptocurrency](https://www.wordpress-premium.net/wallets/) to keep the service running.

### Using Premium Features

Advanced Custom Fields PRO comes with a range of advanced features, including:

- **Repeater Field:** Create a set of subfields that can be repeated as many times as needed.
- **Flexible Content Field:** Define, create, and manage content with multiple layouts and subfield options.
- **Options Page:** Add custom admin pages to edit ACF fields.
- **Gallery Field:** Build fully customizable image galleries.
- **Clone Field:** Reuse existing fields and field groups to streamline your workflow.

To access these features, simply activate the plugin and navigate to the **ACF** section in your WordPress dashboard.

## Changelog

### v6.4.3

**Released:** July 22nd, 2025

* Security - Unsafe HTML in field group labels is now correctly escaped for conditionally loaded field groups, resolving a JS execution vulnerability in the classic editor
* Security - HTML is now escaped from field group labels when output in the ACF admin
* Security - Bidirectional and Conditional Logic Select2 elements no longer render HTML in field labels or post titles
* Security - The `acf.escHtml` function now uses the third-party DOMPurify library to ensure all unsafe HTML is removed. A new `esc_html_dompurify_config` JS filter can be used to modify the default behaviour
* Security - Post titles are now correctly escaped whenever they are output by ACF code. Thanks to Shogo Kumamaru of LAC Co., Ltd. for the responsible disclosure
* Security - An admin notice is now displayed when version 3 of the Select2 library is used, as it has now been deprecated in favor of version 4

For the full changelog, visit [Advanced Custom Fields PRO Changelog](https://www.advancedcustomfields.com/changelog/).
