<?php
require_once '../vendor/autoload.php';
require_once '../app/config/config.php';

use App\Controllers\AuthController;
use App\Services\UserService;
use App\Services\MapService;

$logger = \App\Utils\Logger::getInstance();

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
    //lecho($view);
    
    // Routes publiques (pas besoin de token)
    $publicRoutes = [
        'login' => [AuthController::class, 'login'],
        'createuser' => [UserService::class, 'createUser']
    ];
    
    // Routes protégées (nécessitent un token valide)
    $protectedRoutes = [
        'loadMapData' => [MapService::class, 'loadMapData'],
        'sendgeolocation' => [MapService::class, 'sendgeolocation'],
        'userMarkers' => [MapService::class, 'userMarkers'],
        //'route' => [RouteService::class, 'createRoute'],
    ];
    
    if (isset($publicRoutes[$view])) {
        // Route publique
        [$class, $method] = $publicRoutes[$view];
        $controller = new $class();
        $data = $controller->$method();
    } 
    elseif (isset($protectedRoutes[$view])) {
        // Route protégée : vérifier le token d'abord
        $userid = $_POST['userid'] ?? '';
        $auth = new AuthController();
        
        if (!$auth->testToken($userid)) {
            $data = ['status' => 'error', 'message' => 'Unauthorized. Please login again.'];
        } else {
            [$class, $method] = $protectedRoutes[$view];
            $controller = new $class();
            $data = $controller->$method();
        }
    } 
    else {
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