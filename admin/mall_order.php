<?php
include("../includes/common.php");
$title='商城系统 - 订单管理';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$deliveryTypes = \lib\Mall::getDeliveryTypes();
$statusList = \lib\Mall::getStatusList();
$mallLogisticsOpen = \lib\Mall::isLogisticsOpen();
?>
<style>
.mall-order-box{border:1px solid #e6e6e6;border-radius:4px;padding:12px;margin-bottom:10px;background:#fafafa}
.mall-order-box .box-title{font-weight:700;margin-bottom:8px}
.mall-thumb{width:56px;height:56px;object-fit:cover;border-radius:4px;border:1px solid #eee;background:#fff}
.mall-order-box p{margin-bottom:6px}
.mall-order-sub{color:#888;font-size:12px}
.mall-status-0{color:#337ab7}
.mall-status-1{color:#3c763d}
.mall-status-2,.mall-status-3{color:#a94442}
.mall-status-4{color:#8a6d3b}
</style>
<link href="../assets/css/datepicker.css" rel="stylesheet">
<div class="container-fluid" style="padding-top:70px;">
  <div class="col-md-12 center-block" style="float:none;">
    <form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
      <div class="form-group">
        <label>搜索</label>
        <select name="column" class="form-control">
          <option value="trade_no">系统订单号</option>
          <option value="mall_order_no">商城订单号</option>
          <option value="api_trade_no">第三方单号</option>
          <option value="out_trade_no">商户订单号</option>
          <option value="goods_name">商品名称</option>
        </select>
      </div>
      <div class="form-group">
        <input type="text" class="form-control" name="value" placeholder="搜索内容">
      </div>
      <div class="input-group input-daterange">
        <input type="text" id="starttime" name="starttime" class="form-control" placeholder="开始日期" autocomplete="off" style="width:120px">
        <span class="input-group-addon" onclick="$('#starttime').val('');$('#endtime').val('');"><i class="fa fa-chevron-right"></i></span>
        <input type="text" id="endtime" name="endtime" class="form-control" placeholder="结束日期" autocomplete="off" style="width:120px">
      </div>
      <div class="form-group">
        <select name="status" class="form-control">
          <option value="">全部状态</option>
          <?php foreach($statusList as $key=>$value){?>
          <option value="<?php echo $key?>"><?php echo $value?></option>
          <?php }?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">搜索</button>
      <a href="javascript:searchClear()" class="btn btn-default"><i class="fa fa-refresh"></i></a>
      <button type="button" class="btn btn-default" onclick="batchDeleteOrders()">批量删除</button>
    </form>
    <table id="listTable"></table>
  </div>
</div>

<div class="modal" id="modal-detail" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content animated flipInX">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title">商城订单详情</h4>
      </div>
      <div class="modal-body" id="detailBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="modal-logistics" role="dialog" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content animated flipInX">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        <h4 class="modal-title">编辑物流信息</h4>
      </div>
      <div class="modal-body">
        <form id="logisticsForm" class="form-horizontal">
          <input type="hidden" name="id" id="logistics_id">
          <div class="form-group">
            <label class="col-sm-3 control-label">发货方式</label>
            <div class="col-sm-9">
              <select class="form-control" name="delivery_type" id="logistics_delivery_type">
                <?php foreach($deliveryTypes as $key=>$value){?>
                <option value="<?php echo $key?>"><?php echo $value?></option>
                <?php }?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">物流公司</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" name="delivery_company" id="logistics_delivery_company" placeholder="例如 顺丰">
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-3 control-label">物流单号</label>
            <div class="col-sm-9">
              <div class="input-group">
                <input type="text" class="form-control" name="logistics_no" id="logistics_no" placeholder="手动填写物流单号">
                <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="generateLogisticsNo()" <?php echo $mallLogisticsOpen ? '' : 'disabled title="请先在基础设置中开启物流功能"'?>>随机生成</button></span>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
        <button type="button" class="btn btn-primary" onclick="saveLogistics()">保存</button>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
function escapeHtml(text){
	return $('<div/>').text(text == null ? '' : String(text)).html();
}
function statusClass(status){
	return 'mall-status-' + status;
}
function openOriginOrderByTradeNo(tradeNo){
	if(!tradeNo)return;
	window.open('./order.php?column=trade_no&value='+encodeURIComponent(tradeNo), '_blank');
}
function openOriginOrderByOutTradeNo(outTradeNo){
	if(!outTradeNo)return;
	window.open('./order.php?column=out_trade_no&value='+encodeURIComponent(outTradeNo), '_blank');
}
function openMallOrderDetail(id){
	if(!id)return;
	showOrder(id);
}
function renderDetail(data){
	var apiTradeNo = escapeHtml(data.api_trade_no_show ? data.api_trade_no_show : '-');
	var payTime = escapeHtml(data.paytime_show ? data.paytime_show : '-');
	var goodsImage = data.goods_image_snapshot ? '<img src="'+escapeHtml(data.goods_image_snapshot)+'" class="mall-thumb" onerror="this.style.display=\'none\'">' : '';
	var tradeNoHtml = data.trade_no ? '<a href="javascript:openOriginOrderByTradeNo('+JSON.stringify(String(data.trade_no))+')"><b>'+escapeHtml(data.trade_no)+'</b></a>' : '<b>-</b>';
	var outTradeNoHtml = data.out_trade_no ? '<a href="javascript:openOriginOrderByOutTradeNo('+JSON.stringify(String(data.out_trade_no))+')">'+escapeHtml(data.out_trade_no)+'</a>' : '-';
	var mallOrderNoHtml = data.mall_order_no ? '<a href="javascript:openMallOrderDetail('+data.id+')"><b>'+escapeHtml(data.mall_order_no)+'</b></a>' : '<b>-</b>';
	var money = escapeHtml(data.money || '0.00');
	var typeShowName = escapeHtml(data.typeshowname || '-');
	var goodsName = escapeHtml(data.goods_name_snapshot || '-');
	var statusText = escapeHtml(data.status_show || '-');
	var addTime = escapeHtml(data.addtime || '-');
	var deliveryType = escapeHtml(data.delivery_type_show || '-');
	var logisticsNo = escapeHtml(data.logistics_no || '-');
	var deliveryCompany = escapeHtml(data.delivery_company || '');
	var html = '';
	html += '<div class="mall-order-box"><div class="box-title">订单号</div><div class="row"><div class="col-sm-4"><p>系统订单号</p><div>'+tradeNoHtml+'</div></div><div class="col-sm-4"><p>商城订单号</p><div>'+mallOrderNoHtml+'</div></div><div class="col-sm-4"><p>第三方单号</p><div><b>'+apiTradeNo+'</b></div></div></div></div>';
	html += '<div class="mall-order-box"><div class="box-title">金额与支付方式</div><div class="row"><div class="col-sm-6"><p>金额</p><div><b>¥'+money+'</b></div></div><div class="col-sm-6"><p>支付方式</p><div><b>'+typeShowName+'</b></div></div></div></div>';
	html += '<div class="mall-order-box"><div class="box-title">商品信息</div><div class="row"><div class="col-sm-2">'+goodsImage+'</div><div class="col-sm-10"><p>商品名称</p><div><b>'+goodsName+'</b></div><div class="mall-order-sub">商户订单号：'+outTradeNoHtml+'</div></div></div></div>';
	html += '<div class="mall-order-box"><div class="box-title">支付状态</div><p class="'+statusClass(data.pay_status===null?data.status:data.pay_status)+'"><b>'+statusText+'</b></p></div>';
	html += '<div class="mall-order-box"><div class="box-title">时间信息</div><div class="row"><div class="col-sm-6"><p>创建时间</p><div><b>'+addTime+'</b></div></div><div class="col-sm-6"><p>支付完成时间</p><div><b>'+payTime+'</b></div></div></div></div>';
	html += '<div class="mall-order-box"><div class="box-title">物流信息</div><div class="row"><div class="col-sm-6"><p>发货方式</p><div><b>'+deliveryType+'</b></div></div><div class="col-sm-6"><p>物流单号</p><div><b>'+logisticsNo+'</b></div></div></div>'+(deliveryCompany?'<div class="mall-order-sub">物流公司：'+deliveryCompany+'</div>':'')+'</div>';
	html += '<div class="mall-order-box"><div class="box-title">操作</div><button type="button" class="btn btn-info btn-sm" onclick="openLogistics('+data.id+')">手动填写物流单号</button> <button type="button" class="btn btn-danger btn-sm" onclick="deleteOrder('+data.id+')">删除订单</button></div>';
	$("#detailBody").html(html);
}
function showOrder(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.getJSON('ajax_mall.php?act=getOrder&id='+id, function(data){
		layer.close(ii);
		if(data.code == 0){
			renderDetail(data.data);
			$("#modal-detail").modal('show');
		}else{
			layer.alert(data.msg, {icon:2});
		}
	}).fail(function(){
		layer.close(ii);
		layer.msg('服务器错误');
	});
}
function openLogistics(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.getJSON('ajax_mall.php?act=getOrder&id='+id, function(data){
		layer.close(ii);
		if(data.code == 0){
			$("#logistics_id").val(data.data.id);
			$("#logistics_delivery_type").val(data.data.delivery_type);
			$("#logistics_delivery_company").val(data.data.delivery_company);
			$("#logistics_no").val(data.data.logistics_no);
			$("#modal-logistics").modal('show');
		}else{
			layer.alert(data.msg, {icon:2});
		}
	}).fail(function(){
		layer.close(ii);
		layer.msg('服务器错误');
	});
}
function generateLogisticsNo(){
	$.getJSON('ajax_mall.php?act=generateLogisticsNo', function(data){
		if(data.code == 0){
			$("#logistics_no").val(data.data);
		}else{
			layer.alert(data.msg, {icon:2});
		}
	});
}
function saveLogistics(){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_mall.php?act=updateLogistics',
		data : $("#logisticsForm").serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.msg(data.msg, {icon:1, time:1500}, function(){
					$("#modal-logistics").modal('hide');
					$("#listTable").bootstrapTable('refresh');
				});
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function deleteOrder(id){
	layer.confirm('确定删除这个商城订单吗？', {btn:['确定','取消']}, function(){
		$.post('ajax_mall.php?act=deleteOrder', {id:id}, function(data){
			if(data.code == 0){
				layer.msg(data.msg, {icon:1, time:1500}, function(){
					$("#modal-detail").modal('hide');
					$("#listTable").bootstrapTable('refresh');
				});
			}else{
				layer.alert(data.msg, {icon:2});
			}
		}, 'json');
	});
}
function batchDeleteOrders(){
	var selected = $('#listTable').bootstrapTable('getSelections');
	if(selected.length == 0){
		layer.msg('未选择订单', {time:1500});
		return;
	}
	var ids = [];
	for(var i=0;i<selected.length;i++){
		ids.push(selected[i].id);
	}
	layer.confirm('确定要删除已选中的 '+ids.length+' 个商城订单吗？', {btn:['确定','取消']}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax_mall.php?act=batchDeleteOrder',
			data : {ids: ids},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.msg(data.msg, {icon:1, time:1500}, function(){
						$("#modal-detail").modal('hide');
						$("#listTable").bootstrapTable('refresh');
					});
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
}
$(document).ready(function(){
	updateToolbar();
	$("#listTable").bootstrapTable({
		url: 'ajax_mall.php?act=orderList',
		pageNumber: typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1,
		pageSize: typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : 20,
		classes: 'table table-striped table-hover table-bordered',
		uniqueId: 'id',
		clickToSelect: true,
		columns: [
			{
				field: '',
				checkbox: true
			},
			{
				field: 'mall_order_no',
				title: '系统/商户<br/>商城/第三方',
				formatter: function(value, row){
					var tradeNo = row.trade_no ? '<a href="javascript:openOriginOrderByTradeNo('+JSON.stringify(String(row.trade_no))+')">'+escapeHtml(row.trade_no)+'</a>' : '-';
					var outTradeNo = row.out_trade_no ? '<a href="javascript:openOriginOrderByOutTradeNo('+JSON.stringify(String(row.out_trade_no))+')">'+escapeHtml(row.out_trade_no)+'</a>' : '-';
					var mallOrderNo = value ? '<a href="javascript:openMallOrderDetail('+row.id+')">'+escapeHtml(value)+'</a>' : '-';
					return tradeNo+'<br/>'+outTradeNo+'<br/>'+mallOrderNo+'<br/>'+escapeHtml(row.api_trade_no_show || '-');
				}
			},
			{
				field: 'goods_name_snapshot',
				title: '商品信息',
				formatter: function(value, row){
					var img = row.goods_image_snapshot ? '<img src="'+escapeHtml(row.goods_image_snapshot)+'" class="mall-thumb" onerror="this.style.display=\'none\'">&nbsp;' : '';
					return img + '<b>'+ escapeHtml(value || '-') +'</b><br/><span class="mall-order-sub">'+ escapeHtml(row.delivery_type_show || '-') +'</span>';
				}
			},
			{
				field: 'money',
				title: '金额/方式/通道ID',
				formatter: function(value, row){
					var channel = row.channel ? '<a href="./pay_channel.php?kw='+encodeURIComponent(row.channel)+'" target="_blank">'+escapeHtml(row.channel)+'</a>' : '-';
					return '¥<b>'+ escapeHtml(value || '0.00') +'</b><br/>' + escapeHtml(row.typeshowname || '-') + '<br/>' + channel;
				}
			},
			{
				field: 'status_show',
				title: '支付状态',
				formatter: function(value, row){
					return '<span class="'+statusClass(row.pay_status===null?row.status:row.pay_status)+'">'+ escapeHtml(value || '-') +'</span>';
				}
			},
			{
				field: 'addtime',
				title: '创建/支付时间',
				formatter: function(value, row){
					return escapeHtml(value || '-') + '<br/>' + escapeHtml(row.paytime_show || '-');
				}
			},
			{
				field: 'id',
				title: '操作',
				formatter: function(value){
					let html = '<div class="btn-group dropdown-group" role="group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">操作订单 <span class="caret"></span></button><ul class="dropdown-menu">';
					html += '<li><a href="javascript:showOrder('+value+')">详情</a></li>';
					html += '<li><a href="javascript:openLogistics('+value+')">物流</a></li>';
					html += '<li role="separator" class="divider"></li>';
					html += '<li><a href="javascript:deleteOrder('+value+')">删除订单</a></li>';
					html += '</ul></div>';
					return html;
				}
			}
		],
		onLoadSuccess: function() {
			$('.dropdown-group').on('show.bs.dropdown', function (e) {
				var btnPos = $(e.target)[0].getBoundingClientRect();
				var screenWidth = $(window).width();
				var screenHeight = $(window).height();
				var childrenWidth = $(e.target).children('.dropdown-menu').width();
				var childrenHeight = $(e.target).children('.dropdown-menu').height();
				var top = btnPos.bottom;
				if(top + childrenHeight + 12 > screenHeight){
					top = btnPos.top - childrenHeight - 12;
				}
				var left = btnPos.left;
				if(left + childrenWidth + 7 > screenWidth){
					left = screenWidth - childrenWidth - 7;
				}
				$(e.target).children('.dropdown-menu').css({position:'fixed', top:top, left:left});
			});
		}
	});
});
</script>
