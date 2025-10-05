# Event Schedule Module

Timeline dei tre giorni di manifestazione.

## Campi supportati

- `eyebrow` — stringa breve in alto.
- `title` — titolo della sezione.
- `subtitle` — testo introduttivo.
- `days` — array di giornate. Ogni elemento accetta `label`, `date` e `events`.
  - `events` — array di elementi con `time`, `title`, `location`, `description`.
- `cta` — configurazione inoltrata al modulo `button` oppure a un modulo personalizzato tramite `{ "module": { "name": "", "config": {} } }`.

Esempio:

```json
{
  "days": [
    {
      "label": "Sabato",
      "events": [
        { "time": "09:30", "title": "Family Run" }
      ]
    }
  ],
  "cta": {
    "text": "Scarica il programma",
    "href": "#programma",
    "icon": "fa-solid fa-file-arrow-down"
  }
}
```
