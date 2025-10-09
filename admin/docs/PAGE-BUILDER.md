# 🎨 Page Builder - Guida Completa

## 🎯 Panoramica
Il Page Builder è il sistema di amministrazione per la creazione e gestione di pagine modulari. Permette drag & drop, configurazione real-time e gestione modelli globali.

## 🚀 Funzionalità Principali

### 📄 Gestione Pagine
- **Creazione**: Nuove pagine con template predefiniti
- **Duplicazione**: Copia pagine esistenti con tutti i moduli
- **Eliminazione**: Rimozione sicura con conferma
- **Status**: Pubblicazione/draft con toggle rapido

### 🧩 Gestione Moduli
- **Drag & Drop**: Ordinamento moduli con SortableJS
- **Configurazione**: UI Schema dinamico per ogni modulo
- **Anteprima**: Real-time preview con rendering server-side
- **Modelli Globali**: Sistema template condivisi tra pagine

### 🎨 Personalizzazione
- **Temi**: Applicazione temi dinamici per pagina
- **CSS Variables**: Override variabili CSS per pagina
- **Layout**: Configurazione responsive e grid system

## 🔧 Architettura Tecnica

### File Principali
- `admin/page-builder.php` - Interfaccia principale
- `admin/page-builder.css` - Stili specifici builder
- `admin/page-builder.js` - JavaScript funzionalità

### Database
- `pages` - Pagine del sito
- `module_instances` - Istanze moduli per pagina
- `modules_registry` - Moduli disponibili

## 📋 Workflow Utente

### 1. Creazione Pagina
```
1. Click "🆕 Nuova Pagina"
2. Inserisci titolo e slug
3. Scegli template base
4. Configura tema e variabili CSS
5. Click "Crea Pagina"
```

### 2. Aggiunta Moduli
```
1. Drag modulo da pannello sinistro
2. Drop nella canvas centrale
3. Auto-save con configurazione default
4. Configura parametri specifici
5. Salva configurazione
```

### 3. Modelli Globali
```
1. Configura modulo normalmente
2. Click "Salva come Modello Globale"
3. Inserisci nome modello
4. Modello disponibile per altre pagine
5. Applica modello da dropdown
```

## 🎯 Sistema Modelli Globali

### Concetto
I modelli globali sono istanze master di moduli che possono essere:
- **Condivisi** tra più pagine
- **Modificati** una volta e applicati ovunque
- **Staccati** per personalizzazione locale

### Database Schema
```sql
-- Colonne aggiunte a module_instances
is_template BOOLEAN DEFAULT FALSE
template_name VARCHAR(100)
template_instance_id INT NULL
```

### Workflow
1. **Creazione**: Salva modulo come template globale
2. **Applicazione**: Seleziona template da dropdown
3. **Modifica**: Modifica template master
4. **Stacco**: Converti in istanza locale

## 🔧 Fix Applicati

### Template Literal Bug
**Problema**: `ReferenceError: temp is not defined`
**Soluzione**: Conversione da template literal a concatenazione JavaScript

### Ordinamento Moduli
**Problema**: Stage ≠ Preview ≠ Pagina Reale
**Soluzione**: Auto-save `order_index` dopo ogni modifica

### Percorsi Immagini
**Problema**: Immagini non visualizzate in anteprima
**Soluzione**: MutationObserver per correzione automatica percorsi

### Doppie Chiamate
**Problema**: Event listeners duplicati su template-select
**Soluzione**: Esclusione `#template-select` da `attachConfigListeners`

## 🧪 Testing

### Test Funzionalità
1. **Creazione pagina**: Verifica inserimento database
2. **Duplicazione**: Controlla copia moduli e configurazioni
3. **Drag & Drop**: Test ordinamento e persistenza
4. **Modelli globali**: Verifica creazione e applicazione
5. **Anteprima**: Controlla rendering real-time

### Test Browser
- Chrome/Edge (test principale)
- Firefox (compatibilità)
- Safari (iOS/macOS)
- Mobile browsers (responsive)

## 🚨 Troubleshooting

### Problemi Comuni

#### Modulo non appare in anteprima
**Causa**: Percorso immagine relativo
**Soluzione**: MutationObserver corregge automaticamente

#### Configurazione non salvata
**Causa**: Event listeners non attaccati
**Soluzione**: Chiamare `attachConfigListeners()` dopo rendering

#### Modello globale non applicato
**Causa**: Template literal bug
**Soluzione**: Verificare concatenazione JavaScript corretta

#### Ordinamento non persistente
**Causa**: `updateOrder()` non chiamato
**Soluzione**: Aggiungere chiamata dopo ogni modifica

## 📚 Riferimenti

### File Correlati
- `core/ModuleRenderer.php` - Rendering moduli
- `modules/*/module.json` - Manifest moduli
- `database/schema.sql` - Schema database

### Guide Correlate
- `modules/docs/DEVELOPMENT-GUIDE.md` - Sviluppo moduli
- `docs/THEME-SYSTEM-FINAL.md` - Sistema temi
- `docs/LAYOUT-SYSTEM.md` - Sistema layout

---

**Page Builder - Sistema Modulare Bologna Marathon** 🎨

*Guida completa per amministratori e sviluppatori*
