<?php
// First, run 'composer require pusher/pusher-php-server'
$MY_CHANNEL="video-chat-pusher";
$APP_ID = "1643245";
$APP_KEY = "5ef4ff1ffb434dcb671b";
$APP_SECRET = "fc74e3343e89af1c81d4";
$APP_CLUSTER = "ap2";

$message=$_POST['message'];

require __DIR__ . '/vendor/autoload.php';

$pusher = new Pusher\Pusher($APP_KEY, $APP_SECRET, $APP_ID, array('cluster' => $APP_CLUSTER));

$pusher->trigger($MY_CHANNEL, 'my-event', $message);

?>