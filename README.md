# Pino Blog List

A WordPress plugin that displays published blog posts through the `[pino_blog]` shortcode, with a progressive **Load more** button.

## Usage

```text
[pino_blog]
[pino_blog per_page="10" category="travel"]
```

- `per_page`: posts per page, from 1 to 50 (default: 20).
- `category`: optional category slug.

## Features

- Responsive post cards with featured images, dates, and excerpts.
- Progressive loading through the WordPress AJAX endpoint.
- Keyboard-accessible controls and screen-reader status messages.
- Public published posts only; password-protected posts are excluded.
- Feed link as a fallback when JavaScript is disabled.
- Translatable PHP and JavaScript interface strings.

## Installation

1. Download `pino-blog-list.zip` from the [latest GitHub release](https://github.com/pinovolpe/pino-blog-list/releases/latest), not the automatically generated **Source code** archive.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the ZIP and activate the plugin.
4. Add `[pino_blog]` to a page or post.

## Requirements

- WordPress 6.0 or later.
- PHP 7.4 or later.
- Tested with WordPress 7.1.4.

## Development

The plugin uses the `pino-blog-list` text domain. PHP strings use WordPress translation functions; JavaScript strings use `wp.i18n` and the `wp-i18n` script dependency.

Releases are distributed as installable ZIP packages through the project's GitHub repository. The release asset is named `pino-blog-list.zip`.

## License

Pino Blog List is licensed under GPL-2.0-or-later.
