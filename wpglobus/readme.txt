=== WPGlobus - Multilingual WordPress ===
Contributors: tivnetinc, alexgff, tivnet
Tags: WPGlobus, localization, multilanguage, multilingual, translate
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: trunk
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
	* WPGlobus stores all translations using a special format: `{:en}English{:}{:fr}French{:}{:es}Spanish{:}`. If you decide to **deactivate and uninstall WPGlobus**, you **must run the clean-up tool** to keep only one language. See the details on the "Welcome" tab in the WPGlobus Settings.
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
* **Enable multilingual SEO features** of "Yoast SEO" plugin;
* **Switch the languages at the front-end** using: a drop-down menu extension and/or a customizable widget with various display options;
* **Switch the Administrator interface language** using a top bar selector;

The WPGlobus plugin serves as the **foundation** to other plugins in the family.

= When do I need WPGlobus Premium Add-ons? =

* To translate URLs (`/my-page/` translates to `/fr/ma-page`, `/es/mi-pagina` and so on);
* To "postpone" translation to all languages and publish only those that are ready;
* To have completely separate menus for each language;
* To translate WooCommerce products and taxonomies;
* To have separate "focus keywords" for each language in the Yoast SEO;
* ...and more.

For more details, please check out the descriptions of each paid add-on on our website:

* [WooCommerce WPGlobus](https://wpglobus.com/product/woocommerce-wpglobus/): adds multilingual capabilities to WooCommerce-based online stores.
* [WPGlobus Plus](https://wpglobus.com/product/wpglobus-plus/): adds URL fine-tuning, publishing status per translation, multilingual Yoast SEO analysis and more.
* [WPGlobus Language Widgets](https://wpglobus.com/product/wpglobus-language-widget/): Multilingual widget logic: show and hide widget depending on the current language.
* [WPGlobus Header Images](https://wpglobus.com/product/wpglobus-header-images/): Display different header images per language. Show images depending on the settings in the Customizer.
* [WPGlobus Menu Visibility](https://wpglobus.com/product/wpglobus-menu-visibility/): Show or hide menu items depending on the current language.
* [WPGlobus Mobile Menu](https://wpglobus.com/product/wpglobus-mobile-menu/): makes the WPGlobus language switcher menu compatible with mobile devices and narrow screens.
* [WPGlobus for the "Bridge" theme](https://wpglobus.com/product/wpglobus-for-bridge-theme/): create different sliders for each language when using theme "Bridge".
* [WPGlobus for Ultimate Member](https://wpglobus.com/product/wpglobus-for-ultimate-member/): enables multilingual for all forms of the Ultimate Member (login, registration, user profile, etc), the Member Directories pages, the admin settings that are visible on frontend.
* [WPGlobus Multilingual Popups](https://wpglobus.com/product/wpglobus-multilingual-popups/): enables multilingual support for the following plugins: Popup Maker – Popup for opt-ins, lead gen, & more; Popup Builder — Responsive WordPress Pop up.
* [WPGlobus Multilingual Notices](https://wpglobus.com/product/wpglobus-multilingual-notices/): adds the multilanguage capabilities to various WordPress GDPR/cookie notifications plugins.

= Compatibility with WordPress Themes =

* WPGlobus works correctly with all themes that apply proper filtering before outputting content.
* As most of the themes save their settings in the `options` table, you can use the [WPGlobus Translate Options](https://wordpress.org/plugins/wpglobus-translate-options/) plugin to process those settings correctly.
* Some themes incorporate 3rd party plugins (e.g., sliders, forms, composers) - not all of them are 100% multilingual-ready. When you see elements that cannot be translated, please **tell the theme/plugin authors**. We are ready to help them.
* Read more on the topic [here](https://wpglobus.com/documentation/wpglobus-compatibility-with-themes-and-plugins/).

= Compatibility with WordPress Plugins =

We have tested WPGlobus with many plugins. However, since plugins are frequently updated, some adjustments may be required after a new update. We will do our best to monitor and make the necessary changes on our end.

Some 3rd-party plugins are supported with our [premium add-ons](https://wpglobus.com/shop/):

* [TablePress](https://wpglobus.com/product/wpglobus-plus/#tablepress),
* [WooCommerce and some of its extensions](https://wpglobus.com/product/woocommerce-wpglobus/),

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
* [Before contacting Support...](https://wpglobus.com/before-contacting-wpglobus-support/)

= No automatic translation =

WPGlobus does NOT translate texts! You need to **translate texts manually**.

= After deactivating WPGlobus, all my pages look like garbage! =

What you see is a mix of the languages, which WPGlobus knows how to handle when it's active.
When you deactivate WPGlobus, your site is not multilingual anymore, and you have to remove all translations.

WPGlobus stores all translations using a special format: `{:en}English{:}{:fr}French{:}{:es}Spanish{:}`. If you decide to **deactivate WPGlobus**, you **must run the clean-up tool** to keep only one language. See the details on the "Uninstall" tab in the WPGlobus Settings.

= When I switch language, I am getting 404 on all pages =

Please go to the `Admin - Settings - Permalinks` page. Make sure that the `Common Settings` is not set to "Plain" and then press the `Save Changes` button. It should help.

= Is there a PRO version? =

We do not make a "PRO" plugin that replaces the free one. Instead, we have a set of add-ons that extend the WPGlobus functionality. Please found them on [our website](https://wpglobus.com).

**NOTE:** When you install an add-on, such as **WPGlobus Plus**, you must keep the WPGlobus plugin activated!

= From the WPGlobus.com FAQ Archives: =

* [Do you support browser version x.x?](https://wpglobus.com/faq/support-msie-opera-safari-chrome-firefox/)
* [Do you plan to support subdomains and URL query parameters?](https://wpglobus.com/faq/subdomains-and-url-query-parameters/)
* [I am using WPML, Polylang, Multilingual Press, etc. Can I switch to WPGlobus?](https://wpglobus.com/faq/i-am-using-wpml-qtranslate-polylang-multilingual-press-etc-can-i-switch-to-wpglobus/)
* [Do you support WooCommerce, EDD, other e-Commerce plugins?](https://wpglobus.com/faq/support-woocommerce-edd/)
* [Is it possible to set the user's language automatically based on IP and/or browser language?](https://wpglobus.com/faq/set-language-by-ip/)

== Screenshots ==

1. The Welcome screen.
2. Settings panel.
3. Languages setup.
4. Attaching language switcher to a menu.
5. Editing post in multiple languages.
6. Multilingual Yoast SEO and Featured Images.
7. Language Switcher widget and Multilingual Editor dialog.
8. Multilingual WooCommerce store powered by [WooCommerce WPGlobus](https://wpglobus.com/product/woocommerce-wpglobus/).

== Upgrade Notice ==

= 3.0.0 =

Version 3 includes many code changes related to PHP 8 support. We have also removed support for some outdated plugins. Please let us know if you encounter any issues.

== Changelog ==

= 3.0.0 =
* Fix: PHP-8 warnings
* PHP support: 7.4+
* No longer supported: All-in-One SEO v3, older versions of Yoast SEO.
* See `changelog.txt` for the older entries.
