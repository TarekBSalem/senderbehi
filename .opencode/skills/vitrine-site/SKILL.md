---
name: vitrine-site
description: Use when building the public-facing marketing site — landing page, pricing page, documentation, blog, changelog, contact page, privacy/terms, or SEO optimization.
---

# Vitrine Site (Public Marketing Site)

## Architecture

The vitrine site can be:
1) **Same Laravel app** at root `/` routes (no auth required)
2) **Separate static site** built with a framework (Astro, Next.js, etc.)

**Recommendation:** Use the same Laravel app for simplicity. Inertia can handle both public and authenticated routes. Public pages use `layout: null` or a minimal layout.

## Sitemap

```
mailforge.com
├── /                    Landing page (hero, features, CTA)
├── /features            Detailed features
├── /pricing             Pricing comparison table
├── /docs                Documentation / knowledge base
│   ├── /getting-started
│   ├── /campaigns
│   ├── /templates
│   └── /faq
├── /blog                Marketing blog
│   ├── /archive
│   └── /{slug}
├── /changelog           Public release notes
├── /status              System status page
├── /contact             Contact form
├── /privacy             Privacy policy
├── /terms               Terms of service
└── /login               Redirect to app
```

## Existing Layout System (from app.tsx)

```tsx
layout: (name) => {
    switch (true) {
        case name === 'welcome':
            return null;  // No layout — public page
        case name.startsWith('auth/'):
            return AuthLayout;
        case name.startsWith('settings/'):
            return [AppLayout, SettingsLayout];
        default:
            return AppLayout;  // Sidebar for authenticated pages
    }
}
```

Public vitrine pages should use the `welcome` pattern — no layout or a minimal `PublicLayout`.

## Implementation Order

1. **Landing page** — hero section, feature highlights, CTA to register
2. **Pricing page** — pull plans from `subscription_plans` table, comparison table
3. **Contact page** — form that sends notification to admin
4. **Privacy & Terms** — static legal pages
5. **Docs** — documentation pages with sidebar navigation
6. **Blog** — simple CMS (database-driven or markdown)
7. **Changelog** — from git tags or database
8. **Status page** — basic system status indicator

## SEO Considerations
- Use `<Head>` from `@inertiajs/react` for title and meta tags
- Each public page needs: title, description, og:image
- Consider SSR for public pages (Inertia v3 supports SSR)
- Generate a `sitemap.xml` route
