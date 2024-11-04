<?php

function lecho(...$args): void 
{
    \App\Utils\Logger::getInstance()->log(...$args);
}

function lexit(): never 
{
    \App\Utils\Logger::getInstance()->lexit();
}

function virtual_finish(): void 
{
    \App\Utils\Logger::getInstance()->virtualFinish();
}

function lmicrotime(string $msg = ""): void 
{
    \App\Utils\Logger::getInstance()->microtime($msg);
}

function flushBuffer(): void 
{
    \App\Utils\Logger::getInstance()->flushBuffer();
}
