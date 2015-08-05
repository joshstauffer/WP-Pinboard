=== WP Pinboard ===
Contributors: joshstauffer
Tags: pinboard, bookmarks, feed
Requires at least: 3.5
Tested up to: 4.2.3
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a shortcode to display Pinboard.in bookmarks in your content, widgets, or templates. Public feeds can be filtered by usernames or tags.

== Description ==

Adds a shortcode to display Pinboard.in bookmarks in your content, widgets, or templates. Public feeds can be filtered by usernames or tags.

= URL Patterns =

**The URL patterns for public bookmarks look like this:**

- https://pinboard.in/u:username/
- https://pinboard.in/t:tag/
- https://pinboard.in/recent/
- https://pinboard.in/popular/

**You can filter bookmarks by combining a username with tags:**

- https://pinboard.in/u:username/t:tag1/t:tag2/

**You can filter bookmarks by up to three tags:**

- https://pinboard.in/t:tag1/t:tag2/t:tag3/

*Please note that the URL must begin with `https://pinboard.in/`.*

= Usage Examples =

**Basic Example:**

`[wp_pinboard]https://pinboard.in/u:username/[/wp_pinboard]`

**Advanced Example:**

- description: Whether to show of hide the description of the bookmark. Default 'show'.
- tags: Whether to show of hide the tags of the bookmark. Default 'show'.
- date: Whether to show of hide the date of the bookmark. Default 'show'.
- user: Whether to show of hide the username of the bookmark. Default 'show'.
- count: The number of bookmarks to display (max: 400). Default 20.
- orderby: How to sort the bookmarks. Accepts 'title', 'date', or 'random'. Default 'date'.
`[wp_pinboard description="show" tags="show" user="show" date="show" count="20" orderby="date"]https://pinboard.in/u:username/[/wp_pinboard]`

**Text Widget Example:**

You can print bookmarks using a text widget like so:
`[wp_pinboard]https://pinboard.in/u:username/[/wp_pinboard]`

**Template Example:**

You can also print bookmarks directly in a template like so:
`<?php echo do_shortcode('[wp_pinboard]https://pinboard.in/u:username/[/wp_pinboard]'); ?>`

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the `wp-pinboard/` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Does this plugin cache the list of bookmarks? =

Yes, lists are cached for one hour. If necessary, you can manually refresh a list of bookmarks. Adding a get variable (`?wppb-refresh=1`) to the URL of a page will refresh the cache of any lists of bookmarks on that particular page.

== Changelog ==

= 1.0 =
* First release.
