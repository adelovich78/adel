<?php
// Point d'entrée de l'API de l'espace client.
// La logique vit dans le site principal (public_html/api/_portal_api.php) : même base, même configuration.
foreach ([__DIR__ . '/../../public_html/api', __DIR__ . '/../../../public_html/api', __DIR__ . '/../../api'] as $dir) {
    if (is_file($dir . '/_portal_api.php')) {
        require $dir . '/_portal_api.php';
        exit;
    }
}
http_response_code(500);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'API introuvable : public_html/api/_portal_api.php']);
