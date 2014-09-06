jQuery(function( $ ) {
	// Create the sortable options
	var sortableOptions = {
		tabSize:          16,
		cursor:           'move',
		handle:           'div',
		helper:           'clone',
		items:            'li',
		opacity:          0.6,
		placeholder:      'qs-placeholder',
		revert:           true,
		tolerance:        'pointer',
		toleranceElement: '> div',
	};

	// Create the nestedSortable options
	// a copy of the sortable options + an update event for the parent value
	var nestedSortableOptions = $.extend( {}, sortableOptions, {
		update: function( event, ui ) {
			var parent = ui.item.parent();
			if ( parent.prev( '.inner' ).length > 0 ) {
				parent = parent.prev( '.inner' ).find( '.qs-order-id' ).val();
			} else {
				parent = 0;
			}
			ui.item.find( '> .inner .qs-order-parent' ).val( parent );
		}
	} );

	// Apply the sortable options
	// to order managers NOT using the qs-nested class
	$( '.qs-order-manager' )
		.not( '.qs-nested' )
		.children( 'ol' )
		.sortable( sortableOptions );

	// Apply the nestedSotrable options
	// ONLY to order managers using the qs-nested class
	$( '.qs-order-manager' )
		.filter( '.qs-nested' )
		.children( 'ol' )
		.nestedSortable( nestedSortableOptions );

	// Quick Sort buttons
	$( '.qs-sort button' ).click(function(){
		var method = $(this).val();

		if ( method ) {
			// Get all the first level items
			var collection = $.makeArray( $( '.qs-order-manager > ol > li' ) );
	
			if ( method === 'flip' ) {
				collection.reverse();
			} else {
				// Sort based on data attribute
				collection.sort(function( a, b ){
					var a_ = $(a).data( method );
					var b_ = $(b).data( method );
	
					if ( a_ === b_ ){
						return 0;
					}
	
					return a_ > b_ ? 1 : -1;
				});
			}
	
			// Reload list with sorted items and refresh
			$( '.qs-order-manager > ol' )
				.empty()
				.append( collection )
				.sortable( 'refresh' );
		}
	});
});