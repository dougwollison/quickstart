<?php
namespace QuickStart;

/**
 * The Template Kit: Handy methods for quickly taking care of certain parts of the page templates.
 *
 * @package QuickStart
 * @subpackage Setup
 * @since 1.0.0
 */

class Template {
	/**
	 * Print out the start of the document.
	 *
	 * Doctype and opening html tag with
	 * IE conditional comments for classes.
	 *
	 * @since 1.1.0 Fixed IE9 tagging.
	 * @since 1.0.0
	 */
	public static function doc_start() {
		?><!DOCTYPE html>
<!--[if lte IE 6]>
<html id="ie6" class="ie9- ie8- ie7- ie6-" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" class="ie9- ie8- ie7-" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" class="ie9- ie8-" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 9]>
<html id="ie9" class="ie9-" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8) | !(IE 9)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<?php
	}

	/**
	 * Print out the Google Analytics gode.
	 *
	 * @since 1.6.2
	 *
	 * @param string $account    The ID code of the account to track for.
	 * @param string $production Optional A host name or IP address to check for before printing.
	 */
	public static function ga_code( $account, $production = null ) {
		if ( ! is_null( $production ) ) {
			// By default, check for the host name
			$field = 'SERVER_NAME';

			// If it looks like an IP address, check for server address
			if ( preg_match( '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $production ) ){
				$field = 'SERVER_ADDR';
			}

			// If the check fails, don't print anything
			if ( $_SERVER[ $field ] != $production ) {
				return;
			}
		}

		?>
		<!-- Start Google Analytics tracking code -->
		<script type="text/javascript">

		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', '<?php echo $account; ?>']);
		  _gaq.push(['_trackPageview']);

		  (function() {
		    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();

		</script>
		<!-- End Google Analytics tracking code -->
		<?php
	}
}