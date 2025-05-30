<?php
// https://geo.zefal.com/api/telegram/webhook.php
// Only once to program Telegram


require_once '../../app/config/config.php';
require_once '../../app/config/telegram.php';   // Telegram TOKEN

if (!in_array($_SERVER['REMOTE_ADDR'], ADMIN_IPS)) {
    die('Only for admin, run once to inform Telegram!');
}

$webhookUrl = 'https://api.telegram.org/bot'.TELEGRAM_BOT_TOKEN.'/setWebhook?url='.TELEGRAM_WEBHOOK;
$response = file_get_contents($webhookUrl);
echo $response;
echo "Done";

?>