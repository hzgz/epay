<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('require PHP >= 7.4 !');
}
$is_defend = true;
$allow_search = true;
include("../includes/common.php");

@header('Content-Type: application/json; charset=UTF-8');

if (empty($clientip)) {
    $clientip = function_exists('real_ip') ? real_ip() : $_SERVER['REMOTE_ADDR'];
}

if (!\lib\Mall::isVerifyOpen()) {
    exit(json_encode(['code' => -1, 'msg' => '商城下单验证未开启'], JSON_UNESCAPED_UNICODE));
}
if (!\lib\Mall::hasCaptchaConfig()) {
    exit(json_encode(['code' => -1, 'msg' => '商城暂未开启验证'], JSON_UNESCAPED_UNICODE));
}

if ($conf['captcha_version'] == '1') {
    $result = ['success' => 1, 'gt' => $conf['captcha_id'], 'version' => 1];
} else {
    $GtSdk = new \lib\GeetestLib($conf['captcha_id'], $conf['captcha_key']);
    $data = [
        'user_id' => 'shop',
        'client_type' => 'web',
        'ip_address' => $clientip,
    ];
    $result = $GtSdk->pre_process($data);
    $result['version'] = 0;
}
$_SESSION['gtserver'] = $result['success'];
exit(json_encode(['code' => 0, 'data' => $result], JSON_UNESCAPED_UNICODE));
