=== Pino Blog List ===
Contributors: pinovolpe
Tags: blog, posts, shortcode, ajax
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display published blog posts with a shortcode and a progressive "Load more" button.

== Description ==
Pino Blog List displays published WordPress posts in a responsive card layout. Visitors can load additional posts without reloading the page.

The plugin includes keyboard-accessible controls, screen-reader status messages, featured images, excerpts, dates, category filtering, and a feed link for visitors who have JavaScript disabled. The interface strings are prepared for translation; the default interface language is Italian.

== Installation ==
1. Upload the `pino-blog-list` folder to the `/wp-content/plugins/` directory, or install the ZIP from the WordPress Plugins screen.
2. Activate the plugin.
3. Add `[pino_blog]` to a page or post.

== Usage ==
`[pino_blog]`

`[pino_blog per_page="10" category="travel"]`

Available attributes:
* `per_page`: number of posts per page, from 1 to 50. Default: 20.
* `category`: category slug. Default: all categories.

== Frequently Asked Questions ==

= Does the plugin display private or password-protected posts? =
No. It displays published public posts and excludes password-protected posts.

= Does it work without JavaScript? =
The first page of posts remains visible. A link to the blog feed is provided as an alternative to progressive loading.

== Changelog ==

= 0.2.0 =
* Moved CSS and JavaScript into separate files.
* Prepared PHP and JavaScript interface strings for translation.
* Improved request sanitization and output filtering.
* Added the readme metadata required by Plugin Check.

= 0.1.0 =
* Initial release.

== License ==
This plugin is licensed under the GNU General Public License version 2 or later.
