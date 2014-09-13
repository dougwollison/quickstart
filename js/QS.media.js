/* global _, wp, QS */
window.QS = window.QS || {};

(function( $ ) {
	var media = window.QS.media = {};

	/**
	 * =========================
	 * Private Utilities
	 * =========================
	 */

	/**
	 * Convert string to Title Case
	 *
	 * @since 1.4.0
	 *
	 * @param string str The string to convert.
	 */
	function ucwords( str ) {
		return ( str + '' )
	    .replace( /^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function( $1 ) {
	    	return $1.toUpperCase();
	    });
	}

	/**
	 * Runs selected queries and creates new entries for those elements
	 *
	 * @since 1.4.0
	 *
	 * @param jQuery $elm The base element to use.
	 * @param object opts The options object to edit.
	 * @param array  keys The specific keys to edit.
	 */
	function autoQuery( $elm, opts, keys ) {
		_.each( keys, function( e ) {
			var $e = '$' + e;
			if ( ! opts[ $e ] ) {
				opts[ $e ] = $elm.find( opts[ e ] );
			}
		});
	}

	/**
	 * =========================
	 * Public Utilities
	 * =========================
	 */

	_.extend( media, {
		/**
		 * Extract the selected attachment from the given frame.
		 *
		 * @since 1.0.0
		 *
		 * @param  wp.media frame The frame workflow.
		 *
		 * @return object The first attachment selected.
		 */
		attachment: function( frame ) {
			if ( undefined === frame ) {
				frame = this.frame;
			}

			var attachment = frame.state().get( 'selection' ).first();

			return attachment.attributes;
		},

		/**
		 * Extract the selected attachments from the given frame.
		 *
		 * @since 1.0.0
		 *
		 * @param wp.media frame The frame workflow.
		 *
		 * @return array An array of attachments.
		 */
		attachments: function( frame ) {
			if ( undefined === frame ) {
				frame = this.frame;
			}

			var attachments = [];

			var collection;

			if ( 'gallery-edit' === frame.options.state ) {
				collection = frame.states.get( 'gallery-edit' ).get( 'library' );
			} else {
				collection = frame.state().get( 'selection' );
			}

			collection.map(function( item ) {
				attachments.push( item.attributes );
			});

			return attachments;
		},


		/**
		 * Setup a new wp.media frame workflow, attach events, and set the trigger event.
		 *
		 * @since 1.5.0 Now can return frame instead of setting up trigger.
		 * @since 1.0.0
		 *
		 * @param object attributes The attributes for the frame workflow.
		 * @param object options    The options passed to the hook function.
		 * @param bool   notrigger  Skip the trigger click event setup, return frame.
		 */
		init: function( attributes, options, notrigger ) {
			var frame = wp.media(attributes), $trigger;

			//Run through each event and setup the handlers
			if ( options.events !== undefined ) {
				_.each( options.events, function( event, e ) {
					//Bind the callback to the event, passing QS.media as the context
					//from that they'll be able to access the frame and trigger element
					frame.on( e, event, media );
				});
			}

			// In case they need to hook into it, trigger "init" on the frame,
			// passing the frame itself as an additional parameter, since it
			// can't be linked into QS.media yet
			frame.trigger( 'init', frame );

			// If not setting up the trigger, just return the initialized frame.
			if ( notrigger ) {
				return frame;
			}

			// Assign $trigger based on which is present in options
			// If a special jQuery object is present, use that.
			// Otherwise, query using the provided selector.
			if ( options.$trigger ) {
				$trigger = options.$trigger;
			} else {
				$trigger = $( options.trigger );
			}

			// Create the click event for the trigger if present.
			if ( $trigger.length > 0 ) {
				$trigger.on( 'click', function( e ) {
					e.preventDefault();

					// Link the frame into QS.media
					media.frame = frame;
					// Link the trigger into QS.media
					media.trigger = $( this );

					frame.open();
				});
			}

			return frame;
		},

		/**
		 * Preload the media manager with provided attachment ids.
		 *
		 * @since 1.0.0
		 *
		 * @param string|array ids   A comma separated string or array of ids.
		 * @param wp.media     frame The frame workflow (defaults to current frame stored in QS.media).
		 */
		preload: function( ids, frame ) {
			if ( undefined === frame ) {
				frame = this.frame;
			}

			if ( ids !== undefined ) {
				var selection = frame.state().get( 'selection' );
				var attachment;

				selection.reset( [] );

				if ( typeof ids === 'string' ) {
					ids = ids.split( ',' );
				}

				var id;
				_.each( ids, function( id ) {
					attachment = wp.media.attachment( id );
					attachment.fetch();

					selection.add( attachment ? [ attachment ] : [] );
				} );
			}
		},
	});

	/**
	 * =========================
	 * Manager Hooks
	 * =========================
	 */

	_.extend( media, {
		/**
		 * Hook into the media manager frame for selecting and inserting an image.
		 *
		 * @since 1.5.0 Added notrigger argument, returns frame.
		 * @since 1.0.0
		 *
		 * @param object options    A list of options.
		 * @param bool   notrigger  Skip the trigger click event setup.
		 *
		 * @return Frame The new frame workflow.
		 */
		insert: function( options, notrigger ) {
			var defaults = {
				title:    'Insert Media',
				choose:   'Insert Selected Media',
				multiple: false,
				trigger:  '.qs-button'
			};

			options = _.extend( {}, defaults, options );

			return this.init( {
				title:    options.title,
				choose:   options.choose,
				multiple: options.multiple,
				library:  {
					type:  options.media
				},
				button:   {
					text:  options.choose,
					close: true
				}
			}, options, notrigger );
		},

		/**
		 * Hook into the media manager for editing galleries.
		 *
		 * @since 1.5.0 Added notrigger argument, returns frame.
		 * @since 1.0.0
		 *
		 * @param object options A list of options.
		 * @param bool   notrigger  Skip the trigger click event setup, return frame.
		 *
		 * @return Frame The new frame workflow.
		 */
		gallery: function( options, notrigger ) {
			var defaults = {
				title:   'Edit Gallery',
				trigger: '.qs-button'
			};

			options = _.extend( {}, defaults, options );

			var gallery, attachments, selection;

			if ( options.gallery !== undefined ) {
				// If gallery was not a comma separated string, make it one
				if ( typeof options.gallery !== 'string' ) {
					options.gallery = options.gallery.join( ',' );
				}

				// Generate and parse shortcode
				gallery = wp.shortcode.next( 'gallery', '[gallery ids="' + options.gallery + '"]' );
				gallery = gallery.shortcode;

				// Get the attachments from the gallery shortcode
				attachments = wp.media.gallery.attachments( gallery );
				selection = new wp.media.model.Selection( attachments.models, {
					props:    attachments.props.toJSON(),
					multiple: true
				});

				selection.gallery = attachments.gallery;

				// Fetch the query's attachments, and then break ties from the query to allow for sorting.
				selection.more().done(function() {
					selection.props.set({ query: false });
					selection.unmirror();
					selection.props.unset( 'orderby' );
				});
			}

			return this.init({
				state:     'gallery-edit',
				frame:     'post',
				title:     options.title,
				multiple:  true,
				editing:   true,
				selection: selection
			}, options, notrigger );
		}
	});

	/**
	 * =========================
	 * jQuery Plugins
	 * =========================
	 */

	/**
	 * Setup file adder functionality
	 *
	 * @since 1.6.0 Moved sortable handling, added thubmnail check, show option, and initonly mode
	 * @since 1.5.0 Overhauled for live-plugin purposes
	 * @since 1.2.0
	 *
	 * @param Event event The click event that triggered this.
	 * @param string mode  Pass 'initonly' to setup but not open the frame.
	 */
	QS.addFile = function( event, mode ) {
		var $elm = $( this );

		// If this is a button, update $elm to the parent qs-field
		if ( $elm.hasClass('qs-button') ) {
			$elm = $elm.parents('.qs-addfile');
		}

		// Load the stored addFile configurations
		var plugin = $elm.data('QS.addFile');

		// Extract the passed options
		var options = event.data;

		// Check if plugin data already exists, setup if not
		if ( ! plugin ) {
			// Check for multiple mode
			var is_multi = $elm.hasClass( 'multiple' );

			// Get media type
			var type = $elm.data( 'type' );

			// Get what display for the file
			var show = $elm.data( 'show' );

			// Title and choose button text
			var title = 'Select ' + ucwords( type );
			var choose = 'Use Selected ' + ucwords( type );

			// Add " file" to text bits for non images
			if ( type !== 'image' ) {
				title += ' File';
				choose += ' File';
			}

			// Pluralize text bits if needed
			if ( is_multi ) {
				title += 's';
				choose += 's';
			}

			var defaults = {
				// Component selectors
				trigger:    '.qs-button',
				container:  '.qs-container',
				template:   '.qs-template',
				preview:    '.qs-preview',
				input:      '.qs-value',

				// GUI Text
				title:      title,
				choose:     choose,

				// Functionality Options
				multiple:   is_multi,
				media: 		type,

				// Events
				events:     {
					select: function() {
						// Get the selected files
						var attachments = media.attachments();

						// Loop vars
						var attachment, item, preview, input;

						// Empty the container if not in multiple mode
						if ( ! is_multi ) {
							plugin.$container.empty();
						}

						// Loop through all attachments found
						_.each( attachments, function( attachment, i ) {
							// If not multiple and this isn't the first item, skip
							if ( ! is_multi && 0 === i ) {
								return;
							}

							// Make a copty of the template item
							item = plugin.$template.clone();

							console.log(i);

							// Get the preview and input elements
							preview = item.find( plugin.preview );
							input = item.find( plugin.input );

							// Update preview accordingly
							if ( preview.is( 'img' ) && 'image' === attachment.type ) {
								// Preview is an image, update the source
								// Preview is an image, update the source
								if ( null == attachment.sizes.thumbnail ) {
									preview.attr( 'src', attachment.sizes.thumbnail.url );
								} else {
									preview.attr( 'src', attachment.sizes.full.url );
								}
							} else {
								// Preview should be plain text of the title or filename, update the content
								if ( 'title' === show ) {
									preview.html( attachment.title );
								} else {
									preview.html( attachment.url.replace( /.+?([^\/]+)$/, '$1' ) );
								}
							}

							// Add data attributes for quick sort support
							item.data( 'name', attachment.filename.replace( /[^\w\-]+/g, '-' ).toLowerCase() );
							item.data( 'date', attachment.date.getTime() / 1000 );

							// Store the ID in the input field
							input.val( attachment.id );

							// Add the item to the container
							plugin.$container.append( item );
						});
					}
				}
			};

			// Get the data- attribute values that are allowed
			var attributes = _.pick( $elm.data(), 'title', 'choose', 'trigger', 'container', 'template', 'preview', 'input' );

			// Merge options with the matching data- attribute values
			plugin = _.extend( {}, defaults, options, attributes );

			// Query the trigger, container, and template elements if not present
			autoQuery( $elm, plugin, [ 'trigger', 'container', 'template' ] );

			// Create the template object
			plugin.$template = $( plugin.$template.html() );

			// Setup the insert hook and get the frame
			plugin.frame = media.insert( plugin, true );

			// Store the plugin options for later use
			$elm.data( 'QS.addFile', plugin );
		}

		// Set this frame as the current frame
		media.frame = plugin.frame;

		// End now if initializing only
		if ( 'initonly' === mode ) {
			return;
		}

		// Now, open the frame
		plugin.frame.open();
	};

	/**
	 * Setup gallery editor functionality
	 *
	 * @since 1.6.0 Preloading of gallery items, initonly mode, inline storable
	 * @since 1.5.0 Overhauled for live-plugin purposes
	 * @since 1.0.0
	 *
	 * @param Event event The click event that triggered this.
	 * @param string mode  Pass 'initonly' to setup but not open the frame.
	 */
	QS.editGallery = function( event, mode ) {
		var $elm = $( this );

		// If this is a button, update $elm to the parent qs-field
		if ( $elm.hasClass( 'qs-button' ) ) {
			$elm = $elm.parents('.qs-editgallery' );
		}

		// Load the stored editGallery configurations
		var plugin = $elm.data( 'QS.editGallery' );

		// Extract the passed options
		var options = event.data;

		// Check if plugin data already exists, setup if not
		if ( ! plugin || ! plugin.frame ) {
			var defaults = {
				// Component selectors
				trigger:    '.qs-button',
				preview:    '.qs-preview',
				input:      '.qs-value',

				// GUI Text
				title:      $elm.text(),

				// Events
				events:     {
					update: function() {
						// Get the attachments
						var attachments = media.attachments();

						// Loop vars
						var items = [], img;

						// Empty the preview box
						plugin.$preview.empty();

						// Go through each attachment
						_.each( attachments, function( attachment ) {
							// Add the id to the items list
							items.push( attachment.id );

							// Create a new image with the thumbnail URL
							img = $( '<img src="' + attachment.sizes.thumbnail.url + '">' );

							// Add the new image to the preview
							plugin.$preview.append( img );
						});

						// Update the input with the id list
						plugin.$input.val( items.join( ',' ) );
					}
				}
			};

			// Get the data- attribute values that are allowed
			var attributes = _.pick( $elm.data(), 'title', 'trigger', 'preview', 'input' );

			// Merge options with the matching data- attribute values
			plugin = _.extend( {}, defaults, options, attributes );

			// Query the trigger, container, and template elements if not present
			autoQuery( $elm, plugin, [ 'trigger', 'preview', 'input' ] );

			// If no gallery is defined, use the input's value
			if ( plugin.gallery === undefined ) {
				plugin.gallery = plugin.$input.val();
			}

			// Setup the insert hook and get the frame
			plugin.frame = media.gallery( plugin, true );

			// Store the plugin options for later use
			$elm.data( 'QS.editGallery', plugin );
		}

		// Set this frame as the current frame
		media.frame = plugin.frame;

		// End now if initializing only
		if ( 'initonly' === mode ) {
			return;
		}

		// Now, open the frame
		plugin.frame.open();
	};


	/**
	 * Setup image setter functionality
	 *
	 * @since 1.5.0 Modified to reflect live-plugin approach
	 * @since 1.4.0 Converted to addFile alias
	 * @since 1.0.0
	 *
	 * @param Event event The click event that triggered this.
	 */
	QS.setImage = function( event ) {
		var $elm = $( this );

		// Ensure the type is set to image
		$elm.data( 'type', 'image' );

		// Alias to the addFile method
		return QS.addFile.call( this, event );
	};

	/**
	 * Setup a QS plugin for an element
	 *
	 * @since 1.5.0 Overhauled for live-plugin purposes
	 * @since 1.0.0
	 *
	 * @param string selector Optional. The selector to delegate the click event to.
	 * @param string plugin   The name of the plugin to use.
	 * @param object options  Optional. The custom options to pass to the plugin.
	 */
	jQuery.fn.QS = function( /* [selector,] plugin [, options] */ ) {
		var $elm = $(this),
			selector, plugin, options;

		// Proceed based on number of arguments
		switch ( arguments.length ) {
			case 3: // ( selector, plugin, options )
				selector = arguments[0];
				plugin   = arguments[1];
				options  = arguments[2];
				break;
			case 2: // ( selector, plugin ) OR ( plugin, options )
				if ( typeof arguments[1] === 'string' ) {
					// ( selector, plugin )
					selector = arguments[0];
					plugin   = arguments[1];
				} else {
					// ( plugin, options )
					plugin   = arguments[1];
					options  = arguments[2];
				}
				break;
			case 1:
				// ( plugin )
				plugin = arguments[0];
				break;
			default:
				return;
		}

		var callback = QS[ plugin ];

		// Setup the (delegated) click event
		if ( selector ) {
			$elm.on( 'click', selector, options, callback );
		} else {
			$elm.on( 'click', options, callback );
		}

		// Immediately initialize existing elements
		$elm.find( selector ).trigger( 'click', [ 'initonly' ] );

		return $elm;
	};

	// Clean up. Prevents mobile browsers caching
	$( window ).on( 'unload', function() {
		window.QS = null;
	});

	// Auto register hooks for addFile, setImage and editGallery
	$(function() {
		$( 'body' ).QS( '.qs-addfile .qs-button', 'addFile' );
		$( 'body' ).QS( '.qs-editgallery .qs-button', 'editGallery' );
	});

})( jQuery );