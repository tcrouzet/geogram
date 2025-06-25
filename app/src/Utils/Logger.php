<?php

namespace App\Utils;

class Logger 
{
    private static $instance = null;
    private $startTime;
    private $logBuffer = [];
    private $logFile;
    private $errorLogFile;
    private $loggingEnabled = true;
    
    private function __construct() {
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


    public function disableLogging(): void{
        $this->loggingEnabled = false;
    }

    public function handleError($errno, $errstr, $errfile, $errline): bool {
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
        $msg = trim($msg);
        if(!empty($msg))
            $this->logBuffer[] = $msg . "\n";
    }
    
    public function flushBuffer(): void 
    {
        if (!empty($this->logBuffer) && DEBUG) {
            $logContent = trim(implode('', $this->logBuffer)) . "\n";
            if ($this->loggingEnabled && !file_put_contents($this->logFile, $logContent, FILE_APPEND)) {
                $error = error_get_last();
                throw new \RuntimeException("LogError: " . $error['message']);
            }
        }
        unset($this->logBuffer);
    }    
    

    public function lexit($msg=''): never {
        if($msg) $this->log($msg);
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

    public function context() 
    {
        $this->log("Request info:");
        $this->log("- REQUEST_URI:", $_SERVER['REQUEST_URI'] ?? 'not set');
        $this->log("- SCRIPT_NAME:", $_SERVER['SCRIPT_NAME'] ?? 'not set');
        $this->log("- HTTP_REFERER:", $_SERVER['HTTP_REFERER'] ?? 'not set');
        $this->log("- REQUEST_METHOD:", $_SERVER['REQUEST_METHOD'] ?? 'not set');
        $this->log("- SAPI:", php_sapi_name());
        $this->log("- CLI:", php_sapi_name() === 'cli' ? 'YES' : 'NO');
        $this->log("- Script:", $_SERVER['SCRIPT_FILENAME'] ?? 'unknown');
    }

    public function bactrace($label, $skipLevels = 2): void 
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $this->log("=== BACKTRACE $label ===");
        
        // Filtrer les niveaux non pertinents
        $relevantTrace = array_slice($backtrace, $skipLevels);
        
        if (empty($relevantTrace)) {
            $this->log("No relevant backtrace available");
            $this->context();
            return;
        }
        
        $this->log("Relevant backtrace:");
        foreach($relevantTrace as $i => $trace) {
            $this->log("[" . ($i + $skipLevels) . "]", 
                "File:", $trace['file'] ?? 'no file',
                "Line:", $trace['line'] ?? 'no line', 
                "Function:", $trace['function'] ?? 'no function',
                "Class:", $trace['class'] ?? 'no class'
            );
        }

        $this->context();

    }
}
