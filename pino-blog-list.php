<?php
/**
 * Plugin Name: Pino Blog List
 * Description: Shortcode [pino_blog] per mostrare gli articoli del blog con caricamento progressivo, accessibile da tastiera e da lettori di schermo.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Pino Volpe
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) { exit; }

// Versione usata per gli asset: tienila uguale a quella dell'intestazione.
if (!defined('PVB_BLOG_VERSION')) {
    define('PVB_BLOG_VERSION', '0.1.0');
}

/* ------------------------------------------------------------------
 * Pulizia dei parametri
 * Gli attributi dello shortcode e i dati ricevuti via AJAX arrivano
 * dall'esterno: vengono sempre validati prima di essere usati.
 * ------------------------------------------------------------------ */

// Articoli per pagina: accetta valori da 1 a 50, altrimenti usa 20.
function pvb_blog_clean_per_page($value) {
    $value = is_scalar($value) ? absint($value) : 0; // absint: intero positivo
    return ($value >= 1 && $value <= 50) ? $value : 20;
}

// Categoria: accetta solo lo slug (es. "viaggi"), normalizzato da sanitize_title().
function pvb_blog_clean_category($value) {
    return is_scalar($value) ? sanitize_title(wp_unslash((string) $value)) : '';
}

/* ------------------------------------------------------------------
 * Query degli articoli
 * ------------------------------------------------------------------ */
function pvb_blog_query($page, $per_page = 20, $category = '') {
    $args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $per_page,
        'paged'               => $page,
        'ignore_sticky_posts' => true,
        'has_password'        => false, // esclude gli articoli protetti da password
        'orderby'             => array('date' => 'DESC', 'ID' => 'DESC'),
    );
    if ($category !== '') {
        $args['category_name'] = $category; // filtro per slug di categoria
    }
    return new WP_Query($args);
}

/* ------------------------------------------------------------------
 * Markup delle card
 * $eager_count: quante immagini caricare subito. Le altre sono "lazy":
 * il browser le scarica solo quando stanno per comparire.
 * ------------------------------------------------------------------ */
function pvb_blog_cards($query, $eager_count = 0) {
    $index = 0;
    ob_start();
    while ($query->have_posts()) {
        $query->the_post();
        $url     = get_permalink();
        $title   = get_the_title();
        $excerpt = wp_trim_words(wp_strip_all_tags(get_the_excerpt()), 38, '…');

        $loading = ($index < $eager_count) ? 'eager' : 'lazy';
        $index++;
        ?>
        <article class="pvb-card">
            <a class="pvb-image" href="<?php echo esc_url($url); ?>" tabindex="-1" aria-hidden="true">
                <?php if (has_post_thumbnail()) {
                    echo get_the_post_thumbnail(get_the_ID(), 'large', array(
                        'loading'  => $loading,
                        'decoding' => 'async',
                        'alt'      => '',
                        'sizes'    => '(max-width: 640px) 100vw, 220px',
                    ));
                } else { ?>
                    <span class="pvb-placeholder">Immagine non disponibile</span>
                <?php } ?>
            </a>
            <div class="pvb-content">
                <h2 class="pvb-title"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a></h2>
                <time class="pvb-date" datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
                <p class="pvb-excerpt"><?php echo esc_html($excerpt); ?></p>
            </div>
        </article>
        <?php
    }
    wp_reset_postdata();
    return ob_get_clean();
}

/* ------------------------------------------------------------------
 * Endpoint AJAX per "Carica altri articoli"
 * Di sola lettura: restituisce solo articoli pubblici, quindi non usa
 * il nonce (che scadrebbe nelle pagine servite dalla cache).
 * ------------------------------------------------------------------ */
function pvb_blog_ajax() {
    $page = isset($_POST['page']) && is_scalar($_POST['page']) ? absint($_POST['page']) : 0;
    if ($page < 2 || $page > 10000) {
        wp_send_json_error(array('message' => 'Pagina non valida.'), 400);
    }

    $per_page = pvb_blog_clean_per_page(isset($_POST['per_page']) ? $_POST['per_page'] : 0);
    $category = pvb_blog_clean_category(isset($_POST['category']) ? $_POST['category'] : '');

    $query = pvb_blog_query($page, $per_page, $category);
    $html  = pvb_blog_cards($query, 0); // nelle pagine successive nessuna immagine "eager"

    wp_send_json_success(array(
        'html'     => $html,
        'has_more' => $page < (int) $query->max_num_pages,
        'count'    => (int) $query->post_count,
    ));
}
add_action('wp_ajax_pvb_blog_more', 'pvb_blog_ajax');        // utenti loggati
add_action('wp_ajax_nopriv_pvb_blog_more', 'pvb_blog_ajax'); // visitatori

