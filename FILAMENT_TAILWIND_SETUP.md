# FilamentPHP + Tailwind CSS Custom Styling Setup Guide

## Problem Statement

When adding custom Tailwind CSS classes to FilamentPHP custom pages:

- **Without custom CSS**: Default Filament pages work fine, but custom Tailwind classes don't apply to custom pages
- **With custom CSS (wrong setup)**: Custom pages work, but default Filament resources lose their styling (badges, alerts, dropdowns break)

## Root Cause

FilamentPHP uses **Tailwind CSS v3** internally. When you try to add Tailwind v4 or load CSS incorrectly, it creates conflicts between Filament's built-in styles and your custom styles.

## Complete Solution

### Step 1: Install Required Dependencies

```bash
# Uninstall Tailwind v4 if you have it
npm uninstall tailwindcss @tailwindcss/vite

# Install Tailwind CSS v3 and required plugins
npm install -D tailwindcss@^3 postcss autoprefixer
npm install -D @tailwindcss/forms @tailwindcss/typography
```

### Step 2: Create `tailwind.config.js`

Create a `tailwind.config.js` file in your project root that extends Filament's preset:

```javascript
import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
}
```

**Why this works**: This configuration uses Filament's Tailwind preset, ensuring your custom pages and Filament's default pages use the same Tailwind configuration and plugins.

### Step 3: Create `postcss.config.js`

Create a `postcss.config.js` file in your project root:

```javascript
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
}
```

### Step 4: Update `resources/css/app.css`

Your CSS file should use Tailwind v3 syntax:

```css
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';
```

**Important**: Do NOT use Tailwind v4 syntax like:
```css
/* ❌ Don't use this */
@import 'tailwindcss';
```

### Step 5: Configure `vite.config.js`

Your Vite config should be simple - let PostCSS handle Tailwind:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

**Important**: Do NOT add the `@tailwindcss/vite` plugin here.

### Step 6: Register CSS with Filament Panel

Update your `app/Providers/Filament/AdminPanelProvider.php`:

```php
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
// ... other imports

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/app.css')  // ← Add this line
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            // ... rest of your configuration
    }
}
```

**Critical**: The `->viteTheme('resources/css/app.css')` line tells Filament to load your compiled CSS on all admin panel pages.

### Step 7: Build Assets

```bash
# Build for production
npm run build

# Or run dev server for hot reload during development
npm run dev
```

### Step 8: Clear Caches

```bash
php artisan filament:cache-components
php artisan view:clear
php artisan config:clear
```

### Step 9: Hard Refresh Browser

Press `Cmd+Shift+R` (Mac) or `Ctrl+Shift+F5` (Windows/Linux) to hard refresh your browser.

---

## How It Works

### The Flow:

1. **Tailwind Config**: Your `tailwind.config.js` uses Filament's preset, ensuring compatibility
2. **Content Scanning**: Tailwind scans your Filament PHP files and Blade templates for class usage
3. **PostCSS Processing**: `app.css` is processed by PostCSS/Tailwind, generating all needed utility classes
4. **Vite Compilation**: Vite compiles the CSS file into `public/build/assets/app-[hash].css`
5. **Filament Loading**: The `viteTheme()` method tells Filament to include this compiled CSS on all pages

### Why This Works:

- ✅ **Same Tailwind version**: Everything uses Tailwind v3
- ✅ **Same configuration**: Both Filament and your custom pages share the same config
- ✅ **Proper loading**: Filament knows to load your CSS via `viteTheme()`
- ✅ **No conflicts**: Single source of truth for all Tailwind utilities

---

## Common Mistakes to Avoid

### ❌ Mistake 1: Using Tailwind v4
```javascript
// Don't do this
npm install -D tailwindcss@^4 @tailwindcss/vite
```

**Why**: FilamentPHP uses v3, mixing versions causes conflicts.

### ❌ Mistake 2: Not Using Filament's Preset
```javascript
// Don't do this
export default {
    content: ['./resources/**/*.blade.php'],
    // Missing presets: [preset]
}
```

