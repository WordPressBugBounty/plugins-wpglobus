=== WPGlobus ===
Contributors: tivnetinc, tivnet
Tags: WPGlobus, localization, multilanguage, multilingual, translate
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.0.2
License: GPL-3.0-or-later
License URI: https://spdx.org/licenses/GPL-3.0-or-later.html

Multilingual/Globalization: URL-based multilanguage with an easy translation interface.

== Description ==

**WPGlobus** is a family of WordPress plugins assisting you in translating and maintaining bilingual/multilingual WordPress blogs and sites.

= Quick Start Video =

https://www.youtube.com/watch?v=zoTWY9JrXLs

Please also read the [Quick Start Guide](https://wpglobus.com/quick-start/).

= Important Notes: please read before using WPGlobus! =

* NO AUTOMATIC TRANSLATION:
	* WPGlobus does NOT translate texts automatically! You will **translate texts manually**.
* PAGE BUILDERS / COMPOSERS:
	* WPGlobus supports blocks ("Gutenberg") and WPBakery Page Builder. Other builders, such as "Page Builder by SiteOrigin", "Beaver Builder", Fusion ("Avada"), Elegant ("Divi"), Elementor, etc. have limited or no support.
* IF YOU UNINSTALL, YOU LOSE TRANSLATIONS:
	* WPGlobus stores all translations using a special format: `{:en}English{:}{:fr}French{:}{:es}Spanish{:}`. If you decide to **deactivate and uninstall WPGlobus**, you **must run the cleanup tool** to keep only one language. See the details on the "Welcome" tab in the WPGlobus Settings.
* COOKIES:
    * WPGlobus use browser cookies to store the selected language in the form `wpglobus-language=xx` where `xx` is a two-letter language code: `en`, `de`, `fr`, etc.
* NO MULTISITE:
	* The **multisite** mode (multiple virtual sites sharing a single WordPress installation) is **not tested and not supported**.
* FREE PLUGIN with PAID EXTENSIONS:
	* Some functionality is available only with our **premium add-ons**. Details below.
* OLD PHP / OLD WORDPRESS:
	* We develop and test our software using the **latest versions of PHP, WordPress, and all plugins**. If you have an older version and something is not working properly - please upgrade before contacting us.
* MBSTRING:
	* For the full UTF-8 compatibility and better performance, please make sure that the [Multibyte String](https://www.php.net/manual/en/intro.mbstring.php) PHP extension is enabled.

= What is in the FREE version of WPGlobus? =

The WPGlobus plugin provides you with the general multilingual tools.

* **Manually translate** posts, pages, categories, tags, menus, and widgets;
* **Add one or several languages** to your WP blog/site using custom combinations of country flags, locales and language names;
* **Switch the languages at the front-end** using: a drop-down menu extension and/or a customizable widget with various display options;
* **Switch the Administrator interface language** using a top bar selector;

The WPGlobus plugin serves as the **foundation** to other plugins in the family.

= When do I need WPGlobus Extensions? =

* To translate URLs (`/my-page/` translates to `/fr/ma-page`, `/es/mi-pagina` and so on);
* To "postpone" translation to all languages and publish only those that are ready;
* To have completely separate menus for each language;
* To translate WooCommerce products and taxonomies;
* ...and more.

For more details, please check out the extension descriptions on our website:

* [WooCommerce WPGlobus](https://wpglobus.com/product/woocommerce-wpglobus/): adds multilingual capabilities to WooCommerce-based online stores.
* [WPGlobus Plus](https://wpglobus.com/product/wpglobus-plus/): adds URL fine-tuning, publishing status per translation, and more.
* [WPGlobus - Mobile Menu](https://wpglobus.com/product/wpglobus-mobile-menu/): makes the WPGlobus language switcher menu compatible with mobile devices and narrow screens.
* [WPGlobus – Featured Images](https://wpglobus.com/product/wpglobus-featured-images/): Set featured image separately for each language defined in WPGlobus.
* [WPGlobus – Translate Options](https://wpglobus.com/product/wpglobus-translate-options/): Selective translation of the texts stored in the `wp_options` database table.

= Compatibility with WordPress Themes =

* WPGlobus works correctly with all themes that apply proper filtering before outputting content.
* Some themes incorporate 3rd party plugins (e.g., sliders, forms, composers) - not all of them are 100% multilingual-ready. When you see elements that cannot be translated, please **tell the theme/plugin authors**.
* Read more on the topic [here](https://wpglobus.com/documentation/wpglobus-compatibility-with-themes-and-plugins/).

= Compatibility with WordPress Plugins =

We have tested WPGlobus with many plugins. However, since plugins are frequently updated, some adjustments may be required after a new update. We will do our best to monitor and make the necessary changes on our end.

= Permalinks =

**IMPORTANT:** WPGlobus will not work if your URLs look like `example.com?p=123` or `example.com/index.php/category/post/`.

Please go to `Settings->Permalinks` and change the permalink structure to non-default and with no `index.php` in it. If you are unable to do that for some reason, please talk to your hosting provider or systems administrator.

**Note:** WooCommerce adds their own section to the Permalinks. It is important to fill in all the information. For example, you need to specify your Shop Base, for example, `/product/`. If you leave it blank, WooCommerce will try to translate the base (eg `/produkt/` for German), which will result in a 404 error.

= Developing on `localhost` or custom ports =

WPGlobus may not work correctly on development servers having URLs like `//localhost/mysite` or on custom ports like `//myserver.dev:3000`. Please use a proper domain name (a fake one from `/etc/hosts` is OK), and port 80.

== Installation ==

You can install this plugin directly from your WordPress dashboard:

1. Go to the *Plugins* menu and click *Add New*.
1. Search for *WPGlobus*.
1. Click *Install Now* next to the WPGlobus plugin.
1. Activate the plugin.

Alternatively, see the guide to [Manual Plugin Installation](https://wordpress.org/documentation/article/manage-plugins/#manual-plugin-installation-1).

== Frequently Asked Questions ==

= Please read these first: =

* [The Quick Start Guide](https://wpglobus.com/quick-start/)
* [Before contacting Support...](https://wpglobus.com/support/before-contacting-wpglobus-support/)

= No automatic translation =

WPGlobus does NOT translate texts! You need to **translate texts manually**.

= After deactivating WPGlobus, all my pages look like garbage! =

What you see is a mix of the languages, which WPGlobus knows how to handle when it's active.
When you deactivate WPGlobus, your site is not multilingual anymore, and you have to remove all translations.

WPGlobus stores all translations using a special format: `{:en}English{:}{:fr}French{:}{:es}Spanish{:}`. If you decide to **deactivate WPGlobus**, you **must run the cleanup tool** to keep only one language. See the details on the "Uninstall" tab in the WPGlobus Settings.

= When I switch language, I am getting 404 on all pages =

Please go to the `Admin - Settings - Permalinks` page. Make sure that the `Common Settings` is not set to "Plain" and then press the `Save Changes` button. It should help.

= Is there a PRO version? =

We have a set of add-ons that extend the WPGlobus functionality. Please found them on [our website](https://wpglobus.com).

**NOTE:** When you install an add-on, such as **WPGlobus Plus**, you must keep the WPGlobus plugin activated!

== Screenshots ==

1. The Welcome screen.
2. Settings panel.
3. Languages setup.
4. Attaching language switcher to a menu.
5. Editing post in multiple languages.
6. Multilingual Yoast SEO and Featured Images.
7. Language Switcher widget and Multilingual Editor dialog.
8. Multilingual WooCommerce store powered by [WooCommerce WPGlobus](https://wpglobus.com/product/woocommerce-wpglobus/).

== Changelog ==

= 3.0.2 =
* Fix: option panel broken by incorrect HTML sanitizing
* Fix: multilingual media JS missing
* "Plugin Check" security fixes.

= 3.0.1 =
* Fix: Early translations warning (admin-helpdesk).
* WP tested up to: 6.9

= 3.0.0 =
* Fix: PHP-8 warnings
* PHP support: 7.4+
* No longer supported: All-in-One SEO v3, older versions of Yoast SEO.
* See `changelog.txt` for the older entries.
