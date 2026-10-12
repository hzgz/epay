<?php
// Block crawlers and abnormal requests, but keep storefront previews debuggable.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? null;
$acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
$connectionHeader = $_SERVER['HTTP_CONNECTION'] ?? '';

$headlessShopPreviewAllowed = strpos($userAgent, 'HeadlessChrome') !== false
	&& in_array($requestMethod, ['GET', 'HEAD'], true)
	&& in_array($requestPath, ['/shop', '/shop/', '/shop/index.php', '/shop/detail.php', '/shop/order.php'], true);

if($allow_search){
	if(strpos($userAgent, 'Baiduspider')!==false || strpos($userAgent, '360Spider')!==false || strpos($userAgent, 'YisouSpider')!==false || strpos($userAgent, 'Sogou web spider')!==false || strpos($userAgent, 'Sogou inst spider')!==false || strpos($userAgent, 'bingbot/')!==false || strpos($userAgent, 'Googlebot/')!==false || strpos($userAgent, 'Bytespider')!==false){
		return;
	}
}

if(
	strpos($userAgent, 'Baiduspider')!==false
	|| strpos($userAgent, '360Spider')!==false
	|| strpos($userAgent, 'YisouSpider')!==false
	|| strpos($userAgent, 'Sogou web spider')!==false
	|| strpos($userAgent, 'Sogou inst spider')!==false
	|| strpos($userAgent, 'bingbot/')!==false
	|| strpos($userAgent, 'Googlebot/')!==false
	|| strpos($userAgent, 'Bytespider')!==false
	|| strpos($userAgent, 'GPTBot/')!==false
	|| strpos($userAgent, 'python-requests')!==false
	|| strpos($userAgent, 'aiohttp/')!==false
	|| strpos($userAgent, 'MJ12bot')!==false
	|| strpos($userAgent, 'SemrushBot')!==false
	|| strpos($userAgent, 'AhrefsBot')!==false
	|| strpos($userAgent, 'DotBot')!==false
	|| strpos($userAgent, 'CensysInspect/')!==false
	|| strpos($referer, '.tr.com')!==false
	|| strpos($referer, '.wsd.com')!==false
	|| strpos($referer, '.oa.com')!==false
	|| strpos($referer, '.cm.com')!==false
	|| strpos($referer, '/membercomprehensive/')!==false
	|| strpos($referer, 'www.internalrequests.org')!==false
	|| !isset($_SERVER['HTTP_ACCEPT'])
	|| empty($userAgent)
	|| stripos($userAgent, 'manager')!==false
	|| (strpos($userAgent, 'ozilla')!==false && strpos($userAgent, 'Mozilla')===false)
	|| (strpos($userAgent, 'Windows NT 6.1')!==false && $acceptHeader==='*/*')
	|| (strpos($userAgent, 'Windows NT 5.1')!==false && $acceptHeader==='*/*')
	|| (strpos((string)$acceptHeader, 'vnd.wap.wml')!==false && strpos($userAgent, 'Windows NT 5.1')!==false)
	|| isset($_COOKIE['ASPSESSIONIDQASBQDRC'])
	|| strpos($userAgent, 'Alibaba.Security.Heimdall')!==false
	|| strpos($userAgent, 'wechatdevtools/')!==false
	|| strpos($userAgent, 'libcurl/')!==false
	|| strpos($userAgent, 'Go-http-client')!==false
	|| (strpos($userAgent, 'HeadlessChrome')!==false && !$headlessShopPreviewAllowed)
	|| (strpos($userAgent, 'iPhone OS 9_1')!==false && $connectionHeader==='close')
	|| strpos($userAgent, 'Chrome/359.0.0.288')!==false
	|| (strpos($userAgent, 'iPhone OS 11_0')!==false && strpos($acceptLanguage, 'en')!==false && strpos($acceptLanguage, 'zh')===false)
	|| (strpos($userAgent, ' QQ/')!==false && strpos($userAgent, ' Edg/')!==false)
){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$remoteiplong=bindec(decbin(ip2long($clientip)));
$iptables='979632128~979763199|1909850112~1909981183|2110914560~2110980095|3664510976~3664642047|3679584256~3679649791|995233792~995237887|2105150464~2105208831|3078732898|2061546274';
foreach(explode('|',$iptables) as $iprows){
	if($remoteiplong==$iprows){
		header("HTTP/1.1 404 Not Found");
		exit;
	}
	$ipbanrange=explode('~',$iprows);
	if($remoteiplong>=$ipbanrange[0] && $remoteiplong<=$ipbanrange[1]){
		header("HTTP/1.1 404 Not Found");
		exit;
	}
}
