/* globals jQuery, ajaxurl, alert, prompt */

jQuery(function($){
	if ( $( '#qs_sections-repeater' ).length > 0 ) {
		var postID = $('#post_ID').val();

		$( 'body' ).on( 'click', '.qs-edit-section', function( e ) {
			e.preventDefault();
			var select = $( this ).parents( '.qs-item' ).find( 'select' );

			var id = select.val();

			if ( id > 0 ) {
				window.open( '/wp-admin/post.php?post=' + id + '&action=edit' );
			}else{
				alert( 'Please select an existing section.' );
			}
		});

		var qsSectionEditButton = function() {
			$( this ).append( '<a href="#" class="button button-primary qs-edit-section">Edit</a>' );
		};

		$( '#qs_sections-repeater .qs-item' ).each( qsSectionEditButton );

		$( '#qs_sections-repeater' ).on( 'qs:item-added', function( e ) {
			qsSectionEditButton.apply( e.target );

			var item = $( e.target );
			var title = prompt( 'Please enter the title of the new section.' );

			$.ajax({
				url: ajaxurl,
				data: {
					action: 'qs-new_section',
					title: title,
					parent: postID
				},
				success: function( post ) {
					if ( '0' === post ) {
						alert( 'Error creating new section, please try manually.' );
						window.open( '/wp-admin/post-new.php?post_type=section' );
					}

					var option = $( '<option value="' + post + '">' + ( title ? title : '[New Section]' ) + '</option>' );
					$( 'select', item ).append( option );
					$( 'select', item ).val( post );
				},
				error: function() {
					alert( 'Error creating new section, please try manually.' );
					window.open( '/wp-admin/post-new.php?post_type=section' );
				}
			});
		});
	}
});