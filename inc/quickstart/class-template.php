<?php
namespace QuickStart;

/**
 * The Template Kit: Handy methods for quickly taking care of certain parts of the page templates.
 *
 * @package QuickStart
 * @subpackage Setup
 *
 * @since 1.11.0 Now extends Smart_Plugin
 * @since 1.0.0
 */

class Template extends \Smart_Plugin {
	/**
	 * Print out the start of the header (doctype and head tag).
	 *
	 * This basically merges all the above template functions into one call.
	 * By default only favicon is called, all others must be registered via $features.
	 *
	 * @since 1.12.0 Now handles feature settings for doc_start.
	 * @since 1.11.0 Now eneuques all methods for wp_head, with appropriate priorities.
	 * @since 1.8.0
	 *
	 * @param array $features An array of features to call.
	 */
	public static function the_head( array $features = array() ){
		// Make sure favicon and title features are set
		if ( ! isset( $features['title'] ) && ! in_array( 'title', $features, true ) ) {
			$features[] = 'title';
		}
		if ( ! isset( $features['favicon'] ) && ! in_array( 'favicon', $features, true ) ) {
			$features[] = 'favicon';
		}

		// Get any settings for doc_start
		$doc_start = array();
		if ( isset( $features['doc_start'] ) ) {
			$doc_start = $features['doc_start'];
			// Unset
			unset( $features['doc_start'] );
		}

		// Call the document starter unless unwanted
		if ( $doc_start !== false ) {
			static::doc_start( $doc_start );
		}
		?>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=EDGE">

	<?php
	// A list of methods that should run at the beginning of wp_head
	$pre_wp_head = array( 'viewport', 'title', 'favicon' );

	// Call each feature method
	foreach ( $features as $method => $settings ) {
		if ( is_int( $method ) ) {
			$method = $settings;
			$settings = null;
		}

		// Set priority accordingly
		$priority = in_array( $method, $pre_wp_head ) ? 0 : 10;

		// Make sure the method exists and that the settings isn't set to FALSE
		if ( method_exists( get_called_class(), $method ) && $settings !== false ) {
			// Enqueue the method for wp_head()
			self::setup_callback( $method, array( $settings ), array( 'wp_head', $priority, 0 ) );
		}
	}
	?>

	<?php wp_head(); ?>
</head>
		<?php
		// End output
	}

	/**
	 * Print out the start of the document.
	 *
	 * Doctype and opening html tag with
	 * IE conditional comments for classes.
	 *
	 * @since 1.12.0 Added customizing via $settings argument.
	 * @since 1.9.0  Revised condition for old IE.
	 * @since 1.1.0  Fixed IE9 tagging.
	 * @since 1.0.0
	 */
	public static function doc_start( $settings = array() ) {
		// Abort if already done
		if ( did_action( 'qs_template_doc_start' ) ) {
			return;
		}

		$min_ie = 6;
		$max_ie = 9;

		// Ensure settings is an array
		$settings = (array) $settings;

		// Get the min and max IE version values
		extract( get_array_values( $settings, 'min_ie', 'max_ie' ) );

		// Print the doctype
		echo "<!DOCTYPE html>\n";

		// Action hook here for printing any vanity stuff before the <html> tag.
		do_action( 'qs_template_doc_start' );

		$language_attributes = get_language_attributes();

		// If min/max IE are set, print the conditional tags
		if ( $min_ie && $max_ie ) {
			// Build the classes list
			$versions = array_reverse( range( $min_ie, $max_ie ) );
			foreach ( $versions as &$v ) {
				$v = "ie{$v}-";
			}

			// Merge into a class names value
			$classes = implode( ' ', $versions );

			// Print the <= conditional tag for $min_ie
			echo "<!--[if lte IE {$min_ie}]>\n";
			echo "<html id='ie{$min_ie}' class='{$classes}' {$language_attributes}>\n";
			echo "<![endif]-->\n";

			// Print the conditional tags for each version
			for ( $i = $min_ie + 1; $i <= $max_ie; $i++ ) {
				// Remove the last version from the list, recreate class names
				array_pop( $versions );
				$classes = implode( ' ', $versions );

				// Print the = conditional tag for this version of IE
				echo "<!--[if IE $i]>\n";
				echo "<html id='ie{$i}' class='{$classes}' {$language_attributes}>\n";
				echo "<![endif]-->\n";
			}

			// Print the normal <html> tag
			echo "<!--[if !(lte IE {$max_ie}) ]><!-->\n";
			echo "<html {$language_attributes}>\n";
			echo "<!--<![endif]-->\n";
		} else {
			echo "<html {$language_attributes}>\n";
		}
	}

