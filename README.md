# Advanced Custom Fields PRO

![Version](https://img.shields.io/badge/release-6.8.9-blue) ![Requires WP](https://img.shields.io/badge/WordPress-6.2%2B-blue) ![Requires PHP](https://img.shields.io/badge/PHP-7.4%2B-8892BF) ![Tested up to](https://img.shields.io/badge/tested_up_to-7.1-green) ![GitHub last commit](https://img.shields.io/github/last-commit/wordpress-premium/advanced-custom-fields-pro)

[**Advanced Custom Fields PRO**](https://www.advancedcustomfields.com/) (or **ACF**) is a powerful plugin for WordPress that allows you to customize your website with professional and intuitive fields. ACF PRO provides tools to take full control of your WordPress edit screens, custom field data, and more, making it a favorite among developers.

This copy is **version 6.8.9** (released 27th August 2026). It requires WordPress 6.2+, PHP 7.4+, and is tested up to WordPress 7.1.

**Get more pro and premium plugins on [wordpress-premium.net](https://www.wordpress-premium.net/?utm_source=acf).**

## Download and Installation

[Click here to download Advanced Custom Fields PRO](https://gitlab.com/wordpress-premium/advanced-custom-fields-pro/-/archive/main/advanced-custom-fields-pro-main.zip) as a `.zip` file. Follow [SiteGround's detailed description](https://www.siteground.com/tutorials/wordpress/install-plugins/#How_to_Upload_a_WordPress_Plugin_from_a_File) to upload it via your dashboard or (S)FTP.

## Usage

1. Download the .zip file
2. Upload it via WordPress' admin area
3. Activate the plugin

### License Code

In this version, the license code has already been added. If you try to remove the code, it will automatically be added again with the following license code:

```bash
1415b451be1a13c283ba771ea52d38bb
```

This is the **fully activated premium version** of the plugin, provided by [wordpress-premium.net](https://www.wordpress-premium.net?utm_source=acf). It was statically scanned on 28th August 2026 (see [SECURITY-AUDIT.md](SECURITY-AUDIT.md)): no malware, webshell, or backdoor was found. Intended **for evaluation purposes only**. To use Advanced Custom Fields PRO on a live website, please [purchase a license](https://www.advancedcustomfields.com/pro/) directly from the official website.

**Important:** Unlicensed ("nulled") usage may violate the developer's terms and will not include official updates or support.

> [!TIP]
>
> ## Donate
>
> If [WordPress Premium](https://www.wordpress-premium.net/?utm_source=acf) helps you access premium plugins safely, consider supporting us via [a donation through PayPal](https://www.paypal.com/paypalme/thaikolja) or in any of the available [cryptocurrencies](https://www.wordpress-premium.net/wallets/) to keep the service running.

## Malware

Every plugin WordPress Premium provides is rigorously scanned for malware, webshells, and backdoors and, if necessary, cleaned. You can [download the full report](SECURITY-AUDIT.md).

## Changelog

### v6.8.9

**Release Date:** 27th August 2026

- **feat(blocks):** blocks registered without an explicit version now default to v3 on WordPress 7.1+ (`acf/blocks/default_block_version` filter)
- **feat(blocks):** add `renderPreview` option in `block.json` (`false` shows a placeholder instead of a live preview)
- **fix(blocks):** inline editable fields in V3 no longer require a second click
- **fix(admin):** radio buttons now appear correctly in ACF admin screens
- **fix(fields):** Image and Gallery fields no longer reject SVG uploads when Safe SVG is active

---

For the full changelog, visit [Advanced Custom Fields PRO Changelog](https://www.advancedcustomfields.com/changelog/).
