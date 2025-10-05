# Cursor Rules – Bologna Marathon CMS

1. **Rispetta i manifest**: ogni nuovo modulo deve avere `module.json` aggiornato con `default_config` e `ui_schema`.
2. **Admin parity**: se modifichi file sotto `admin/` replica sempre la modifica in `build/admin/` (stessi percorsi).
3. **Niente hardcoding colori**: usa le variabili definite in `assets/css/core/variables.css`.
4. **API Page Builder**: aggiungi nuovi endpoint solo in `admin/api/page_builder.php` e documentali nel README.
5. **UI Admin**: non utilizzare pre-processori o nesting CSS (`&`). Layout responsive gestito via classi dedicate.
6. **Test minimi**: prima del commit esegui `php -l` sui file PHP toccati e `npm run release` se cambi pipeline o asset.
7. **Documentazione**: aggiorna README e README del modulo quando introduci nuove feature o campi di configurazione.
8. **Compatibilità cloud**: evita dipendenze runtime Node/Composer in produzione; tutto deve funzionare con PHP + MySQL.
9. **Naming istanze**: mantieni unici i nomi modulo per pagina; usa funzioni helper già presenti.
10. **Versionamento moduli**: incrementa `version` nel manifest quando cambi il comportamento di un modulo esistente.