/* ------------------------------------------------------------------
 * Stile e script
 * Registrati e accodati con le funzioni di WordPress. La variabile
 * statica evita di aggiungere gli stessi contenuti più volte quando lo
 * shortcode compare più di una volta nella stessa pagina.
 * ------------------------------------------------------------------ */
function pvb_blog_enqueue_assets() {
    static $done = false;
    if ($done) { return; }
    $done = true;

    // --pvb-accent usa currentColor: il contorno del focus resta visibile.
    $css = <<<'CSS'
.pvb-blog{--pvb-accent:currentColor;--pvb-divider:rgba(127,127,127,.35);width:100%}
.pvb-blog,.pvb-blog *{box-sizing:border-box}
.pvb-blog .pvb-card{display:grid;grid-template-columns:220px minmax(0,1fr);gap:28px;align-items:start;margin:0;padding:30px 0;border:0;border-bottom:1px solid var(--pvb-divider);background:transparent;box-shadow:none}
.pvb-blog .pvb-card:first-child{padding-top:0}
.pvb-blog .pvb-image{display:block;width:100%;aspect-ratio:1;overflow:hidden;border-radius:6px;background:rgba(127,127,127,.08)}
.pvb-blog .pvb-image img{display:block;width:100%;height:100%;max-width:none;object-fit:cover;margin:0;border-radius:0}
.pvb-blog .pvb-placeholder{display:flex;align-items:center;justify-content:center;height:100%;padding:20px;text-align:center;font-size:.875rem}
.pvb-blog .pvb-title{margin:0 0 10px;padding:0;font-size:clamp(1.3rem,2.3vw,1.75rem);line-height:1.25;overflow-wrap:anywhere}
.pvb-blog .pvb-title a{color:inherit;text-decoration:none}
.pvb-blog .pvb-title a:hover{text-decoration:underline;text-decoration-color:var(--pvb-accent);text-underline-offset:4px}
.pvb-blog .pvb-date{display:block;font-size:.875rem;line-height:1.5;margin-bottom:12px;opacity:.8}
.pvb-blog .pvb-excerpt{margin:0;line-height:1.65;overflow-wrap:anywhere}
.pvb-blog .pvb-actions{text-align:center;padding-top:30px}
.pvb-blog .pvb-more{appearance:none;display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:12px 24px;border:1px solid currentColor;border-radius:4px;background:transparent;color:inherit;font:inherit;line-height:1.3;cursor:pointer;white-space:normal}
.pvb-blog .pvb-more:hover{border-color:var(--pvb-accent)}
.pvb-blog .pvb-more:disabled{opacity:.6;cursor:wait}
.pvb-blog [hidden]{display:none!important}
.pvb-blog a:focus-visible,.pvb-blog button:focus-visible,.pvb-blog .pvb-title:focus{outline:2px solid var(--pvb-accent);outline-offset:5px}
.pvb-blog .pvb-status{margin:12px 0 0;font-size:.9rem}
@media(max-width:900px){.pvb-blog .pvb-card{grid-template-columns:180px minmax(0,1fr);gap:22px}}
@media(max-width:640px){.pvb-blog .pvb-card{grid-template-columns:minmax(0,1fr);gap:18px;padding:26px 0}.pvb-blog .pvb-image{aspect-ratio:16/9}.pvb-blog .pvb-title{font-size:1.4rem}.pvb-blog .pvb-more{width:100%}}
CSS;

    $js = <<<'JS'
(() => {
    // Inizializza ogni istanza dello shortcode presente nella pagina.
    const init = (root) => {
        if (root.dataset.ready) return;
        root.dataset.ready = '1';
        const button = root.querySelector('.pvb-more');
        const list = root.querySelector('.pvb-list');
        const status = root.querySelector('.pvb-status');
        if (!button || !list || !status) return;
        let page = 1;

        button.addEventListener('click', async () => {
            if (button.disabled) return;
            button.disabled = true;
            button.textContent = 'Caricamento…';
            list.setAttribute('aria-busy', 'true');
            status.textContent = '';
            let failed = false;
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 25000);
            try {
                const body = new URLSearchParams({
                    action: 'pvb_blog_more',
                    page: String(page + 1),
                    per_page: root.dataset.perPage || '',
                    category: root.dataset.category || ''
                });
                const response = await fetch(root.dataset.endpoint, {
                    method: 'POST', credentials: 'same-origin', body, signal: controller.signal
                });
                if (!response.ok) throw new Error('HTTP');
                const result = await response.json();
                if (!result.success || typeof result.data?.html !== 'string') throw new Error('Risposta');
                const fragment = document.createElement('template');
                fragment.innerHTML = result.data.html;
                const firstTitle = fragment.content.querySelector('.pvb-title');
                list.append(fragment.content);
                page++;
                status.textContent = result.data.count
                    ? result.data.count + ' articoli aggiunti.' + (result.data.has_more ? '' : ' Hai raggiunto la fine del blog.')
                    : 'Hai raggiunto la fine del blog.';
                button.hidden = !result.data.has_more;
                // Sposta il focus sul primo articolo nuovo (o sul messaggio finale).
                if (firstTitle) {
                    firstTitle.setAttribute('tabindex', '-1');
                    firstTitle.focus({preventScroll: true});
                } else if (button.hidden) {
                    status.setAttribute('tabindex', '-1');
                    status.focus({preventScroll: true});
                }
            } catch (error) {
                failed = true;
                status.textContent = 'Caricamento non riuscito. Premi nuovamente il pulsante per riprovare.';
            } finally {
                clearTimeout(timeout);
                button.disabled = false;
                button.textContent = 'Carica altri articoli';
                list.removeAttribute('aria-busy');
                // Un pulsante disabilitato perde il focus: dopo un errore lo
                // restituiamo, così chi usa la tastiera non resta "perso".
                if (failed) button.focus();
            }
        });
    };
    document.querySelectorAll('.pvb-blog').forEach(init);
})();
JS;

    wp_register_style('pvb-blog', false, array(), PVB_BLOG_VERSION);
    wp_add_inline_style('pvb-blog', $css);
    wp_enqueue_style('pvb-blog');

    wp_register_script('pvb-blog', false, array(), PVB_BLOG_VERSION, true); // true = nel footer
    wp_add_inline_script('pvb-blog', $js);
    wp_enqueue_script('pvb-blog');
}

