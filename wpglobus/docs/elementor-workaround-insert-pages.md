# WPGlobus + Elementor: Workaround Using Insert Pages Plugin

## Problem

Elementor does not work well with WPGlobus because Elementor uses its own rendering
pipeline and content storage, which conflicts with WPGlobus language delimiters.

## Solution

Use the [Insert Pages](https://wordpress.org/plugins/insert-pages/) plugin
to create separate single-language pages and combine them into one.

## Steps

1. Install and activate the **Insert Pages** plugin: https://wordpress.org/plugins/insert-pages/
2. Create a page with the **EN** content (using Elementor as needed).
3. Create a page with the **VI** content (using Elementor as needed).
4. Create a **new combined page**.
5. Use the Insert Pages plugin to insert the **EN** page.
6. Switch to the **VI** version of the combined page
   (use the WPGlobus switcher in Gutenberg or the language tab in Classic Editor
   -- **do not use Elementor** for the combined page).
7. Insert the **VI** page.
8. Save the combined page and test -- you should see it working.
9. The separate EN and VI pages -- make sure they are **not visible** in the
   catalog/search. Set their visibility to **"Private"**.