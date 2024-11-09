<?php

namespace App\Utils;

class TempFiles {
    private static $instance = null;
    private $tempDir;
    
    private function __construct() {
        $this->tempDir = UPLOAD_PATH . '/tmp/';
        $this->initTempDir();
    }
    
    // Singleton pattern
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Initialise le dossier temp s'il n'existe pas
    private function initTempDir() {
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
            // Créer le .htaccess
            file_put_contents($this->tempDir . '.htaccess', 'Deny from all');
        }
    }
    
    // Crée un fichier temporaire
    public function createTempFile($prefix = '', $content = '') {
        $filename = $this->tempDir . $prefix . uniqid() . '.tmp';
        if (!empty($content)) {
            file_put_contents($filename, $content);
        }
        return $filename;
    }
    
    // Nettoie les vieux fichiers
    public function cleanup($maxAge = 3600) {
        foreach (glob($this->tempDir . '*') as $file) {
            if (basename($file) === '.htaccess') continue;
            if (time() - filemtime($file) > $maxAge) {
                unlink($file);
            }
        }
    }
    
    // Supprime un fichier spécifique
    public function removeFile($filename) {
        if (strpos($filename, $this->tempDir) === 0 && file_exists($filename)) {
            unlink($filename);
            return true;
        }
        return false;
    }
    
    // Récupère le chemin du dossier temp
    public function getTempDir() {
        return $this->tempDir;
    }
}