/* ------------------------------------------------------------------
 * Shortcode
 * Uso: [pino_blog] oppure [pino_blog per_page="10" category="viaggi"]
 * ------------------------------------------------------------------ */
function pvb_blog_shortcode($atts) {
    $atts = shortcode_atts(array(
        'per_page' => 20,
        'category' => '',
    ), $atts, 'pino_blog');
    $per_page = pvb_blog_clean_per_page($atts['per_page']);
    $category = pvb_blog_clean_category($atts['category']);

    pvb_blog_enqueue_assets();

    $query = pvb_blog_query(1, $per_page, $category);
    $id = wp_unique_id('pvb-blog-');

    // Nella prima pagina solo le prime due immagini sono caricate subito.
    $cards = $query->have_posts() ? pvb_blog_cards($query, 2) : '<p>Nessun articolo disponibile.</p>';

    ob_start();
    ?>
    <section id="<?php echo esc_attr($id); ?>" class="pvb-blog" aria-label="Articoli del blog"
        data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
        data-per-page="<?php echo esc_attr($per_page); ?>"
        data-category="<?php echo esc_attr($category); ?>">
        <div class="pvb-list" id="<?php echo esc_attr($id); ?>-list">
            <?php echo $cards; // HTML già costruito con escaping in pvb_blog_cards() ?>
        </div>
        <div class="pvb-actions">
            <button type="button" class="pvb-more" aria-controls="<?php echo esc_attr($id); ?>-list" <?php echo $query->max_num_pages <= 1 ? 'hidden' : ''; ?>>Carica altri articoli</button>
            <p class="pvb-status" role="status" aria-live="polite" aria-atomic="true"></p>
        </div>
        <noscript><p>Attiva JavaScript per caricare altri articoli oppure <a href="<?php echo esc_url(get_feed_link()); ?>">consulta il feed del blog</a>.</p></noscript>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('pino_blog', 'pvb_blog_shortcode');