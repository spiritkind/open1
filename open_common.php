<?php
require_once('func.php');

function open_handle(string $name, array $valid_tokens, string $device_id, bool $log_enabled): void
{
    $token = $_SERVER['HTTP_X_OPEN1_TOKEN'] ?? '';

    $ok = false;
    foreach ($valid_tokens as $t) {
        if (hash_equals($t, $token)) {
            $ok = true;
            break;
        }
    }

    if ($log_enabled) {
        $ip = 'unknown';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $timestamp   = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $tokenForLog = $token !== '' ? $token : '(empty)';
        $result      = $ok ? 'authorized' : 'unauthorized';
        $logLine     = sprintf("[%s] script=%s ip=%s token=%s result=%s\n", $timestamp, $name, $ip, $tokenForLog, $result);
        file_put_contents(__DIR__ . '/../../logs/' . $name . '_access.log', $logLine, FILE_APPEND | LOCK_EX);
    }

    if (!$ok) {
        http_response_code(401);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Unauthorized\n";
        exit;
    }

    echo yandex_iot_power_change($device_id, "true") . "\n";
}
