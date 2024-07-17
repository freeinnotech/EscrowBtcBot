<?php
$token = 'YOUR_TELEGRAM_BOT_API_TOKEN';
$webhook_url = 'https://yourdomain.com/bot.php';

$url = "https://api.telegram.org/bot$token/setWebhook?url=$webhook_url";
$response = file_get_contents($url);
echo $response;
?>
