# 🔧 Troubleshooting Modulo Highlights

## Problema: "Vedo una sola slide fullscreen ma non riesco a scorrere"

### Causa principale
La libreria **Swiper JS non viene caricata** prima del codice del modulo, oppure c'è un errore nell'inizializzazione.

---

## ✅ SOLUZIONE RAPIDA

### 1. Test il modulo standalone
Apri nel browser:
```
http://localhost/sito_modulare/test-highlights.html
```

Questo test carica Swiper direttamente dal CDN senza passare per il sistema PHP.

**Se funziona qui:** Il problema è nel caricamento vendor assets del sistema PHP  
**Se NON funziona:** Il problema è nel CSS o nel JavaScript del modulo

### 2. Test il modulo con sistema PHP
Apri nel browser:
```
http://localhost/sito_modulare/test-highlights.php
```

Questo test mostra esattamente quali assets vengono caricati dal sistema PHP.

**Verifica:**
- ✅ Vendor CSS deve includere: `https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css`
- ✅ Vendor JS deve includere: `https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js`
- ✅ Module CSS deve includere: `modules/highlights/highlights.css`
- ✅ Module JS deve includere: `modules/highlights/highlights.js`

---

## 🐛 PROBLEMI COMUNI E SOLUZIONI

### Problema 1: Swiper library non caricata
**Sintomo:** Console mostra `Swiper is not defined`

**Soluzione:**
```bash
# Verifica che module.json abbia i vendor corretti
cat modules/highlights/module.json | grep -A 5 vendors
```

Deve contenere:
```json
"vendors": [
  {
    "css": ["https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"],
    "js": ["https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"]
  }
]
```

### Problema 2: JavaScript caricato prima di Swiper
**Sintomo:** Errore `Swiper is not defined` anche se Swiper è nel DOM

**Soluzione:** 
Il sistema PHP carica i vendor JS **prima** dei module JS, quindi dovrebbe funzionare.
Verifica l'ordine nel sorgente HTML della pagina:
1. Prima: `<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>`
2. Dopo: `<script src="modules/highlights/highlights.js"></script>`

### Problema 3: CSS width fisso impedisce responsive
**Sintomo:** Slide troppo grandi o troppo piccole, breakpoint non funzionano

**Soluzione:** ✅ GIÀ CORRETTO nella v1.1.0
- CSS NON deve avere `width` fisso sulle card
- Swiper calcola automaticamente le dimensioni basandosi su `slidesPerView`

### Problema 4: Bottoni navigazione non visibili
**Sintomo:** Non vedo le frecce prev/next

**Soluzione:**
Ispeziona con DevTools e verifica:
```css
.highlights_btn {
  z-index: 10; /* Deve essere sopra le slide */
  pointer-events: all; /* Deve essere cliccabile */
  background: var(--bg-glass); /* Deve avere sfondo visibile */
}
```

### Problema 5: Module.json path sbagliati
**Sintomo:** 404 nel network tab per `highlights/highlights.js`

**Soluzione:** ✅ GIÀ CORRETTO
Il path corretto nel `module.json` deve essere:
```json
"assets": {
  "css": ["modules/highlights/highlights.css"],
  "js": ["modules/highlights/highlights.js"]
}
```

---

## 🔍 DEBUG CHECKLIST

### Apri DevTools Console e verifica:

```javascript
// 1. Swiper disponibile?
console.log('Swiper:', typeof Swiper !== 'undefined' ? '✅' : '❌');

// 2. Classe Highlights disponibile?
console.log('Highlights:', typeof window.Highlights !== 'undefined' ? '✅' : '❌');

// 3. Elemento DOM presente?
console.log('DOM .highlights:', document.querySelector('.highlights') ? '✅' : '❌');

// 4. Swiper inizializzato?
const swiperEl = document.querySelector('.highlights_swiper');
console.log('Swiper instance:', swiperEl && swiperEl.swiper ? '✅' : '❌');

// 5. Configurazione Swiper
if (swiperEl && swiperEl.swiper) {
  console.log('SlidesPerView:', swiperEl.swiper.params.slidesPerView);
  console.log('Totale slide:', swiperEl.swiper.slides.length);
  console.log('Space between:', swiperEl.swiper.params.spaceBetween);
}
```

### Verifica Network Tab:

1. Cerca `swiper-bundle.min.js` → Deve essere Status 200
2. Cerca `highlights.js` → Deve essere Status 200
3. Cerca `highlights.css` → Deve essere Status 200
4. Ordine di caricamento: Swiper **prima** di highlights.js

---

## 🚀 CONFIGURAZIONE CORRETTA

### module.json ✅
```json
{
  "name": "highlights",
  "version": "1.1.0",
  "assets": {
    "css": ["modules/highlights/highlights.css"],
    "js": ["modules/highlights/highlights.js"],
    "vendors": [
      {
        "css": ["https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"],
        "js": ["https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"]
      }
    ]
  }
}
```

### highlights.js ✅
```javascript
slidesPerView: 1.5, // Mobile default
breakpoints: {
  480: { slidesPerView: 2 },
  640: { slidesPerView: 2.5 },
  768: { slidesPerView: 3 },
  1024: { slidesPerView: 4 },
  1280: { slidesPerView: 5 },
  1536: { slidesPerView: 6 }
}
```

### highlights.css ✅
```css
/* NO WIDTH FISSO */
.highlight_card {
  width: 100%; /* Lascia che Swiper calcoli */
}

.highlights_swiper .swiper-slide {
  /* NO WIDTH QUI - Swiper gestisce tutto */
}
```

---

## 📞 SUPPORTO

Se dopo aver seguito tutti i passaggi il problema persiste:

1. Condividi l'output della console (DevTools → Console)
2. Condividi screenshot del Network tab
3. Condividi il risultato di `test-highlights.php`
4. Verifica la versione del modulo: **v1.1.0**

---

**Modulo Highlights v1.1.0** - Fix Swiper Configuration  
Ultimo aggiornamento: Ottobre 2025

