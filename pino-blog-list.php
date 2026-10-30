<?php
/**
 * Plugin Name: Pino Blog List
 * Description: Displays blog posts with the [pino_blog] shortcode and progressive loading.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Pino Volpe
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pino-blog-list
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PVB_BLOG_VERSION' ) ) {
	define( 'PVB_BLOG_VERSION', '0.2.0' );
}

/**
 * Load translations bundled with the plugin.
 * WordPress.org language packs can also provide translations when applicable.
 */
function pvb_blog_load_textdomain() {
	load_plugin_textdomain(
		'pino-blog-list',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'pvb_blog_load_textdomain' );

/**
 * Validate the number of posts requested by the shortcode or AJAX endpoint.
 * Values outside the supported range fall back to 20.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function pvb_blog_clean_per_page( $value ) {
	$value = is_scalar( $value ) ? absint( $value ) : 0;
	return ( $value >= 1 && $value <= 50 ) ? $value : 20;
}

/**
 * Sanitize a category slug. The caller must unslash request data first.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function pvb_blog_clean_category( $value ) {
	return is_scalar( $value ) ? sanitize_title( (string) $value ) : '';
}

/**
 * Query public, published posts only.
 *
 * @param int    $page     Page number.
 * @param int    $per_page Posts per page.
 * @param string $category Category slug.
 * @return WP_Query
 */
function pvb_blog_query( $page, $per_page = 20, $category = '' ) {
	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $per_page,
		'paged'               => $page,
		'ignore_sticky_posts' => true,
		'has_password'        => false,
		'orderby'             => array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		),
	);

	if ( '' !== $category ) {
		$args['category_name'] = $category;
	}

	return new WP_Query( $args );
}

/**
 * Allowed markup generated for post cards.
 * This keeps the image attributes and accessibility attributes while
 * explicitly filtering the HTML before it is returned or sent over AJAX.
 *
 * @return array<string, array<string, bool>>
 */
function pvb_blog_allowed_card_html() {
	return array(
		'article' => array( 'class' => true ),
		'a'       => array(
			'class'       => true,
			'href'        => true,
			'tabindex'    => true,
			'aria-hidden' => true,
		),
		'img'      => array(
			'class'    => true,
			'src'      => true,
			'alt'      => true,
			'width'    => true,
			'height'   => true,
			'srcset'   => true,
			'sizes'    => true,
			'loading'  => true,
			'decoding' => true,
		),
		'div'  => array( 'class' => true ),
		'h2'   => array( 'class' => true ),
		'time' => array(
			'class'    => true,
			'datetime' => true,
		),
		'p'    => array( 'class' => true ),
		'span' => array( 'class' => true ),
	);
}

/**
 * Build the post-card markup.
 *
 * @param WP_Query $query       Query object.
 * @param int      $eager_count Number of images to load eagerly.
 * @return string
 */
function pvb_blog_cards( $query, $eager_count = 0 ) {
	$index = 0;
	ob_start();

	while ( $query->have_posts() ) {
		$query->the_post();
		$url     = get_permalink();
		$title   = get_the_title();
		$excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 38, '…' );
		$loading = ( $index < $eager_count ) ? 'eager' : 'lazy';
		$index++;
		?>
		<article class="pvb-card">
			<a class="pvb-image" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
				<?php
				if ( has_post_thumbnail() ) {
					$thumbnail = get_the_post_thumbnail(
						get_the_ID(),
						'large',
						array(
							'loading'  => $loading,
							'decoding' => 'async',
							'alt'      => '',
							'sizes'    => '(max-width: 640px) 100vw, 220px',
						)
					);
					echo wp_kses( $thumbnail, pvb_blog_allowed_card_html() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is filtered through the plugin's explicit allowlist.
				} else {
					?>
					<span class="pvb-placeholder"><?php echo esc_html__( 'Immagine non disponibile', 'pino-blog-list' ); ?></span>
					<?php
				}
				?>
			</a>
			<div class="pvb-content">
				<h2 class="pvb-title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a></h2>
				<time class="pvb-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<p class="pvb-excerpt"><?php echo esc_html( $excerpt ); ?></p>
			</div>
		</article>
		<?php
	}

	wp_reset_postdata();
	return (string) ob_get_clean();
}

/**
 * Public, read-only AJAX endpoint. It returns only published, public posts.
 * A nonce is intentionally not required because the response is public and
 * the shortcode can be served from full-page caches; request values are
 * nevertheless unslashed, validated, and sanitized below.
 */
