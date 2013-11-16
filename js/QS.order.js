jQuery(function( $ ) {
	var menuOrderOptions = {
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
		update:           function( event, ui ) {
			var parent = ui.item.parent();
			if ( parent.prev( '.inner' ).length > 0 ) {
				parent = parent.prev( '.inner' ).find( '.qs-order-id' ).val();
			} else {
				parent = 0;
			}
			ui.item.find( '> .inner .qs-order-parent' ).val( parent );
		}
	};
	
	// Auto apply both plugins to preset classes
	$( '.qs-order-manager' )
		.not( '.qs-nested' )
		.children( 'ol' )
		.sortable( menuOrderOptions );
	$( '.qs-order-manager' )
		.filter( '.qs-nested' )
		.children( 'ol' )
		.nestedSortable( menuOrderOptions );
});