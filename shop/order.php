<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('require PHP >= 7.4 !');
}
$is_defend = true;
$allow_search = true;
include("../includes/common.php");

$shopOrderNo = isset($_GET['order_no']) ? trim($_GET['order_no']) : '';
$tradeNo = isset($_GET['trade_no']) ? trim($_GET['trade_no']) : '';
if ($shopOrderNo === '' && $tradeNo === '') {
    header('Location: /shop/');
    exit;
}

$loadfile = \lib\Template::load('shop_order');
include $loadfile;