function pvb_blog_ajax() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Public read-only endpoint; nonce would make cached pages unreliable.
	$page_input = ( isset( $_POST['page'] ) && is_scalar( $_POST['page'] ) )
		? sanitize_text_field( wp_unslash( $_POST['page'] ) )
		: '';
	$per_page_input = ( isset( $_POST['per_page'] ) && is_scalar( $_POST['per_page'] ) )
		? sanitize_text_field( wp_unslash( $_POST['per_page'] ) )
		: '';
	$category_input = ( isset( $_POST['category'] ) && is_scalar( $_POST['category'] ) )
		? sanitize_text_field( wp_unslash( $_POST['category'] ) )
		: '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	$page = absint( $page_input );
	if ( $page < 2 || $page > 10000 ) {
		wp_send_json_error(
			array( 'message' => __( 'Pagina non valida.', 'pino-blog-list' ) ),
			400
		);
	}

	$per_page = pvb_blog_clean_per_page( $per_page_input );
	$category = pvb_blog_clean_category( $category_input );
	$query    = pvb_blog_query( $page, $per_page, $category );
	$html     = pvb_blog_cards( $query, 0 );

	wp_send_json_success(
		array(
			'html'     => wp_kses( $html, pvb_blog_allowed_card_html() ),
			'has_more' => $page < (int) $query->max_num_pages,
			'count'    => (int) $query->post_count,
		)
	);
}
add_action( 'wp_ajax_pvb_blog_more', 'pvb_blog_ajax' );
add_action( 'wp_ajax_nopriv_pvb_blog_more', 'pvb_blog_ajax' );

/**
 * Enqueue external styles and scripts once per page.
 */
function pvb_blog_enqueue_assets() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	wp_enqueue_style(
		'pvb-blog',
		plugin_dir_url( __FILE__ ) . 'assets/css/pino-blog-list.css',
		array(),
		PVB_BLOG_VERSION
	);

	wp_enqueue_script(
		'pvb-blog',
		plugin_dir_url( __FILE__ ) . 'assets/js/pino-blog-list.js',
		array( 'wp-i18n' ),
		PVB_BLOG_VERSION,
		true
	);

	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations(
			'pvb-blog',
			'pino-blog-list',
			plugin_dir_path( __FILE__ ) . 'languages'
		);
	}
}

/**
 * Shortcode: [pino_blog] or [pino_blog per_page="10" category="travel"].
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function pvb_blog_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'per_page' => 20,
			'category' => '',
		),
		$atts,
		'pino_blog'
	);

	$per_page = pvb_blog_clean_per_page( $atts['per_page'] );
	$category = pvb_blog_clean_category( $atts['category'] );

	pvb_blog_enqueue_assets();

	$query = pvb_blog_query( 1, $per_page, $category );
	$id    = wp_unique_id( 'pvb-blog-' );

	$cards = $query->have_posts()
		? pvb_blog_cards( $query, 2 )
		: '<p class="pvb-empty">' . esc_html__( 'Nessun articolo disponibile.', 'pino-blog-list' ) . '</p>';

	ob_start();
	?>
	<section
		id="<?php echo esc_attr( $id ); ?>"
		class="pvb-blog"
		aria-label="<?php echo esc_attr__( 'Articoli del blog', 'pino-blog-list' ); ?>"
		data-endpoint="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		data-per-page="<?php echo esc_attr( $per_page ); ?>"
		data-category="<?php echo esc_attr( $category ); ?>"
	>
		<div class="pvb-list" id="<?php echo esc_attr( $id . '-list' ); ?>">
			<?php echo wp_kses( $cards, pvb_blog_allowed_card_html() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is filtered through the plugin's explicit allowlist. ?>
		</div>
		<div class="pvb-actions">
			<button type="button" class="pvb-more" aria-controls="<?php echo esc_attr( $id . '-list' ); ?>" <?php if ( $query->max_num_pages <= 1 ) : ?>hidden<?php endif; ?>><?php echo esc_html__( 'Carica altri articoli', 'pino-blog-list' ); ?></button>
			<p class="pvb-status" role="status" aria-live="polite" aria-atomic="true"></p>
		</div>
		<noscript>
			<p><?php echo esc_html__( 'Attiva JavaScript per caricare altri articoli oppure ', 'pino-blog-list' ); ?><a href="<?php echo esc_url( get_feed_link() ); ?>"><?php echo esc_html__( 'consulta il feed del blog', 'pino-blog-list' ); ?></a>.</p>
		</noscript>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'pino_blog', 'pvb_blog_shortcode' );
