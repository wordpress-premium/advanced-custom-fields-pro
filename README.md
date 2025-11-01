# Advanced Custom Fields PRO

![GitLab Release](https://img.shields.io/gitlab/v/release/wordpress-premium%2Fadvanced-custom-fields-pro) ![GitHub followers](https://img.shields.io/github/followers/wordpress-premium?style=flat&color=lightblue) ![GitHub Repo stars](https://img.shields.io/github/stars/wordpress-premium/advanced-custom-fields-pro?style=flat&color=orange) ![GitHub forks](https://img.shields.io/github/forks/wordpress-premium/advanced-custom-fields-pro?style=flat) ![GitHub last commit](https://img.shields.io/github/last-commit/wordpress-premium/advanced-custom-fields-pro)

[**Advanced Custom Fields PRO**](https://www.advancedcustomfields.com/) (or **ACF**) is a powerful plugin for WordPress that allows you to customize your website with professional and intuitive fields. ACF PRO provides tools to take full control of your WordPress edit screens, custom field data, and more, making it a favorite among developers.

**Get more pro and premium plugins on [wordpress-premium.net](https://www.wordpress-premium.net/?utm_source=acf).**

## Download and Installation

[Click here to download Advanced Custom Fields PRO](https://gitlab.com/wordpress-premium/advanced-custom-fields-pro/-/archive/main/advanced-custom-fields-pro-main.zip) as a `.zip` file. Follow [SiteGround's detailed description](https://www.siteground.com/tutorials/wordpress/install-plugins/#How_to_Upload_a_WordPress_Plugin_from_a_File) to upload it via your dashboard or (S)FTP.

## Usage

> [!IMPORTANT]
>
> Upon activation, the plugin will display a message that it has not been registered. To resolve this, go to "ACF" → "Updates" and enter **any random text** into the field and press "Activate License".

### License Code

```bash
83A5BB0E-2AD5-1646-90BC-7A42AE592CF5
```

This is the **fully activated premium version** of the plugin, provided by [wordpress-premium.net](https://www.wordpress-premium.net?utm_source=acf). It has been scanned for security issues and is intended **for evaluation purposes only**. To use Advanced Custom Fields PRO on a live website, please [purchase a license](https://www.advancedcustomfields.com/pro/) directly from the official website.

**Important:** Unlicensed ("nulled") usage may violate the developer's terms and will not include official updates or support.

> [!TIP]
>
> ## Donate
>
> If [WordPress Premium](https://www.wordpress-premium.net/?utm_source=acf) helps you access premium plugins safely, consider supporting us via a donation in any of the available [cryptocurrencies](https://www.wordpress-premium.net/wallets/) to keep the service running.

## Changelog

### v6.6.2

**Release Date:** 29th October 2025

* Enhancement - Added a new `convert_field_name_to_lowercase` JS filter to allow uppercase letters in ACF field names
* Enhancement - The form for V3 Blocks can now be optionally hidden from the sidebar via a new `hideFieldsInSidebar` setting in block.json
* Enhancement - V3 Blocks now display an "Open Expanded Editor" button in the sidebar for easier access to the full edit form
* Fix - The buttons to reorder ACF metaboxes are no longer hidden for metaboxes in the block editor sidebar
* Fix - V3 Blocks now display a fallback message when the block preview can't be rendered due to invalid HTML being used in field values
* Fix - V3 Blocks no longer show a loading spinner when preloaded
* Fix - V3 Blocks now save default field values even if the block wasn't interacted with before saving
* Fix - Pressing CMD/CTRL + Z no longer causes the fields to disappear in V3 Blocks
* Fix - The form for V3 Blocks now opens on the left side in RTL languages

---

For the full changelog, visit [Advanced Custom Fields PRO Changelog](https://www.advancedcustomfields.com/changelog/).
