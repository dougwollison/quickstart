window.QS = window.QS || {};

(function($){
	var media = window.QS.media = {};

	/**
	 * =========================
	 * Utilities
	 * =========================
	 */

	_.extend(media, {
		/**
		 * Extract the selected attachment from the given frame
		 *
		 * @param  wp.media frame The frame workflow
		 *
		 * @return object The first attachment selected
		 */
		attachment: function( frame ) {
			if ( frame == undefined )
				frame = this.frame;

			var attachment = frame.state().get( 'selection' ).first();

			return attachment.attributes;
		},

		/**
		 * Extract the selected attachments from the given frame
		 *
		 * @param wp.media frame The frame workflow
		 *
		 * @return array An array of attachments
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

			collection.map( function( item, i, items ) {
				attachments.push( item.attributes );
			} );

			return attachments;
		},


		/**
		 * media.init(attributes, options)
		 *
		 * Setup a new wp.media frame workflow, attach events, and set the trigger event
		 *
		 * @param {object} attributes The attributes for the frame workflow
		 * @param {object} options The options passed to the hook function
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
					media.trigger = $( this );

					frame.open();
				} );
			}
		},

		/**
		 * Preload the media manager with provided attachment ids
		 *
		 * @param string|array ids   A comma separated string or array of ids
		 * @param wp.media     frame The frame workflow (defaults to current frame stored in QS.media)
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

	_.extend(media, {
	});

	/**
	 * =========================
	 * jQuery Plugins
	 * =========================
	 */

})(jQuery);