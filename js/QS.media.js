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
			if ( undefined === frame )
				frame = this.frame;

			var attachments = [];

			var collection;

			if ( frame.options.state == 'gallery-edit' ) {
				collection = frame.states.get( 'gallery-edit' ).get( 'library' );
			} else {
				collection = frame.state().get( 'selection' );
			}

			collection.map(function( item, i, items ) {
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
				for ( var e in options.events ) {
					//Bind the callback to the event, passing QS.media as the context
					//from that they'll be able to access the frame and trigger element
					frame.on( e, options.events[ e ], media );
				}
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
					media.trigger = $(this);

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
				var selection = frame.state().get('selection');
				var attachment;

				selection.reset( [] );

				if ( 'string' == typeof ids ) {
					ids = ids.split( ',' );
				}

				var id;
				for ( var i in ids ) {
					id = ids[ i ];

					attachment = wp.media.attachment( id );
					attachment.fetch();

					selection.add( attachment ? [ attachment ] : [] );
				}
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
				if ( typeof options.gallery != 'string' ) {
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
					selection.props.set( { query: false });
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

	jQuery.fn.QS = function( plugin, options ) {
		return $( this ).QS[ plugin ].call( this, options );
	};

	jQuery.fn.QS.addFile = function( options ) {
		return $( this ).each(function() {
			var $this = $( this );
			var thisOptions;

			// Determine multiple mode and media type
			var multi = $this.hasClass('multiple');
			var type  = $this.data('type');

			// Title and choose button text
			var title = 'Select ' + ucwords( type );
			var choose = 'Use Selected ' + ucwords( type );

			// Add "file" to text bits for non images
			if ( type != 'image' ) {
				title += ' File';
				choose += ' File';
			}

			// Pluralize text bits if needed
			if ( multi ) {
				title += 's';
				choose += 's';
			}

			var defaults = {
				multiple:   multi,
				$trigger:   '.qs-button', // will convert to jQuery object
				$container: '.qs-container', // will convert to jQuery object
				$template:  '.qs-template', // will convert to jQuery object
				preview:    '.qs-preview',
				input:      '.qs-input',
				title:      title,
				choose:     choose,
				media: 		type,
				events:     {
					select: function() {
						// Get the selected files
						var attachments = media.attachments();

						// Loop vars
						var attachment, item, preview, input;

						// Empty the container if not in multiple mode
						if ( ! multi ) {
							thisOptions.$container.empty();
						}

						// Loop through all attachments found
						for ( var i in attachments ) {
							// Get the current attachment
							attachment = attachments[ i ];

							// Make a copty of the template item
							item = thisOptions.$template.clone();

							// Get the preview and input elements
							preview = item.find( thisOptions.preview );
							input = item.find( thisOptions.input );

							// Update preview accordingly
							if ( preview.is( 'img' ) && attachment.type == 'image' ) {
								// Preview is an image, update the source
								preview.attr( 'src', attachment.sizes.thumbnail.url );
							} else {
								// Preview should be a span, update the content
								preview.html( attachment.url.replace( /.+?([^\/]+)$/, '$1' ) );
							}

							// Store the ID in the input field
							input.val( attachment.id );

							// Add the item to the container
							thisOptions.$container.append( item );

							// No multiple = stop after the first one
							if ( ! multi ) break;
						}
					}
				}
			};

			// Combine the options with the data args
			$.extend( options, $this.data() );

			// Process the options with the defaults
			thisOptions = setupOptions( $this, options, defaults );

			// Create the template object
			thisOptions.$template = $( thisOptions.$template.html() );

			// Setup the media selector hook
			media.insert( thisOptions );

			// Setup sortability if multiple
			if ( multi ) {
				thisOptions.$container.sortable( {
					items: '.qs-item',
					axis: type == 'image' ? false : 'y', // If images, allow sideways sorting
				} );
			}
		});
	};

	jQuery.fn.QS.setImage = function( options ) {
		// This just aliases to addFile,
		// but ensures it's in image-only mode.
		$( this ).data( 'media', 'image' );
		return $( this ).QS( 'addFile', options );
	};

	jQuery.fn.QS.editGallery = function( options ) {
		return $( this ).each(function() {
			var $this = $(this);
			var thisOptions;
			var defaults = {
				media:    'image',
				$input:   '.qs-value', // will convert to jQuery object
				$preview: '.qs-preview', // will convert to jQuery object
				$trigger: '.qs-button', // will convert to jQuery object
				title:    $this.text(),
				events:   {
					update: function() {
						var attachments = media.attachments();
						var items = [];
						var img;

						thisOptions.$preview.empty();

						for ( var i in attachments ) {
							items.push( attachments[ i ].id );
							img = $( '<img src="' + attachments[ i ].sizes.thumbnail.url + '">' );
							thisOptions.$preview.append( img );
						}

						thisOptions.$input.val( items.join( ',' ) );
					}
				}
			};

			// Process the options with the defaults
			thisOptions = setupOptions( $this, options, defaults );

			// Preload with the current images
			thisOptions.gallery = thisOptions.$input.val();

			//Setup the media selector hook
			media.gallery( thisOptions );
		});
	};

	// Clean up. Prevents mobile browsers caching
	$( window ).on( 'unload', function() {
		window.QS = null;
	});

	// Auto register hooks for setImage and editGallery
	$(function() {
		$( '.qs-addfile' ).QS( 'addFile' );
		$( '.qs-setimage' ).QS( 'setImage' );
		$( '.qs-editgallery' ).QS( 'editGallery' );
	});

})( jQuery );