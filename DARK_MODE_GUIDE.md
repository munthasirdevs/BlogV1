# Dark Mode Feature Guide

## Overview

The Masterclass Blog Platform now includes a fully-functional dark mode feature that:
- Persists user preference across sessions
- Respects system preference (auto-detect)
- Provides a toggle button on all pages
- Smoothly transitions between light and dark themes

## Features

### 🌙 Toggle Button
- **Location**: Top navigation bar (next to search/auth buttons)
- **Icon**: Moon icon (light mode) / Sun icon (dark mode)
- **Accessibility**: Full keyboard support and ARIA labels

### 💾 Preference Storage
- Stored in `localStorage` as `theme`
- Values: `'light'` or `'dark'`
- Automatically loaded on page visit

### 🖥️ System Preference Detection
- Automatically detects `prefers-color-scheme` media query
- Falls back to system preference if no user setting exists
- Updates icon accordingly

### 🎨 Styled Components
All UI components are styled for dark mode:
- ✅ Cards and backgrounds
- ✅ Text and headings
- ✅ Buttons and forms
- ✅ Navigation elements
- ✅ Dropdowns and modals
- ✅ Badges and avatars
- ✅ Blog content (prose)
- ✅ Comments section
- ✅ Admin dashboard

## Usage

### Toggle Dark Mode

**Click the toggle button** in the navigation bar:
```javascript
// Programmatically toggle
UI.toggleDarkMode();

// Check current mode
const isDark = UI.isDarkMode();

// Initialize (auto-detect)
UI.initDarkMode();
```

### Listen for Theme Changes

```javascript
window.addEventListener('themechange', (e) => {
    console.log('Theme changed:', e.detail.isDark);
});
```

## CSS Implementation

### Tailwind Configuration
```javascript
tailwind.config = {
    darkMode: 'class',
    // ... other config
}
```

### HTML Class Strategy
Dark mode is activated by adding `class="dark"` to the `<html>` element:

```html
<!-- Light mode (default) -->
<html lang="en">

<!-- Dark mode -->
<html lang="en" class="dark">
```

### CSS Customization
All dark mode styles use the `html.dark` selector:

```css
/* Light mode */
body {
    @apply bg-gray-50 text-gray-900;
}

/* Dark mode */
html.dark body {
    @apply bg-gray-900 text-gray-100;
}

html.dark .card {
    @apply bg-gray-800 border-gray-700;
}
```

## Color Palette

### Light Mode
- Background: `bg-gray-50`
- Cards: `bg-white`
- Text: `text-gray-900`
- Muted: `text-gray-500`
- Borders: `border-gray-200`

### Dark Mode
- Background: `bg-gray-900`
- Cards: `bg-gray-800`
- Text: `text-gray-100`
- Muted: `text-gray-400`
- Borders: `border-gray-700`

### Accent Colors
- Primary: `blue-600` (light) → `blue-400` (dark)
- Success: `green-600` (light) → `green-400` (dark)
- Warning: `yellow-600` (light) → `yellow-400` (dark)
- Danger: `red-600` (light) → `red-400` (dark)

## Files Updated

### Core Files
- `frontend/css/app.css` - All dark mode CSS styles
- `frontend/js/ui.js` - Dark mode JavaScript logic

### Pages
- `frontend/index.html` - Home page with toggle
- `frontend/pages/login.html` - Login page
- `frontend/pages/register.html` - Registration page
- `frontend/pages/blog-list.html` - Articles listing
- `frontend/pages/blog-detail.html` - Single article view
- `frontend/pages/create-post.html` - Post creation form
- `frontend/pages/admin/dashboard.html` - Admin panel

## Testing

### Manual Testing
1. Open any page
2. Click the dark mode toggle
3. Verify all components change colors
4. Refresh page - preference should persist
5. Check toggle icon changes (moon ↔ sun)

### Browser DevTools
1. Open DevTools → Application → Local Storage
2. Check `theme` key value
3. Delete key to reset to system preference

### System Preference
Change your OS theme setting:
- **Windows**: Settings → Personalization → Colors → Choose your default app mode
- **macOS**: System Preferences → General → Appearance
- **Linux**: Depends on desktop environment

## Accessibility

### Keyboard Navigation
- Toggle button is focusable with `Tab`
- Activated with `Enter` or `Space`
- Visible focus ring for visibility

### Screen Readers
- Toggle has `aria-label` attribute
- Dynamically updates: "Switch to dark/light mode"
- Icon changes are announced

### Contrast Ratios
All text meets WCAG AA standards:
- Light mode: 4.5:1 minimum
- Dark mode: 4.5:1 minimum

## Performance

### No Flash of Wrong Theme
Inline script in `<head>` runs before render:
```html
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
```

### Smooth Transitions
CSS transitions on color changes:
```css
.transition-colors {
    transition: background-color 0.2s, color 0.2s;
}
```

## Customization

### Add Dark Mode to New Pages

1. **Add Tailwind config** in `<head>`:
```html
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: { /* ... */ }
    }
</script>
```

2. **Add prevention script** in `<head>`:
```html
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
```

3. **Add toggle button** in navigation:
```html
<button data-theme-toggle onclick="UI.toggleDarkMode()" 
        class="dark-mode-toggle" aria-label="Toggle dark mode">
    <svg><!-- icon --></svg>
</button>
```

4. **Initialize** in script:
```javascript
document.addEventListener('DOMContentLoaded', () => {
    UI.initDarkMode();
    // ... other init code
});
```

5. **Add dark mode classes** to elements:
```html
<body class="bg-gray-50 dark:bg-gray-900">
<header class="bg-white dark:bg-gray-800">
<h1 class="text-gray-900 dark:text-gray-100">
```

## Troubleshooting

### Issue: Toggle doesn't work
**Solution**: Check `ui.js` is loaded before toggle button

### Issue: Colors don't change
**Solution**: Verify `darkMode: 'class'` in Tailwind config

### Issue: Preference not saved
**Solution**: Check browser allows localStorage

### Issue: Flash of light mode
**Solution**: Ensure prevention script is in `<head>` before CSS

### Issue: Some elements not styled
**Solution**: Add `html.dark` styles for missing components in `app.css`

## Browser Support

| Browser | Version | Support |
|---------|---------|---------|
| Chrome | 76+ | ✅ Full |
| Firefox | 67+ | ✅ Full |
| Safari | 12.1+ | ✅ Full |
| Edge | 79+ | ✅ Full |
| Opera | 62+ | ✅ Full |

## Future Enhancements

- [ ] Auto-switch based on time of day
- [ ] Multiple dark themes (dim, dark, midnight)
- [ ] Per-page theme override
- [ ] Animation on theme switch
- [ ] Theme preview modal
- [ ] Keyboard shortcut (e.g., `Ctrl+Shift+D`)

---

**Version**: 1.0.0  
**Last Updated**: 2026-04-01  
**Status**: ✅ Production Ready
