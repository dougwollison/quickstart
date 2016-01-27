/* global alert, _, QS, google, ajaxurl */
window.QS = window.QS || {};

(function( $ ) {
	var helpers = window.QS.helpers = {};

	/**
	 * =========================
	 * Public Utilities
	 * =========================
	 */

	 _.extend( helpers, {
		/**
		 * Sort some elements by a specified method.
		 *
		 * @since 1.6.0
		 *
		 * @param string|jQuery $parent The parent element's selector or jQuery object.
		 * @param string        item    The item element's selector.
		 * @param string        method  The method to sort by ('flip' or a data-attribute).
		 *
		 * @return array The sorted collection of elements.
		 */
		sortItems: function( $parent, item, method ) {
			if ( typeof $parent === 'string' ) {
				$parent = $( $parent );
			}

			// Get all the first level items
			var collection = $parent.children( item );

			if ( method === 'flip' ) {
				// Since jQuery has no reverse method...
				collection.each(function() {
					$( this ).prependTo( $parent );
				});
			} else {
				// Sort based on data attribute
				collection.sort(function( a, b ) {
					var a_ = $( a ).data( method );
					var b_ = $( b ).data( method );

					if ( a_ === b_ ){
						return 0;
					}

					return a_ > b_ ? 1 : -1;
				});

				// Reload list with sorted items
				collection.detach().appendTo( $parent );
			}

			// Refresh the sortability
			$parent.sortable( 'refresh' );
		}
	});
})( jQuery );

