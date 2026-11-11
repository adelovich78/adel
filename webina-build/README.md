# Webina — générateur du site

Sources des pages de `webina/` (webina.com.tr).

- `i18n.json`, `demo_i18n.json`, `quote_i18n.json`, `details.py` : textes en 4 langues (tr, en, fr, ar)
- `layout.tpl.html` : gabarit commun (navigation, pied de page, cookies, WhatsApp)
- `pages/*.html` : corps de chaque page
- `build_site.py` : génère les pages HTML et `js/i18n.js`

```bash
cd webina-build && python3 build_site.py
```

Le CSS, le JS, les images, les polices, l'API PHP et `pricing.json` se modifient directement dans `webina/`.
