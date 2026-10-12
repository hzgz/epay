<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('require PHP >= 7.4 !');
}
$is_defend = true;
$allow_search = true;
include("../includes/common.php");

$goodsId = 0;
if (isset($_GET['id'])) {
    $goodsId = intval($_GET['id']);
} elseif (isset($_GET['goods'])) {
    $goodsId = intval($_GET['goods']);
}

if ($goodsId < 1) {
    header('Location: /shop/');
    exit;
}

$loadfile = \lib\Template::load('shop_detail');
include $loadfile;
