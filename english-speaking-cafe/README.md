# XY Academy — English Speaking Café

Application web complète (site vitrine + réservation + paiements + espaces connectés + back-office) en **PHP 8 + MySQL**, sans Node, sans Composer, sans étape de build : elle s'installe telle quelle sur un hébergement **cPanel**.

## Déploiement sur cPanel

1. **Base de données** — cPanel → *MySQL® Database Wizard* : créer une base, un utilisateur, cocher *ALL PRIVILEGES*.
2. **Fichiers** — cPanel → *File Manager* → `public_html/` (ou un sous-dossier) → *Upload* du ZIP du dossier `english-speaking-cafe/` → *Extract*. Le contenu (index.php, .htaccess, app/, assets/…) doit être directement dans `public_html/`.
3. **PHP** — cPanel → *MultiPHP Manager* : PHP **8.1 ou plus**. Extensions requises (activées par défaut) : `pdo_mysql`, `mbstring`, `curl`, `fileinfo`.
4. **Installation** — ouvrir `https://votredomaine.com/install.php`, renseigner la base, le compte admin, et cocher ou non les données de démo. L'installeur crée les tables, écrit `config/config.php` puis se verrouille.
5. **Sécurité** — supprimer `install.php` (File Manager). Activer le SSL (*SSL/TLS Status* → AutoSSL) puis décommenter le bloc « Forcer HTTPS » dans `.htaccess`.
6. **E-mails** — cPanel → *Email Accounts* : créer l'adresse expéditrice saisie à l'installation (envoi via `mail()`).
7. **Paiements Stripe (optionnel)** — dans `config/config.php` : `secret_key` (sk_live_…) et `webhook_secret`. Webhook Stripe : `https://votredomaine.com/stripe/webhook`, évènement `checkout.session.completed`. Sans clé, un simulateur de paiement est actif (désactivable : `payments_demo => false`).

Installation dans un sous-dossier : décommenter `RewriteBase /sous-dossier/` dans `.htaccess`.

## Comptes de démonstration (mot de passe `demo1234`)

| Rôle | E-mail |
|---|---|
| Élève | student@demo.cafe |
| Parent (2 enfants) | parent@demo.cafe |
| Entreprise (3 salariés) | hr@demo.cafe |
| Professeur | julia@xyacademy.cafe |
| Admin | celui créé à l'installation |

À supprimer dans *Admin → Users* avant la mise en ligne.

## Fonctionnalités

- **Pages** : Home, Programs (+ fiche programme), 1-to-1, Business, Events, Teachers, Memberships, About, Contact, Library, Legal.
- **Réservation** : niveau A1–C2 → objectif → programme → professeur → date/place en temps réel → paiement (Stripe, crédit d'abonnement ou sur place).
- **Anti-surbooking** : transaction + verrou `SELECT … FOR UPDATE` sur la séance (testé : 12 réservations simultanées sur 5 places → 5 confirmées, 7 en liste d'attente).
- **Liste d'attente** : FIFO, promotion automatique à chaque annulation / hausse de capacité / place non payée, e-mail au bénéficiaire.
- **Bibliothèque** : 18 livres PDF (A1 → C2) stockés hors web (`storage/books`), téléchargeables selon le niveau de l'élève (ou de ses enfants / salariés). Ajout de livres depuis l'admin.
- **Tableaux de bord** : Élève (réservations, abonnement, paiements, progression radar + courbe), Professeur (planning, présences, notes, rapports de progression), Parent (enfants, réservations, progression), Entreprise (salariés, réservations, assiduité), Admin (KPI, CRUD complet, planning récurrent, réglages).
- **Progression** : CEFR, speaking, listening, pronunciation, vocabulary, confidence, assiduité.
- **Éditable par l'admin** : prix, capacités, horaires, programmes, formules, événements, coordonnées, règles d'annulation…
- **Thème clair / sombre** (logos adaptés automatiquement), animations GSAP + ScrollTrigger + Lenis auto-hébergées, vidéos en fond.
- **Prêt pour la suite** : API REST `/api/v1` (jetons Bearer pour app mobile), tables `ai_jobs` (prononciation / speaking IA) et `online_lessons` (e-learning), progression `source = ai`.

## Structure (convention Client-First pour le HTML/CSS)

```
index.php            contrôleur frontal (routes)
install.php          assistant d'installation
.htaccess            réécriture d'URL + protections
app/core/            DB, Auth, Router, View, Booking, Payment, Mailer, helpers
app/controllers/     Site, Auth, Booking, Dashboard, Admin, Api
app/views/           layouts, pages, partials, dashboard, admin, booking, library, auth
assets/              css, js, vendor (gsap, lenis), fonts, img, video, logo
database/            schema.sql, seed.php, books.json
storage/             books (PDF privés), logs
uploads/             images envoyées depuis l'admin
```

## Logos

`assets/logo/` : versions claire et sombre (complet, symbole, horizontal) en SVG + PNG transparent, favicon et apple-touch-icon. Les logos inline du site changent de couleur automatiquement avec le thème.

## Vidéos

`assets/video/` contient 4 boucles générées à partir de la photo du café. Pour utiliser vos propres tournages, remplacez les fichiers en gardant les mêmes noms (`hero.mp4`, `people.mp4`, `table.mp4`, `lamps.mp4`, MP4 H.264, idéalement < 4 Mo) et les affiches `assets/img/poster-*.jpg`.
