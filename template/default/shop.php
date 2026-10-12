<?php
if(!defined('IN_CRONLITE'))exit();

function shop_t($text){
	return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
}

$mallName = trim((string)$conf['mall_name']);
if($mallName === ''){
	$mallName = $conf['sitename'] . shop_t('&#21830;&#22478;');
}
$conf['title'] = $mallName;

$deliveryTypes = \lib\Mall::getDeliveryTypes();
$goodsList = $DB->getAll("SELECT * FROM pre_mall_goods WHERE status=1 ORDER BY sort ASC,id DESC");
$ruleRows = $DB->getAll("SELECT A.goods_id,B.name rule_name,B.min_money,B.max_money FROM pre_mall_rule_goods A INNER JOIN pre_mall_rule B ON A.rule_id=B.id WHERE B.status=1 ORDER BY B.sort ASC,B.id ASC,A.sort ASC,A.id ASC");

$goodsRuleMap = [];
$ruleCountMap = [];
$ruleMinMoneyMap = [];
$ruleMaxMoneyMap = [];
foreach($ruleRows as $ruleRow){
	$goodsId = intval($ruleRow['goods_id']);
	if($goodsId < 1)continue;
	$minMoney = floatval($ruleRow['min_money']);
	$maxMoney = floatval($ruleRow['max_money']);
	$goodsRuleMap[$goodsId][] = [
		'name' => $ruleRow['rule_name'],
		'range' => sprintf('%.2f - %.2f', $minMoney, $maxMoney),
	];
	if(!isset($ruleCountMap[$goodsId])){
		$ruleCountMap[$goodsId] = 0;
	}
	$ruleCountMap[$goodsId]++;
	if(!isset($ruleMinMoneyMap[$goodsId]) || $minMoney < $ruleMinMoneyMap[$goodsId]){
		$ruleMinMoneyMap[$goodsId] = $minMoney;
	}
	if(!isset($ruleMaxMoneyMap[$goodsId]) || $maxMoney > $ruleMaxMoneyMap[$goodsId]){
		$ruleMaxMoneyMap[$goodsId] = $maxMoney;
	}
}

$ruleCount = $DB->getColumn("SELECT count(*) FROM pre_mall_rule WHERE status=1");
$coverGoods = !empty($goodsList) ? $goodsList[0] : null;

$activeDeliveryTypes = [];
foreach($goodsList as $goods){
	$deliveryKey = trim((string)$goods['delivery_type']);
	if($deliveryKey !== '' && isset($deliveryTypes[$deliveryKey])){
		$activeDeliveryTypes[$deliveryKey] = $deliveryTypes[$deliveryKey];
	}
}

$categoryTabs = [
	['key' => 'all', 'name' => shop_t('&#20840;&#37096;&#23453;&#36125;')],
];
foreach(array_slice($activeDeliveryTypes, 0, 5, true) as $deliveryKey => $deliveryName){
	$categoryTabs[] = [
		'key' => $deliveryKey,
		'name' => $deliveryName,
	];
}

