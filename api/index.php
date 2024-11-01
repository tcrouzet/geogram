<?php
require_once '../vendor/autoload.php';
require_once '../app/config/config.php';

use App\Controllers\AuthController;
use App\Services\UserService;

$logger = \App\Services\Logger::getInstance();

// Configuration des erreurs
set_time_limit(60);

if (DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Gestion CORS si nécessaire
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

try {
    $view = $_POST['view'] ?? '';
    lecho($view);
    
    // Pour commencer, on peut garder la logique existante
    // mais on la déplacera progressivement vers les controllers
    switch($view) {
        case 'login':
            $auth = new AuthController();
            $data = $auth->login();
            break;
        case 'createuser':
            $uservice = new UserService();
            $data = $auth->createUser();
            break;
    
        // ... autres cas
        default:
            $data = ['status' => 'error', 'message' => 'Invalid endpoint'];
    }
    
    echo json_encode($data);

} catch (Exception $e) {
    if (DEBUG) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'An internal error occurred'
        ]);
    }
}

lexit();