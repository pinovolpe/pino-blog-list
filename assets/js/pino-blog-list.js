(() => {
    'use strict';

    const { __, sprintf } = wp.i18n;

    // Initialize every shortcode instance on the page.
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
            button.textContent = __('Caricamento…', 'pino-blog-list');
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
                    method: 'POST',
                    credentials: 'same-origin',
                    body,
                    signal: controller.signal
                });

                if (!response.ok) throw new Error('HTTP');

                const result = await response.json();
                if (!result.success || typeof result.data?.html !== 'string') {
                    throw new Error('Invalid response');
                }

                const fragment = document.createElement('template');
                fragment.innerHTML = result.data.html;
                const firstTitle = fragment.content.querySelector('.pvb-title');
                list.append(fragment.content);
                page++;

                if (result.data.count) {
                    status.textContent = sprintf(
                        __('%d articoli aggiunti.', 'pino-blog-list'),
                        result.data.count
                    );
                    if (!result.data.has_more) {
                        status.textContent += ' ' + __('Hai raggiunto la fine del blog.', 'pino-blog-list');
                    }
                } else {
                    status.textContent = __('Hai raggiunto la fine del blog.', 'pino-blog-list');
                }

                button.hidden = !result.data.has_more;

                // Move focus to the first new post, or to the final status message.
                if (firstTitle) {
                    firstTitle.setAttribute('tabindex', '-1');
                    firstTitle.focus({ preventScroll: true });
                } else if (button.hidden) {
                    status.setAttribute('tabindex', '-1');
                    status.focus({ preventScroll: true });
                }
            } catch (error) {
                failed = true;
                status.textContent = __('Caricamento non riuscito. Premi nuovamente il pulsante per riprovare.', 'pino-blog-list');
            } finally {
                clearTimeout(timeout);
                button.disabled = false;
                button.textContent = __('Carica altri articoli', 'pino-blog-list');
                list.removeAttribute('aria-busy');

                // Restore focus after an error because a disabled button can lose it.
                if (failed) button.focus();
            }
        });
    };

    document.querySelectorAll('.pvb-blog').forEach(init);
})();
