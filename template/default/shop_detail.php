<?php
if(!defined('IN_CRONLITE'))exit();

function shop_detail_h($text){
	return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function shop_detail_image_url($path){
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

function shop_detail_money($value){
	return number_format((float)$value, 2, '.', '');
}

function shop_detail_initial($text){
	$text = trim((string)$text);
	if($text === '') return 'M';
	return mb_substr($text, 0, 1, 'UTF-8');
}

$mallName = trim((string)$conf['mall_name']);
if($mallName === ''){
	$mallName = $conf['sitename'] . '商城';
}
$conf['title'] = $mallName;

$deliveryTypes = \lib\Mall::getDeliveryTypes();
$goods = $DB->getRow("SELECT * FROM pre_mall_goods WHERE id=:id AND status=1 LIMIT 1", [':id'=>intval($goodsId)]);
$ruleRows = [];
$detailImages = [];
$configuredDetailImages = [];
$goodsImage = '';
$deliveryText = '商品展示';
$remarkText = '';
$hasMoneyRange = false;
$minMoney = 0;
$maxMoney = 0;
$salesText = '0';
$shopPayTypes = \lib\Mall::getShopPayTypes();
$shopPayAvailable = !empty($shopPayTypes['wxpay']['available']) || !empty($shopPayTypes['alipay']['available']);
$shopPayUid = intval($conf['mall_pay_uid']);
$shopVerifyOpen = \lib\Mall::isVerifyOpen();
$shopVerifyConfigured = \lib\Mall::hasCaptchaConfig();
$canSubmitOrder = false;
$buyNotice = '';

if($goods){
	$goodsId = intval($goods['id']);
	$goodsImage = shop_detail_image_url($goods['image']);
	$deliveryKey = trim((string)$goods['delivery_type']);
	$deliveryText = isset($deliveryTypes[$deliveryKey]) ? $deliveryTypes[$deliveryKey] : '商品展示';
	$remarkText = trim((string)$goods['remark']);
	$ruleRows = $DB->getAll("SELECT B.name,B.min_money,B.max_money FROM pre_mall_rule_goods A INNER JOIN pre_mall_rule B ON A.rule_id=B.id WHERE A.goods_id=:goods_id AND B.status=1 ORDER BY B.sort ASC,B.id ASC,A.sort ASC,A.id ASC", [':goods_id'=>$goodsId]);
	if(!$ruleRows)$ruleRows = [];
	foreach($ruleRows as $ruleRow){
		$ruleMin = floatval($ruleRow['min_money']);
		$ruleMax = floatval($ruleRow['max_money']);
		if(!$hasMoneyRange || $ruleMin < $minMoney){
			$minMoney = $ruleMin;
		}
		if(!$hasMoneyRange || $ruleMax > $maxMoney){
			$maxMoney = $ruleMax;
		}
		$hasMoneyRange = true;
	}
	$configuredDetailImages = \lib\Mall::splitDetailImages(isset($goods['detail_images']) ? $goods['detail_images'] : '');
	foreach($configuredDetailImages as $image){
		$image = shop_detail_image_url($image);
		if($image !== ''){
			$detailImages[] = $image;
		}
	}
	if(empty($detailImages) && $goodsImage !== ''){
		$detailImages[] = $goodsImage;
	}
	$salesCount = max(18, ($goodsId % 90) + count($ruleRows) * 7 + count($detailImages) * 3);
	$salesText = $salesCount >= 100 ? '100+' : (string)$salesCount;

	if(!$hasMoneyRange){
		$buyNotice = '当前商品暂未配置购买金额';
	}elseif($shopVerifyOpen && !$shopVerifyConfigured){
		$buyNotice = '商城暂未开启验证';
	}elseif($shopPayUid < 1){
		$buyNotice = '商城暂未配置收款商户ID';
	}elseif(!$shopPayAvailable){
		$buyNotice = '当前商户暂未开启微信或支付宝支付';
	}else{
		$canSubmitOrder = true;
	}
}

$shopStandalone = true;
require INDEX_ROOT.'head.php';
?>
<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/shop.css?v=20260805.10">
<div class="shop-shell shop-detail-shell">
  <section class="shop-detail-band">
    <div class="container shop-container">
      <div class="shop-detail-breadcrumb">
        <a href="/shop/" class="shop-detail-back"><i class="fa fa-angle-left"></i> 返回商城</a>
        <span class="shop-detail-breadcrumb-name"><?php echo shop_detail_h($mallName)?></span>
      </div>

      <?php if($goods){?>
      <div class="shop-detail-panel">
        <div class="shop-detail-cover">
          <?php if($goodsImage){?>
          <img src="<?php echo shop_detail_h($goodsImage)?>" alt="<?php echo shop_detail_h($goods['name'])?>" loading="eager">
          <?php }else{?>
          <div class="shop-detail-placeholder"><?php echo shop_detail_h(shop_detail_initial($goods['name']))?></div>
          <?php }?>
        </div>
        <div class="shop-detail-summary">
          <div class="shop-detail-tag-row">
            <span class="shop-service-badge">正品保障</span>
            <span class="shop-detail-tag"><?php echo shop_detail_h($deliveryText)?></span>
          </div>
          <h1><?php echo shop_detail_h($goods['name'])?></h1>
          <p><?php echo shop_detail_h($remarkText !== '' ? $remarkText : '商品详情图按后台填写顺序展示，适合放置尺码、材质、实拍与售后说明。')?></p>
          <div class="shop-detail-price-box">
            <div>
              <?php if($hasMoneyRange){?>
              <strong>&yen;<?php echo shop_detail_h(shop_detail_money($maxMoney))?></strong>
              <span>区间最高价</span>
              <?php }else{?>
              <strong class="shop-price-empty">待配置</strong>
              <span>金额区间未绑定</span>
              <?php }?>
            </div>
            <em><?php echo shop_detail_h($salesText)?>人付款</em>
          </div>
          <div class="shop-detail-services" aria-label="商品服务承诺">
            <div class="shop-detail-service-row">
              <span class="shop-detail-service-icon"><i class="fa fa-truck"></i></span>
              <span class="shop-detail-service-main">48小时内发货</span>
              <span class="shop-detail-service-divider"></span>
              <span class="shop-detail-service-sub">快递：免运费</span>
              <span class="shop-detail-service-arrow"><i class="fa fa-angle-right"></i></span>
            </div>
            <div class="shop-detail-service-row">
              <span class="shop-detail-service-icon"><i class="fa fa-heart-o"></i></span>
              <span class="shop-detail-service-main">7天无理由退货</span>
              <span class="shop-detail-service-divider"></span>
              <span class="shop-detail-service-sub">极速退款</span>
              <span class="shop-detail-service-arrow"><i class="fa fa-angle-right"></i></span>
            </div>
          </div>
          <div class="shop-detail-meta">
            <div><span>商城名称</span><b><?php echo shop_detail_h($mallName)?></b></div>
            <div><span>发货方式</span><b><?php echo shop_detail_h($deliveryText)?></b></div>
            <div><span>详情图片</span><b><?php echo count($configuredDetailImages)?> 张</b></div>
          </div>
          <div class="shop-detail-action-strip">
            <a href="/shop/" class="shop-detail-cart-btn" aria-label="返回店铺">
              <i class="fa fa-shopping-cart"></i>
            </a>
            <button
              type="button"
              class="shop-detail-buy-btn<?php echo $canSubmitOrder ? '' : ' is-disabled'?>"
              <?php echo $canSubmitOrder ? 'data-toggle="modal" data-target="#shopBuyModal"' : 'disabled'?>
              aria-disabled="<?php echo $canSubmitOrder ? 'false' : 'true'?>"
            >立即购买</button>
          </div>
          <?php if(!$canSubmitOrder && $buyNotice !== ''){?>
          <div class="shop-detail-buy-tip"><?php echo shop_detail_h($buyNotice)?></div>
          <?php }?>
        </div>
      </div>

      <div class="shop-detail-content" id="detail-images">
        <div class="shop-detail-content-head">
          <h2>宝贝详情</h2>
          <span>DETAIL</span>
        </div>
        <?php if(!empty($detailImages)){?>
        <div class="shop-detail-image-flow">
          <?php foreach($detailImages as $index=>$image){?>
          <img src="<?php echo shop_detail_h($image)?>" alt="<?php echo shop_detail_h($goods['name'])?> 详情图 <?php echo $index+1?>" loading="lazy" decoding="async">
          <?php }?>
        </div>
        <?php }else{?>
        <div class="shop-empty-card">
          <div class="shop-empty-icon"><i class="fa fa-picture-o"></i></div>
          <h3>暂无详情图</h3>
          <p>请在后台商品管理中为该商品填写详情图片链接。</p>
        </div>
        <?php }?>
      </div>

      <div class="modal fade shop-order-modal" id="shopBuyModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-label="关闭"><span aria-hidden="true">&times;</span></button>
              <h4 class="modal-title">填写购买信息</h4>
            </div>
            <div class="modal-body">
              <div class="shop-order-modal-summary">
                <div class="shop-order-modal-thumb">
                  <?php if($goodsImage){?>
                  <img src="<?php echo shop_detail_h($goodsImage)?>" alt="<?php echo shop_detail_h($goods['name'])?>">
                  <?php }else{?>
                  <span><?php echo shop_detail_h(shop_detail_initial($goods['name']))?></span>
                  <?php }?>
                </div>
                <div class="shop-order-modal-copy">
                  <h5><?php echo shop_detail_h($goods['name'])?></h5>
                  <p><?php echo shop_detail_h($deliveryText)?></p>
                  <strong><?php echo $hasMoneyRange ? '&yen;'.shop_detail_h(shop_detail_money($maxMoney)) : '待配置'?></strong>
                </div>
              </div>

              <form id="shopBuyForm" class="shop-order-form" autocomplete="off">
                <input type="hidden" name="goods_id" value="<?php echo intval($goods['id'])?>">
                <div class="shop-order-grid">
                  <div class="shop-order-field">
                    <label for="shopBuyerName">姓名</label>
                    <input type="text" class="form-control" name="buyer_name" id="shopBuyerName" maxlength="60" placeholder="请输入收货姓名">
                  </div>
                  <div class="shop-order-field">
                    <label for="shopBuyerPhone">电话</label>
                    <input type="text" class="form-control" name="buyer_phone" id="shopBuyerPhone" maxlength="32" placeholder="请输入联系电话">
                  </div>
                  <div class="shop-order-field shop-order-field-full">
                    <label for="shopBuyerEmail">邮箱</label>
                    <input type="email" class="form-control" name="buyer_email" id="shopBuyerEmail" maxlength="120" placeholder="请输入联系邮箱">
                  </div>
                  <div class="shop-order-field shop-order-field-full">
                    <label for="shopBuyerAddress">邮寄地址</label>
                    <textarea class="form-control" name="buyer_address" id="shopBuyerAddress" rows="3" placeholder="请输入完整收货地址"></textarea>
                  </div>
                  <div class="shop-order-field shop-order-field-full">
                    <label for="shopBuyerRemark">备注</label>
                    <textarea class="form-control" name="buyer_remark" id="shopBuyerRemark" rows="2" placeholder="选填，可填写尺码、颜色或特殊说明"></textarea>
                  </div>
                  <div class="shop-order-field shop-order-field-full">
                    <label>支付方式</label>
                    <div class="shop-order-paytypes">
                      <label class="shop-order-paytype<?php echo !empty($shopPayTypes['wxpay']['available']) ? '' : ' is-disabled'?>">
                        <input type="radio" name="pay_type" value="wxpay" <?php echo !empty($shopPayTypes['wxpay']['available']) ? 'checked' : 'disabled'?>>
                        <span><i class="fa fa-weixin"></i> 微信支付</span>
                      </label>
                      <label class="shop-order-paytype<?php echo !empty($shopPayTypes['alipay']['available']) ? '' : ' is-disabled'?>">
                        <input type="radio" name="pay_type" value="alipay" <?php echo empty($shopPayTypes['wxpay']['available']) && !empty($shopPayTypes['alipay']['available']) ? 'checked' : (!empty($shopPayTypes['alipay']['available']) ? '' : 'disabled')?> <?php echo !empty($shopPayTypes['alipay']['available']) ? '' : 'disabled'?>>
                        <span><i class="fa fa-credit-card"></i> 支付宝</span>
                      </label>
                    </div>
                  </div>
                  <?php if($shopVerifyOpen){?>
                  <div class="shop-order-field shop-order-field-full">
                    <label>下单验证</label>
                    <div class="shop-order-captcha" id="shopCaptcha">
                      <div class="shop-order-captcha-text" id="shopCaptchaText">正在加载验证码</div>
                      <div class="shop-order-captcha-wait" id="shopCaptchaWait">验证控件加载中，请稍候...</div>
                    </div>
                  </div>
                  <?php }?>
                </div>
                <div class="shop-order-form-message" id="shopBuyMessage"></div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">稍后再买</button>
              <button type="button" class="btn btn-danger shop-order-submit" id="shopBuySubmit"<?php echo $canSubmitOrder ? '' : ' disabled'?>>提交订单并支付</button>
            </div>
          </div>
        </div>
      </div>
      <?php }else{?>
      <div class="shop-empty-card">
        <div class="shop-empty-icon"><i class="fa fa-cube"></i></div>
        <h3>商品不存在或已下架</h3>
        <p>请返回商城首页选择其它商品。</p>
      </div>
      <?php }?>
    </div>
  </section>
</div>
<?php if($goods){?>
<?php if($shopVerifyOpen && $shopVerifyConfigured){?>
<script>
window.appendChildOrg = window.appendChildOrg || Element.prototype.appendChild;
if(!window.__shopCaptchaReferrerPatched){
	Element.prototype.appendChild = function() {
		if(arguments[0] && arguments[0].tagName == 'SCRIPT'){
			arguments[0].setAttribute('referrerpolicy', 'no-referrer');
		}
		return window.appendChildOrg.apply(this, arguments);
	};
	window.__shopCaptchaReferrerPatched = true;
}
</script>
<script src="//static.geetest.com/static/tools/gt.js"></script>
<script src="//static.geetest.com/v4/gt4.js"></script>
<?php }?>
<script>
$(function(){
	var canSubmitOrder = <?php echo $canSubmitOrder ? 'true' : 'false'?>;
	var shopVerifyOpen = <?php echo $shopVerifyOpen ? 'true' : 'false'?>;
	var shopVerifyConfigured = <?php echo $shopVerifyConfigured ? 'true' : 'false'?>;
	var $modal = $('#shopBuyModal');
	var $form = $('#shopBuyForm');
	var $submit = $('#shopBuySubmit');
	var $message = $('#shopBuyMessage');
	var shopCaptchaReady = false;
	var shopSubmitPending = false;

	function showMessage(text, type){
		$message.removeClass('is-error is-success').text(text || '');
		if(type){
			$message.addClass(type === 'success' ? 'is-success' : 'is-error');
		}
	}

	function getSubmitData(){
		var formData = $form.serializeArray();
		if(shopVerifyOpen && $.shopCaptchaResult){
			$.each($.shopCaptchaResult, function(key, value){
				formData.push({name:key, value:value});
			});
		}
		return $.param(formData);
	}

	function resetShopCaptcha(){
		$.shopCaptchaResult = null;
		shopSubmitPending = false;
		if($.shopCaptchaObj && typeof $.shopCaptchaObj.reset === 'function'){
			$.shopCaptchaObj.reset();
		}
	}

	function submitOrder(){
		showMessage('');
		$submit.prop('disabled', true).text('正在创建订单...');
		$.ajax({
			type: 'POST',
			url: '/shop/create.php',
			data: getSubmitData(),
			dataType: 'json',
			success: function(res){
				if(res && res.code === 0 && res.data && res.data.pay_url){
					showMessage('订单创建成功，正在跳转支付...', 'success');
					window.location.href = res.data.pay_url;
					return;
				}
				showMessage(res && res.msg ? res.msg : '创建订单失败，请稍后重试', 'error');
				resetShopCaptcha();
				$submit.prop('disabled', false).text('提交订单并支付');
			},
			error: function(){
				showMessage('网络繁忙，请稍后重试', 'error');
				resetShopCaptcha();
				$submit.prop('disabled', false).text('提交订单并支付');
			}
		});
	}

	function initShopCaptcha(){
		if(!shopVerifyOpen || !shopVerifyConfigured){
			return;
		}
		$.ajax({
			url: '/shop/captcha.php',
			type: 'GET',
			cache: false,
			dataType: 'json',
			success: function (res) {
				if(!res || res.code !== 0 || !res.data){
					canSubmitOrder = false;
					$submit.prop('disabled', true).text('提交订单并支付');
					showMessage(res && res.msg ? res.msg : '商城暂未开启验证', 'error');
					return;
				}
				var data = res.data;
				var handlerShopCaptcha = function(captchaObj){
					$.shopCaptchaObj = captchaObj;
					captchaObj.appendTo('#shopCaptcha');
					captchaObj.onReady(function () {
						shopCaptchaReady = true;
						$('#shopCaptchaWait').hide();
					}).onSuccess(function () {
						var result = captchaObj.getValidate();
						if (!result) {
							return alert('请完成验证');
						}
						$.shopCaptchaResult = result;
						if(shopSubmitPending){
							shopSubmitPending = false;
							submitOrder();
						}
					}).onClose(function(){
						shopSubmitPending = false;
					});
				};
				$('#shopCaptchaText').hide();
				$('#shopCaptchaWait').show();
				if(data.version == 1){
					initGeetest4({
						captchaId: data.gt,
						product: 'popup',
						protocol: 'https://',
						riskType: 'slide',
						hideSuccess: true,
						nativeButton: {width: '100%'}
					}, handlerShopCaptcha);
				}else{
					initGeetest({
						gt: data.gt,
						challenge: data.challenge,
						new_captcha: data.new_captcha,
						product: 'popup',
						width: '100%',
						offline: !data.success
					}, handlerShopCaptcha);
				}
			},
			error: function(){
				canSubmitOrder = false;
				$submit.prop('disabled', true).text('提交订单并支付');
				showMessage('验证加载失败，请刷新页面后重试', 'error');
			}
		});
	}

	$('.shop-detail-buy-btn').on('click', function(){
		if(!canSubmitOrder){
			return false;
		}
	});

	$modal.on('hidden.bs.modal', function(){
		showMessage('');
		$form[0].reset();
		resetShopCaptcha();
		if($form.find('input[name="pay_type"][value="wxpay"]:enabled').length){
			$form.find('input[name="pay_type"][value="wxpay"]').prop('checked', true);
		}else if($form.find('input[name="pay_type"][value="alipay"]:enabled').length){
			$form.find('input[name="pay_type"][value="alipay"]').prop('checked', true);
		}
		$submit.prop('disabled', !canSubmitOrder).text('提交订单并支付');
	});

	$submit.on('click', function(){
		if(!canSubmitOrder){
			return;
		}
		if(shopVerifyOpen){
			if(!shopVerifyConfigured){
				showMessage('商城暂未开启验证', 'error');
				return;
			}
			if(!shopCaptchaReady || !$.shopCaptchaObj){
				showMessage('验证加载中，请稍后重试', 'error');
				return;
			}
			showMessage('');
			resetShopCaptcha();
			shopSubmitPending = true;
			if(typeof $.shopCaptchaObj.showCaptcha === 'function'){
				$.shopCaptchaObj.showCaptcha();
			}
			return;
		}
		submitOrder();
	});

	if(shopVerifyOpen && shopVerifyConfigured){
		initShopCaptcha();
	}
});
</script>
<?php }?>
<?php require INDEX_ROOT.'foot.php';?>
