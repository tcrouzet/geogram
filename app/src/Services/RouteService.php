<?php

namespace App\Services;

use App\Services\Database;
use App\Services\UserService;
use App\Services\Gpx\GpxService;
use App\Services\Gpx\GpxTools;
use App\Utils\Tools;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class RouteService 
{
    private $db;
    private $fileManager;
    private $error = false;
    private $userService;
    private $user = null;
    private $userid = null;
    
    public function __construct($user=null) 
    {
        $this->db = Database::getInstance()->getConnection();
        $this->fileManager = new FilesManager();
        $this->userService = new UserService();
        $this->user = $user;
        if($this->user)
            $this->userid = $this->user['userid'];
    }

    public function getError() {
        return $this->error;
    }

    public function getroutes(): array {
        lecho("getroutes user: $this->userid");

        // Récupère l'ID de la route connectée (userroute)
        $userroute = 0;
        if ($this->user && isset($this->user['userroute'])) {
            $userroute = (int) $this->user['userroute'];
        } else if ($this->userid) {
            $u = $this->userService->get_user($this->userid);
            if ($u && isset($u['userroute'])) {
                $userroute = (int) $u['userroute'];
            }
        }

        // Règles d'inclusion:
        // - routes liées (c.conid IS NOT NULL)
        // - routes publiques non liées (r.routestatus < 2)  <-- toujours incluses
        //
        // Tri:
        // - 0: route connectée (r.routeid = $userroute)
        // - 1: constatus = 3
        // - 2: constatus = 2
        // - 3: constatus = 1
        // - 4: constatus = 0 (si utilisé)
        // - 5: publiques non liées
        $sql = "
            SELECT
                c.conid, c.conrouteid, c.conuserid, c.contime, c.constatus,
                r.*
            FROM routes r
            LEFT JOIN connectors c
            ON c.conrouteid = r.routeid
            AND c.conuserid  = ?
            WHERE
                c.conid IS NOT NULL
            OR r.routestatus < 2
            ORDER BY
                (r.routeid = ?) DESC,
                CASE
                    WHEN c.constatus = 3 THEN 1
                    WHEN c.constatus = 2 THEN 2
                    WHEN c.constatus = 1 THEN 3
                    WHEN c.constatus = 0 THEN 4
                    ELSE 5
                END ASC,
                r.routestart DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Prepare failed: ' . $this->db->error];
        }

        $stmt->bind_param("ii", $this->userid, $userroute);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $routes = $result->fetch_all(MYSQLI_ASSOC);

            $superchargedRoutes = array_map(function($route) {
                return $this->supercharge($route);
            }, $routes);

            return [
                'status' => 'success',
                'routes' => $superchargedRoutes,
                'serverTimestamp' => time()
            ];
        }

        return ['status' => 'error', 'message' => "Loading routes fail"];
    }

    public function getpublicroutes() {
        lecho("getpublicroutes");
        
        $query = "SELECT * FROM routes WHERE routestatus <2 AND gpx = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result){
            $routes = $result->fetch_all(MYSQLI_ASSOC);
            return ['status' => 'success', 'routes' => $routes];
        }
        return ['status' => 'error', 'message' => "Loading routes fail"];
    }

    public function route_exists($routeid) {
        $query = "SELECT 1 FROM `routes` WHERE routeid = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $routeid);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function get_route_by_id($routeid){
        $query="SELECT * FROM `routes` WHERE routeid = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $routeid);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    public function get_route_by_slug($slug){
        $query="SELECT * FROM `routes` WHERE routeslug = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    public function get_route_by_telegram($chatID){
        $query="SELECT * FROM `routes` WHERE routetelegram = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $chatID);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    public function get_route_by_link($link){
        lecho("get_route_by_link");
        $query="SELECT * FROM `routes` WHERE routepublisherlink = ? OR 	routeviewerlink = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $link, $link);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result && $result->num_rows > 0){
            return $this->supercharge($result->fetch_assoc());
        }else{
            return false;
        }
    }

    private function supercharge($route): array {
        $route['photopath'] = $this->fileManager->route_photo_web($route);
        $route['invitpath'] = $this->make_invitation_link($route);
        $route['publishpath'] = $this->make_publish_link($route);
        $route['qrcodepath'] = $this->fileManager->route_QRcode_web($route);
        return $route;
    }

    private function make_invitation_link($route){
        $link = BASE_URL . "/login?link=" . $route["routeviewerlink"];
        if($route["routetelegram"]){
            $link .= "&telegram=" . $route["routetelegram"];
        }
        return $link;
    }

    private function make_publish_link($route){
        $link = BASE_URL . "/login?link=" . $route["routepublisherlink"];
        if($route["routetelegram"]){
            $link .= "&telegram=" . $route["routetelegram"];
        }
        return $link;
    }

    function routeAction(){
        lecho("routeAction");
    
        $userid = $_POST['userid'] ?? '';    
        $routeid = intval($_POST['routeid'] ?? '');
        $action = $_POST['action'] ?? '';
    
        // lecho($_POST);
        // lecho($action);
    
        if($action == "purgeroute"){
            $message = $this->delete_all_logs($routeid);
        }else if($action == "purgephotos"){
            $message = $this->delete_logs_and_photos($routeid);
        }else if($action == "deleteroute"){
            $message = $this->delete_logs_and_photos($routeid);
            if($message){
                $message = $this->delete_route($routeid);
                if($message){
                    return ['status' => 'redirect', 'url' => '/routes'];
                }
            }
        }else if($action == "renewInvitation"){
            $routeviewerlink = $this->generateInvitation($routeid, 1);
            $routepublisherlink = $this->generateInvitation($routeid, 2);
            $this->routeQRcode($routeid);
            $this->updateRouteInvitation($routeid, $routeviewerlink, $routepublisherlink);
            return ['status' => 'redirect', 'url' => '/routes'];
        }else{
            return ['status' => 'error', 'message' => "Unknown action: $action"];        
        }
    
        if($message)
            return ['status' => 'success', 'message' => "Action $action done"];
        else
            return ['status' => 'error', 'message' => "Action $action fail"];
    }

    public function delete_route($routeid){
        if($this->delete_logs_and_photos($routeid)){
            if($this->delete_connectors_by_route($routeid)){
                $stmt = $this->db->prepare("DELETE FROM routes WHERE routeid=?");
                $stmt->bind_param("i", $routeid);
                if ($stmt->execute())
                    return true;
            }
        }
        return false;
    }

    public function delete_connectors_by_route($routeid){
        $stmt = $this->db->prepare("DELETE FROM connectors WHERE conrouteid=?");
        $stmt->bind_param("i", $routeid);
        if ($stmt->execute())
            return true;
        return false;
    }

    public function delete_all_logs($routeid){
        $stmt = $this->db->prepare("DELETE FROM rlogs WHERE logroute=?");
        $stmt->bind_param("i", $routeid);
        if ($stmt->execute())
            return true;
        return false;
    }

    public function delete_logs_and_photos($routeid){
        $stmt = $this->db->prepare("SELECT * FROM rlogs WHERE logroute = ? AND logphoto >0");
        $stmt->bind_param("i", $routeid);
        $stmt->execute();
        if( $result = $stmt->get_result()){
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            foreach($logs as $log){
                $this->fileManager->purgeUserRouteData($log['loguser'], $routeid);
            }
            return $this->delete_all_logs($routeid);
        }
        return false;
    }

    public function newRoute(){
    
        $userid = $_POST['userid'] ?? '';
        $routename = $_POST['routename'] ?? '';
        if(empty($routename)){
            return ['status' => 'error', 'message' => 'Empty routename'];
        }
    
        $slug = Tools::slugify($routename);
        $initials = Tools::initial($routename);
    
        $query = "SELECT * FROM routes WHERE routename=? OR routeslug=?;";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $routename, $slug);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result && $result->num_rows > 0) {
            return ['status' => 'error', 'message' => 'Route already exists'];
        } else {
            $insertQuery = "INSERT INTO routes (routename, routeinitials, routeslug, routeuserid) VALUES (?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertQuery);
            $insertStmt->bind_param("sssi", $routename, $initials, $slug, $userid);
            
            if ($insertStmt->execute()) {
                // Retourne les données du nouvel utilisateur
                $routeid = $this->db->insert_id;
                $this->updateUserRoute($userid,$routeid);
                $routeviewerlink = $this->generateInvitation($routeid, 1);
                $routepublisherlink = $this->generateInvitation($routeid, 2);
                $this->updateRouteInvitation($routeid, $routeviewerlink, $routepublisherlink);

                $this->routeQRcode($routeid);
            
                $this->connect($userid,$routeid,3);
                $route = [
                    'routeid' => $routeid,
                    'routename' => $routename,
                    'routeslug' => $slug,
                    'routeuserid' => $userid,
                ];
                return [
                    'status' => "success",
                    'routedata' => $route
                ];
            } else {
                return [
                    'status' => "fail",
                    'message' => "Can't add route"
                ];
            }
        
        }
    
    }

    public function routeQRcode($routeid){
        $qrcode_png = $this->fileManager->route_QRcode($routeid);
        $loginUrl = BASE_URL . "/?login=qrcode&routeid=" . $routeid;
        
        $writer = new PngWriter();
        
        // Nouveau constructeur en v6
        $qrCode = new QrCode(
            data: $loginUrl,
            size: 400,
            margin: 20
        );
        
        $result = $writer->write($qrCode);
        
        // Sauver le fichier
        $result->saveToFile($qrcode_png);
        
        return true;
    }

    public function updateUserRoute($userid,$routeid){
        $stmt = $this->db->prepare("UPDATE users SET userroute = ? WHERE userid = ?");
        $stmt->bind_param("si", $routeid, $userid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }

    public function updateRouteInvitation($routeid,$viewer,$publisher){
        $stmt = $this->db->prepare("UPDATE routes SET routepublisherlink = ?, routeviewerlink = ? WHERE routeid = ?");
        $stmt->bind_param("ssi", $publisher, $viewer, $routeid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }
    
    // public function generateInvitationOld($routeid, $status) {
    //     $randomString = bin2hex(random_bytes(8));
    //     $data = $routeid . '|' . $status . '|' . $randomString;
    
    //     $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    //     $encryptedData = openssl_encrypt($data, 'aes-256-cbc', JWT_SECRET, 0, $iv);
    //     $token = base64_encode($iv . $encryptedData);
    
    //     return $token;
    // }
    
    // public function decodeInvitationOld($token) {
    //     $decoded = base64_decode($token);
    //     $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    //     $iv = substr($decoded, 0, $ivLength);
    //     $encryptedData = substr($decoded, $ivLength);
    
    //     $data = openssl_decrypt($encryptedData, 'aes-256-cbc', JWT_SECRET, 0, $iv);
    //     if ($data === false) {
    //         return false;
    //     }
    
    //     list($routeid, $status, $randomString) = explode('|', $data);
    //     lecho("Decoded invitation:", $routeid, $status);
    //     return ['routeid' => $routeid, 'status' => $status];
    // }

    // AES-256-CBC avec token hexadécimal
    public function generateInvitation($routeid, $status) {
        $ivLen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = random_bytes($ivLen);

        // Données en clair (tu gardes tes pipes ici, ce n'est PAS le token)
        $randomString = bin2hex(random_bytes(8));
        $data = $routeid . '|' . $status . '|' . $randomString;

        // Clé binaire 32 octets
        $key = hash('sha256', JWT_SECRET, true);

        // Ciphertext binaire (important: OPENSSL_RAW_DATA)
        $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            $this->error = 'Encryption failed';
            return false;
        }

        // Token = hex( IV || ciphertext )
        $token = bin2hex($iv . $ciphertext);
        return $token; // uniquement 0-9 et a-f
    }

    public function decodeInvitation($token) {
        $binary = hex2bin($token);
        if ($binary === false) {
            $this->error = 'Bad Invitation link';
            return false;
        }

        $ivLen = openssl_cipher_iv_length('aes-256-cbc');
        if (strlen($binary) < $ivLen) {
            $this->error = 'Bad Invitation link length';
            return false;
        }

        $iv = substr($binary, 0, $ivLen);
        $ciphertext = substr($binary, $ivLen);

        $key = hash('sha256', JWT_SECRET, true);

        // Decrypt (OPENSSL_RAW_DATA car ciphertext binaire)
        $data = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($data === false) {
            $this->error = 'Bad RSS Invitation';
            return false;
        }

        $parts = explode('|', $data);
        if (count($parts) !== 3) {
            $this->error = 'Bad Invitation parts';
            return false;
        }

        list($routeid, $status, $randomString) = $parts;
        lecho("Decoded invitation:", $routeid, $status);
        return ['routeid' => $routeid, 'status' => $status];
    }
    
    public function connect($userid, $routeid, $status = 2) {
        lecho("connect", $userid, $routeid);

        // Affecter la route à l'utilisateur
        if (!$this->userService->set_user_route($userid,$routeid)) {
            return false;
        }
    
        // Requête SQL avec ON DUPLICATE KEY UPDATE pour mettre à jour le statut uniquement s'il est supérieur
        $insertQuery = "INSERT INTO connectors (conrouteid, conuserid, constatus) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE constatus = IF(VALUES(constatus) > constatus, VALUES(constatus), constatus)";
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->bind_param("iii", $routeid, $userid, $status);
    
        return $insertStmt->execute();
    }

    public function updateroute(){
        lecho("updateroute");

        lecho($_POST);
    
        $routeid = intval($_POST['routeid'] ?? 0);
        if($routeid == 0){
            return ['status' => 'error', 'message' => 'No route id'];
        }

        if(!$this->route_exists($routeid)){
            $this->error = 'Unknown route';
            return ['status' => 'error', 'message' => 'Unknown route'];
        }
    
        $routename = $_POST['routename'] ?? '';
        if(empty($routename)){
            return ['status' => 'error', 'message' => 'Empty routename'];
        }

        $routetimediff = intval($_POST['routetimediff'] ?? 0);
        lecho("routetimediff",$routetimediff);

        $routetelegram = intval($_POST['routetelegram'] ?? 0);
        lecho("routetelegram",$routetelegram);

        $routeverbose = intval($_POST['routeverbose'] ?? 0);
        lecho("routeteverbose",$routeverbose);

        $routelocationduration = intval($_POST['routelocationduration'] ?? 0);
        lecho("routelocationduration",$routelocationduration);
 
        $routelastdays = intval($_POST['routelastdays'] ?? 0);
 
        $routestatus = intval($_POST['routestatus'] ?? 0);

        $routerem = $_POST['routerem'] ?? '';
        lecho($routerem);

        $routemode = intval($_POST['routemode'] ?? 0);

        $routestart = $_POST['routestart'] ?? '0000-00-00 00:00:00';
        if ($routestart === 'null' || $routestart === '') {
            $routestart = '0000-00-00 00:00:00';
        }
        
        $routestop = $_POST['routestop'] ?? '0000-00-00 00:00:00';
        if ($routestop === 'null' || $routestop === '') {
            $routestop = '0000-00-00 00:00:00';
        }

        $this->routeQRcode($routeid);

        // Requête modifiée pour gérer explicitement NULL
        $query = "UPDATE routes SET 
            routename = ?,
            routerem = ?,
            routetimediff = ?,
            routestatus = ?,
            routetelegram = ?,
            routemode = ?,
            routelastdays = ?,
            routestart = ?,
            routestop = ?,
            routeverbose = ?,
            routelocationduration = ?
            WHERE routeid = ?";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Prepare failed: ' . $this->db->error];
        }
        lecho("prepare done");

        $bindResult = $stmt->bind_param("ssiiiiissiii", $routename, $routerem, $routetimediff, $routestatus, $routetelegram, $routemode, $routelastdays, $routestart, $routestop, $routeverbose, $routelocationduration, $routeid);

        if($bindResult){
            lecho("Bind param done");

            $executeResult = $stmt->execute();
            
            if ($executeResult){
                lecho("success");
                return ['status' => 'success', 'message' => 'Update done'];
            }else{
                lecho("execute problem");
                $this->error = $this->db->error;
            }
        }
        lecho("Bin problem");
        $this->error = $stmt->error;
        return ['status' => 'error', 'message' => $this->error];
    }

    public function isConnected($userid, $routeid) {
        $stmt = $this->db->prepare("SELECT constatus FROM connectors WHERE conuserid = ? AND conrouteid = ? LIMIT 1");
        $stmt->bind_param("ii", $userid, $routeid);
        $stmt->execute();
        $result = $stmt->get_result();
        // lecho("Connexions $userid to $routeid: " . ($result ? $result->num_rows : 'erreur') );
        
        if($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return (int)$row['constatus']; // Retourne constatus (0, 1, 2 ou 3)
        }
        
        $stmt->close();
        return null; // Retourne null si aucun enregistrement trouvé
    }

    public function routeconnect(){
        lecho("routeconnect");
    
        $userid = $_POST['userid'] ?? '';
        $routeid = intval($_POST['routeid'] ?? '');
        $link = urldecode($_POST['link'] ?? '');

        lecho("routeconnect",$userid,$routeid,$link);
    
        if (!empty($userid) && $user = $this->userService->get_user($userid)) {

            if (!empty($link)){
                lecho("link not roueid");
                $route = $this->get_route_by_link($link);
                if($route){
                    $routeid = $route['routeid'];
                }else{
                    return ['status' => 'error', 'message' => 'Unknown route'];
                }
            }
            lecho("routeid:",$routeid);

            if($routeid>0){
                $stmt = $this->db->prepare("UPDATE users SET userroute = ? WHERE userid = ?");
                $stmt->bind_param("ii", $routeid, $userid);
                if ($stmt->execute()){

                    $status = $this->isConnected($userid, $routeid);

                    if ($status === null){
                        // Is not connected
                        $this->connect($userid,$routeid,0);
                    }else{
                        lecho("is conected with status $status");
                    }

                    $user = $this->userService->get_user($userid);
                    return ['status' => 'success', 'user' => $user ];
                }else
                    return ['status' => 'error', 'message' => 'Update fail'];
            }
    
        }
        return ['status' => 'error', 'message' => 'Unknown user'];
    }
    
    public function gpxupload(){
        lecho("gpxUpload");
    
        $userid = $_POST['userid'] ?? '';
    
        if (!isset($_FILES['gpxfile']) || $_FILES['gpxfile']['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'message' => 'Bad gpx file'];
        }
    
        $routeid = $_POST['routeid'] ?? '';
        if(empty($routeid)){
            return ['status' => 'error', 'message' => 'Empty routename'];
        }
    
        $source = $this->fileManager->gpx_source($routeid);
    
        if($source){
            if (move_uploaded_file($_FILES['gpxfile']['tmp_name'], $source)) {
    
                $nimigpx = GpxTools::gpx_minimise($source);
                $geojson = GpxTools::gpx_geojson($nimigpx);
                if($geojson){
                    $gpxService = new GpxService($routeid);
                    if ($gpxdata = $gpxService->new_GPX()){
                        $this->routeGPX($routeid,1);
                        return ['status' => 'success', 'gpx' => $gpxdata];
                    }
                }
            }
        }
    
        $this->routeGPX($routeid,0);
        return ['status' => 'error', 'message' => 'Upload fail'];
    
    }

    public function routeGPX($routeid,$value){
        lecho("routeGPX");
        $stmt = $this->db->prepare("UPDATE routes SET gpx = ?, routeupdate = NOW() WHERE routeid = ?");
        $stmt->bind_param("ii", $value, $routeid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }

    public function routephoto(){
        lecho("Route Photo Upload");
        
        if (!isset($_FILES['photofile']) || $_FILES['photofile']['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'message' => 'Bad photo file'];
        }
    
        $routeid = $_POST['routeid'] ?? '';
        if(empty($routeid)){
            return ['status' => 'error', 'message' => 'Empty route'];
        }
    
       $target = $this->fileManager->route_photo($routeid);
    
        if($target){
            if(Tools::resizeImage($_FILES['photofile']['tmp_name'], $target, 500)){
                $this->set_route_photo($routeid,1);
                return ['status' => 'success', 'message' => 'File uploaded successfully'];
            }else{
                $this->set_route_photo($routeid,0);
            }
        }
    
        return ['status' => 'error', 'message' => 'Upload fail'];
    
    }
    
    public function set_route_photo($routeid,$value){
        $stmt = $this->db->prepare("UPDATE routes SET routephoto = ? WHERE routeid = ?");
        $stmt->bind_param("ii", $value, $routeid);
        if ($stmt->execute())
            return true;
        else
            return false;
    }
    
}