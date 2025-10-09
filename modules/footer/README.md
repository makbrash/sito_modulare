# Modulo Footer

Footer modulare super responsive per Bologna Marathon.

## Caratteristiche

- **Design Moderno**: Layout pulito e professionale
- **Super Responsive**: Adattamento perfetto a tutti i dispositivi
- **CSS Variables**: Supporto completo per temi dinamici
- **BEM Methodology**: Nomenclatura consistente
- **Font Awesome**: Icone professionali
- **Theme Colors**: Colori dinamici per ogni gara

## Struttura

Il footer è diviso in 4 sezioni principali:

1. **Brand**: Logo, titolo, descrizione e info evento
2. **Gare**: Links a tutte le gare con colori tema
3. **Informazioni**: Links utili al sito
4. **Contatti**: Email, telefono, indirizzo e social

## Configurazione

### Configurazione Base
```php
$config = [
    'logo' => 'assets/images/logo-bologna-marathon.svg',
    'title' => 'THERMAL <span class="footer__title-highlight">BOLOGNA</span> MARATHON',
    'description' => 'Tre percorsi unici...',
    'date' => '2 Marzo 2026',
    'location' => 'Bologna, Italia'
];
```

### Gare
Array configurabile con classi tema:
```php
'races' => [
    ['label' => 'Maratona 42K', 'url' => '#maratona', 'class' => 'theme-marathon'],
    ['label' => '30K Portici', 'url' => '#portici', 'class' => 'theme-portici'],
    // ...
]
```

### Contatti
```php
'email' => 'info@bolognamarathon.run',
'phone' => '+39 051 123 4567',
'address' => 'Via Indipendenza, 8',
'city' => '40121 Bologna (BO)'
```

### Social
```php
'social_links' => [
    ['icon' => 'fab fa-facebook-f', 'url' => '#', 'label' => 'Facebook'],
    ['icon' => 'fab fa-instagram', 'url' => '#', 'label' => 'Instagram'],
    // ...
]
```

## Responsive Design

### Desktop (>1024px)
- Layout a 4 colonne: Brand (2fr) + Gare (1fr) + Info (1fr) + Contatti (1.5fr)
- Spaziatura ampia e confortevole

### Tablet (768px - 1024px)
- Layout a 3 colonne
- Brand occupa intera larghezza

### Mobile Large (480px - 768px)
- Layout a 2 colonne
- Brand occupa intera larghezza

### Mobile (< 480px)
- Layout a 1 colonna
- Tutto impilato verticalmente
- Social icons ridotti

## Theme Support

Il footer supporta i colori dinamici delle gare tramite classi tema:

```css
.footer__link.theme-marathon:hover { color: #23a8eb; }
.footer__link.theme-portici:hover { color: #dc335e; }
.footer__link.theme-run-tune-up:hover { color: #cbdf44; }
.footer__link.theme-5k:hover { color: #ff6b35; }
.footer__link.theme-kidsrun:hover { color: #007b5f; }
```

## Accessibilità

- **ARIA Labels**: Tutti i link social hanno label descrittivi
- **Semantic HTML**: Struttura semantica corretta
- **Keyboard Navigation**: Navigazione da tastiera completa
- **Screen Reader**: Compatibile con screen reader

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- iOS Safari
- Chrome Mobile

## Performance

- **CSS Puro**: Nessuna dipendenza JavaScript
- **Font Awesome**: Caricato da CDN
- **Ottimizzato**: CSS minimizzato in produzione
- **Lightweight**: ~3KB gzipped

## Esempi

### Footer Standard
```php
echo $renderer->renderModule('footer', []);
```

### Footer Personalizzato
```php
echo $renderer->renderModule('footer', [
    'title' => 'BOLOGNA <strong>MARATHON</strong>',
    'email' => 'custom@email.com',
    'phone' => '+39 333 123 4567'
]);
```

## Troubleshooting

### Icone non appaiono
- Verifica che Font Awesome sia caricato
- Controlla che le classi icone siano corrette

### Layout rotto su mobile
- Controlla che il CSS sia caricato correttamente
- Verifica i breakpoints nel CSS

### Colori tema non funzionano
- Verifica che colors.css sia caricato
- Controlla che le classi tema siano corrette


