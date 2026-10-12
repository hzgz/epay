<?php
include("../includes/common.php");
$title='商城系统 - 基本设置';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$deliveryTypes = \lib\Mall::getDeliveryTypes();
function mall_selected_attr($current, $value){
  return strval($current) === strval($value) ? ' selected' : '';
}
?>
<div class="container" style="padding-top:70px;">
  <div class="col-md-10 center-block" style="float:none;">
    <div class="panel panel-default">
      <div class="panel-heading"><h3 class="panel-title">商城系统 - 基本设置</h3></div>
      <div class="panel-body">
        <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
          <div class="form-group">
            <label class="col-sm-3 control-label">商城功能开关</label>
            <div class="col-sm-9">
              <select class="form-control" name="mall_open" default="<?php echo $conf['mall_open']?>">
                <option value="0"<?php echo mall_selected_attr($conf['mall_open'], 0); ?>>关闭</option>
                <option value="1"<?php echo mall_selected_attr($conf['mall_open'], 1); ?>>开启</option>
              </select>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">金额区间匹配</label>
            <div class="col-sm-9">
              <select class="form-control" name="mall_match_open" default="<?php echo $conf['mall_match_open']?>">
                <option value="0"<?php echo mall_selected_attr($conf['mall_match_open'], 0); ?>>关闭</option>
                <option value="1"<?php echo mall_selected_attr($conf['mall_match_open'], 1); ?>>开启</option>
              </select>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">商城名称</label>
            <div class="col-sm-9">
              <input type="text" name="mall_name" value="<?php echo $conf['mall_name']; ?>" class="form-control" placeholder="用于支付链路中的商城展示名称"/>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">收款商户ID</label>
            <div class="col-sm-9">
              <input type="text" name="mall_pay_uid" value="<?php echo $conf['mall_pay_uid']; ?>" class="form-control" placeholder="用于 /shop 商城前台购买订单的收款商户ID"/>
              <font color="green">商城前台用户提交购买表单后，会直接使用这里配置的商户号创建支付订单。</font>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">商城下单验证</label>
            <div class="col-sm-9">
              <select class="form-control" name="mall_verify_open" default="<?php echo $conf['mall_verify_open']?>">
                <option value="0"<?php echo mall_selected_attr($conf['mall_verify_open'], 0); ?>>关闭</option>
                <option value="1"<?php echo mall_selected_attr($conf['mall_verify_open'], 1); ?>>开启</option>
              </select>
              <font color="green">开启后，/shop 提交购买订单时会复用网站信息配置中的极验滑动验证。若未配置极验ID和密钥，则商城下单会提示“商城暂未开启验证”。</font>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">第三方商品名称</label>
            <div class="col-sm-9">
              <select class="form-control" name="mall_pass_goods_name" default="<?php echo $conf['mall_pass_goods_name']?>">
                <option value="1"<?php echo mall_selected_attr($conf['mall_pass_goods_name'], 1); ?>>传递匹配商品名称</option>
                <option value="0"<?php echo mall_selected_attr($conf['mall_pass_goods_name'], 0); ?>>保留原始商品名称</option>
              </select>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">商城订单号前缀</label>
            <div class="col-sm-9">
              <input type="text" name="mall_order_prefix" value="<?php echo $conf['mall_order_prefix']; ?>" class="form-control" placeholder="例如 MO"/>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">物流功能开关</label>
            <div class="col-sm-9">
              <select class="form-control" name="mall_logistics_open" default="<?php echo $conf['mall_logistics_open']?>">
                <option value="0"<?php echo mall_selected_attr($conf['mall_logistics_open'], 0); ?>>关闭</option>
                <option value="1"<?php echo mall_selected_attr($conf['mall_logistics_open'], 1); ?>>开启</option>
              </select>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">默认发货方式</label>
            <div class="col-sm-9">
              <select class="form-control" name="mall_default_delivery" default="<?php echo $conf['mall_default_delivery']?>">
                <?php foreach($deliveryTypes as $key=>$value){?>
                <option value="<?php echo $key?>"<?php echo mall_selected_attr($conf['mall_default_delivery'], $key); ?>><?php echo $value?></option>
                <?php }?>
              </select>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">物流单号前缀</label>
            <div class="col-sm-9">
              <input type="text" name="mall_logistics_prefix" value="<?php echo $conf['mall_logistics_prefix']; ?>" class="form-control" placeholder="例如 WL"/>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">物流单号随机长度</label>
            <div class="col-sm-9">
              <input type="text" name="mall_logistics_length" value="<?php echo $conf['mall_logistics_length']; ?>" class="form-control" placeholder="默认10"/>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">未支付购买记录自动清理</label>
            <div class="col-sm-9">
              <input type="number" min="7" max="30" name="mall_purchase_clean_days" value="<?php echo intval($conf['mall_purchase_clean_days']); ?>" class="form-control" placeholder="填写 7 到 30，填 0 关闭"/>
              <font color="green">填写 7 到 30 表示自动清理对应天数前的未支付商城购买记录，并同步清理关联的未支付易支付订单；填写 0 表示关闭自动清理。</font>
            </div>
          </div><br/>
          <div class="form-group">
            <label class="col-sm-3 control-label">已支付购买记录自动清理</label>
            <div class="col-sm-9">
              <input type="number" min="7" max="30" name="mall_purchase_paid_clean_days" value="<?php echo intval($conf['mall_purchase_paid_clean_days']); ?>" class="form-control" placeholder="填写 7 到 30，填 0 关闭"/>
              <font color="green">填写 7 到 30 表示自动清理对应天数前的已支付商城购买记录，并同步清理关联的易支付订单和商城订单；填写 0 表示关闭自动清理。</font>
            </div>
          </div><br/>
          <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
              <button type="submit" class="btn btn-primary">保存设置</button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading"><h3 class="panel-title">购买记录清理</h3></div>
      <div class="panel-body">
        <p class="text-muted">这里用于清理商城前台购买记录。无论清理未支付还是已支付记录，都会同步联动删除关联的易支付订单；如存在关联商城订单，也会一并删除，避免残留。</p>
        <button type="button" class="btn btn-block btn-default" onclick="cleanPurchaseRecords(30)">删除30天前未支付购买记录</button><br/>
        <form onsubmit="return submitCleanPurchase(this)" class="form-inline" role="form">
          <div class="form-group">
            <label>购买记录：</label>
            <input type="number" name="days" min="7" max="30" value="" placeholder="7-30天数" class="form-control"/>
            <span>天前的未支付购买记录</span>
          </div>
          <button type="submit" class="btn btn-danger">立即删除</button>
        </form>
        <hr/>
        <button type="button" class="btn btn-block btn-default" onclick="cleanPaidPurchaseRecords(30)">删除30天前已支付购买记录</button><br/>
        <form onsubmit="return submitCleanPaidPurchase(this)" class="form-inline" role="form">
          <div class="form-group">
            <label>购买记录：</label>
            <input type="number" name="paid_days" min="7" max="30" value="" placeholder="7-30天数" class="form-control"/>
            <span>天前的已支付购买记录</span>
          </div>
          <button type="submit" class="btn btn-danger">立即删除</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
