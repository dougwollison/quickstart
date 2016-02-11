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
	 * Convert string to Title Case.
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
	 * Runs selected queries and creates new entries for those elements.
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
		 * @param wp.media frame The frame workflow.
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
		 * @since 1.8.0 Added support for passing a single id.
		 * @since 1.0.0
		 *
		 * @param string|array|number ids   A comma separated string, array of ids, or single id.
		 * @param wp.media            frame The frame workflow (defaults to current frame stored in QS.media).
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
				} else if ( typeof ids !== 'object' ) {
					ids = [ ids ];
				}

				_.each( ids, function( id ) {
					attachment = wp.media.attachment( id );
					attachment.fetch();

					selection.add( attachment ? [ attachment ] : [] );
				} );
			}
		}
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
		 * @since 1.7.1 Fixed overly strict check for existing gallery value.
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

			if ( options.gallery ) {
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
	 * Setup media attachment functionality.
	 *
	 * Replaces addFile, editGallery, and setImage.
	 *
	 * @since 1.11.0 Added media-changed events and do_preload option.
	 * @since 1.10.0 Now uses value-(filled|empty) classes.
	 * @since 1.8.0
	 *
	 * @param Event  event The (click) event that triggered this.
	 * @param string mode  Pass 'initonly' to setup but not open the frame.
	 */
	QS.setupMedia = function( event, mode ) {
		var $elm = $( this ), $btn;

		// Cancel the default event trigger
		event.preventDefault();

		// If this is a button, update $elm to the parent qs-field
		if ( $elm.hasClass( 'qs-button' ) ) {
			$elm = $elm.parents( '.qs-field' ).eq( 0 );
			$btn = $( this );
		} else {
			$btn = $elm.find( '.qs-button' );
		}

		// Load the stored addFile configurations
		var plugin = $elm.data('QS.setupMedia');

		// Extract any passed options
		var options = event.data || {};

		// Check if plugin data already exists, setup if not
		if ( ! plugin ) {
			// Check for multiple mode
			var is_multi = $elm.hasClass( 'multiple' );

			// Check for gallery mode
			var is_gallery = $elm.hasClass( 'gallery' );

			// Check for preload option
			var do_preload = $elm.data('preload');
				// Default to true if not set
				if ( typeof do_preload === 'undefined' || do_preload === null || do_preload === '' ) {
					do_preload = true;
				}

			// Get display mode
			var show = $elm.data( 'show' );

			// Get media type
			var mimetype = $elm.data( 'type' );

			// Create the type's name for label purposes
			var typename = ucwords( mimetype ) || 'File';
			if ( typename.indexOf( '/' ) >= 0 ) {
				typename = typename.replace( /.+?\//, '' );
				typename = typename.toUpperCase();
				typename += ' File';
			}

			// Title and choose button text
			var title = 'Select ' + typename;
			var choose = 'Use Selected ' + typename;

			// Pluralize text bits if needed
			if ( is_multi ) {
				title += 's';
				choose += 's';
			}

			var defaults = {
				// Component selectors
				trigger:    '.qs-button',
				preview:    '.qs-preview',
				input:      '.qs-value',
			};

			// Extend defaults based on mode
			if ( is_gallery ) {
				_.extend( defaults, {
					// GUI Text
					title:      $btn.text(),

					// Events
					events:     {
						update: function() {
							// Get the attachments
							var attachments = media.attachments();

							// Loop vars
							var items = [];

							// Empty the preview box
							plugin.$preview.empty();

							// Go through each attachment
							_.each( attachments, function( attachment ) {
								// Add the id to the items list
								items.push( attachment.id );

								var src = '';
								// Use thumbnail or full size if unavailable
								if ( typeof attachment.sizes.thumbnail !== 'undefined' ) {
									src = attachment.sizes.thumbnail.url;
								} else {
									src = attachment.sizes.full.url;
								}

								// Create a new image with the thumbnail URL
								var $img = $( '<img src="' + src + '">' );

								// Add the new image to the preview
								plugin.$preview.append( $img );
							});

							// Update the input with the id list
							plugin.$input.val( items.join( ',' ) );

							// Ensure the empty class is removed and the filled class added
							plugin.$elm.removeClass( 'value-empty' ).addClass( 'value-filled' );

							// Trigger the media-changed event
							plugin.$elm.trigger( 'qs:media-changed' );
						}
					}
				});
			} else  {
				_.extend( defaults, {
					// Additional component selectors
					container:  '.qs-container',
					template:   '.qs-template',

					// GUI Text
					title:      title,
					choose:     choose,

					// Functionality Options
					multiple:   is_multi,
					media:		mimetype,

					// Events
					events:     {
						open: function() {
							if ( is_multi || ! do_preload ) {
								// Don't preload if it's for multiple items or disabled
								return;
							}

							// Get and preload the already selected item
							var value = plugin.$input.val();
							this.preload( value, this.frame );
						},
						select: function() {
							// Get the selected files
							var attachments = media.attachments();

							// Update preview if singular
							if ( ! is_multi ) {
								var attachment = attachments[0], img;
								img = document.createElement( 'img' );

								// Clear the preview
								plugin.$preview.empty();

								if ( attachment.type == 'image' ) {
									// Attachment is an image, set the img.src to medium size...
									if ( typeof attachment.sizes.medium !== 'undefined'  ) {
										img.src = attachment.sizes.medium.url;
									} else {
										// ...Or full size if no medium version is set
										img.src = attachment.sizes.full.url;
									}
								} else {
									// Attachment is some kind of file, set img.src to icon
									img.src = attachment.icon;
								}

								// Update the preivew, input value
								plugin.$preview.append( img );
								plugin.$input.val( attachment.id );

								// Ensure the empty class is removed and the filled class added
								plugin.$elm.removeClass( 'value-empty' ).addClass( 'value-filled' );
							} else {
								// Loop through attachments and add them to the preview
								var $item, $preview, $input;
								_.each( attachments, function( attachment ) {
									// Make a copty of the template item
									$item = plugin.$template.clone();

									// Get the preview and input elements
									$preview = $item.find( plugin.preview );
									$input = $item.find( plugin.input );

									// Update preview accordingly
									if ( $preview.is( 'img' ) && 'image' === attachment.type ) {
										// Preview is an image, update the source
										// Use thumbnail or full size if unavailable
										if ( typeof attachment.sizes.thumbnail !== 'undefined' ) {
											$preview.attr( 'src', attachment.sizes.thumbnail.url );
										} else {
											$preview.attr( 'src', attachment.sizes.full.url );
										}
									} else {
										// Preview should be plain text of the title or filename, update the content
										if ( 'title' === show ) {
											$preview.html( attachment.title );
										} else {
											$preview.html( decodeURI( attachment.filename ) );
										}
									}

									// Make sure the clear button is visible
									plugin.$container.find('.qs-clear').show();

									// Convert the date if only a timestamp
									if ( typeof attachment.date === 'number' ) {
										attachment.date = new Date( attachment.date );
									}

									// Add data attributes for quick sort support
									$item.data( 'name', attachment.filename.replace( /[^\w\-]+/g, '-' ).toLowerCase() );
									$item.data( 'date', attachment.date.getTime() / 1000 );

									// Store the ID in the input field
									$input.val( attachment.id );

									// Add the item to the container, ensure it has the filled and not empty class
									plugin.$container.append( $item ).removeClass( 'value-empty' ).addClass( 'value-filled' );

									// Trigger the media-added event
									$item.trigger( 'qs:media-added' );
								});
							}

							// Trigger the media-changed event
							plugin.$elm.trigger( 'qs:media-changed' );
						}
					}
				});
			}

			// Get the data- attribute values that are allowed
			var data = $elm.data() || {};
			var attributes = _.pick( data, 'title', 'choose', 'trigger', 'container', 'template', 'preview', 'input' );

			// Merge options with the matching data- attribute values
			plugin = _.extend( {}, defaults, options, attributes );

			// Query the necessary elments
			var elements = [ 'trigger' ];
			if ( is_multi ) {
				elements = elements.concat( 'container', 'template' );
			} else {
				elements = elements.concat( 'preview', 'input' );
			}
			autoQuery( $elm, plugin, elements );

			// Store the parent elment
			plugin.$elm = $elm;

			// If multiple mode, create the template object
			if ( is_multi && plugin.$template.length > 0 ) {
				plugin.$template = $( plugin.$template.html() );
			}

			// If gallery mode, no gallery is defined, and do_preload is true
			// use the input's value
			if ( is_gallery && do_preload && plugin.gallery === undefined ) {
				plugin.gallery = plugin.$input.val();
			}

			// Call appropriate setup function
			var func = is_gallery ? 'gallery' : 'insert';
			plugin.frame = media[ func ]( plugin, true );

			// Store the plugin options for later use
			$elm.data( 'QS.setupMedia', plugin );
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
	 * Setup file adder functionality.
	 *
	 * @deprecated 1.8.0 Converted to setupMedia alias.
	 *
	 * @since 1.6.2 Fixed attachment date for just-uploaded files.
	 * @since 1.6.1 Fixed typo with single attachment check.
	 * @since 1.6.0 Moved sortable handling, added thubmnail check, show option, and initonly mode.
	 * @since 1.5.0 Overhauled for live-plugin purposes.
	 * @since 1.2.0
	 *
	 * @param Event event The click event that triggered this.
	 * @param string mode  Pass 'initonly' to setup but not open the frame.
	 */
	QS.addFile = function() {
		// Alias to the addFile method
		return QS.setupMedia.apply( this, arguments );
	};

	/**
	 * Setup gallery editor functionality.
	 *
	 * @deprecated 1.8.0 Converted to setupMedia alias.
	 *
	 * @since 1.6.0 Preloading of gallery items, initonly mode.
	 * @since 1.5.0 Overhauled for live-plugin purposes.
	 * @since 1.0.0
	 *
	 * @param Event event The click event that triggered this.
	 * @param string mode  Pass 'initonly' to setup but not open the frame.
	 */
	QS.editGallery = function() {
		var $elm = $( this );

		// Ensure the type is set to image
		$elm.data( 'type', 'image' );

		// Alias to the addFile method
		return QS.setupMedia.apply( this, arguments );
	};


	/**
	 * Setup image setter functionality.
	 *
	 * @deprecated 1.8.0 Converted to setupMedia alias.
	 *
	 * @since 1.5.0 Modified to reflect live-plugin approach.
	 * @since 1.4.0 Converted to addFile alias.
	 * @since 1.0.0
	 *
	 * @param Event event The click event that triggered this.
	 */
	QS.setImage = function() {
		var $elm = $( this );

		// Ensure the type is set to image
		$elm.data( 'type', 'image' );

		// Alias to the addFile method
		return QS.setupMedia.apply( this, arguments );
	};

	/**
	 * Setup a QS plugin for an element.
	 *
	 * @since 1.5.0 Overhauled for live-plugin purposes.
	 * @since 1.0.0
	 *
	 * @param string selector Optional. The selector to delegate the click event to.
	 * @param string plugin   The name of the plugin to use.
	 * @param object options  Optional. The custom options to pass to the plugin.
	 */
	jQuery.fn.QS = function( /* [selector,] plugin [, options] */ ) {
		var $elm = $( this ),
			selector = '.qs-button', plugin, options;

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
					plugin   = arguments[0];
					options  = arguments[1];
				}
				break;
			case 1: // ( plugin )
				plugin = arguments[0];
				break;
			default:
				return;
		}

		var callback = QS[ plugin ];

		// Setup the (delegated) click event
		if ( selector ) {
			$elm.on( 'click', selector, options, callback );
			// Immediately initialize existing elements
			$elm.find( selector ).trigger( 'click', [ 'initonly' ] );
		} else {
			$elm.on( 'click', options, callback );
			// Immediately initialize existing elements
			$elm.trigger( 'click', [ 'initonly' ] );
		}

		return $elm;
	};

	// Clean up. Prevents mobile browsers caching
	$( window ).on( 'unload', function() {
		window.QS = null;
	});

	// Auto register hooks for addFile, setImage and editGallery
	$(function() {
		$( 'body' ).QS( '.qs-media .qs-button', 'setupMedia' );
		$( 'body' ).QS( '.qs-addfile .qs-button', 'addFile' );
		$( 'body' ).QS( '.qs-editgallery .qs-button', 'editGallery' );
	});

})( jQuery );
