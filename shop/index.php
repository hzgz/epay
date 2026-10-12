<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('require PHP >= 7.4 !');
}
$is_defend = true;
$allow_search = true;
include("../includes/common.php");

$loadfile = \lib\Template::load('shop');
include $loadfile;