var items = $("select[default]");
for(var i = 0; i < items.length; i++){
	$(items[i]).val($(items[i]).attr("default") || 0);
}
function saveSetting(obj){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax.php?act=set',
		data : $(obj).serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('设置保存成功！', {
					icon: 1,
					closeBtn: false
				}, function(){
					window.location.reload();
				});
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
	return false;
}
function cleanPurchaseRecords(days){
	if(!days || days < 7 || days > 30){
		layer.alert('购买记录清理天数只能填写 7 到 30 天', {icon:2});
		return false;
	}
	layer.confirm('确定清理 '+days+' 天前的未支付购买记录吗？这会同步清理关联的未支付易支付订单。', {btn:['确定','取消']}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax_mall.php?act=cleanPurchaseRecords',
			data : {days:days},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.msg(data.msg, {icon:1, time:2000});
				}else{
					layer.alert(data.msg, {icon:2});
				}
			},
			error:function(){
				layer.close(ii);
				layer.msg('服务器错误');
			}
		});
	});
	return false;
}
function submitCleanPurchase(obj){
	var days = parseInt($(obj).find("input[name='days']").val(), 10) || 0;
	return cleanPurchaseRecords(days);
}
function cleanPaidPurchaseRecords(days){
	if(!days || days < 7 || days > 30){
		layer.alert('购买记录清理天数只能填写 7 到 30 天', {icon:2});
		return false;
	}
	layer.confirm('确定清理 '+days+' 天前的已支付购买记录吗？这会同步联动删除关联的易支付订单和商城订单。', {btn:['确定','取消']}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax_mall.php?act=cleanPaidPurchaseRecords',
			data : {days:days},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.msg(data.msg, {icon:1, time:2000});
				}else{
					layer.alert(data.msg, {icon:2});
				}
			},
			error:function(){
				layer.close(ii);
				layer.msg('服务器错误');
			}
		});
	});
	return false;
}
function submitCleanPaidPurchase(obj){
	var days = parseInt($(obj).find("input[name='paid_days']").val(), 10) || 0;
	return cleanPaidPurchaseRecords(days);
}
</script>
