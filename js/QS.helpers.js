jQuery(function($){
	function randStr(){
		return Math.round(Math.random() * 100000000).toString(36);
	}

	$( 'body' ).on( 'click', '.qs-delete', function() {
		$( this ).parents( '.qs-item' ).animate({
			height:  'toggle',
			opacity: 'toggle'
		}, function() {
			$( this ).remove();
		});
	}).on( 'click', '.qs-clear', function() {
		var parent = $( this ).parent();
		parent.find( '.qs-item' ).animate({
			height:  'toggle',
			opacity: 'toggle'
		}, function() {
			$( this ).remove();
		});
	});

	$( '.qs-sortable' ).each(function() {
		$( this ).sortable({
			items: '.qs-item',
			containment: 'parent'
		});
	});
	
	$( '.qs-repeater' ).each(function() {
		var repeater = $( this );
		var container = repeater.find( '.qs-container' );
		
		// Update the index of all item fields
		var updateItems = function() {
			repeater.find( '.qs-item' ).each(function( i ) {
				$( this ).find( 'input, select, textarea' ).each(function() {
					var name = $( this ).attr( 'name' );
					name = name.replace( /\[-?\d+\]/, '['+i+']' );
					$( this ).attr( 'name', name );
				});
			});
		};
		
		repeater.on( 'click', '.qs-add', function() {
			var count = repeater.find( '.qs-item' ).length;
	
			var template = repeater.find( '.qs-template' );
			if ( template.length == 0 ) {
				return alert( 'No template to work from for new item.' );
			}
			template = template.html();
			template = $( template );
			
			var unique = randStr();
			
			// Setup all div/input id and label for attributes
			template.find( 'div, input, select, textarea, label' ).each(function() {
				// Figure out which attribute to retrieve
				var attr = this.nodeName.toLowerCase() == 'label' ? 'for' : 'id';
			
				// Get the attribute value, abort if not found
				var id = $( this ).attr( attr );
				if ( ! id ) return;
				
				// Inser the unique string
				id = id.replace( '%', unique );
				$( this ).attr( attr, id );
			});
			
			// Insert the new item, update the set
			container.append(template);
			updateItems();
		}).on( 'click', '.qs-delete', updateItems);
		
		container.sortable({
			items: '.qs-item',
			axis: 'y',
			update: updateItems
		});
	});
});