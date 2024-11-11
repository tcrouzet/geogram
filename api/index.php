<?php
require_once '../vendor/autoload.php';
require_once '../app/config/config.php';
require_once '../app/config/telegram.php';

use App\Controllers\AuthController;
use App\Services\UserService;
use App\Services\MapService;
use App\Services\RouteService;
use App\Services\AuthService;
//use App\Services\Telegram\TelegramService;

// Configuration des erreurs
set_time_limit(60);

microtime();
//lecho($_POST);

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
    $view = $_POST['view'] ?? $_GET['view'] ?? '';

    // Si c'est un callback Auth0, forcer la vue
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/api/callback') !== false) {
        $view = 'callback';
    }
     
    lecho("View:",$view);
    
    // Routes publiques (pas besoin de token)
    $publicRoutes = [
        'loginSocial' => [AuthController::class, 'loginSocial'],
        'callback' => [AuthController::class, 'callback'],
        'getSession' => [AuthController::class, 'getSession'],
        'logout' => [AuthController::class, 'logout'],

        // 'createuser' => [UserService::class, 'createUser'],
        'loadMapData' => [MapService::class, 'loadMapData'],
        'userMarkers' => [MapService::class, 'userMarkers'],
    ];
    
    // Routes protégées (nécessitent un token valide)
    $protectedRoutes = [
        'telegram' => [AuthService::class, 'handleTelegram'],

        'sendgeolocation' => [MapService::class, 'sendgeolocation'],
        'logphoto' => [MapService::class, 'logphoto'],
        'submitComment' => [MapService::class, 'submitComment'],
        'story' => [MapService::class, 'story'],
        
        'getroutes' => [RouteService::class, 'getroutes'],
        'routeAction' => [RouteService::class, 'routeAction'],
        'newRoute' => [RouteService::class, 'newRoute'],
        'updateroute' => [RouteService::class, 'updateroute'],
        'routeconnect' => [RouteService::class, 'routeconnect'],
        'gpxupload' => [RouteService::class, 'gpxupload'],
        'routephoto' => [RouteService::class, 'routephoto'],

        'updateuser' => [UserService::class, 'updateuser'],
        'userphoto' => [UserService::class, 'userphoto'],
        'userAction' => [UserService::class, 'userAction'],
        'userAction' => [UserService::class, 'userAction'],
        'getUserChannels' => [UserService::class, 'getUserChannels'],

    ];

    if (isset($publicRoutes[$view])) {
        // Route publique
        [$class, $method] = $publicRoutes[$view];
        lecho("Public", $class, $method);
        $controller = new $class();
        if ($response = $controller->getError()) {
            lecho("error", $response);
            $data = $response;
        } else {
            lecho("Method:",$method);
            $data = $controller->$method();
        }
    } 
    elseif (isset($protectedRoutes[$view])) {
        // Route protégée : vérifier le token d'abord

       // Nouvelle vérification avec Auth0
       $authService = new AuthService();
       $session = $authService->handleSession();
       
       if ($session['status'] === 'error') {
            $data = $session;
        } else {
            [$class, $method] = $protectedRoutes[$view];
            // Passer l'utilisateur au constructeur
            $controller = new $class($session['user']);
            if ($response = $controller->getError()) {
                $data = $response;
            } else {
                // Passer l'ID utilisateur Auth0 au contrôleur si nécessaire
                $data = $controller->$method();
            }
        }

    } else {
        $data = ['status' => 'error', 'message' => "Invalid endpoint... $view"];
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