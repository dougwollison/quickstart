<?php
namespace QuickStart;

/**
 * The Template Kit: Handy methods for quickly taking care of certain parts of the page templates.
 *
 * @package QuickStart
 * @subpackage Setup
 * @since 1.0.0
 */

class Template{
	/**
	 * Print out the start of the document.
	 *
	 * Doctype and opening html tag with
	 * IE conditional comments for classes.
	 *
	 * @since 1.0.0
	 */
	public static function doc_start() {
		?><!DOCTYPE html>
<!--[if lte IE 6]>
<html id="ie6" class="ie8- ie7- ie6-" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" class="ie8- ie7-" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" class="ie8-" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 9]>
<html id="ie8" class="ie8-" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8) | !(IE 9)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<?php
	}
}