jQuery(function( $ ) {
	function randStr() {
		return Math.round(Math.random() * 100000000).toString(36);
	}

	// Delete item button setup
	$( 'body' ).on( 'click', '.qs-delete', function() {
		var $item = $( this ).parents( '.qs-item' );

		$item.animate({
			height:  'toggle',
			opacity: 'toggle'
		}, function() {
			$( this ).remove();
		});

		// Fire an item-removed
		$item.trigger( 'qs:item-deleted' );
	});

	// Clear items button setup
	$( 'body' ).on( 'click', '.qs-clear', function() {
		// Get the parent field or repeater
		var $parent = $( this ).parents( '.qs-field, .qs-repeater' ).eq( 0 );

		if ( $parent.hasClass( 'qs-media' ) ) {
			if ( $parent.hasClass( 'single' ) ) {
				// Empty the preview, replacing it with the add_label text
				var $preview = $parent.find( '.qs-preview' );
				$preview.html( $preview.attr( 'title' ) );
				$parent.find( '.qs-value' ).val( '' );

				// And update the parent's value-* class
				$parent.removeClass( 'value-filled' ).addClass( 'value-empty' );
			} else if ( $parent.hasClass( 'gallery' ) ) {
				// Empty the gallery preview and input value
				$parent.find( '.qs-preview' ).animate({
					height:  'toggle',
					opacity: 'toggle'
				}, function() {
					$( this ).empty().show();
				});
				$parent.find( '.qs-value' ).val( '' );
			} else if ( $parent.hasClass( 'multiple' ) ) {
				// Delete the single item
				$parent.find( '.qs-item' ).animate({
					height:  'toggle',
					opacity: 'toggle'
				}, function() {
					$( this ).remove();
				});
			}

			// Trigger the media-changed event
			$parent.trigger( 'qs:media-changed' );
		} else {
			// Remove all items by triggering their delete buttons
			$parent.find( '.qs-item .qs-delete' ).click();
		}
	});

	// Sortable setup
	$( '.qs-sortable' ).each(function() {
		var axis = $( this ).data( 'axis' );

		$( this ).sortable({
			items:       '.qs-item',
			containment: 'parent',
			axis:        axis ? axis : false
		});
	});

	// Quick Sort buttons
	$( '.qs-field' ).on( 'click', '.qs-sort button', function() {
		var method = $( this ).val();
		var $parent = $( this ).parents( '.qs-field' );

		if ( method ) {
			QS.helpers.sortItems( $parent.find( '.qs-container' ), '.qs-item', method );
		}
	});

	// Repeater setup
	$( '.qs-repeater' ).each(function() {
		var $repeater = $( this );

		// Get the container and template (make sure to grab closest decendants)
		var $container = $repeater.find( '.qs-container' ).eq(0);
		var $template = $repeater.find( '.qs-template' ).eq(0);

		if ( $template.length === 0 ) {
			return;
		}

		// Create the template object
		$template = $( $template.html() );

		// Update the index of all item fields
		var updateItems = function() {
			$repeater.find( '.qs-item' ).each(function( i ) {
				$( this ).find( 'input, select, textarea' ).each(function() {
					var name = $( this ).attr( 'name' );
					name = name.replace( /\[-?\d+\]/, '[' + i + ']' );
					$( this ).attr( 'name', name );
				});
			});
		};

		$repeater.on( 'click', '.qs-add', function() {
			// Clone the template as a new item
			var $item = $template.clone();

			var unique = randStr();

			// Setup all div/input id and label for attributes
			$item.find( 'div, input, select, textarea, label' ).each(function() {
				// Figure out which attribute to retrieve
				var attr = this.nodeName.toLowerCase() === 'label' ? 'for' : 'id';

				// Get the attribute value, abort if not found
				var id = $( this ).attr( attr );
				if ( ! id ) {
					return;
				}

				// Inser the unique string
				id = id.replace( '%', unique );
				$( this ).attr( attr, id );
			});

			// Insert the new item, update the set
			$container.append( $item );
			updateItems();

			// Fire an item-added event
			$item.trigger( 'qs:item-added' );
		})
		// Have updateItems triggered when one is deleted
		.on( 'click', '.qs-delete', updateItems );

		// Add an update callback to the sortable
		$container.sortable( 'option', 'update', updateItems );
	});

	// Setup map fields, provided google maps is loaded
	if ( typeof google !== 'undefined' && google.maps ) {
		$( '.qs-map' ).each(function() {
			var $ui = $( this );

			var type = $ui.data( 'type' ) || 'ROADMAP';
				type = type.toUpperCase();

			var defaultLat = $ui.data( 'lat' ) || 0;
			var defaultLng = $ui.data( 'lng' ) || 0;
			var defaultZoom = $ui.data( 'zoom' ) || 5;

			var $lat    = $ui.find( '.qs-value-lat' );
			var $lng    = $ui.find( '.qs-value-lng' );
			var $zoom   = $ui.find( '.qs-value-zoom' );
			var $canvas = $ui.find( '.qs-map-canvas' );
			var $search = $ui.find( '.qs-map-search' );

			var lat  = $lat.val();
			var lng  = $lng.val();
			var zoom = $zoom.val();

			var showMarker = false;

			// Check if values are set, use defaults if not
			if ( lat !== '' && lng !== '' ) {
				showMarker = true;
				if ( ! zoom ) {
					zoom = defaultZoom;
				}
			} else {
				lat  = defaultLat;
				lng  = defaultLng;
				zoom = defaultZoom;
			}

			// Convert to floats/integers
			lat = parseFloat( lat );
			lng = parseFloat( lng );
			zoom = parseInt( zoom );

			// Create the map
			var map = new google.maps.Map( $canvas[0], {
				mapTypeId: google.maps.MapTypeId[ type ],
				center:    new google.maps.LatLng( lat, lng ),
				zoom:      zoom
			});

			// Create a blank marker
			var marker = new google.maps.Marker({
				position:  new google.maps.LatLng( lat, lng ),
				clickable: false
			});

			// Update/show the marker if possible
			if ( showMarker ) {
				marker.setMap( map );
			}

			// Setup the click-to-place-marker callback
			google.maps.event.addListener( map, 'click', function( data ) {
				var lat  = data.latLng.lat();
				var lng  = data.latLng.lng();
				var zoom = map.getZoom();

				$lat.val( lat );
				$lng.val( lng );
				$zoom.val( zoom );

				marker.setPosition( new google.maps.LatLng( lat, lng ) );
				marker.setMap( map );

				// Fire a marker-placed event, passing the marker object
				$canvas.trigger( 'qs:marker-placed', [ marker ] );
			});

			// Setup the update-zoom-on-zoom callback
			google.maps.event.addListener( map, 'zoom_changed', function() {
				$zoom.val( map.getZoom() );
			});

			$ui.find( '.qs-clear' ).click(function() {
				marker.setMap( null );
			});

			// Search feature if present
			if ( $search.length > 0 ){
				// Setup the search functionality
				$ui.find( '.qs-search' ).click(function() {
					var query = $search.val();

					$.ajax({
						url: ajaxurl,
						data: {
							action:  'qs_helper_geocode',
							address: query,
						},
						type: 'GET',
						dataType: 'json',
						success: function( data ) {
							if ( !data ) {
								return alert( 'Search for "' + query + '" turned up no results' );
							}

							// Get the coordiates
							var lat    = data.lat;
							var lng    = data.lng;
							var coords = new google.maps.LatLng( lat, lng );

							// Update the fields
							$lat.val( lat );
							$lng.val( lng );
							$zoom.val( 10 );

							// Update the marker
							marker.setPosition( coords );
							marker.setMap( map );

							// Center/zoom the map
							map.setCenter( coords );
							map.setZoom( 10 );

							// Fire a marker-placed event, passing the marker object
							$canvas.trigger( 'qs:marker-placed', [ marker ] );
						},
						error: function() {
							alert( 'Error attempting to geocode "' + query + '"' );
						}
					});
				});
			}
		});

		// Prevent hitting enter while entering a search address
		// from triggering form submit
		$( 'form#post' ).keypress(function( e ) {
			if ( e.which === 13 && $( e.target ).is( 'input.qs-map-search' ) ) {
				e.preventDefault();
				// Trigger the search callback instead
				$( e.target ).parents( '.qs-map-field' ).find( '.qs-search' ).click();
			}
		});
	}
});
