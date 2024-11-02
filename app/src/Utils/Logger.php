<?php

namespace App\Utils;

class Logger 
{
    private static $instance = null;
    private $startTime;
    private $logBuffer = [];
    private $logFile;
    private $errorLogFile;
    
    private function __construct() 
    {
        $this->startTime = microtime(true);
        $this->logFile = ROOT_PATH . '/logs/robot.log';
        $this->errorLogFile = ROOT_PATH . '/logs/error_php.log';

        $this->setupErrorHandlers();
    }
    
    private function setupErrorHandlers(): void 
    {
        // Définir le gestionnaire d'erreurs
        set_error_handler([$this, 'handleError']);
        
        // Définir le gestionnaire d'exceptions non attrapées
        set_exception_handler([$this, 'handleException']);
        
        // Définir le gestionnaire de fin de script
        register_shutdown_function([$this, 'handleShutdown']);
        
        // Configuration des logs PHP
        ini_set('log_errors', 'On');
        ini_set('error_log', $this->errorLogFile);
        
        if (DEBUG) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', 0);
        }
    }

    public function handleError($errno, $errstr, $errfile, $errline): bool 
    {
        if (!(error_reporting() & $errno)) {
            // Ce code d'erreur n'est pas inclus dans error_reporting()
            return false;
        }
        
        $errorType = match($errno) {
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            default => 'Unknown Error'
        };
        
        $message = sprintf(
            "[%s] %s: %s in %s on line %d\n",
            date('Y-m-d H:i:s'),
            $errorType,
            $errstr,
            $errfile,
            $errline
        );
        
        error_log($message, 3, $this->errorLogFile);
        
        if (DEBUG) {
            $this->log($message);
        }
        
        // Ne pas exécuter le gestionnaire d'erreur interne de PHP
        return true;
    }
    
    public function handleException(\Throwable $exception): void 
    {
        $message = sprintf(
            "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        error_log($message, 3, $this->errorLogFile);
        
        if (DEBUG) {
            $this->log($message);
        }
    }
    
    public function handleShutdown(): void 
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $message = sprintf(
                "[%s] Fatal Error: %s in %s on line %d\n",
                date('Y-m-d H:i:s'),
                $error['message'],
                $error['file'],
                $error['line']
            );
            
            error_log($message, 3, $this->errorLogFile);
            
            if (DEBUG) {
                $this->log($message);
            }
        }
        
        // Vider le buffer de log avant la fin
        $this->flushBuffer();
    }
    
    public static function getInstance(): self 
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log(...$args): void 
    {
        $msg = "";
        foreach ($args as $value) {
            if (is_object($value) || is_array($value)) {
                $msg .= json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $msg .= $value;
            }
            $msg .= " ";
        }
        $this->logBuffer[] = trim($msg) . "\n";
    }
    
    private function flushBuffer(): void 
    {
        if (!empty($this->logBuffer) && DEBUG) {
            $logContent = trim(implode('', $this->logBuffer)) . "\n";
            if (!file_put_contents($this->logFile, $logContent, FILE_APPEND)) {
                $error = error_get_last();
                throw new \RuntimeException("LogError: " . $error['message']);
            }
        }
        unset($this->logBuffer);
    }    
    

    public function lexit(): never {
        $endTime = microtime(true);
        $executionTime = $endTime - $this->startTime;
        $mydate = trim(date("d.m.y H:i", time())) . " (" . number_format($executionTime, 4) . ")";
        
        $this->log($mydate . "\n---\n");
        
        $this->flushBuffer();
        http_response_code(200);
        exit;
    }
        
    public function virtualFinish(): void 
    {
        http_response_code(200);
        flush();
        $this->microtime("Virtual finish");
    }
    
    public function microtime(string $msg = ""): void 
    {
        $this->log("microtime:", microtime(true) - $this->startTime, $msg);
    }
}
