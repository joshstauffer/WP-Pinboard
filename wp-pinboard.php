<?php
/*
Plugin Name: WP Pinboard
Plugin URI:  https://wordpress.org/plugins/wp-pinboard/
Description: Adds a shortcode to display Pinboard.in bookmarks in your content, widgets, or templates. Public feeds can be filtered by usernames or tags.
Version:     1.0
Author:      Josh Stauffer
Author URI:  http://www.joshstauffer.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: wp-pinboard

WP Pinboard is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
WP Pinboard is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with WP Pinboard. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WP_Pinboard {

	public function __construct() {
		// Register the shortcode.
		add_action( 'init', array( $this, 'register_shortcode' ) );

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_pinboard_scripts' ) );

		// Enable the use of shortcodes in text widgets.
		add_filter( 'widget_text', 'do_shortcode' );
	}

	public function register_shortcode() {
		add_shortcode( 'wp_pinboard', array( $this, 'wp_pinboard_shortcode' ) );
	}

	public function wp_pinboard_scripts() {
		wp_enqueue_style( 'wp-pinboard', plugins_url( 'css/style.css', __FILE__ ) );
	}

	/**
	 * Builds the wp_pinboard shortcode output.
	 *
	 * @param array $attr {
	 *     Array of default attributes.
	 *
	 *     @type string  $description  Whether to show of hide the description of the bookmark. Default 'show'.
	 *     @type string  $tags         Whether to show of hide the tags of the bookmark. Default 'show'.
	 *     @type string  $date         Whether to show of hide the date of the bookmark. Default 'show'.
	 *     @type string  $user         Whether to show of hide the username of the bookmark. Default 'show'.
	 *     @type int     $count        The number of bookmarks to display (max: 400). Default 20.
	 *     @type string  $orderby      How to sort the bookmarks. Accepts 'title', 'date' or 'random'. Default 'date'.
	 * }
	 * @param string $url Url of pinboard.in public bookmarks page.
	 *
	 * @return string Bookmark output, error message, or empty string.
	 */
	public function wp_pinboard_shortcode( $attr, $url = '' ) {
		// The base url for the Pinboard website.
		$base_url = 'https://pinboard.in';

		// The base url for getting bookmarks.
		$feed_base_url = 'https://feeds.pinboard.in/json';

		// Replace http:// with https://.
		$url = trim( str_replace( 'http://', 'https://', $url ) );

		// Return error if URL does not start with $base_url.
		if ( 0 !== stripos( $url, $base_url ) ) {
			return "ERROR: the URL must begin with '{$base_url}/'";
		}

		// Replace base url with feed url.
		$url = str_replace( $base_url, $feed_base_url, $url );

		// Set default attribute values.
		$default_atts = array(
			'description' => 'show',
			'tags'        => 'show',
			'date'        => 'show',
			'user'        => 'show',
			'count'       => 20,
			'orderby'     => 'date',
		);

		// Merge user attributes with defaults.
		$atts = shortcode_atts( $default_atts, $attr, 'wp_pinboard' );

		// Convert the value to a non-negative integer.
		$atts['count'] = absint( $atts['count'] );

		// If the count is zero, return empty string.
		if ( 0 == $atts['count'] ) {
			return '';
		}

		// Add count variable to the URL. Overrides count if count exists in URL.
		$url = add_query_arg( array( 'count' => $atts['count'] ), $url );

		// Use first 7 characters of plugin namespace as a prefix.
		$namespace = substr( 'wp_pinboard', 0, 7 );

		// Build a transient name that is unique and less than 40 chars.
		$transient_name = $namespace . md5( $url );

		// Get transient.
		$json = get_transient( $transient_name );

		// Do not create transient by default.
		$create_transient = false;

		// Transient does not exist, does not have a value, or has expired.
		if ( false === $json ) {
			$create_transient = true;
		// Query variable used to force a refresh.
		} elseif ( isset( $_GET['wppb-refresh'] ) && $_GET['wppb-refresh'] ) {
			$create_transient = true;
		}

		// Create the transient if necessary.
		if ( $create_transient ) {
			// Retrieve the body of an already retrieved HTTP request.
			$json = wp_remote_retrieve_body( wp_remote_get( $url ) );
			// Save the transient.
			set_transient( $transient_name, $json, $this->transient_lifespan() );
		}

		// Decode the JSON string into an array of objects.
		$items = json_decode( $json );

		// If no items retrieved, return empty string.
		if ( empty( $items ) ) {
			return '';
		}

		// Order the items by date, title or randomly.
		switch ( $atts['orderby'] ) {
			case 'random' :
				// Shuffle the array of items.
				shuffle( $items );
				break;
			case 'title' :
				// Sort the items by title ascending.
				usort( $items, array( $this, 'title_comparison' ) );
				break;
			default :
				// Items ordered by date, newest first.
		}

		// Turn on output buffering.
		ob_start();

		// Loop through bookmarks.
		foreach ( $items as $item ) :
			$link        = isset( $item->u ) ? $item->u : '';
			$title       = isset( $item->d ) ? $item->d : '';
			$description = isset( $item->n ) ? $item->n : '';
			$datetime    = isset( $item->dt ) ? $item->dt : '';
			$user        = isset( $item->a ) ? $item->a : '';
			$tags        = isset( $item->t ) ? $item->t : array();

			// Remove empty values from tags array.
			$tags = array_filter( $tags );

			// Hide the descriptions?
			if ( 'show' != $atts['description'] ) {
				$description = '';
			}

			// Hide the user?
			if ( 'show' != $atts['user'] ) {
				$user = '';
			}

			// Hide the tags?
			if ( 'show' != $atts['tags'] ) {
				$tags = array();
			}

			// Set a user URI segment.
			if ( ! empty( $user ) ) {
				$user_segment = "/u:{$user}";
			} else {
				$user_segment = '';
			}

			// Set a timestamp in the correct timezone.
			if ( 'show' == $atts['date'] && ! empty( $datetime ) ) {
				$timestamp = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $datetime ) ) ) );
			} else {
				$timestamp = false;
			}

			if ( ! empty( $timestamp ) ) {
				// Set date in user configured format.
				$date = date( get_option( 'date_format' ), $timestamp );
				// Set time in user configured format.
				$time = date( get_option( 'time_format' ), $timestamp );
				// Set to human time difference or month year.
				if ( ( abs( current_time( 'timestamp' ) - $timestamp ) ) < 12 * WEEK_IN_SECONDS ) {
					$date_label = sprintf( __( '%s ago', 'wp-pinboard' ), human_time_diff( $timestamp, current_time( 'timestamp' ) ) );
				} else {
					$date_label = date( 'F Y', $timestamp );
				}
			}
			?>
			<li class="wppb-bookmark">
				<div class="wppb-header"><a class="wppb-title" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></div>
				<?php if ( $description ) { ?><div class="wppb-description"><?php echo esc_html( $description ); ?></div><?php } ?>
				<?php if ( $tags ) { ?>
				<div class="wppb-tags">
					<?php foreach ( $tags as $tag ) { ?>
					<a class="wppb-tag" href="<?php echo esc_url( $base_url . $user_segment . "/t:{$tag}/" ); ?>"><?php echo esc_html( $tag ); ?></a>
					<?php } ?>
				</div>
				<?php } ?>
				<?php if ( $timestamp || $user ) { ?>
				<div class="wppb-footer">
					<?php if ( $timestamp ) { ?><abbr class="wppb-date" title="<?php echo esc_attr( sprintf( __( '%s   %s', 'wp-pinboard' ), $date, $time ) ); ?>"><?php echo esc_html( $date_label ); ?></abbr><?php } ?>
					<?php if ( $user ) { ?><span class="wppb-user">by <a href="<?php echo esc_attr( $base_url . $user_segment . '/' ); ?>"><?php echo esc_html( $user ); ?></a><?php } ?>
				</div>
				<?php } ?>
			</li>
			<?php
		endforeach;

		// Return output buffer contents.
		return '<ul class="wppb-bookmarks">' . ob_get_clean() . '</ul>';
	}

	// Compare bookmark titles for ordering.
	public function title_comparison( $a, $b ) {
		return strcmp( $a->d, $b->d );
	}

	// If the user is a super admin and debug mode is on, only store transients for a second.
	public function transient_lifespan() {
		return HOUR_IN_SECONDS;
		if( is_super_admin() && WP_DEBUG ) {
			return 1;
		} else {
			return HOUR_IN_SECONDS;
		}
	}

}
new WP_Pinboard();