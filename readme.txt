=== QuickStart ===
Contributors: dougwollison
Tags: development, function, utility, utilities, framework, code, coding
Requires at least: 3.5
Tested up to: 3.8.x
Stable tag: 1.3.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A utility kit for quick development of WordPress themes (and plugins).

== Description ==

QuickStart is an ever expanding utility kit of handy functions, callbacks and tools for setting up various features of your theme, namely the backend stuff like custom post types, meta boxes, style/script registration, tinyMCE features, and much more.

== Installation ==

1. Upload the contents of `quickstart.tar.gz` to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. In your theme's functions.php folder, call QuickStart(), passing a configuration array, and an optional defaults array.

== Changelog ==

**Details on each release can be found [on the GitHub releases page](https://github.com/dougwollison/quickstart/releases) for this project.**

= 1.3.3 =
Added addfile field type, minor bug fixes with submenus and metaboxes.

= 1.3.2 =
Fixed bug with sidebars not being registered.

= 1.3.1 =
Updated post_type_count, dropped taxonomy_count.

= 1.3.0 =
Revisions to custom page/settings handling, field building, and metabox building. Added disable_quickedit.

= 1.2.0 =
New php/js tools, metabox saving and plugin registration fixes.

= 1.1.4 =
Fixed issue with custom page registration.

= 1.1.3 =
Fixed issue with wrap_with_label setting, also cleaned up QuickStart\Form.

= 1.1.2 =
Fixed preloading when passing a numeric array of terms.

= 1.1.1 =
Fixed save_meta_box issue when saving field data.

= 1.1.0 =
Bug fixes, key changes, documented example code.

= 1.0.0 =
Initial public release.
