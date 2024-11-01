<?php

function lecho(...$args): void 
{
    \App\Services\Logger::getInstance()->log(...$args);
}

function lexit(...$args): never 
{
    \App\Services\Logger::getInstance()->exit(...$args);
}

function virtual_finish(): void 
{
    \App\Services\Logger::getInstance()->virtualFinish();
}

function lmicrotime(string $msg = ""): void 
{
    \App\Services\Logger::getInstance()->microtime($msg);
}
