<?php

function lecho(...$args): void 
{
    \App\Utils\Logger::getInstance()->log(...$args);
}

function lexit(...$args): never 
{
    \App\Utils\Logger::getInstance()->exit(...$args);
}

function virtual_finish(): void 
{
    \App\Utils\Logger::getInstance()->virtualFinish();
}

function lmicrotime(string $msg = ""): void 
{
    \App\Utils\Logger::getInstance()->microtime($msg);
}
