# Webina — générateur du site

Sources des pages de `webina/` (webina.com.tr).

- `content.py` : secteurs (un par démo en ligne), solutions, formules, étapes, navigation — 4 langues (tr, en, fr, ar)
- `i18n.json`, `demo_i18n.json`, `quote_i18n.json`, `details.py` : textes conservés (contact, devis, équipe, démos)
- `layout.tpl.html` : gabarit commun (navigation, pied de page, cookies, WhatsApp)
- `pages/*.html` : corps des pages (`sector.html` sert aux 17 pages secteur)
- `build_site.py` : génère les pages HTML, `js/i18n.js` et `sitemap.xml`

```bash
cd webina-build && python3 build_site.py
```

Pages : `/`, `/cozumler`, `/sektorler` (+ `/sektorler/<métier>`), `/demolar`, `/fiyatlar`, `/hakkimizda`, `/iletisim`, `/teklif`.
Les anciennes adresses (`/services`, `/secteurs`, `/portfolio`, `/contact`) sont redirigées (301) par `webina/.htaccess`.

Le CSS, le JS, les images, les polices, l'API PHP et `pricing.json` se modifient directement dans `webina/`.
Espace client : `my/` (sous-domaine my.webina.com.tr), logique PHP dans `webina/api/_portal*.php`.
Prévisualisation : `subdomains/onizleme/` (test et test2).
