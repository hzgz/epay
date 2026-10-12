<?php
if(!defined('IN_CRONLITE'))exit();

function shop_order_h($text){
	return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function shop_order_image_url($path){
	$path = trim((string)$path);
	if($path === '') return '';
	if(strpos($path, '//') === 0 || preg_match('/^https?:\/\//i', $path)){
		return $path;
	}
	if($path[0] !== '/'){
		$path = '/' . $path;
	}
	return $path;
}

function shop_order_money($value){
	return number_format((float)$value, 2, '.', '');
}

function shop_order_initial($text){
	$text = trim((string)$text);
	if($text === '') return 'M';
	return mb_substr($text, 0, 1, 'UTF-8');
}

$mallName = trim((string)$conf['mall_name']);
if($mallName === ''){
	$mallName = $conf['sitename'] . '商城';
}
$conf['title'] = $mallName;

$order = \lib\Mall::getShopOrderDetail($shopOrderNo, $tradeNo);
$statusCode = $order ? intval(isset($order['pay_status']) && $order['pay_status'] !== null ? $order['pay_status'] : $order['status']) : 0;
$isPaid = $statusCode === 1;
$canContinuePay = $order && $statusCode === 0 && !empty($order['trade_no']) && intval($order['type_id']) > 0;
$goodsImage = $order ? shop_order_image_url($order['goods_image_snapshot']) : '';
$payTypeText = $order && !empty($order['typeshowname']) ? $order['typeshowname'] : ($order && $order['pay_type'] === 'wxpay' ? '微信支付' : '支付宝');
$statusText = $order ? \lib\Mall::getShopOrderStatusText($statusCode) : '';
$payTime = $order ? (!empty($order['endtime']) ? $order['endtime'] : $order['paytime']) : '';
$orderUrl = $order ? '/shop/order.php?order_no=' . urlencode($order['shop_order_no']) : '/shop/';
$continuePayUrl = $canContinuePay ? '/submit2.php?typeid=' . intval($order['type_id']) . '&trade_no=' . urlencode($order['trade_no']) : '';

$shopStandalone = true;
require INDEX_ROOT.'head.php';
?>
<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/shop.css?v=20260805.10">
<div class="shop-shell">
  <section class="shop-order-page">
    <div class="container shop-container">
      <div class="shop-detail-breadcrumb">
        <a href="/shop/" class="shop-detail-back"><i class="fa fa-angle-left"></i> 返回商城</a>
        <span class="shop-detail-breadcrumb-name"><?php echo shop_order_h($mallName)?></span>
      </div>

      <?php if($order){?>
      <div class="shop-order-panel">
        <div class="shop-order-banner<?php echo $isPaid ? ' is-paid' : ''?>">
          <div class="shop-order-banner-copy">
            <h1><?php echo $isPaid ? '购买成功，订单已同步支付状态' : '订单已创建，请继续完成支付'?></h1>
            <p><?php echo $isPaid ? '你填写的收货信息和支付订单已保存到商城购买记录中，可凭订单号随时查询。' : '支付完成后页面会自动同步订单状态，如果已支付还未刷新，可以稍后重新打开这个订单页面查看。'?></p>
            <div class="shop-order-banner-actions">
              <?php if($canContinuePay){?>
              <a href="<?php echo shop_order_h($continuePayUrl)?>" class="shop-order-banner-btn shop-order-banner-btn-primary">继续支付</a>
              <?php }?>
              <a href="<?php echo shop_order_h($orderUrl)?>" class="shop-order-banner-btn">刷新订单</a>
              <a href="/shop/" class="shop-order-banner-btn">返回商城</a>
            </div>
          </div>
        </div>

        <div class="shop-order-goods">
          <div class="shop-order-goods-thumb">
            <?php if($goodsImage){?>
            <img src="<?php echo shop_order_h($goodsImage)?>" alt="<?php echo shop_order_h($order['goods_name_snapshot'])?>">
            <?php }else{?>
            <span><?php echo shop_order_h(shop_order_initial($order['goods_name_snapshot']))?></span>
            <?php }?>
          </div>
          <div class="shop-order-goods-copy">
            <h2><?php echo shop_order_h($order['goods_name_snapshot'])?></h2>
            <p>商城名称：<?php echo shop_order_h(!empty($order['mall_name']) ? $order['mall_name'] : $mallName)?>，支付方式：<?php echo shop_order_h($payTypeText)?></p>
            <div class="shop-order-goods-price-row">
              <div class="shop-order-goods-price">&yen;<?php echo shop_order_h(shop_order_money($order['money']))?></div>
              <span class="shop-order-goods-status<?php echo $isPaid ? ' is-paid' : ''?>"><?php echo shop_order_h($statusText)?></span>
            </div>
          </div>
        </div>

        <div class="shop-order-grid-panels">
          <div class="shop-order-card">
            <h3>订单号信息</h3>
            <div class="shop-order-info-list">
              <div class="shop-order-info-item">
                <span>系统订单号</span>
                <strong><?php echo shop_order_h($order['trade_no'])?></strong>
              </div>
              <div class="shop-order-info-item">
                <span>商户订单号</span>
                <strong><?php echo shop_order_h($order['out_trade_no'])?></strong>
              </div>
              <div class="shop-order-info-item">
                <span>商城订单号</span>
                <strong><?php echo shop_order_h($order['shop_order_no'])?></strong>
              </div>
              <div class="shop-order-info-item">
                <span>第三方单号</span>
                <strong><?php echo shop_order_h($order['api_trade_no_show'] ? $order['api_trade_no_show'] : '待回写')?></strong>
              </div>
            </div>
          </div>

          <div class="shop-order-card">
            <h3>支付信息</h3>
            <div class="shop-order-info-list">
              <div class="shop-order-info-item">
                <span>支付状态</span>
                <strong><?php echo shop_order_h($statusText)?></strong>
              </div>
              <div class="shop-order-info-item">
                <span>支付方式</span>
                <strong><?php echo shop_order_h($payTypeText)?></strong>
              </div>
              <div class="shop-order-info-item">
                <span>创建时间</span>
                <strong><?php echo shop_order_h($order['addtime'])?></strong>
              </div>
              <div class="shop-order-info-item">
                <span>支付完成时间</span>
                <strong><?php echo shop_order_h($payTime ? $payTime : '待支付')?></strong>
              </div>
            </div>
          </div>

          <div class="shop-order-card">
            <h3>购买人信息</h3>
            <div class="shop-order-info-list">
              <div class="shop-order-info-item">
                <span>姓名</span>
                <strong><?php echo shop_order_h($order['buyer_name'])?></strong>
              </div>
              <div class="shop-order-info-item">
                <span>电话</span>
                <strong><?php echo shop_order_h($order['buyer_phone'])?></strong>
              </div>
              <div class="shop-order-info-item shop-order-info-item-full">
                <span>邮箱</span>
                <strong><?php echo shop_order_h($order['buyer_email'])?></strong>
              </div>
              <div class="shop-order-info-item shop-order-info-item-full">
                <span>邮寄地址</span>
                <p><?php echo nl2br(shop_order_h($order['buyer_address']))?></p>
              </div>
            </div>
          </div>

          <div class="shop-order-card">
            <h3>订单备注</h3>
            <div class="shop-order-info-list">
              <div class="shop-order-info-item shop-order-info-item-full">
                <span>买家备注</span>
                <p><?php echo shop_order_h($order['buyer_remark'] ? $order['buyer_remark'] : '未填写备注')?></p>
              </div>
              <div class="shop-order-info-item">
                <span>收款商户ID</span>
                <strong><?php echo intval($order['uid'])?></strong>
              </div>
              <div class="shop-order-info-item">
                <span>订单页面</span>
                <strong><?php echo shop_order_h($order['shop_order_no'])?></strong>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php }else{?>
      <div class="shop-empty-card shop-order-empty">
        <div class="shop-empty-icon"><i class="fa fa-file-text-o"></i></div>
        <h3>未找到对应订单</h3>
        <p>请返回商城重新选择商品，或确认订单号后再次访问。</p>
      </div>
      <?php }?>
    </div>
  </section>
</div>
<?php if($order && !$isPaid){?>
<script>
setTimeout(function(){
	window.location.reload();
}, 5000);
</script>
<?php }?>
<?php require INDEX_ROOT.'foot.php';?>
