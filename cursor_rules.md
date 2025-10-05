# Cursor Rules · Bologna Marathon CMS

Queste regole valgono per chi utilizza Cursor (o editor simili) per lavorare sul progetto.

## Branch & Commit
- Usa branch descrittivi: `feature/<nome>`, `fix/<tema>` o `docs/<argomento>`.
- Commit con convenzioni semantiche (`feat:`, `fix:`, `docs:`, `refactor:`). Evita commit generici.
- Ogni commit deve includere solo modifiche coerenti; niente refactor mescolati a feature.

## Coding style
- PHP: PSR-12, short array `[]`, niente logica in viste dei moduli oltre al rendering.
- JS: ES2018, niente optional chaining nei bundle pubblici (ok nell'admin). Struttura i file come moduli separati in `assets/js` o `admin/assets/js`.
- CSS: utilizza esclusivamente CSS vanilla + custom properties. Vietato il nesting tipo `&`. Le palette sono definite in `assets/css/core/variables.css`.

## Moduli
- Non modificare il modulo `menu` esistente: crea varianti in nuove cartelle.
- Ogni nuovo modulo deve avere `module.json` completo (slug, path, css_class, default_config, aliases).
- Includi un `README.md` nel modulo se introduce configurazioni non banali (serve ai futuri LLM).
- Quando un modulo dipende da librerie esterne, documenta la sorgente e la licenza nel README del modulo.

## Admin & Builder
- Mantieni l'estetica coerente con `admin/assets/css/admin.css`.
- Qualsiasi nuova azione deve avere messaggi di feedback (flash) e usare i helper in `admin/includes/`.
- Per nuove API interne, aggiungi helper riutilizzabili invece di scrivere query raw sparpagliate.

## Documentazione
- Aggiorna `README.md` e file di modulo quando introduci o rimuovi funzionalità.
- Logica complessa → aggiungi commenti PHPDoc.

## QA
- Prima di aprire una PR esegui almeno:
  - `php -l` sui file PHP toccati.
  - Comandi npm rilevanti (`npm run css:build`, `npm run js:build`, `npm run release` se cambi asset/build).
- Non committare file generati (`build/`) salvo richieste esplicite.

Seguendo queste linee guida manteniamo il sistema modulare coerente, pronto per automazioni future e facilmente estendibile.
