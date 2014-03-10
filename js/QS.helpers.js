jQuery(function($){
	$( 'body' ).on( 'click', '.qs-delete', function() {
		$( this ).parents( '.qs-item' ).animate({
			height:  'toggle',
			opacity: 'toggle'
		}, function() {
			$( this ).remove();
		});
	}).on( 'click', '.qs-clear', function(){
		var parent = $( this ).parent();
		parent.find( '.qs-item' ).animate({
			height:  'toggle',
			opacity: 'toggle'
		}, function() {
			$( this ).remove();
		});
	});
});