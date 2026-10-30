# Pino Blog List

Shortcode WordPress `[pino_blog]` che mostra gli articoli del blog con pulsante "Carica altri articoli".

## Uso

    [pino_blog]
    [pino_blog per_page="10" category="slug-categoria"]

## Installazione

1. Scarica `pino-blog-list.zip` dall'[ultima release](https://github.com/pinovolpe/pino-blog-list/releases/latest) (non "Source code").
2. In WordPress: Plugin → Aggiungi nuovo → Carica plugin.
3. Attiva il plugin e inserisci `[pino_blog]` in una pagina.

## Demo

Lo shortcode è in uso su [pinovolpe.com/blog](https://www.pinovolpe.com/blog/).

| Attributo | Predefinito | Descrizione |
|---|---|---|
| per_page | 20 | Articoli per pagina (1-50) |
| category | vuoto | Slug della categoria |

## Requisiti
WordPress 6.0+ · PHP 7.4+

## Accessibilità
Navigazione da tastiera con focus visibile, annunci per lettori di schermo,
alternativa senza JavaScript tramite link al feed.

## Sicurezza
Endpoint pubblico in sola lettura, solo articoli pubblici e senza password,
parametri validati ed escaping dell'output.

## Note sullo sviluppo
Sviluppato con il supporto di strumenti di AI generativa; il codice è stato
letto, verificato e provato in staging.

## Prossimi passi

- Spostare CSS e JavaScript in file separati
- Rendere i testi traducibili con `__()`
- Valutare l'API REST al posto di `admin-ajax.php`

## Licenza
GPL-2.0-or-later