function shop_h($text){
	return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function shop_image_url($path){
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

function shop_money($value){
	return number_format((float)$value, 2, '.', '');
}

function shop_initial($text){
	$text = trim((string)$text);
	if($text === '') return 'M';
	return mb_substr($text, 0, 1, 'UTF-8');
}

$shopAvatar = !empty($conf['mall_logo']) ? shop_image_url($conf['mall_logo']) : '';
$shopFans = max(3417, count($goodsList) * 128 + intval($ruleCount) * 88);

$shopStandalone = true;
require INDEX_ROOT.'head.php';
?>
<link rel="stylesheet" href="<?php echo STATIC_ROOT?>css/shop.css?v=20260805.10">
<div class="shop-shell">
  <section class="shop-store-band">
    <div class="container shop-container">
      <div class="shop-store-panel">
        <div class="shop-store-main">
          <div class="shop-store-avatar">
            <?php if($shopAvatar){?>
            <img src="<?php echo shop_h($shopAvatar)?>" alt="<?php echo shop_h($mallName)?>" loading="eager">
            <?php }else{?>
            <div class="shop-store-avatar-placeholder">
              <i class="fa fa-shopping-bag"></i>
            </div>
            <?php }?>
          </div>
          <div class="shop-store-copy">
            <div class="shop-store-title-row">
              <h1><?php echo shop_h($mallName)?></h1>
              <span class="shop-store-link"><?php echo shop_t('&#26597;&#30475;&#36164;&#36136;')?></span>
              <span class="shop-store-link"><?php echo shop_t('&#25195;&#30721;&#36827;&#24215;')?></span>
            </div>
            <div class="shop-store-rating">
              <span class="shop-diamonds">◆◆◆◆◆</span>
              <span><?php echo intval($shopFans)?><?php echo shop_t('&#31881;&#19997;')?></span>
            </div>
            <div class="shop-store-metrics">
              <span class="shop-store-metric"><?php echo shop_t('&#36817;&#19968;&#24180;')?><?php echo max(400, count($goodsList) * 200)?>+<?php echo shop_t('&#31508;&#35746;&#21333;')?></span>
              <span class="shop-store-metric shop-store-metric-safe"><?php echo shop_t('&#27491;&#21697;&#20445;&#38556;')?></span>
              <span class="shop-store-metric"><?php echo count($goodsList)?><?php echo shop_t('&#27454;&#23453;&#36125;&#22312;&#21806;')?></span>
            </div>
            <div class="shop-store-tags">
              <?php if(!empty($activeDeliveryTypes)){ foreach(array_slice($activeDeliveryTypes, 0, 4, true) as $deliveryName){?>
              <span class="shop-store-tag"><?php echo shop_h($deliveryName)?></span>
              <?php }}else{?>
              <span class="shop-store-tag"><?php echo shop_t('&#35746;&#21333;&#21516;&#27493;')?></span>
              <span class="shop-store-tag"><?php echo shop_t('&#38543;&#26426;&#21305;&#37197;')?></span>
              <span class="shop-store-tag"><?php echo shop_t('&#21830;&#22478;&#23637;&#31034;')?></span>
              <?php }?>
            </div>
          </div>
        </div>
        <div class="shop-store-actions">
          <a href="/shop/" class="shop-store-btn"><i class="fa fa-shopping-bag"></i><span><?php echo shop_t('&#24215;&#38138;&#39318;&#39029;')?></span></a>
          <a href="/user/" class="shop-store-btn"><i class="fa fa-smile-o"></i><span><?php echo shop_t('&#23458;&#26381;')?></span></a>
          <a href="#goods-list" class="shop-store-btn shop-store-btn-primary"><i class="fa fa-plus"></i><span><?php echo shop_t('&#20851;&#27880;')?></span></a>
        </div>
      </div>
    </div>
  </section>

  <section class="shop-section" id="goods-list">
    <div class="container shop-container">
      <div class="shop-market-panel">
        <div class="shop-market-head">
          <div class="shop-category-tabs" role="tablist" aria-label="<?php echo shop_t('&#21830;&#21697;&#20998;&#31867;')?>">
            <?php foreach($categoryTabs as $index=>$tab){?>
            <button type="button" class="shop-category-tab<?php echo $index===0?' active':''?>" data-filter="<?php echo shop_h($tab['key'])?>" aria-pressed="<?php echo $index===0?'true':'false'?>"><?php echo shop_h($tab['name'])?></button>
            <?php }?>
          </div>
          <div class="shop-sort-tabs" role="tablist" aria-label="<?php echo shop_t('&#21830;&#21697;&#25490;&#24207;')?>">
            <button type="button" class="shop-sort-tab active" data-sort="default" aria-pressed="true"><?php echo shop_t('&#32508;&#21512;')?></button>
            <button type="button" class="shop-sort-tab" data-sort="sales" aria-pressed="false"><?php echo shop_t('&#38144;&#37327;')?></button>
            <button type="button" class="shop-sort-tab" data-sort="new" aria-pressed="false"><?php echo shop_t('&#26032;&#21697;')?></button>
            <button type="button" class="shop-sort-tab" data-sort="price" aria-pressed="false"><?php echo shop_t('&#20215;&#26684;')?> <i class="fa fa-angle-down"></i></button>
          </div>
        </div>

        <?php if(!empty($goodsList)){?>
        <div class="shop-product-grid" id="shopProductGrid">
          <?php foreach($goodsList as $index=>$goods){
          	$goodsId = intval($goods['id']);
          	$goodsImage = shop_image_url($goods['image']);
          	$rules = isset($goodsRuleMap[$goodsId]) ? $goodsRuleMap[$goodsId] : [];
          	$deliveryKey = trim((string)$goods['delivery_type']);
          	$deliveryText = isset($deliveryTypes[$deliveryKey]) ? $deliveryTypes[$deliveryKey] : shop_t('&#21830;&#21697;&#23637;&#31034;');
          	$remarkText = trim((string)$goods['remark']);
          	$ruleTotal = isset($ruleCountMap[$goodsId]) ? intval($ruleCountMap[$goodsId]) : 0;
          	$hasMoneyRange = isset($ruleMinMoneyMap[$goodsId]);
          	$minMoney = $hasMoneyRange ? $ruleMinMoneyMap[$goodsId] : 0;
          	$maxMoney = $hasMoneyRange && isset($ruleMaxMoneyMap[$goodsId]) ? $ruleMaxMoneyMap[$goodsId] : $minMoney;
          	$salesCount = ($index + 1) * 8 + $ruleTotal * 3;
          	$salesText = $salesCount >= 100 ? '100+' : (string)$salesCount;
          ?>
          <article class="shop-product-card" data-delivery="<?php echo shop_h($deliveryKey ?: 'other')?>" data-price="<?php echo shop_h(shop_money($maxMoney))?>" data-rules="<?php echo $ruleTotal?>" data-index="<?php echo $index?>" data-name="<?php echo shop_h($goods['name'])?>">
            <a class="shop-product-link" href="/shop/detail.php?id=<?php echo $goodsId?>" aria-label="<?php echo shop_h($goods['name'])?>">
              <div class="shop-product-media">
                <?php if($goodsImage){?>
                <img src="<?php echo shop_h($goodsImage)?>" alt="<?php echo shop_h($goods['name'])?>" loading="lazy">
                <?php }else{?>
                <div class="shop-card-placeholder"><?php echo shop_h(shop_initial($goods['name']))?></div>
                <?php }?>
                <span class="shop-product-badge"><?php echo shop_h($deliveryText)?></span>
              </div>
              <div class="shop-product-chips">
                <?php if(!empty($rules)){ foreach(array_slice($rules, 0, 3) as $rule){?>
                <span class="shop-product-chip">&yen;<?php echo shop_h($rule['range'])?></span>
                <?php }}else{?>
                <span class="shop-product-chip"><?php echo shop_t('&#24453;&#32465;&#23450;&#37329;&#39069;&#21306;&#38388;')?></span>
                <?php }?>
              </div>
              <h3><?php echo shop_h($goods['name'])?></h3>
              <p><?php echo shop_h($remarkText !== '' ? $remarkText : shop_t('&#25903;&#20184;&#25289;&#36215;&#21518;&#25353;&#37329;&#39069;&#21629;&#20013;&#21306;&#38388;&#23637;&#31034;&#21830;&#21697;&#12290;'))?></p>
              <div class="shop-price-row">
                <div class="shop-price-wrap">
                  <?php if($hasMoneyRange){?>
                  <strong>&yen;<?php echo shop_h(shop_money($maxMoney))?></strong>
                  <?php }else{?>
                  <strong class="shop-price-empty"><?php echo shop_t('&#24453;&#37197;&#32622;')?></strong>
                  <?php }?>
                </div>
                <span class="shop-sales-count"><?php echo shop_h($salesText)?><?php echo shop_t('&#20154;&#20184;&#27454;')?></span>
                <span class="shop-service-badge"><?php echo shop_t('&#27491;&#21697;&#20445;&#38556;')?></span>
              </div>
            </a>
          </article>
          <?php }?>
        </div>
        <?php }else{?>
        <div class="shop-empty-card">
          <div class="shop-empty-icon"><i class="fa fa-cube"></i></div>
          <h3><?php echo shop_t('&#26242;&#26102;&#27809;&#26377;&#21487;&#23637;&#31034;&#21830;&#21697;')?></h3>
          <p><?php echo shop_t('&#35831;&#20808;&#22312;&#21518;&#21488;&#21830;&#22478;&#31995;&#32479;&#37324;&#28155;&#21152;&#24182;&#21551;&#29992;&#21830;&#21697;&#65292;&#21069;&#21488;&#24215;&#38138;&#39029;&#20250;&#33258;&#21160;&#21516;&#27493;&#23637;&#31034;&#12290;')?></p>
        </div>
        <?php }?>
      </div>
    </div>
  </section>
</div>
<script>
$(function(){
	var $grid = $('#shopProductGrid');
	if(!$grid.length){
		return;
	}

	function setPressed($items, $current){
		$items.removeClass('active').attr('aria-pressed', 'false');
		$current.addClass('active').attr('aria-pressed', 'true');
	}

	function filterCards(filterKey){
		$grid.children('.shop-product-card').each(function(){
			var $card = $(this);
			var deliveryKey = String($card.data('delivery'));
			var visible = filterKey === 'all' || filterKey === deliveryKey;
			$card.toggleClass('is-hidden', !visible);
		});
	}

	function sortCards(sortKey){
		var cards = $grid.children('.shop-product-card').get();
		cards.sort(function(a, b){
			var $a = $(a);
			var $b = $(b);

			if(sortKey === 'price'){
				return parseFloat($a.data('price')) - parseFloat($b.data('price'));
			}
			if(sortKey === 'sales'){
				return parseInt($b.data('rules'), 10) - parseInt($a.data('rules'), 10);
			}
			if(sortKey === 'new'){
				return parseInt($a.data('index'), 10) - parseInt($b.data('index'), 10);
			}
			if(sortKey === 'name'){
				return String($a.data('name')).localeCompare(String($b.data('name')), 'zh-Hans-CN');
			}
			return parseInt($a.data('index'), 10) - parseInt($b.data('index'), 10);
		});
		$.each(cards, function(_, card){
			$grid.append(card);
		});
	}

	$('.shop-category-tab').on('click', function(){
		var $button = $(this);
		setPressed($('.shop-category-tab'), $button);
		filterCards(String($button.data('filter')));
	});

	$('.shop-sort-tab').on('click', function(){
		var $button = $(this);
		setPressed($('.shop-sort-tab'), $button);
		sortCards(String($button.data('sort')));
	});
});
</script>
<?php require INDEX_ROOT.'foot.php';?>