	/**
	 * Print out the viewport meta tag.
	 *
	 * @since 1.9.0 Added support for passing the content as a string.
	 * @since 1.8.0
	 *
	 * @param array|string $settings Optional An array/string of settings to add/overwrite.
	 */
	public static function viewport( $settings = array() ){
		if ( empty( $settings ) ) {
			$settings = (array) $settings;
		}

		// Process the $settings if it's an array
		if ( is_array( $settings ) ) {
			// Handle the settings to go in the content attribue
			$settings = wp_parse_args( $settings, array(
				'width' => 'device-width',
				'initial-scale' => 1,
			) );

			$content = array();
			foreach ( $settings as $key => $value ) {
				// Skip empty values
				if ( is_null( $value ) ) {
					continue;
				}

				// Add the pair
				$content[] = "$key=$value";
			}

			$settings = implode( ',', $content );
		}

		echo '<meta name="viewport" content="' . $settings . '">';
		echo "\n";
	}

	/**
	 * Print out the title tag.
	 *
	 * @since 1.9.0 Restructured to allow passing settings as separate arugments,
	 *				renamed $seplocation to $side, added detection of passing
	 *				$side as sole argument, and $filter option.
	 * @since 1.8.0
	 *
	 * @param string|array $settings Optional The wp_title options like separator and location.
	 */
	public static function title( $settings = null ) {
		global $page, $paged;

		$sep = '|';
		$side = 'right';
		$filter = true;

		// If multiple arguments were passed, make that $settings
		if ( func_num_args() > 1 ) {
			$settings = func_get_args();
		}

		if ( $settings == 'nofilter' ) {
			// $settings is just the fitler toggle set to false
			$filter = false;
		} elseif ( in_array( $settings, array( 'left', 'right' ) ) ) {
			// $settings is just the side
			$side = $settings;
		} elseif ( is_string( $settings ) ) {
			// $settings is just the separator
			$sep = $settings;
		} elseif ( is_array( $settings ) ) {
			// $settings is multiple options...
			extract( get_array_values( $settings, 'sep', 'side', 'filter' ) );
		}

		// Add the custom filter if not disabled
		if ( $filter ) {
			add_filter( 'wp_title', array( get_called_class(), 'title_filter' ), 999, 3 );
		}

		// Get the title and build the output
		$title = wp_title( $sep, false, $side );

		echo '<title>' . $title . '</title>';
		echo "\n";
	}

	/**
	 * Filter the page title. Added via title().
	 *
	 * @since 1.11.0 Forgot to global in $paged and $page.
	 * @since 1.9.0
	 *
	 * @param string $title The title to filter.
	 * @param string $sep   The title separator.
	 * @param string $side  The separator location (left|right)
	 *
	 * @return string The filtered title.
	 */
	public static function title_filter( $title, $sep, $side ) {
		global $paged, $page;

		if ( $side == 'right' ) {
			$title = $title . get_bloginfo( 'name', 'display' );
		} else {
			$title = get_bloginfo( 'name', 'display' ) . $title;
		}

		$site_description = get_bloginfo( 'description', 'display' );
		if ( $site_description && ( is_home() || is_front_page() ) ) {
			$title .= " $sep $site_description";
		}

		if ( ( $paged >= 2 || $page >= 2 ) && ! is_404() ) {
			$title .= " $sep " . sprintf( __( 'Page %s' ), max( $paged, $page ) );
		}

		return $title;
	}

	/**
	 * Print out the favicon link (along with apple icons if passed).
	 *
	 * @since 1.9.0 Restructured to allow passing settings as separate arugments.
	 * 				Also added check for if an icon url is set before printing.
	 * @since 1.8.0
	 *
	 * @param string|array $settings Optional The favicon URL or array of favicons.
	 */
	public static function favicon( $settings = null ) {
		$icon_url = home_url('/favicon.ico');
		$apple_touch = array();
		$settings = (array) $settings;

		// If multiple arguments were passed, make that $settings
		if ( func_num_args() > 1 ) {
			$settings = func_get_args();
		}

		if ( is_string( $settings ) ) {
			// $settings is just the separator
			$icon_url = $settings;
		} elseif ( is_array( $settings ) ) {
			// $settings is multiple options...
			extract( get_array_values( $settings, 'icon_url', 'apple_touch' ) );
		}

		// Print the favicon if present, getting the mimetype from the extension
		if ( $icon_url ) {
			$ext = pathinfo( $icon_url, PATHINFO_EXTENSION );
			$mimetype = $ext == 'ico' ? 'icon' : $ext;
			echo '<link rel="shortcut icon" type="image/' . $mimetype . '" href="' . $icon_url . '" />';
			echo "\n";
		}

		// Handle apple-touch icons if present
		if ( $apple_touch ) {
			$apple_touch = (array) $apple_touch;
			$rel = 'apple-touch-icon-precomposed';

			foreach ( $apple_touch as $size => $url ) {
				if ( is_int( $size ) ) {
					$size = '60x60';
				}

				echo '<link rel="' . $rel .'" sizes="' . $size . '" href="' . $url . '" />';
				echo "\n";
			}
		}
	}

