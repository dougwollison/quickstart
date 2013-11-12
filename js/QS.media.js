window.QS = window.QS || {};

(function($){
	var media = window.QS.media = {};

	/**
	 * =========================
	 * Utilities
	 * =========================
	 */
	
	/**
	 * Proccess the options with the defaults,
	 * also querying the $elements and returning
	 * a new extended object.
	 *
	 * @since 1.0.0
	 *
	 * @param jQuery $elm     The jQuery element.
	 * @param object options  The options to process.
	 * @param object defaults The defaults to use.
	 */
	function setupOptions( $elm, options, defaults ) {
		var newOptions = _.extend( {}, defaults, options );
		var k, v;
		
		for ( var k in newOptions ) {
			v = newOptions[ k ];
			if ( k.indexOf( '$' ) === 0 ) {
				// This should be a jQuery element.
				// if it's a string, query it.
				if ( typeof v == 'string' ) {
					newOptions[ k ] = $elm.find( v );
				}
			}
		}
		
		return newOptions;
	}

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
			if ( frame == undefined )
				frame = this.frame;

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
			if ( frame == undefined )
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
		 * @since 1.0.0
		 *
		 * @param object attributes The attributes for the frame workflow.
		 * @param object options    The options passed to the hook function.
		 */
		init: function( attributes, options ) {
			var frame = wp.media(attributes);

			//Run through each event and setup the handlers
			if ( options.events !== undefined ) {
				for ( var e in options.events ) {
					//Bind the callback to the event, passing QS.media as the context
					//from that they'll be able to access the frame and trigger element
					frame.on( e, options.events[ e ], media );
				}
			}

			//In case they need to hook into it, trigger "init" on the frame,
			//passing the frame itself as an additional parameter, since it
			//can't be linked into QS.media yet
			frame.trigger( 'init', frame );

			var trigger = $( options.trigger );

			//Create the click event for the trigger if present
			if ( trigger.length > 0 ) {
				trigger.on( 'click', function( e ) {
					e.preventDefault();

					//Link the frame into QS.media
					media.frame = frame;
					//Link the trigger into QS.media
					media.trigger = $(this);

					frame.open();
				});
			}
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
			if ( frame == undefined )
				frame = this.frame;

			if ( ids != undefined ) {
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
		 * @since 1.0.0
		 *
		 * @param object options A list of options.
		 */
		insert: function( options ) {
			var defaults = {
				title:    'Insert Media',
				choose:   'Insert Selected Media',
				media:    'image',
				multiple: false,
				trigger:  '.qs-button'
			};

			options = _.extend( {}, defaults, options );

		    this.init( {
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
			}, options );
		},

		/**
		 * Hook into the media manager for editing galleries.
		 *
		 * @since 1.0.0
		 *
		 * @param object options A list of options.
		 */
		gallery: function( options ) {
			var defaults = {
				title:   'Edit Gallery',
				trigger: '.qs-button'
			};

			options = _.extend( {}, defaults, options );

			var gallery, attachments, selection;

			if ( options.gallery !== undefined ) {
				//If gallery was not a comma separated string, make it one
				if ( typeof options.gallery != 'string' ) {
					options.gallery = options.gallery.join( ',' );
				}

				//Generate and parse shortcode
				gallery = wp.shortcode.next( 'gallery', '[gallery ids="' + options.gallery + '"]' );
				gallery = gallery.shortcode;

				//Get the attachments from the gallery shortcode
				attachments = wp.media.gallery.attachments( gallery );
				selection = new wp.media.model.Selection( attachments.models, {
			        props:    attachments.props.toJSON(),
			        multiple: true
			    });

			    selection.gallery = attachments.gallery;

			    //Fetch the query's attachments, and then break ties from the query to allow for sorting.
			    selection.more().done(function() {
			        selection.props.set( { query: false });
			        selection.unmirror();
			        selection.props.unset( 'orderby' );
			    });
			}

		    this.init( {
				state:     'gallery-edit',
				frame:     'post',
				title:     options.title,
				multiple:  true,
				editing:   true,
				selection: selection
			}, options );
		}
	});

	/**
	 * =========================
	 * jQuery Plugins
	 * =========================
	 */

	jQuery.fn.QS = function(plugin, options){
		return $(this).QS[plugin].call(this, options);
	};

	jQuery.fn.QS.setImage = function(options){
		return $(this).each(function() {
			var $this = $(this);
			var thisOptions;
			var defaults = {
				$input:   '.qs-input',
				$preview: '.qs-preview',
				$trigger: '.qs-button',
				title:    $this.text(),
				choose:   'Use Selected Image',
				events:   {
					open:   function() {
						media.preload( thisOptions.$input.val() );
					},
					select: function() {
						var attachment = media.attachment();
						var img = $( '<img src="' + attachment.sizes.thumbnail.url + '">' );
						thisOptions.$preview.empty().append( img );
						thisOptions.$input.val( attachment.id );
					}
				}
			}
			
			// Process the options with the defaults
			thisOptions = setupOptions( $this, options, defaults );

			// Preload with the current images
			thisOptions.gallery = thisOptions.$input.val();

			//Setup the media selector hook
			media.insert( thisOptions );
		});
	}

	jQuery.fn.QS.editGallery = function(options){
		return $(this).each(function() {
			var $this = $(this);
			var thisOptions;
			var defaults = {
				$input:   '.qs-input',
				$preview: '.qs-preview',
				$trigger: '.qs-button',
				title:    $this.text(),
				events:   {
					update: function() {
						var attachments = media.attachments();
						var items = [];
						var img;
	
						options.$preview.empty();
	
						for ( var i in attachments ) {
							items.push( attachments[ i ].id );
							img = $( '<img src="' + attachments[ i ].sizes.thumbnail.url + '">' );
							options.$preview.append( img );
						}
	
						options.$input.val( items.join( ',' ) );
					}
				}
			}
			
			// Process the options with the defaults
			thisOptions = setupOptions( $this, options, defaults );

			// Preload with the current images
			thisOptions.gallery = thisOptions.$input.val();

			//Setup the media selector hook
			media.gallery( thisOptions );
		});
	};

	// Clean up. Prevents mobile browsers caching
	$(window).on( 'unload', function() {
		window.QS = null;
	});

	// Auto register hooks for setImage and editGallery
	$(function() {
		$( '.qs-setimage' ).QS( 'setImage' );
		$( '.qs-editgallery' ).QS( 'editGallery' );
	});

})(jQuery);