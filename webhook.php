<?php
// https://geogram.tcrouzet.com/webhook.php
// Only once to program Telegram
require_once(__DIR__ . '/admin/secret.php');
$webhookUrl = BASE_URL.'telegram.php';
$response = file_get_contents('https://api.telegram.org/bot'.TELEGRAM_BOT_TOKEN.'/setWebhook?url='.$webhookUrl);
echo $response;

?>