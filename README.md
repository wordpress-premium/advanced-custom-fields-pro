# Advanced Custom Fields PRO

![GitHub followers](https://img.shields.io/github/followers/wordpress-premium) ![GitHub Repo stars](https://img.shields.io/github/stars/wordpress-premium/advanced-custom-fields-pro) ![GitHub last commit](https://img.shields.io/github/last-commit/wordpress-premium/advanced-custom-fields-pro)

**Advanced Custom Fields Pro** (ACF Pro) is a popular WordPress plugin that allows developers to create custom fields and content for their websites easily. **This is the fully activated [pro version](https://www.advancedcustomfields.com/pro/), which has been thoroughly checked for malware and is safe to use for research purposes.** 

**Note:** Using activated software may violate the original author's licensing terms and may not receive official support or updates.

**If you appreciate this service and would like to keep getting recent, malware-free updates, please consider [leaving a tip via PayPal](https://www.paypal.com/paypalme/thaikolja).**

## Changelog

### v6.3.4 (latest)

**Release Date:** 18th July 2024

* **Security Fix** - The ACF shortcode now prevents access to fields from different private posts by default. View the [release notes](https://www.advancedcustomfields.com/blog/acf-6-3-4) for more information
* **Fix** - Users without the `edit_posts` capability but with custom capabilities for editing a custom post type can now correctly load field groups loaded via conditional location rules
* **Fix** - Block validation no longer validates a field’s sub-fields on page load, only on edit. This resolves inconsistent validation errors on page load or when first adding a block
* **Fix** - Deactivating an ACF PRO license will now remove the license key even if the server call fails
* **Fix** - Field types returning objects no longer cause PHP warnings and errors when output via `the_field`, `the_sub_field`, or the ACF shortcode, or when retrieved by a `get_` function with the escape html parameter set
* **Fix** - Server-side errors during block rendering now gracefully display an error to the editor

---

For changelogs of older versions, check the [official website](https://www.advancedcustomfields.com/changelog/).
