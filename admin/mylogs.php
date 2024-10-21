<?php 

$startTime = microtime(true);
$logBuffer = [];

function lecho(){
    global $logBuffer;
    $args = func_get_args();
    
    $msg =  "";
    foreach ($args as $value) {

        if (is_object($value) || is_array($value)) {
            $msg .= json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }else{
            $msg .= $value;
        }
        $msg .= " ";
    }
    $logBuffer[] = trim($msg) . "\n";

}

function flushLogBuffer() {
    global $logBuffer;
    if (!empty($logBuffer) && DEBUG) {
        $logContent = trim(implode('', $logBuffer))."\n";
        if(!file_put_contents('logs/robot.log', $logContent, FILE_APPEND)) {
            $error = error_get_last();
            exit("LogError: " . $error['message']);        
        }
    }
}

function lexit(){
    global $startTime;
    $args = func_get_args();
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    $mydate = trim(date("d.m.y H:i",time())) . " (". number_format($executionTime, 4) . ")";
    array_unshift($args, $mydate);
    array_push($args,"\n---\n");
    lecho(...$args);
    flushLogBuffer();
    http_response_code(200);
    exit;
}

function virtual_finish(){
    http_response_code(200);
    flush();
    lmicrotime("Virtual finish");
}

function lmicrotime($msg=""){
    global $startTime;
    lecho("microtime:",microtime(true) - $startTime,$msg);
}
?>