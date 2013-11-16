QuickStart
==========

\* *cheesy salesman voice* \*

Are you a WordPress theme developer? Are you tired of writing/pasting so much code to setup your custom post types, taxonomies, and other system doo-hickeys? Well then, you should try QuickStart! The faster way to get the technicaly bits of your theme setup with less coding and carpal tunnel!

\* *end cheesy salesman voice* \*

- - -

## Overview

QuickStart is an ever expanding utility kit of handy functions, callbacks and tools for setting up various features of your theme, namely the backend stuff like custom post types, meta boxes, style/script registration, tinyMCE features, and much more.

These feautres are accessible through the following classes:
- `Setup`, for, well, setting up things like post types, taxonomies, meta boxes, theme supports/features, tinyMCE styles/buttons/plugins, settings, and admin pages.
- `Hooks`, for setting up simpler callbacks for things like frontend and backend enqueuing, taxonomy filter dropdowns, and more.
- `Tools`, various utility methods for both internal and external use, including style/script enqueuing, post/page/comment/links/wp_head hiding, faster shortcode registration, Post relabeling, file upload handling, among other things.
- `Form`, the field building kit for creating inputs and other form elements.
- `Template`, various (currently just 1) methods for quickly handling repetative elements of page template files.
- `Callbacks`, assorted (again, just 1 at the moment) callback methods for special uses.

## Setup Class

The Setup class is the main point of this plugin. To use it, call a new isntance of it (either directly or through the provided `QuickStart()` alias function) and pass the configuration array, along with an optional array of default values to be used for certain things.

Here's a full list of keys Setup looks for (in no particular order):
- `hide` => a list* of things you want hidden in the admin (i.e. posts, pages, comments, links, and/or wp_head garbage).
- `supports` => a list/array of theme supports you wish to add.
- `image_sizes` => an array of image sizes you want to add.
- `editor_style` => a url or list of urls for the stylesheets you want added to the editor.
- `menus` => an array of nav menus you wish to register.
- `sidebars` => a list of sidebars you wish to register.
- `shortcodes` => a list/array shortcodes you want to register.
- `relabel_posts` => a string (or array of singular/plural/menu_name forms) to relabel Posts to (e.g. Article(s)/News).
- `helpers` => a list/array of QuickStart helper files you wish to load (details further down).
- `enqueue` => an array of frontend and/or backend scripts and styles you wish to enqueue.
- `mce` => an array of buttons, plugins and/or styles you wish to add to tinyMCE.
- `post_types` => an array of post types you wish to register.
- `taxonomies` => an array of taxonomies you wish to register (can include as part of post_types entries).
- `meta_boxes` => an array of meta boxes you wish to add (can include as part of post_types entries).
- `features` => an array of built in QuickStart features you wish to use (can include some as part of post_types entries).
- `settings` => an array of custom settings fields you wish to register.
- `pages` => an array of admin pages you wish to add.

\* In most cases, simple lists can be passed as either an array, or a comma/space sparated string.

Full details can be found in the wiki, which is still being written.

## Helpers

Included in QuickStart are a number of helpers; files that can be included for accessing features and functions.

Below is a list of helpers currently available:
- `media_manager`: enques the QS.media javascript object; a collection of utilities and plugins for accessing the WordPress Media Manager (also enques the css for the special setimage and editgallery field types).
- `post_chunks`: adds a hook to split the post content into chunks at each `<!--more-->` tag, as well as functions for accessing and looping through said chunks (`get_chunk`, `the_chunk`, `have_chunks`).
- `post_field`: utility functions (`get_postfield` and `the_postfield`) for quickly fetching a posts table field for a post.
- `post_meta`: utility functions (`get_postmeta` and `the_postneta`) for fetching post metadata (aliases to `get_post_meta` but lets you pass the field first, then optionally the post ID, without needing to specify $single = true).
- `teaser`: utility functions (`get_teaser` and `the_teaser`) for creating a teaser of the post (similar to get_excerpt but with more control).

## Examples

Coming soon.