	/**
	 * Print out the IE stylesheet.
	 *
	 * Pass a string for the URL (default is css/ie.css in the theme folder)
	 * Pass an int for the IE version cap (default to 9)
	 *
	 * @since 1.9.0 Restructured to allow passing settings as separate arugments.
	 * @since 1.8.0
	 *
	 * @param string|int|array $settings Optional The stylesheet URL and/or version number
	 */
	public static function ie_css( $settings = null ) {
		$version = 8;
		$css_url = THEME_URL . '/css/ie.css';

		// If multiple arguments were passed, make that $settings
		if ( func_num_args() > 1 ) {
			$settings = func_get_args();
		}

		if ( is_string( $settings ) ) {
			// $settings is just the source
			$css_url = $settings;
		} elseif ( is_int( $settings ) ) {
			// $settings is just the version
			$version = $settings;
		} elseif ( is_array( $settings ) ) {
			// $settings is multiple options...
			extract( get_array_values( $settings, 'css_url', 'version' ) );
		}

		echo '<!--[if lte IE ' . $version . ']><link rel="stylesheet" type="text/css" href="' . $css_url . '" /><![endif]-->';
		echo "\n";
	}

	/**
	 * Print out the HTML5 shiv, either a provided one or the google one.
	 *
	 * @since 1.8.0
	 *
	 * @param string $shiv_url Optional The URL to the shiv file (defaults to google one)
	 */
	public static function html5shiv( $shiv_url = null ){
		if ( empty( $shiv_url ) ) {
			$shiv_url = '//html5shiv.googlecode.com/svn/trunk/html5.js';
		}

		// Print out within an IE conditional comment
		echo '<!--[if lt IE 9]><script src="' . $shiv_url . '"></script><![endif]-->';
		echo "\n";
	}

	/**
	 * Print out the WP AJAX url for javascript.
	 *
	 * @since 1.8.0
	 */
	public static function ajaxurl(){
		echo '<script>var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
		echo "\n";
	}

	/**
	 * Print out the template url for javascript.
	 *
	 * @since 1.10.0 Now uses TEMPLATE_URL constant for value.
	 * @since 1.8.0
	 */
	public static function template_url(){
		echo '<script>var template_url = "' . TEMPLATE_URL . '";</script>';
		echo "\n";
	}

	/**
	 * Print out the theme url for javascript.
	 *
	 * @since 1.10.0 Now uses THEME_URL constant for value.
	 * @since 1.9.0
	 */
	public static function theme_url(){
		echo '<script>var theme_url = "' . THEME_URL . '";</script>';
		echo "\n";
	}

	/**
	 * Print out the Google Analytics gode.
	 *
	 * @since 1.9.2 Updated arguments list.
	 * @since 1.9.0 Restructured to allow passing settings as separate arugments.
	 * @since 1.8.0 Added ability to pass $account & $production as array for first argument.
	 * @since 1.6.2
	 *
	 * @param string|array $settings Optional The account number and/or other options
	 */
	public static function ga_code( $settings ) {
		$account = null;
		$production = null;
		$universal = true;

		// If multiple arguments were passed, make that $settings
		if ( func_num_args() > 1 ) {
			$settings = func_get_args();
		}

		if ( is_string( $settings ) ) {
			// $settings is just the account
			$account = $settings;
		} elseif ( is_array( $settings ) ) {
			// $settings is multiple options...
			extract( get_array_values( $settings, 'account', 'production', 'universal' ) );
		}

		if ( ! is_null( $production ) ) {
			$check = false;

			// Convert production test to array and loop
			foreach ( (array) $production as $match ) {
				// By default, check for the host name
				$field = 'SERVER_NAME';

				// If it looks like an IP address, check for server address
				if ( preg_match( '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $match ) ){
					$field = 'SERVER_ADDR';
				}

				// If the check passes skip further testing
				if ( $_SERVER[ $field ] == $match ) {
					$check = true;
					break;
				}
			}

			// Abort if check fails
			if ( ! $check ) {
				return;
			}
		}

		?>
		<!-- Start Google Analytics tracking code -->
		<script type="text/javascript">
		<?php if($universal):?>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', '<?php echo $account; ?>', 'auto');
			ga('send', 'pageview');
		<?php else:?>
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', '<?php echo $account; ?>']);
			_gaq.push(['_trackPageview']);

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		<?php endif;?>
		</script>
		<!-- End Google Analytics tracking code -->
		<?php
	}
}
