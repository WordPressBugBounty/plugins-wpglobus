=== WPGlobus ===
Contributors: tivnetinc, tivnet
Tags: WPGlobus, localization, multilanguage, multilingual, translate
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 3.0.5
License: GPL-3.0-or-later
License URI: https://spdx.org/licenses/GPL-3.0-or-later.html

Multilingual/Globalization: URL-based multilanguage with an easy interface to enter your translations.

== Description ==

**WPGlobus** turns your WordPress site into a multilingual one. You choose the languages you want, and WPGlobus adds the fields where you enter your own translations - post and page titles, content, excerpts, categories, tags, and more.

= Free and Pro versions =

* Free: **WPGlobus** - this plugin. The essential multilingual tools, free from WordPress.org.
* Pro: **TIV Globus** - the successor to WPGlobus Plus and WooCommerce WPGlobus. The complete multilingual tools, sold on the WooCommerce.com marketplace.

= WPGlobus allows you to: =

* Enter translations for posts, pages, custom post types, categories, tags, menus, and widgets;
* Enter translations for the Site Title and Tagline;
* Enter multilingual SEO titles and meta descriptions - compatible with Yoast SEO (free version);
* Add one or several languages using custom combinations of country flags, locales, and language names;
* Give each language its own URL (`example.com/`, `example.com/es/`, `example.com/fr/`, and so on);
* Switch languages on the front-end with a drop-down menu extension and/or a customizable widget with various display options;
* Switch the admin interface language from the top bar.

https://www.youtube.com/watch?v=zoTWY9JrXLs

= What is in the Pro version (TIV Globus)? =

Everything in the Free version, plus the ability to:

* Give each page a full translated address, including "slug" (`/my-page/` becomes `/fr/ma-page/`, `/es/mi-pagina/`, and so on);
* Enter translations for WooCommerce products, categories, and attributes;
* Use popular page builders (Elementor, Avada, and more) in every language;
* ...and much more.

https://www.youtube.com/watch?v=s37KofMsKBw

