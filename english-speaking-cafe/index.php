<?php
/**
 * XY Academy — English Speaking Café
 * Contrôleur frontal unique (toutes les URL passent ici via .htaccess)
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Router;
use App\Controllers\{SiteController as Site, AuthController as AuthC, BookingController as Book, DashboardController as Dash, AdminController as Admin, ApiController as Api, PlacementController as Test};

$r = new Router();

// --- Pages publiques
$r->get('/', [Site::class, 'home']);
$r->get('/programs', [Site::class, 'programs']);
$r->get('/programs/{slug}', [Site::class, 'program']);
$r->get('/one-to-one', [Site::class, 'oneToOne']);
$r->get('/business', [Site::class, 'business']);
$r->get('/events', [Site::class, 'events']);
$r->get('/teachers', [Site::class, 'teachers']);
$r->get('/memberships', [Site::class, 'memberships']);
$r->get('/about', [Site::class, 'about']);
$r->get('/contact', [Site::class, 'contact']);
$r->post('/contact', [Site::class, 'contactSend']);
$r->get('/library', [Site::class, 'library']);
$r->get('/library/download/{id}', [Site::class, 'download']);
$r->get('/legal', [Site::class, 'legal']);
$r->get('/level-test', [Test::class, 'show']);
$r->post('/level-test', [Test::class, 'submit']);
$r->get('/level-test/result', [Test::class, 'result']);
$r->post('/level-test/save', [Test::class, 'save']);

// --- Authentification
$r->get('/login', [AuthC::class, 'loginForm']);
$r->post('/login', [AuthC::class, 'login']);
$r->get('/register', [AuthC::class, 'registerForm']);
$r->post('/register', [AuthC::class, 'register']);
$r->post('/logout', [AuthC::class, 'logout']);
$r->get('/forgot', [AuthC::class, 'forgotForm']);
$r->post('/forgot', [AuthC::class, 'forgot']);
$r->get('/reset/{token}', [AuthC::class, 'resetForm']);
$r->post('/reset/{token}', [AuthC::class, 'reset']);

// --- Réservation & paiement
$r->get('/book', [Book::class, 'wizard']);
$r->post('/book', [Book::class, 'store']);
$r->post('/bookings/{id}/cancel', [Book::class, 'cancel']);
$r->post('/payments/{id}/pay', [Book::class, 'pay']);
$r->get('/checkout/{id}', [Book::class, 'demoCheckout']);
$r->post('/checkout/{id}', [Book::class, 'demoConfirm']);
$r->get('/payment/success', [Book::class, 'success']);
$r->get('/payment/cancel', [Book::class, 'cancelled']);
$r->post('/memberships/{id}/join', [Book::class, 'joinMembership']);
$r->post('/events/{id}/register', [Book::class, 'registerEvent']);
$r->post('/stripe/webhook', [Book::class, 'webhook']);

// --- Tableaux de bord
$r->get('/dashboard', [Dash::class, 'index']);
$r->post('/dashboard/profile', [Dash::class, 'profile']);
$r->post('/dashboard/children', [Dash::class, 'addChild']);
$r->post('/dashboard/employees', [Dash::class, 'addEmployee']);
$r->post('/dashboard/attendance/{id}', [Dash::class, 'attendance']);
$r->post('/dashboard/progress', [Dash::class, 'saveProgress']);
$r->post('/dashboard/note/{id}', [Dash::class, 'note']);
$r->post('/dashboard/api-token', [Dash::class, 'apiToken']);

// --- Administration (CRUD générique)
$r->get('/admin', [Admin::class, 'index']);
$r->get('/admin/settings', [Admin::class, 'settings']);
$r->post('/admin/settings', [Admin::class, 'saveSettings']);
$r->post('/admin/payments/{id}/paid', [Admin::class, 'markPaid']);
$r->post('/admin/bookings/{id}/status', [Admin::class, 'bookingStatus']);
$r->post('/admin/sessions/generate', [Admin::class, 'generateSessions']);
$r->get('/admin/{entity}', [Admin::class, 'list']);
$r->get('/admin/{entity}/new', [Admin::class, 'form']);
$r->get('/admin/{entity}/{id}/edit', [Admin::class, 'form']);
$r->post('/admin/{entity}/save', [Admin::class, 'save']);
$r->post('/admin/{entity}/{id}/delete', [Admin::class, 'delete']);

// --- API v1 (app mobile, IA, e-learning)
$r->get('/api/v1', [Api::class, 'index']);
$r->get('/api/v1/programs', [Api::class, 'programs']);
$r->get('/api/v1/teachers', [Api::class, 'teachers']);
$r->get('/api/v1/sessions', [Api::class, 'sessions']);
$r->get('/api/v1/events', [Api::class, 'events']);
$r->get('/api/v1/books', [Api::class, 'books']);
$r->post('/api/v1/auth/token', [Api::class, 'token']);
$r->get('/api/v1/me', [Api::class, 'me']);
$r->get('/api/v1/me/bookings', [Api::class, 'myBookings']);
$r->get('/api/v1/me/progress', [Api::class, 'myProgress']);
$r->post('/api/v1/bookings', [Api::class, 'book']);
$r->post('/api/v1/ai/pronunciation', [Api::class, 'aiPronunciation']);
$r->post('/api/v1/ai/speaking', [Api::class, 'aiSpeaking']);

try {
    \App\Core\Migrator::run(); // mises à jour de base automatiques (une seule fois)
    $r->dispatch();
} catch (\PDOException $e) {
    log_line('db.log', $e->getMessage());
    abort(500, config('app.debug') ? $e->getMessage() : 'Database unavailable. Please try again in a moment.');
}
