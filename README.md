# Advanced Custom Fields PRO

**Advanced Custom Fields Pro** (ACF Pro) is a popular WordPress plugin that allows developers to easily create custom fields and content for their websites. **This is the fully activated pro version, which has been thoroughly checked for malware and is safe to use.** However, using cracked software may violate the original author's licensing terms and may not receive official support or updates.

**If you appreciate this service and would like to keep getting recent, malware-free updates, please consider [leaving a tip via PayPal](https://www.paypal.com/paypalme/thaikolja).**

## Changelog

### v6.3.0.1

**Release Date:** 22nd May 2024 - PRO Only Release

* **Fix** - A possible fatal error no longer occurs in the new site health functionality for ACF PRO users
* **Fix** - A possible undefined index error no longer occurs in ACF Blocks for ACF PRO users


### v6.3.0

**Release Date:** 22nd May 2024

* **New** - ACF now requires WordPress version 6.0 or newer, and PHP 7.4 or newer.
* **New** - ACF Blocks now support validation rules for fields. View the release notes for more information
* **New** - ACF Blocks now supports storing field data in the postmeta table rather than in the post content
* **New** - Conditional logic rules for fields now support selecting specific values for post objects, page links, taxonomies, relationships and users rather than having to enter the ID
* **New** - New Icon Picker field type for ACF and ACF PRO
* **New** - Icon selection for a custom post type menu icon
* **New** - Icon selection for an options page menu icon
* **New** - ACF now surfaces debug and status information in the WordPress Site Health area
* **New** - The escaped html notice can now be permanently dismissed
* **Enhancement** - Tab field now supports a selected attribute to specify which should be selected #by default, and support class attributes
* **Fix** - Block Preloading now works reliably in WordPress 6.5 or newer
* **Fix** - Select2 results loaded by AJAX for post object fields no longer double encode HTML entities
* **Fix** - Custom post types registered with ACF will now have custom field support enabled by default to better support revisions
* **Fix** - The first preview after publishing a post in the classic editor now displays ACF fields correctly
* **Fix** - ACF fields and Flexible Content layouts are now correctly positioned while dragging
* **Fix** - Copying the title of a field inside a Flexible Content layout no longer adds whitespace to the copied value
* **Fix** - Flexible Content layout names are no longer converted to lowercase when edited
* **Fix** - ACF Blocks with attributes without a default now correctly register
* **Fix** - User fields no longer trigger a 404 when loading results if the nonce generated only contains numbers
* **Fix** - Description fields for ACF items now support being solely numeric characters
* **Fix** - The field group header no longer appears above the WordPress admin menu on small screens
* **Fix** - The acf/json/save_file_name filter now correctly applies when deleting JSON files
* **i18n** - All errors raised during ACF PRO license or update checks are now translatable
* **Other** - The ACF Shortcode is now disabled by default for new installations of ACF as discussed in the ACF as discussed in the ACF
