<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

function mall_delivery_text($type){
	$list = \lib\Mall::getDeliveryTypes();
	return isset($list[$type]) ? $list[$type] : $type;
}

function mall_purchase_status_text($status){
	return \lib\Mall::getShopOrderStatusText($status);
}

function mall_format_purchase_row($row){
	if(!$row)return $row;
	$row['status_show'] = mall_purchase_status_text(isset($row['pay_status']) && $row['pay_status'] !== null ? $row['pay_status'] : $row['status']);
	$row['paytime_show'] = !empty($row['endtime']) ? $row['endtime'] : (!empty($row['paytime']) ? $row['paytime'] : null);
	$row['api_trade_no_show'] = !empty($row['api_trade_no_real']) ? $row['api_trade_no_real'] : $row['api_trade_no'];
	if(empty($row['typeshowname']) && !empty($row['pay_type'])){
		$row['typeshowname'] = $row['pay_type'] == 'wxpay' ? '微信支付' : ($row['pay_type'] == 'alipay' ? '支付宝' : $row['pay_type']);
	}
	return $row;
}

switch($act){
case 'getGoods':
	$id = intval($_GET['id']);
	$row = $DB->find('mall_goods', '*', ['id'=>$id]);
	if(!$row)exit(json_encode(['code'=>-1,'msg'=>'商品不存在']));
	exit(json_encode(['code'=>0,'data'=>$row]));
break;

case 'saveGoods':
	$action = trim($_POST['action']);
	$id = intval($_POST['id']);
	$name = trim($_POST['name']);
	$image = trim($_POST['image']);
	$detail_images = \lib\Mall::normalizeDetailImages(isset($_POST['detail_images']) ? $_POST['detail_images'] : '');
	$delivery_type = trim($_POST['delivery_type']);
	$delivery_company = trim($_POST['delivery_company']);
	$weight = intval($_POST['weight']);
	$sort = intval($_POST['sort']);
	$status = intval($_POST['status']);
	$remark = trim($_POST['remark']);
	if($name == '')exit(json_encode(['code'=>-1,'msg'=>'商品名称不能为空']));
	if($weight < 1)$weight = 1;
	if(!isset(\lib\Mall::getDeliveryTypes()[$delivery_type]))$delivery_type = 'express';
	$data = [
		'name' => $name,
		'image' => $image ?: null,
		'detail_images' => $detail_images ?: null,
		'delivery_type' => $delivery_type,
		'delivery_company' => $delivery_company ?: null,
		'weight' => $weight,
		'sort' => $sort,
		'status' => $status == 1 ? 1 : 0,
		'remark' => $remark ?: null,
		'uptime' => 'NOW()',
	];
	if($action == 'edit' && $id > 0){
		$DB->update('mall_goods', $data, ['id'=>$id]);
	}else{
		$data['addtime'] = 'NOW()';
		$id = $DB->insert('mall_goods', $data);
	}
	if(!$id)exit(json_encode(['code'=>-1,'msg'=>'保存商品失败']));
	exit(json_encode(['code'=>0,'msg'=>'保存成功']));
break;

case 'delGoods':
	$id = intval($_GET['id']);
	if(!$id)exit(json_encode(['code'=>-1,'msg'=>'参数错误']));
	$DB->delete('mall_goods', ['id'=>$id]);
	$DB->delete('mall_rule_goods', ['goods_id'=>$id]);
	exit(json_encode(['code'=>0,'msg'=>'删除成功']));
break;

case 'setGoodsStatus':
	$id = intval($_GET['id']);
	$status = intval($_GET['status']) == 1 ? 1 : 0;
	$DB->update('mall_goods', ['status'=>$status, 'uptime'=>'NOW()'], ['id'=>$id]);
	exit(json_encode(['code'=>0,'msg'=>'状态已更新']));
break;

case 'getRule':
	$id = intval($_GET['id']);
	$row = $DB->find('mall_rule', '*', ['id'=>$id]);
	if(!$row)exit(json_encode(['code'=>-1,'msg'=>'区间规则不存在']));
	$row['goods_ids'] = \lib\Mall::getRuleGoodsIds($id);
	exit(json_encode(['code'=>0,'data'=>$row]));
break;

case 'saveRule':
	$action = trim($_POST['action']);
	$id = intval($_POST['id']);
	$name = trim($_POST['name']);
	$min_money = round(floatval($_POST['min_money']), 2);
	$max_money = round(floatval($_POST['max_money']), 2);
	$sort = intval($_POST['sort']);
	$status = intval($_POST['status']) == 1 ? 1 : 0;
	$remark = trim($_POST['remark']);
	$goods_ids = isset($_POST['goods_ids']) ? $_POST['goods_ids'] : [];
	if($name == '')exit(json_encode(['code'=>-1,'msg'=>'区间名称不能为空']));
	if($min_money < 0 || $max_money < 0)exit(json_encode(['code'=>-1,'msg'=>'金额不能小于0']));
	if(empty($goods_ids))exit(json_encode(['code'=>-1,'msg'=>'请至少选择一个商品']));
	try{
		\lib\Mall::validateRuleRange($id, $min_money, $max_money);
	}catch(Exception $e){
		exit(json_encode(['code'=>-1,'msg'=>$e->getMessage()]));
	}
	$data = [
		'name' => $name,
		'min_money' => sprintf('%.2f', $min_money),
		'max_money' => sprintf('%.2f', $max_money),
		'sort' => $sort,
		'status' => $status,
		'remark' => $remark ?: null,
		'uptime' => 'NOW()',
	];
	if($action == 'edit' && $id > 0){
		$DB->update('mall_rule', $data, ['id'=>$id]);
	}else{
		$data['addtime'] = 'NOW()';
		$id = $DB->insert('mall_rule', $data);
	}
	if(!$id)exit(json_encode(['code'=>-1,'msg'=>'保存区间规则失败']));
	\lib\Mall::saveRuleGoods($id, $goods_ids);
	exit(json_encode(['code'=>0,'msg'=>'保存成功']));
break;

case 'delRule':
	$id = intval($_GET['id']);
	if(!$id)exit(json_encode(['code'=>-1,'msg'=>'参数错误']));
	$DB->delete('mall_rule', ['id'=>$id]);
	$DB->delete('mall_rule_goods', ['rule_id'=>$id]);
	exit(json_encode(['code'=>0,'msg'=>'删除成功']));
break;

case 'orderList':
	$sql = "1=1";
	$deleted = isset($_POST['deleted']) ? intval($_POST['deleted']) : 0;
	if($deleted == 1){
		$sql .= " AND A.is_delete=1";
	}else{
		$sql .= " AND A.is_delete=0";
	}
	if(isset($_POST['status']) && $_POST['status'] !== ''){
		$status = intval($_POST['status']);
		$sql .= " AND IFNULL(B.status,A.status)='{$status}'";
	}
	if(!empty($_POST['starttime'])){
		$starttime = daddslashes($_POST['starttime']);
		$sql .= " AND A.addtime>='{$starttime} 00:00:00'";
	}
	if(!empty($_POST['endtime'])){
		$endtime = daddslashes($_POST['endtime']);
		$sql .= " AND A.addtime<='{$endtime} 23:59:59'";
	}
	if(isset($_POST['value']) && $_POST['value'] !== ''){
		$value = daddslashes(trim($_POST['value']));
		$column = trim($_POST['column']);
		if($column == 'trade_no'){
			$sql .= " AND A.trade_no='{$value}'";
		}elseif($column == 'mall_order_no'){
			$sql .= " AND A.mall_order_no='{$value}'";
		}elseif($column == 'api_trade_no'){
			$sql .= " AND (A.api_trade_no='{$value}' OR B.api_trade_no='{$value}')";
		}elseif($column == 'out_trade_no'){
			$sql .= " AND A.out_trade_no='{$value}'";
		}else{
			$sql .= " AND A.goods_name_snapshot LIKE '%{$value}%'";
		}
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) FROM pre_mall_order A LEFT JOIN pre_order B ON A.trade_no=B.trade_no WHERE {$sql}");
	$list = $DB->getAll("SELECT A.*,B.status pay_status,B.endtime,B.type,B.channel,B.api_trade_no api_trade_no_real,C.name typename,C.showname typeshowname FROM pre_mall_order A LEFT JOIN pre_order B ON A.trade_no=B.trade_no LEFT JOIN pre_type C ON B.type=C.id WHERE {$sql} ORDER BY A.id DESC LIMIT {$offset},{$limit}");
	foreach($list as &$row){
		$row['status_show'] = \lib\Mall::getStatusText(is_null($row['pay_status']) ? $row['status'] : $row['pay_status']);
		$row['paytime_show'] = $row['endtime'] ? $row['endtime'] : $row['paytime'];
		$row['api_trade_no_show'] = $row['api_trade_no_real'] ? $row['api_trade_no_real'] : $row['api_trade_no'];
		$row['delivery_type_show'] = mall_delivery_text($row['delivery_type']);
	}
	unset($row);
	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'getOrder':
	$id = intval($_GET['id']);
	$row = $DB->getRow("SELECT A.*,B.status pay_status,B.endtime,B.type,B.channel,B.api_trade_no api_trade_no_real,C.name typename,C.showname typeshowname FROM pre_mall_order A LEFT JOIN pre_order B ON A.trade_no=B.trade_no LEFT JOIN pre_type C ON B.type=C.id WHERE A.id=:id LIMIT 1", [':id'=>$id]);
	if(!$row)exit(json_encode(['code'=>-1,'msg'=>'商城订单不存在']));
	$row['status_show'] = \lib\Mall::getStatusText(is_null($row['pay_status']) ? $row['status'] : $row['pay_status']);
	$row['paytime_show'] = $row['endtime'] ? $row['endtime'] : $row['paytime'];
	$row['api_trade_no_show'] = $row['api_trade_no_real'] ? $row['api_trade_no_real'] : $row['api_trade_no'];
	$row['delivery_type_show'] = mall_delivery_text($row['delivery_type']);
	exit(json_encode(['code'=>0,'data'=>$row]));
break;

case 'deleteOrder':
	$id = intval($_POST['id']);
	if(!$id)exit(json_encode(['code'=>-1,'msg'=>'参数错误']));
	$result = \lib\Mall::deleteMallOrders([$id]);
	$msg = '删除商城订单成功';
	if($result['order_count'] > 0 || $result['purchase_count'] > 0){
		$msg .= '，同步删除易支付订单 '.$result['order_count'].' 条、购买记录 '.$result['purchase_count'].' 条';
	}
	exit(json_encode(['code'=>0,'msg'=>$msg,'data'=>$result]));
break;

case 'batchDeleteOrder':
	$ids = isset($_POST['ids']) ? $_POST['ids'] : [];
	if(!is_array($ids) || empty($ids))exit(json_encode(['code'=>-1,'msg'=>'请选择要删除的商城订单']));
	$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
	if(empty($ids))exit(json_encode(['code'=>-1,'msg'=>'参数错误']));
	$result = \lib\Mall::deleteMallOrders($ids);
	$msg = '批量删除商城订单成功，共删除 '.$result['mall_order_count'].' 条';
	if($result['order_count'] > 0 || $result['purchase_count'] > 0){
		$msg .= '，同步删除易支付订单 '.$result['order_count'].' 条、购买记录 '.$result['purchase_count'].' 条';
	}
	exit(json_encode(['code'=>0,'msg'=>$msg,'data'=>$result]));
break;

case 'restoreOrder':
	$id = intval($_POST['id']);
	if(!$id)exit(json_encode(['code'=>-1,'msg'=>'参数错误']));
	$DB->update('mall_order', ['is_delete'=>0, 'uptime'=>'NOW()'], ['id'=>$id]);
	exit(json_encode(['code'=>0,'msg'=>'订单已恢复']));
break;

case 'generateLogisticsNo':
	try{
		$logisticsNo = \lib\Mall::generateLogisticsNo();
	}catch(Exception $e){
		exit(json_encode(['code'=>-1, 'msg'=>$e->getMessage()]));
	}
	exit(json_encode(['code'=>0, 'data'=>$logisticsNo]));
break;

case 'updateLogistics':
	$id = intval($_POST['id']);
	$delivery_type = trim($_POST['delivery_type']);
	$delivery_company = trim($_POST['delivery_company']);
	$logistics_no = trim($_POST['logistics_no']);
	if(!$id)exit(json_encode(['code'=>-1,'msg'=>'参数错误']));
	if(!isset(\lib\Mall::getDeliveryTypes()[$delivery_type]))$delivery_type = 'express';
	$data = [
		'delivery_type' => $delivery_type,
		'delivery_company' => $delivery_company ?: null,
		'logistics_no' => $logistics_no ?: null,
		'uptime' => 'NOW()',
	];
	$DB->update('mall_order', $data, ['id'=>$id]);
	exit(json_encode(['code'=>0,'msg'=>'物流信息已保存']));
break;

case 'purchaseList':
	$sql = "1=1";
	if(isset($_POST['status']) && $_POST['status'] !== ''){
		$status = intval($_POST['status']);
		$sql .= " AND IFNULL(B.status,A.status)='{$status}'";
	}
	if(!empty($_POST['starttime'])){
		$starttime = daddslashes($_POST['starttime']);
		$sql .= " AND A.addtime>='{$starttime} 00:00:00'";
	}
	if(!empty($_POST['endtime'])){
		$endtime = daddslashes($_POST['endtime']);
		$sql .= " AND A.addtime<='{$endtime} 23:59:59'";
	}
	if(isset($_POST['value']) && $_POST['value'] !== ''){
		$value = daddslashes(trim($_POST['value']));
		$column = trim($_POST['column']);
		if($column == 'trade_no'){
			$sql .= " AND A.trade_no='{$value}'";
		}elseif($column == 'shop_order_no'){
			$sql .= " AND A.shop_order_no='{$value}'";
		}elseif($column == 'api_trade_no'){
			$sql .= " AND (A.api_trade_no='{$value}' OR B.api_trade_no='{$value}')";
		}elseif($column == 'out_trade_no'){
			$sql .= " AND A.out_trade_no='{$value}'";
		}elseif($column == 'buyer_name'){
			$sql .= " AND A.buyer_name LIKE '%{$value}%'";
		}elseif($column == 'buyer_phone'){
			$sql .= " AND A.buyer_phone LIKE '%{$value}%'";
		}else{
			$sql .= " AND A.goods_name_snapshot LIKE '%{$value}%'";
		}
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) FROM pre_mall_purchase A LEFT JOIN pre_order B ON A.trade_no=B.trade_no WHERE {$sql}");
	$list = $DB->getAll("SELECT A.*,B.status pay_status,B.endtime,B.type,B.channel,B.api_trade_no api_trade_no_real,C.name typename,C.showname typeshowname FROM pre_mall_purchase A LEFT JOIN pre_order B ON A.trade_no=B.trade_no LEFT JOIN pre_type C ON A.type_id=C.id WHERE {$sql} ORDER BY A.id DESC LIMIT {$offset},{$limit}");
	foreach($list as &$row){
		$row = mall_format_purchase_row($row);
	}
	unset($row);
	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'getPurchase':
	$id = intval($_GET['id']);
	$row = $DB->getRow("SELECT A.*,B.status pay_status,B.endtime,B.type,B.channel,B.api_trade_no api_trade_no_real,C.name typename,C.showname typeshowname FROM pre_mall_purchase A LEFT JOIN pre_order B ON A.trade_no=B.trade_no LEFT JOIN pre_type C ON A.type_id=C.id WHERE A.id=:id LIMIT 1", [':id'=>$id]);
	if(!$row)exit(json_encode(['code'=>-1,'msg'=>'购买记录不存在']));
	$row = mall_format_purchase_row($row);
	exit(json_encode(['code'=>0,'data'=>$row]));
break;

case 'deletePurchase':
	$id = intval($_POST['id']);
	if(!$id)exit(json_encode(['code'=>-1,'msg'=>'参数错误']));
	$result = \lib\Mall::deletePurchaseRecords([$id], true);
	$msg = '删除购买记录成功';
	if($result['order_count'] > 0 || $result['mall_order_count'] > 0){
		$msg .= '，同步删除易支付订单 '.$result['order_count'].' 条、商城订单 '.$result['mall_order_count'].' 条';
	}
	exit(json_encode(['code'=>0,'msg'=>$msg,'data'=>$result]));
break;

case 'batchDeletePurchase':
	$ids = isset($_POST['ids']) ? $_POST['ids'] : [];
	if(!is_array($ids) || empty($ids))exit(json_encode(['code'=>-1,'msg'=>'请选择要删除的购买记录']));
	$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
	if(empty($ids))exit(json_encode(['code'=>-1,'msg'=>'参数错误']));
	$result = \lib\Mall::deletePurchaseRecords($ids, true);
	$msg = '批量删除购买记录成功，共删除 '.$result['purchase_count'].' 条';
	if($result['order_count'] > 0 || $result['mall_order_count'] > 0){
		$msg .= '，同步删除易支付订单 '.$result['order_count'].' 条、商城订单 '.$result['mall_order_count'].' 条';
	}
	exit(json_encode(['code'=>0,'msg'=>$msg,'data'=>$result]));
break;

case 'cleanPurchaseRecords':
	$days = intval($_POST['days']);
	try{
		$result = \lib\Mall::cleanupExpiredPurchases($days);
		$DB->exec("OPTIMIZE TABLE `pre_mall_purchase`");
		$DB->exec("OPTIMIZE TABLE `pre_mall_order`");
		$DB->exec("OPTIMIZE TABLE `pre_order`");
		$msg = '已清理 '.$days.' 天前未支付购买记录 '.$result['purchase_count'].' 条';
		if($result['order_count'] > 0 || $result['mall_order_count'] > 0){
			$msg .= '，同步删除易支付订单 '.$result['order_count'].' 条、商城订单 '.$result['mall_order_count'].' 条';
		}
		exit(json_encode(['code'=>0,'msg'=>$msg,'data'=>$result]));
	}catch(Exception $e){
		exit(json_encode(['code'=>-1,'msg'=>$e->getMessage()]));
	}
break;

case 'cleanPaidPurchaseRecords':
	$days = intval($_POST['days']);
	try{
		$result = \lib\Mall::cleanupExpiredPurchases($days, 'paid');
		$DB->exec("OPTIMIZE TABLE `pre_mall_purchase`");
		$DB->exec("OPTIMIZE TABLE `pre_mall_order`");
		$DB->exec("OPTIMIZE TABLE `pre_order`");
		$msg = '已清理 '.$days.' 天前已支付购买记录 '.$result['purchase_count'].' 条';
		if($result['order_count'] > 0 || $result['mall_order_count'] > 0){
			$msg .= '，并同步删除易支付订单 '.$result['order_count'].' 条、商城订单 '.$result['mall_order_count'].' 条';
		}
		exit(json_encode(['code'=>0,'msg'=>$msg,'data'=>$result]));
	}catch(Exception $e){
		exit(json_encode(['code'=>-1,'msg'=>$e->getMessage()]));
	}
break;

default:
	exit(json_encode(['code'=>-4,'msg'=>'No Act']));
break;
}
