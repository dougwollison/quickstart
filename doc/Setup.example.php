<?php
/**
 * A breakdown of the numerous options accessible through the Setup class.
 *
 * *	A list can be a numeric array, or a string of comma/space separated values.
 * **	A field can be an array of configuration data, or a string for a simple text input
 *		with the passed name.
 *
 *		A field can be passed in an array of fields once of two ways:
 *
 *		'simple_text_field', // Passed numerically, or...
 *		'complex_custom_field' => {
 *			// Passed associatively, with options...
 *
 *			@type string $text The field type to use.
 *				Accepted types:
 *					textarea,
 *					select,
 *					checkbox,
 *					checklist, (a list of checkboxes)
 *					radiolist, (a list of radio buttons)
 *					setimage, (a special QuickStart form input)
 *					editgallery, (a special QuickStart form input)
 *					[anything] (any input[type] value; e.g. text, password)
 *				(defaults to 'text')
 *
 *			@type string $name The name attribute of the field
 *				(defaults to the field key)
 *
 *			@type string $id The id attribute of the field
 *				(defaults to the field key, processed into a valid id)
 *
 *			@type string $label The label to tie to the field
 *				(defaults to the field key, processed into legible form)
 *
 *			@type string $data_name The name of the meta_key or option_name to fetch.
 *				(defaults to the field key)
 *
 *			@type bool $print_label Wether or not to print a label for the field.
 *				(defaults to TRUE)
 *
 *			@type string $value The value to compare with.
 *				(used only for checkboxes, defaults to 1)
 *
 *			@type string|array|callable $values The values to compare with.
 *				Can be a comma/space separated string, a numeric array of values/labels,
 *				an associative array of value => label, or a callback that returns one.
 *				(used only in check/radiolist and select)
 *
 *			@type mixed $attributes... Any HTML attributes to apply to the input
 *				or parent in the case of a check/radiolist. These can be passed
 *				numerically for valueless ones like mutliple or readonly, or as an
 *				array (like for class) to be imploded into space separated values.
 *				(Allowed attributes: accesskey, autocomplete, checked', class, cols,
 *				disabled, id, max, maxlength, min, multiple, name, placeholder,
 *				readonly, required, rows, size, style, tabindex, title, type, value,
 *				and anything prefixed with data-)
 *		}
 */

