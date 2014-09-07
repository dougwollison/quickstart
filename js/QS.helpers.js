/* global alert, _, QS */
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
		 * @param string|jQuery parent The parent element's selector or jQuery object.
		 * @param string        item   The item element's selector.
		 * @param string        method The method to sort by ('flip' or a data-attribute).
		 *
		 * @return array The sorted collection of elements.
		 */
		 sortItems: function( parent, item, method ) {
		 	if ( typeof parent === 'string' ) {
			 	parent = $( parent );
		 	}
		 
			// Get all the first level items
			var collection = parent.children( item );
			
			if ( method === 'flip' ) {
				// Since jQuery has no reverse method...
				collection.each(function(){
					$(this).prependTo( parent );
				});
			} else {
				// Sort based on data attribute
				collection.sort(function( a, b ) {
					var a_ = $(a).data( method );
					var b_ = $(b).data( method );
			
					if ( a_ === b_ ){
						return 0;
					}
			
					return a_ > b_ ? 1 : -1;
				});
				
				// Reload list with sorted items
				collection.detach().appendTo( parent );
			}
			
			// Refresh the sortability
			parent.sortable( 'refresh' );
		 }
	 });

})( jQuery );

jQuery(function($){
	function randStr(){
		return Math.round(Math.random() * 100000000).toString(36);
	}

	// Delete item button setup
	$( 'body' ).on( 'click', '.qs-delete', function() {
		$( this ).parents( '.qs-item' ).animate({
			height:  'toggle',
			opacity: 'toggle'
		}, function() {
			$( this ).remove();
		});
	});
	
	// Clear items button setup
	$( 'body' ).on( 'click', '.qs-clear', function() {
		var parent = $( this ).parent();

		if ( parent.hasClass( 'qs-editgallery' ) ) {
			// Empty the gallery preview and input value
			parent.find( '.qs-preview' ).animate({
				height:  'toggle',
				opacity: 'toggle'
			}, function() {
				$( this ).empty().show();
			});
			parent.find( '.qs-value' ).val( '' );
		} else {
			// Remove all items
			parent.find( '.qs-item' ).animate({
				height:  'toggle',
				opacity: 'toggle'
			}, function() {
				$( this ).remove();
			});
		}
	});

	// Sortable setup
	$( '.qs-sortable' ).each(function() {
		var axis = $( this ).data( 'axis' );

		$( this ).sortable({
			items: '.qs-item',
			containment: 'parent',
			axis: axis ? axis : false,
		});
	});

	// Quick Sort buttons
	$( '.qs-field' ).on( 'click', '.qs-sort button', function(){
		var method = $(this).val();
		var parent = $(this).parents( '.qs-field' );

		if ( method ) {
			QS.helpers.sortItems( parent.find( '.qs-container' ), '.qs-item', method );
		}
	});
	
	// Repeater setup
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
			var template = repeater.find( '.qs-template' );
			if ( template.length === 0 ) {
				return alert( 'No template to work from for new item.' );
			}
			template = template.html();
			template = $( template );
			
			var unique = randStr();
			
			// Setup all div/input id and label for attributes
			template.find( 'div, input, select, textarea, label' ).each(function() {
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