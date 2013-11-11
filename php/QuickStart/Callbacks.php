<?php
namespace QuickStart;

/**
 * The Callbacks Kit: A collection of callback methods for various purposes.
 *
 * @package QuickStart
 * @subpackage Callbacks
 * @since 1.0.0
 */

class Callbacks extends \SmartPlugin{
	/**
	 * Default admin page callback
	 * This will server for most admin pages; uses the WordPress Settings API
	 * to print out any fields registered.
	 *
	 * @since 1.0.0
	 */
	public static function default_admin_page() {
		$screen = get_current_screen();

		if ( preg_match( '/^.+?_page_(.+)$/', $screen->id, $matches ) ) {
			// Submenu page
			$page = $matches[1];
		} else {
			// Top level page
			$page = str_replace( 'toplevel_page_', '', $screen->id );
		}

		?>
		<div class="wrap">
			<?php screen_icon( 'generic' ); ?>
			<h2><?php echo get_admin_page_title(); ?></h2>

			<br>

			<form method="post" action="options.php">
				<?php settings_fields( $page ); ?>
				<?php do_settings_sections( $page ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}