// Call the Setup class to setup the theme.
QuickStart( array(
	// The theme configuration options

	/**
	 * A list* of things to hide.
	 *
	 * Options: comments, posts, pages, links, wp_head
	 *
	 * @type string|array
	 */
	'hide' => 'posts, comments, wp_head',

	/**
	 * A list* of supports to register.
	 *
	 * @type string|array
	 */
	'supports' => array(
		'post-thumbnails',
		'post-formats' => array( 'aside', 'gallery' ),
	),

	/**
	 * An array of image sizes to regiser,
	 * in $name => $specs format.
	 *
	 * @type array
	 */
	'image_sizes' => array(
		/**
		 * The options for the image size.
		 *
		 * Width, Height, Crop?
		 *
		 * @type array
		 */
		'size1' => array( 500, 500 ),
		'size2' => array( 700, 350, true ),
	),

	/**
	 * A single URL or array of URLs for the editor stylesheet(s)
	 *
	 * @type string|array
	 */
	'editor_style' => 'css/editor.css',

	/**
	 * A list of menus to register,
	 * in $location => $description format.
	 *
	 * @type array
	 */
	'nav_menus' => array(
		'header' => __( 'Header Navigation' ),
	),

	/**
	 * A list of sidebars to register,
	 * in $args['id'] => $args format.
	 *
	 * @type array
	 */
	'sidebars' => array(
		'footer' => array(
			'name' => __( 'Footer Widgets', 'theme_text_domain' ),

			// before_(widget|title) can be passed without specifying the
			// custom after version; it will auto create them appropriately

			'before_widget' => '<article id="%1$s" class="widget %2$s">',
			// 'after_widget' => '</article>' will be auto generated
			'before_title' => '<h3 class="widget-title">',
			// 'after_title' => '</h3>' will be auto generated
		),
	),

	/**
	 * A list of shortcodes to register,
	 * in $tag => $callback format.
	 *
	 * Can also pass a shortcode tag numerically,
	 * and it will use the Tools::simple_shortcode().
	 *
	 * @type array
	 */
	'shortcodes' => array(
		'simple',

		/**
		 * A callback for add_shortcode()
		 *
		 * @type callable
		 */
		'complex' => function( $attrs, $content ){
			// do stuff...
		},
	),

	/**
	 * What to relable Posts to in the admin.
	 *
	 * Pass either a singular form to auto-pluralize,
	 * or an array with Singular, Plural, Menu Name (optional) values.
	 *
	 * @type string|array
	 */
	'relabel_posts' => array( 'Article', 'Articles', 'News' ),

	/**
	 * A list* of built in QuickStart helpers to load.
	 *
	 * @type string|array
	 */
	'helpers' => 'media_manager, post_chunks',

	/**
	 * The frontend and/or backend scripts/styles to enqueue.
	 *
	 * @type array {
	 *		@type array $frontend The frontend scripts/styles to enqueue.
	 *		@type array $backend The backend scripts/styles to enqueue.
	 * }
	 */
	'enqueue' => array(
		/**
		 * @type array {
		 *		@type array $css The styles to enqueue.
		 *		@type array $js The scripts to enqueue.
		 * }
		 */
		'backend' => array(
			'js' => array(
				/**
				 * A script to enqueue.
				 *
				 * @type string|array A source url, or an array (numeric|associative)
				 *		of the $src, $deps, $ver, $in_footer arguments.
				 */
				'google-maps' => 'https://maps.googleapis.com/maps/api/js?sensor=true',
				'mytheme' => array(
					'src' => THEME_URL . '/js/admin.js',
					'deps' => array( 'google-maps' ),
				),
			),
		),
	),

	/**
	 * Any buttons, plugins or styles to register for tinyMCE.
	 *
	 * @type array
	 */
	'tinymce' => array(
		/**
		 * A list* of buttons to add.
		 *
		 * Bust be previously registered to work,
		 * so it's namely for built-in yet hidden
		 * buttons.
		 *
		 * @type string|array
		 */
		'buttons' => 'hr',

		/**
		 * The list of plugins to register,
		 * in $plugin => array($src, $button, $row) format.
		 *
		 * @type array
		 */
		'plugins' => array(
			/**
			 * A plugin to register.
			 *
			 * @type array {
			 *		@type string      $src    The url to the script for the plugin.
			 * 		@type string|bool $button The ID of the button to add (true to me the plugin slug).
			 * 		@type int         $row    The row number to add the button to (1, 2 or 3. Default 1).
			 * }
			 */
			'myplugin' => array(
				'src' => THEME_URL . '/js/tinymce.myplugin.js',
				'button' => 'mybutton',
				'row' => 3,
			),
		),

		'styles' => array(
			/**
			 * A custom style format object.
			 *
			 * @type array {
			 *		@type string       $title    The title in the dropdown.
			 *		@type string       $selector The css selector to target.
			 *		@type string       $inline   Optional The inline tag to wrap it in.
			 *		@type string       $block    Optional The block tag to wrap it in.
			 *		@type string       $classes  Optional The class attribute content.
			 *		@type string|array $styles   Optional The style attribute content.
			 * }
			 */
			array(
				'title' => 'Button',
				'selector' => 'a',
				'classes' => 'btn',
			),
		),
	),

	/**
	 * A list of post types to register,
	 * in $slug => $args format.
	 *
	 * @type array
	 */
	'post_types' => array(
		'simple' // Creates a public, archive enabled post type.

		/**
		 * A post type to register.
		 *
		 * @type array {
		 *		@type array $labels An array of labels.
		 *			(see WordPress' register_post_type())
		 *
		 *		@type string $singular The singular form of the post type.
		 *			(defaults to legible form of the post type key)
		 *
		 *		@type string $plural The plural form the post type name.
		 *			(defaults to the auto-pluralized form of $singular)
		 *
		 *		@type string $menu_name The menu name for the post type in the admin.
		 *			(defaults to $plural)
		 *
		 *		@type array $taxonomies The existing or custom taxonomies to link with this post type.
		 *			(see $taxonomies as detailed further down; can be defined here)
		 *
		 *		@type array $meta_boxes The metaboxes to add to this post type.
		 *			(see $meta_boxes as detailed further down; can be defined here)
		 *
		 *		@type mixed $arguments... The standard arguments for register_post_type()
		 * }
		 */
		'complex' => array(
			'menu_name' => 'Stuff',
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
			'hierarchical' => true,
		),
	),

	/**
	 * A list of taxonomies to register,
	 * in $slug => $args format.
	 *
	 * @type array
	 */
	'taxonomies' => array(
		'simple', // Creates a hierarchical, admin-column enabled taxonomy.

		/**
		 * A taxonomy to register.
		 *
		 * @type array {
		 *		@type array $labels An array of labels.
		 *			(see WordPress' register_post_type())
		 *
		 *		@type string $singular The singular form of the post type.
		 *			(defaults to legible form of the post type key)
		 *
		 *		@type string $plural The plural form the post type name.
		 *			(defaults to the auto-pluralized form of $singular)
		 *
		 *		@type string $menu_name The menu name for the post type in the admin.
		 *			(defaults to $plural)
		 *
		 *		@type array $post_type The existing or custom post_types to link with this taxonomy.
		 *			(slugs only, no defining them here)
		 *
		 *		@type string|array $preload A list* of terms to automatically add under this taxonomy once created.
		 *			(will of course only add terms that don't already exist)
		 *
		 *		@type mixed $arguments... The standard arguments for register_post_type()
		 * }
		 */
		'complex' => array(
			'rewrite' => array(
				'slug' => 'stuff',
				'with_front' => false,
			),

			/**
			 * A list of terms to preload once the taxonomy is registered,
			 * should be in $name or $name => $args format.
			 *
			 * @type array
			 */
			'preload' => array(
				'Thing 1',
				'Thing 2',
				'Thing 3',
			),
		),
	),

	/**
	 * A list of meta boxes to register,
	 * in $id => $args format.
	 *
	 * @type array
	 */
	'meta_boxes' => array(
		'simple', // Creates a metabox with a label-less text input.

		/**
		 * A meta box to register.
		 *
		 * @type array {
		 *		// Standard arguments from WordPress add_meta_box()
		 *
		 *		@type string $title The title of the metabox.
		 *		@type string $context The part of the screen it should be shown (normal, advanced or side)
		 *		@type string $priority The priority within the context (hight, core, default or low)
		 *		@type string|array $post_type The list* of post types to assign this metabox to.
		 *
		 *		// Custom arguments for use in QuickStart
		 *
		 *		@type array|callable $field A single field** to use, or a callback to print it out.
		 *			(the metabox $id is used for the field $key)
		 *
		 *		@type array|callable $fields A list of fields** to use, or a callback to print them out.
		 *
		 *		@type callable $save The callback to use when saving the metabox data.
		 *			(by default will use Setup::save_meta_box())
		 *
		 *		@type string|array $save_fields A list* of fields to expect from $_POST and save.
		 *			(either in $meta_key => $field format, or just $field and it will be used for both).
		 * }
		 */
		'complex' => array(
			'title' => 'More Stuff',
			'context' => 'side',
			'post_type' => 'page, my_post_type',
			'fields' => 'my_complex_metabox',
		),
	),

	/**
	 * A list* of QuickStart features to enable,
	 * in $feautre => $options format.
	 *
	 * @type array
	 */
	'features' => array(
		/**
		 * A feature to enable.
		 *
		 * Options depends on feature.
		 *
		 * @type array
		 */
		'order_manager' => array(
			'post_type' => 'page, my_post_type',
		),
	),
	
	/**
	 * A list of settings to register,
	 * in $page => $settings format.
	 *
	 * Any settings are registered to the "default"
	 * section of the $page.
	 *
	 * @type array.
	 */
	'settings' => array(
		/**
		 * A list of settings to regsiter for a page,
		 * in $option_name => $args format.
		 *
		 * @type array
		 */
		'general' => array(
			'simple', // Adds a plain text input to the settings table.
			
			/**
			 * A setting to register.
			 *
			 * @type array {
			 *		@type string $title The title/label for the setting.
			 *			(defaults the the setting name in legible form)
			 *
			 *		@type callable $sanitize The sanitize callback to run the data through.
			 *			(defaults to none).
			 *
			 *		@type array|callable $field A single field** to use, or a callback to print it out.
			 *			(the $option_name is used for the field $key)
			 *
			 *		@type array|callable $fields A list of fields** to use, or a callback to print them out.
			 * }
			 */
			'complex' => array(
				'title' => 'Extra Info',
				'field' => array(
					'type' => 'textarea'
				),
			),
		),
	),
	
	/**
	 * A list of admin pages to register,
	 * in $slug => $args format.
	 *
	 * @type array
	 */
	'pages' => array(
		/**
		 * An admin page to register.
		 *
		 * @type array {
		 *		@type string $title The page/menu title.
		 *			(defaults to the page $slug)
		 *
		 *		@type string $menu_title The title as it appears in the menu.
		 *			(defaults to $title)
		 *
		 *		@type string $page_title The title as it appears on the page.
		 *			(defaults to $menu_title)
		 *
		 *		@type string $capability The capability required for this menu to be displayed to the user.
		 *			(defaults to 'manage_options')
		 *
		 *		@type callable $callback The function to be called to output the content for this page.
		 *			(defaults to QuickStart\Callbacks::default_admin_page())
		 *
		 *		@type array $fields A list of settings fields** to register for this page.
		 *
		 *		@type string $parent The slug name for the parent menu.
		 *			(only applicable if creating a submenu page)
		 *
		 *		@type array $children The child pages to register.
		 *			(only applicable if creating a top level menu, same structure applies)
		 * }
		 */
		'mytheme' => array(
			'title' => 'My Theme Options',
			'parent' => 'theme',
			'capability' => 'edit_theme_options',
			'callback' => 'mytheme_options_page',
			'fields' => array(
				'mytheme-hero-images',
			),
		),
	),
), array(
	// The custom defaults
	
	/**
	 * A list of default arguments for the sidebars.
	 *
	 * See 'sidebars' entry of theme configurations above.
	 *
	 * @type array
	 */
	'sidebar' => array(
		'after_widget' => '</div><hr>',
	),
	
	/**
	 * A list of default arguments for the post_types.
	 *
	 * See 'post_types' entry of theme configurations above.
	 *
	 * @type array
	 */
	'post_type' => array(
		'show_ui' => false	
	),
	
	/**
	 * A list of default arguments for the taxonomies.
	 *
	 * See 'taxonomies' entry of theme configurations above.
	 *
	 * @type array
	 */
	'taxonomy' => array(
		'hierarchical' => false	
	),
	
	/**
	 * A list of default arguments for the meta boxes.
	 *
	 * See 'meta_boxes' entry of theme configurations above.
	 *
	 * @type array
	 */
	'meta_box' => array(
		'priority' => 'low'	
	),
) );