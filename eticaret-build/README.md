# Bio&Baio — générateur de la boutique de démonstration

Sources des pages de `eticaret/` (eticaret.webina.com.tr).

- `data.json` : catégories et produits (prix en centimes d'euro)
- `i18n.py` : textes en 4 langues (fr, tr, en, ar)
- `build.py` : génère les pages HTML, `js/i18n.js`, `sitemap.xml` et `robots.txt`

```bash
cd eticaret-build && python3 build.py
```
