# Pino Blog List

Shortcode WordPress `[pino_blog]` che mostra gli articoli del blog con pulsante "Carica altri articoli".

## Uso

    [pino_blog]
    [pino_blog per_page="10" category="slug-categoria"]

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

## Licenza
GPL-2.0-or-later