**Why**: You'll miss Filament's custom Tailwind configuration and plugins.

### ❌ Mistake 3: Not Registering with viteTheme()
```php
// Don't forget this
->viteTheme('resources/css/app.css')
```

**Why**: Your CSS won't be loaded on Filament pages, even if it's compiled.

### ❌ Mistake 4: Using @vite in Blade Templates
```blade
{{-- Don't do this on custom Filament pages --}}
@vite('resources/css/app.css')

<x-filament-panels::page>
    <!-- content -->
</x-filament-panels::page>
```

**Why**: Use `viteTheme()` in the panel provider instead. It's the proper Filament way.

---

## File Checklist

After setup, you should have these files:

```
your-project/
├── tailwind.config.js          ← Extends Filament preset
├── postcss.config.js           ← PostCSS config
├── vite.config.js              ← Vite config (no Tailwind v4 plugin)
├── package.json                ← Has tailwindcss@^3, @tailwindcss/forms, @tailwindcss/typography
├── resources/
│   └── css/
│       └── app.css             ← Uses Tailwind v3 @import syntax
└── app/
    └── Providers/
        └── Filament/
            └── AdminPanelProvider.php  ← Has ->viteTheme('resources/css/app.css')
```

---

## Testing Your Setup

### Test 1: Check Default Filament Pages
Visit any Filament resource page (e.g., `/admin/users`):
- ✅ Badges should have colors
- ✅ Alerts should be styled
- ✅ Dropdowns should work
- ✅ Tables should be styled

### Test 2: Check Custom Pages
Visit your custom pages (e.g., `/admin/point-of-sale`):
- ✅ Custom Tailwind classes should work (e.g., `grid`, `lg:grid-cols-3`, `hover:bg-primary-50`)
- ✅ Responsive classes should work
- ✅ Dark mode classes should work

### Test 3: Verify Compilation
```bash
# Check if your classes are in the built CSS
grep -o "max-h-96" public/build/assets/*.css
grep -o "bg-primary-50" public/build/assets/*.css
```

Both should return results if working correctly.

---

## Troubleshooting

### Issue: Custom classes still not showing

**Solution**:
```bash
# Clear all caches
php artisan filament:cache-components
php artisan view:clear
php artisan config:clear
npm run build

# Hard refresh browser (Cmd+Shift+R or Ctrl+Shift+F5)
```

### Issue: Default Filament pages broken

**Check**:
1. Are you using Tailwind v3? (`npm list tailwindcss`)
2. Is `viteTheme()` pointing to the right file?
3. Did you extend Filament's preset in `tailwind.config.js`?

### Issue: Classes not being compiled

**Check**:
1. Are your files included in `tailwind.config.js` content array?
2. Did you run `npm run build` or `npm run dev`?
3. Is PostCSS configured correctly?

---

## Additional Customization

### Adding Custom Colors
```javascript
// tailwind.config.js
import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                'custom-blue': '#1E40AF',
            },
        },
    },
}
```

### Adding Custom CSS Classes
Add them to your `resources/css/app.css`:

```css
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';

@layer components {
    .btn-primary {
        @apply bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700;
    }
}
```

---

## Summary

The key to making custom Tailwind classes work with FilamentPHP without breaking default pages:

1. **Use Tailwind v3** (not v4)
2. **Extend Filament's preset** in your config
3. **Register CSS with `viteTheme()`** in your AdminPanelProvider
4. **Use standard PostCSS processing** (not Tailwind v4's Vite plugin)

This ensures a single, unified Tailwind CSS setup that works across all Filament pages - both default and custom.

---

## Reference

- FilamentPHP Docs: https://filamentphp.com/docs/3.x/panels/themes
- Tailwind CSS v3 Docs: https://v3.tailwindcss.com/docs
- Laravel Vite: https://laravel.com/docs/vite

---

**Created**: 2025-10-25
**Tested On**: FilamentPHP v3.x, Laravel 11, Tailwind CSS v3
