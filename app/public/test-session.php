<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'cookies' => $_COOKIE,
    'session' => $_SESSION,
]);
