# 🚀 Bologna Marathon - API REST Documentation

## 📋 Overview

API RESTful per la gestione del sistema modulare Bologna Marathon. Tutte le API restituiscono JSON.

## 🔑 Base URL

```
http://localhost/sito_modulare/admin/api/
```

## 📦 Response Format

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Operazione completata"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Descrizione errore",
  "errors": { ... }
}
```

---

## 📄 Pages API

**Endpoint**: `/admin/api/pages.php`

### GET - Lista Pagine
```
GET /admin/api/pages.php
GET /admin/api/pages.php?status=published
GET /admin/api/pages.php?search=marathon&page=1&per_page=20
```

**Query Parameters**:
- `status`: published | draft
- `theme`: Nome tema (es: race-marathon)
- `search`: Ricerca in titolo e slug
- `page`: Numero pagina (default: 1)
- `per_page`: Risultati per pagina (default: 20)

### GET - Singola Pagina
```
GET /admin/api/pages.php?id=1
```

### POST - Crea Pagina
```json
POST /admin/api/pages.php

{
  "slug": "nuova-pagina",
  "title": "Nuova Pagina",
  "description": "Descrizione pagina",
  "status": "draft",
  "theme": "race-marathon",
  "layout_config": {},
  "css_variables": {}
}
```

**Required**: `slug`, `title`

### PUT/PATCH - Aggiorna Pagina
```json
PUT /admin/api/pages.php?id=1

{
  "title": "Titolo Aggiornato",
  "status": "published"
}
```

### DELETE - Elimina Pagina
```
DELETE /admin/api/pages.php?id=1
```

### POST - Duplica Pagina
```json
POST /admin/api/pages.php?id=1&action=duplicate

{
  "slug": "pagina-copia"
}
```

---

## 🧩 Modules API

**Endpoint**: `/admin/api/modules.php`

### GET - Lista Moduli Registrati
```
GET /admin/api/modules.php
GET /admin/api/modules.php?is_active=1
```

### GET - Istanze Moduli per Pagina
```
GET /admin/api/modules.php?action=instances&page_id=1
GET /admin/api/modules.php?action=instances&page_id=1&include_inactive=1
```

### GET - Template Globali
```
GET /admin/api/modules.php?action=templates
GET /admin/api/modules.php?action=templates&module_name=menu
```

### POST - Sincronizza Moduli da Filesystem
```
POST /admin/api/modules.php?action=sync
```

### POST - Crea Istanza Modulo
```json
POST /admin/api/modules.php

{
  "page_id": 1,
  "module_name": "hero",
  "instance_name": "hero-main",
  "config": {
    "title": "Bologna Marathon",
    "subtitle": "Corri attraverso la storia"
  },
  "order_index": 0,
  "is_active": true
}
```

**Required**: `module_name`, `instance_name`

### PUT/PATCH - Aggiorna Istanza Modulo
```json
PUT /admin/api/modules.php?id=10

{
  "config": {
    "title": "Nuovo Titolo"
  },
  "order_index": 5
}
```

### DELETE - Elimina Istanza Modulo
```
DELETE /admin/api/modules.php?id=10
```

### POST - Riordina Moduli
```json
POST /admin/api/modules.php?action=reorder

{
  "page_id": 1,
  "order": {
    "10": 0,
    "11": 1,
    "12": 2
  }
}
```

---

## 🎨 Themes API

**Endpoint**: `/admin/api/themes.php`

### GET - Lista Temi
```
GET /admin/api/themes.php
GET /admin/api/themes.php?active_only=1
```

### GET - Singolo Tema
```
GET /admin/api/themes.php?id=1
```

### GET - Tema Default
```
GET /admin/api/themes.php?action=default
```

### POST - Crea Tema
```json
POST /admin/api/themes.php

{
  "name": "Marathon Theme",
  "alias": "marathon",
  "class_name": "race-marathon",
  "is_active": true,
  "is_default": false,
  "primary_color": "#23a8eb",
  "secondary_color": "#1583b9",
  "accent_color": "rgb(34 211 238)",
  "info_color": "#5DADE2",
  "success_color": "#52bd7b",
  "warning_color": "#F39C12",
  "error_color": "#E74C3C",
  "regenerate_css": true
}
```

**Required**: `name`, `alias`

### PUT/PATCH - Aggiorna Tema
```json
PUT /admin/api/themes.php?id=1

{
  "primary_color": "#ff0000",
  "regenerate_css": true
}
```

### DELETE - Elimina Tema
```
DELETE /admin/api/themes.php?id=1
```

### POST - Genera CSS Temi
```
POST /admin/api/themes.php?action=generate-css
```

### POST - Applica Tema a Pagina
```json
POST /admin/api/themes.php?action=apply-to-page

{
  "page_id": 1,
  "theme_alias": "marathon"
}
```

### POST - Imposta Tema Default
```
POST /admin/api/themes.php?action=default&id=1
```

### GET - Esporta Tema
```
GET /admin/api/themes.php?action=export&id=1
```
Scarica file JSON del tema.

### POST - Importa Tema
```json
POST /admin/api/themes.php?action=import

{
  "json": "{ ... }"
}
```

---

## 📊 HTTP Status Codes

- `200 OK` - Richiesta completata con successo
- `201 Created` - Risorsa creata con successo
- `400 Bad Request` - Errore validazione o parametri mancanti
- `401 Unauthorized` - Autenticazione richiesta (quando AUTH_ENABLED)
- `403 Forbidden` - Accesso negato
- `404 Not Found` - Risorsa non trovata
- `405 Method Not Allowed` - Metodo HTTP non supportato
- `422 Unprocessable Entity` - Errore di validazione
- `500 Internal Server Error` - Errore del server

---

## 🔐 Autenticazione (Future)

Quando `AUTH_ENABLED=true` in `.env`, tutte le API richiederanno autenticazione:

```
Authorization: Bearer TOKEN
```

---

## 🧪 Testing

### Con cURL

**Lista Pagine**:
```bash
curl http://localhost/sito_modulare/admin/api/pages.php
```

**Crea Pagina**:
```bash
curl -X POST http://localhost/sito_modulare/admin/api/pages.php \
  -H "Content-Type: application/json" \
  -d '{"slug":"test","title":"Test Page"}'
```

**Sincronizza Moduli**:
```bash
curl -X POST http://localhost/sito_modulare/admin/api/modules.php?action=sync
```

### Con JavaScript (Fetch API)

```javascript
// Lista pagine
const pages = await fetch('/admin/api/pages.php')
  .then(res => res.json());

// Crea pagina
const newPage = await fetch('/admin/api/pages.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    slug: 'nuova-pagina',
    title: 'Nuova Pagina',
    status: 'draft'
  })
}).then(res => res.json());

// Aggiorna pagina
const updated = await fetch('/admin/api/pages.php?id=1', {
  method: 'PUT',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    title: 'Titolo Aggiornato',
    status: 'published'
  })
}).then(res => res.json());
```

---

## 📝 Notes

- Tutte le API supportano CORS
- Response format sempre JSON
- Date in formato ISO 8601
- Parametri GET per filtri e paginazione
- Body JSON per POST/PUT/PATCH
- ID risorsa sempre in query string `?id=X`
- Azioni speciali con `?action=X`

---

**Bologna Marathon API v1.0** 🏃‍♂️

