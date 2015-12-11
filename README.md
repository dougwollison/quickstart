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
- `Tools`, various utilities used internally and available externally, some with self-hooking capabilities.
- `Form`, the field building kit for creating inputs and other form elements.
- `Template`, various (currently just 1) methods for quickly handling repetative elements of page template files.
- `Callbacks`, assorted (again, just 1 at the moment) callback methods for special uses.
- `Features`, setup and utility methods for special features offered by QuickStart.

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
- `attachment`: shortcuts for getting the URL or an attachment or post thumbnail.
- `family`: utility functions for testing relations between posts.
- `index`: utility functions used by and in conjuction with the `index_page` feature.
- `media_manager`: enques the QS.media javascript object; a collection of utilities and plugins for accessing the WordPress Media Manager (also enques the css for the special setimage and editgallery field types).
- `post_chunks`: adds a hook to split the post content into chunks at each `<!--more-->` tag, as well as functions for accessing and looping through said chunks (`get_chunk`, `the_chunk`, `have_chunks`).
- `post_field`: utility functions (`get_postfield` and `the_postfield`) for quickly fetching a posts table field for a post.
- `post_meta`: utility functions (`get_postmeta` and `the_postneta`) for fetching post metadata (aliases to `get_post_meta` but lets you pass the field first, then optionally the post ID, without needing to specify $single = true).
- `sections`: adds a section manager meta box to pages and/or other post types, allowing you to separate a page into independent components with their own title/content/meta.
- `teaser`: utility functions (`get_teaser` and `the_teaser`) for creating a teaser of the post (similar to get_excerpt but with more control).
- `term_meta`: adds meta data support for terms, along with ability to register meta fields on the term edit screen.
- `walkers`: offers a collection of custom walkers for navigation menus.
- `wpedit`: template functions (`get_wpedit_link` and `wpedit_link`) for adding buttons to parts of the template that link to relevant screens in the backend (e.g. Edit Menu appearing on a navigation menu).

## Bare Minimum Information

QuickStart is designed to take the bare minimum amount of information, and fill in the blanks for you.

For example, let's say you create a post_type called "project". Obviously, the labels will be pretty obvious; "Project" for singular and "Projects" for plural. QuickStart will automatically create these singular and plural forms if none are present. Included are utility functions that convert slugs into legible names (underscores become spaces and the whole string is titlecased), as well as into singular and plural form (or at least try to, basic plural forms tend to be generated perfectly).

Another example is fields and meta boxes. By default, all fields are plain text inputs with a label. If that's all you need, you can just pass the field name as the entry for it, rather than an array of options. Metaboxes are by default just a text input with no label (the title of the meta box serving that function).

In short, QuickStart does as much of the work as it can for you; you just point it in the right direction.

NOTE: QuickStart's defaults for post types and taxonomies vary slightly from WordPress';
- post types are by default public and have has_archive enabled.
- taxonomies are by default hierarchical and have show_admin_column enabled.

You can override these defaults by passing an array of new defaults to the Setup class when you call it.

## Examples

Coming soon.
