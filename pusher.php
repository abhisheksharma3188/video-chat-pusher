<?php
// First, run 'composer require pusher/pusher-php-server'
$MY_CHANNEL="MY_CHANNEL";
$APP_ID = "APP_ID";
$APP_KEY = "APP_KEY";
$APP_SECRET = "APP_SECRET";
$APP_CLUSTER = "APP_CLUSTER";

$message=$_POST['message'];

require __DIR__ . '/vendor/autoload.php';

$pusher = new Pusher\Pusher($APP_KEY, $APP_SECRET, $APP_ID, array('cluster' => $APP_CLUSTER));

$pusher->trigger($MY_CHANNEL, 'my-event', $message);

?>
