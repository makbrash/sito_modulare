# Highlights Module

Modulo vetrina per riassumere i punti di forza dell'evento.

## Configurazione

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `eyebrow` | string | Etichetta superiore (es. "Perché Bologna"). |
| `title` | string | Titolo principale in maiuscolo. |
| `subtitle` | string | Testo descrittivo. |
| `theme` | enum (`dark\|light`) | Palette di riferimento. |
| `items` | array | Lista di card. Ogni item supporta `icon`, `title`, `description`, `meta`. |
| `cta` | object | Configurazione passata al modulo `button` (o `module` personalizzato). |

Esempio JSON:

```json
{
  "eyebrow": "Perché Bologna",
  "title": "Una maratona, mille motivi per esserci",
  "items": [
    { "icon": "fa-solid fa-city", "title": "Cuore storico", "description": "..." }
  ],
  "cta": {
    "text": "Scopri il programma",
    "href": "#programma",
    "variant": "ghost"
  }
}
```
