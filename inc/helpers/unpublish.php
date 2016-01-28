<?php
/**
 * Unpublish helper; adds one-click "unpublishing" of posts by changing status to pending.
 *
 * @package QuickStart
 * @subpackage Unpublish
 *
 * @since 1.13.0
 */

/**
 * Check if unpublish action was requested, update post_status if so.
 *
 * @since 1.13.0
 */
function qs_helper_unpublish_process() {
	if ( isset( $_POST['unpublish'] ) && ! empty( $_POST['unpublish'] )
	  && isset( $_POST['post_status'] ) && $_POST['post_status'] == 'publish' ) {
		$_POST['post_status'] = 'pending';
	}
}

add_action( 'admin_init', 'qs_helper_unpublish_process' );

/**
 * On published posts, output an Unpublish button.
 *
 * @since 1.13.0
 *
 * @param WP_Post $post The post being edited.
 */
function qs_helper_unpublish_action( $post ) {
	if ( $post->post_status == 'publish' ) {
		?>
		<input name="unpublish" type="submit" class="button" id="unpublish" value="<?php esc_attr_e( 'Unpublish' ) ?>" />
		<?php
	}
}

add_action( 'post_submitbox_minor_actions', 'qs_helper_unpublish_action' );

/**
 * Print styling for unpublish button.
 *
 * @since 1.13.0
 */
function qs_helper_unpublish_styles() {
	?>
	<style type="text/css" media="screen">
		#unpublish {
			float: left;
		}
	</style>
	<?php
}

add_action( 'admin_head', 'qs_helper_unpublish_styles' );