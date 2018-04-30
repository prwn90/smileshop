=== YITH WooCommerce Brands Add-On ===

Contributors: yithemes
Tags: brand, brands, logo, manufacturer, yit, e-commerce, ecommerce, shop, supplier, woocommerce brand, woocommerce filter, filter, brand filter, woocommerce manufacturer, woocommerce supplier, brands for woocommerce, brands for wc, product brands, brands for products
Requires at least: 4.0
Tested up to: 4.7.3
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

YITH WooCommerce Brands Add-on allows you to show brands in your store that are for online customers a very important additional reason for reliability.


== Description ==

Showing known brands in your shop has many advantages both for you and for your customers, especially in an online store, where quality cannot be "touched" and a product might be easily under- or overestimated. Not only do well known brands attract customers and grant you more visibility, but your customers will feel much more confident when buying, because the brand itself is a guarantee of quality. Leaving aside the possibility to keep prices high and all other benefits that we cannot list here, now you can add this simple but powerful feature to your shop wiht YITH WooCommerce Brands Add-On.

= Free Features =

* Tassonomy “Brands” will be added to WooCommerce products
* You can create a brand, assign a logo, a name and a description
* You can manage brands hierarchically
* You can assign one or more brands to each product
* You can view brands of a product in single product page
* You can view archive page for each brand

== Installation ==

1. Unzip the downloaded zip file.
2. Upload the plugin folder into the `wp-content/plugins/` directory of your WordPress site.
3. Activate `YITH WooCommerce Brands Add-on` from Plugins page

YITH WooCommerce Brands Add-on will add a new submenu called "Brands" under "YIT Plugins" menu. Here you are able to configure all the plugin settings.

== Screenshots ==

1. Single product page with brands
2. Brand archive page
3. Brand taxonomy page
4. Edit brand page
5. Brands option panel

== Changelog ==

= 1.1.1 - Released: Apr, 11 - 2017 =

* New: WooCommerce 3.0.1 compatibility
* Fix: terms meta query overwritten by "sorting" method
* Fix: preventing notice when crop is not set for image sizes

= 1.1.0 - Released: Apr, 04 - 2017 =

* New: WordPress 4.7.3 compatibility
* New: WooCommerce 3.0.0 compatibility
* New: admin can sort brands with Drag & Drop
* New: now terms are retrieved using default ordering (menu ordering)
* Tweak: added wpautop to brand description
* Tweak: changed description container from p to div
* Tweak: Check over "cb" column existence for taxonomy view
* Tweak: added compatibiity with YIT Layout
* Fix: added "Brands" column when using attributes as brand taxonomy
* Dev: added yith_wcbr_taxonomy_labels filter, to let customers change taxonomy labels
* Dev: added yith_wcbr_brand_filter_terms filter, to let developers change shortcode term sorting
* Dev: added filter yith_wcbr_taxonomy_slug to customize taxonomy slug (use it at your own risk, as changing taxonomy slug will remove all terms and product associations)

= 1.0.8 - Released: Nov, 28 - 2016 =

* Add: spanish translation
* Tweak: updated plugin-fw
* Tweak: changed text-domain to yith-woocommerce-brands-add-on

= 1.0.7 - Released: Jun, 10 - 2016 =

* Added: WordPress 4.5.2 support
* Added: WooCommerce 2.6-RC1 support
* Added: italian translation
* Tweak: added yith_wcbr_get_terms to pass different params to get_terms for WP > 4.5

= 1.0.6 - Released: May, 02 - 2016 =

* Added: WordPress 4.5.1 support
* Added: WooCommerce 2.5.5 support
* Added: rich snippets for brand
* Added: filter yith_wcbr_taxonomy_label for brands label
* Added: filter yith_wcbr_print_brand_description to hide product brand description
* Added: flag with_front on register_taxonomy, with filter yith_wcbr_taxonomy_with_front to change default value
* Fixed: error with assets inclusion caused by wrong screen id

= 1.0.5 - Released: Oct, 23 - 2015 =

* Added: Dutch translation (thanks to Bart V.)
* Tweak: Performance improved with new plugin core 2.0
* Fixed: plugin-fw breaking theme-editor.php page

= 1.0.4 - Released: Sep, 21 - 2015 =

* Added: yith_wcbr_taxonomy_capabilities filter
* Added: YITH WooCommerce Multi Vendor Support

= 1.0.3 - Released: Aug, 13 - 2015 =

* Added: Compatibility with WC 2.4.2
* Tweak: Updated internal plugin-fw

= 1.0.2 - Released: Jul, 13 - 2015 =

* Added: WC 2.3.13 support
* Added: improved YITH WooCommerce Product Filter compatibility
* Fixed: minor bugs

= 1.0.1 - Released: Jun, 19 - 2015 =

* Added: WC 2.3.11 support
* Fixed: minor bugs
* Fixed: wrong text domain in some string localization

= 1.0.0 - Released: May, 21 - 2015 =

* Initial release

== Suggestions ==

If you have suggestions about how to improve YITH WooCommerce Brands Add-On, you can [write us](mailto:plugins@yithemes.com "Your Inspiration Themes") so we can bundle them into YITH WooCommerce Brands Add-On.

== Translators ==

= Available Languages =
* English - UNITED KINGDOM (Default)
* Dutch - NETHERLANDS
* Italian - ITALY
* Spanish - SPAIN

Need to translate this plugin into your own language? You can contribute to its translation from [this page](https://translate.wordpress.org/projects/wp-plugins/yith-woocommerce-brands-add-on "Translating WordPress").
Your help is precious! Thanks

== Documentation ==

Full documentation is available [here](http://yithemes.com/docs-plugins/yith-woocommerce-brands-add-on).
