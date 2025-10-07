# Hero Module

Hero principale con CTA e statistiche configurabili.

## Campi principali

- `height`: altezza minima (es. `min(100vh, 860px)`).
- `eyebrow`: oggetto con `icon` (classi Font Awesome) e `label`.
- `title`: testo H1.
- `subtitle`: sottotitolo in maiuscolo.
- `description`: paragrafo descrittivo (testo semplice, va in `nl2br`).
- `background`: oggetto con `image`, `position`, `size`, `overlay`, `overlay_opacity`.
- `actions`: array di CTA. Ogni elemento può:
  - contenere i parametri del modulo `button` (`text`, `href`, `variant`, ecc.),
  - oppure `module.name` e `module.config` per annidare un modulo personalizzato.
- `stats`: array di metriche con `value`, `label`, `icon`.

## Esempio

```json
{
  "title": "Thermal Bologna Marathon",
  "actions": [
    { "text": "Scopri le gare", "href": "#gare", "variant": "primary" }
  ],
  "stats": [
    { "value": "12K+", "label": "Atleti attesi" }
  ]
}
```
