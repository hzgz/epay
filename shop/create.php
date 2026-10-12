<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('require PHP >= 7.4 !');
}
$is_defend = true;
$allow_search = true;
include("../includes/common.php");

@header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['code' => -1, 'msg' => '请求方式错误'], JSON_UNESCAPED_UNICODE));
}

$goodsId = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
if ($goodsId < 1) {
    exit(json_encode(['code' => -1, 'msg' => '商品参数错误'], JSON_UNESCAPED_UNICODE));
}

$clientIp = function_exists('real_ip') ? real_ip() : $_SERVER['REMOTE_ADDR'];
$verifyOpen = \lib\Mall::isVerifyOpen();
if ($verifyOpen) {
    if (!\lib\Mall::hasCaptchaConfig()) {
        exit(json_encode(['code' => -1, 'msg' => '商城暂未开启验证'], JSON_UNESCAPED_UNICODE));
    }
    if (!isset($_SESSION['gtserver'])) {
        exit(json_encode(['code' => -1, 'msg' => '验证加载失败，请刷新后重试'], JSON_UNESCAPED_UNICODE));
    }
    if (!verify_captcha('shop')) {
        exit(json_encode(['code' => -1, 'msg' => '验证失败，请重新验证'], JSON_UNESCAPED_UNICODE));
    }
}

try {
    $result = \lib\Mall::createShopPurchaseOrder($goodsId, $_POST, $clientIp);
    exit(json_encode(['code' => 0, 'msg' => '订单创建成功', 'data' => $result], JSON_UNESCAPED_UNICODE));
} catch (Exception $e) {
    exit(json_encode(['code' => -1, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
}
