jQuery( function( $ ) {
	var hidden = false;
	$( '#wp-admin-bar-wpedit-toggle a' ).click(function() {
		// Toggle hidden status
		hidden = ! hidden;
		// Update text
		$( this ).text( hidden ? 'Show Edit Buttons' : 'Hide Edit Buttons' );
		// Change body class
		$( 'body' ).toggleClass( 'wpedit-hidden' );
	});
} );