[Get TIV Globus](https://wpglobus.com/tiv-globus/) - the Pro version of WPGlobus. It replaces WPGlobus without losing your settings or translations, and works with or without WooCommerce.

= A free upgrade for our customers =

Already own **WPGlobus Plus** or **WooCommerce WPGlobus**? You can move to TIV Globus at no extra cost. This is a limited-time offer for existing customers.

[Claim your free upgrade](https://wpglobus.com/free-tiv-globus/)

= Important Notes: please read first! =

These notes apply to both **WPGlobus** (free) and **TIV Globus** (Pro).

* NO AUTOMATIC TRANSLATION:
	* The plugin does NOT translate texts automatically! You **translate texts manually**.
* IF YOU UNINSTALL, YOU MUST REMOVE THE EXTRA LANGUAGES:
	* All translations are stored together in a special format: `{:en}English{:}{:fr}French{:}{:es}Spanish{:}`. If the plugin is not active, every language shows at once and your site becomes unreadable. Before you **deactivate and uninstall**, you **must run the cleanup tool** to remove all languages but one. See the details in the plugin's settings.
* PAGE BUILDERS / COMPOSERS:
	* Page-builder support is available only in **TIV Globus** (Pro).
* COOKIES:
	* The plugin uses a browser cookie to remember your language preference. It stores only the language code (such as `en` or `es`) - no personal information.
* MULTISITE:
	* The **multisite** mode (multiple virtual sites sharing a single WordPress installation) is **not tested and not officially supported**. It may still work, but use it at your own discretion.

= Compatibility with themes and plugins =

WPGlobus works with any theme or plugin that passes its text through the standard WordPress filters before displaying it - which covers the large majority of them.

Some add-ons that render their own output (sliders, forms, page composers, and the like) are not always multilingual-ready. When you find an element that will not translate, please **let its author know** - the fix is usually small.

We test against many popular themes and plugins and follow their updates as closely as we can. Since third-party code changes frequently, an occasional tweak on our side may be needed after an update.

= Permalinks =

WPGlobus needs "pretty" permalinks. Go to `Settings -> Permalinks` and pick any structure other than "Plain" (with no `index.php` in it) - URLs like `example.com/?p=123` will not work.

**WooCommerce:** set your store bases explicitly (for example, Shop Base `/product/`). If you leave them blank, WooCommerce may translate the base itself (e.g. `/produkt/` for German), causing 404 errors.

== Installation ==

You can install this plugin directly from your WordPress dashboard:

1. Go to the *Plugins* menu and click *Add New*.
1. Search for *WPGlobus*.
1. Click *Install Now* next to the WPGlobus plugin.
1. Activate the plugin.

Alternatively, see the guide to [Manual Plugin Installation](https://wordpress.org/documentation/article/manage-plugins/#manual-plugin-installation-1).

== Frequently Asked Questions ==

= No automatic translation =

WPGlobus does NOT translate texts! You need to **translate texts manually**.

= After deactivating WPGlobus, all my pages look like garbage! =

What you see is a mix of the languages, which WPGlobus knows how to handle when it's active.
When you deactivate WPGlobus, your site is not multilingual anymore, and you have to remove all translations.

WPGlobus stores all translations using a special format: `{:en}English{:}{:fr}French{:}{:es}Spanish{:}`. If you decide to **deactivate WPGlobus**, you **must run the cleanup tool** to keep only one language. See the details on the "Uninstall" tab in the WPGlobus Settings.

= When I switch language, I am getting 404 on all pages =

Please go to the `Admin - Settings - Permalinks` page. Make sure that the `Common Settings` is not set to "Plain" and then press the `Save Changes` button. It should help.

= What is the difference between WPGlobus and TIV Globus? =

**WPGlobus** (this plugin) is the free version, with the essential multilingual tools. **TIV Globus** is the Pro version: it adds translated URLs, WooCommerce, page-builder support, and more. See "Free and Pro versions" near the top for the full comparison, or visit the [TIV Globus page](https://wpglobus.com/tiv-globus/).

= I own WPGlobus Plus or WooCommerce WPGlobus. Are they still supported? =

Those add-ons have been replaced by **TIV Globus**, which brings their features together in one product. As an existing customer, you can move to TIV Globus at no extra cost - see [the free upgrade](https://wpglobus.com/free-tiv-globus/).

= Will my translations and settings carry over to TIV Globus? =

Yes. TIV Globus reads the same storage format as WPGlobus, so your existing translations and language settings are kept. Your content stays compatible with WPGlobus, so you can switch back later if you ever need to.

= Does TIV Globus require WooCommerce? =

No. TIV Globus is sold on the WooCommerce.com marketplace, but it works on any site - with or without WooCommerce.

== Changelog ==

= 3.0.5 =
* Tested up to: 7.0.4

= 3.0.4 =
* Fix: Block editor (Gutenberg) - saving a page or post while editing only the content no longer clears the title. This corrects a data-loss regression introduced in 3.0.3.
* Fix: Block editor (Gutenberg) - editing only the title no longer clears the content.
* Fix: On sites whose address includes a port number (for example a staging server on `host:8080`), the language switcher links and hreflang tags now build the correct per-language URLs instead of all pointing to the home page.

= 3.0.3 =
* Added: TIV Globus (Pro) - the successor to WPGlobus Plus and WooCommerce WPGlobus. In-plugin recommendations and the plugin description now point to it.
* Fix: PHP warnings ("Undefined array key") in the admin bar language switcher when the stored language settings were incomplete.
* Fix: Block editor (Gutenberg) - resolved issues that could occur under certain conditions when translating posts and pages (language switcher and title/content handling on save and reopen).
* Removed: Automatic redirect to the About page after activation.
* WP tested up to: 7.0.2

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
