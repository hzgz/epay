<?php
include("../includes/common.php");
$title='商城系统 - 商品管理';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$deliveryTypes = \lib\Mall::getDeliveryTypes();
$list = $DB->getAll("SELECT * FROM pre_mall_goods ORDER BY sort ASC,id DESC");
?>
<style>
.mall-thumb{width:54px;height:54px;object-fit:cover;border-radius:4px;border:1px solid #eee;background:#fafafa}
</style>
<div class="container" style="padding-top:70px;">
  <div class="col-md-10 center-block" style="float:none;">
    <div class="modal" id="modal-store" role="dialog" aria-hidden="true" data-backdrop="static">
      <div class="modal-dialog">
        <div class="modal-content animated flipInX">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title" id="modal-title">商品管理</h4>
          </div>
          <div class="modal-body">
            <form class="form-horizontal" id="form-store">
              <input type="hidden" name="action" id="action"/>
              <input type="hidden" name="id" id="id"/>
              <div class="form-group">
                <label class="col-sm-3 control-label">商品名称</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" name="name" id="name" placeholder="请输入商品名称">
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">商品主图</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" name="image" id="image" placeholder="图片URL或站内路径">
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">详情图</label>
                <div class="col-sm-9">
                  <textarea class="form-control" name="detail_images" id="detail_images" rows="5" placeholder="每行一个详情图片链接，按填写顺序显示"></textarea>
                  <small class="help-block">支持 http/https 图片链接或站内图片路径，一行一张。</small>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">发货方式</label>
                <div class="col-sm-9">
                  <select class="form-control" name="delivery_type" id="delivery_type">
                    <?php foreach($deliveryTypes as $key=>$value){?>
                    <option value="<?php echo $key?>"><?php echo $value?></option>
                    <?php }?>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">物流公司</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" name="delivery_company" id="delivery_company" placeholder="例如 顺丰">
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">随机权重</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" name="weight" id="weight" value="100">
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">排序</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" name="sort" id="sort" value="0">
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">状态</label>
                <div class="col-sm-9">
                  <select class="form-control" name="status" id="status">
                    <option value="1">开启</option>
                    <option value="0">关闭</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">备注</label>
                <div class="col-sm-9">
                  <textarea class="form-control" name="remark" id="remark" rows="3"></textarea>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
            <button type="button" class="btn btn-primary" onclick="save()">保存</button>
          </div>
        </div>
      </div>
    </div>

    <div class="panel panel-info">
      <div class="panel-heading"><h3 class="panel-title">商品管理 <span class="pull-right"><a href="javascript:addframe()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i> 新增商品</a></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>ID</th><th>商品信息</th><th>发货方式</th><th>权重/排序</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
          <?php foreach($list as $row){?>
          <tr>
            <td><?php echo $row['id']?></td>
            <td>
              <?php if($row['image']){?><img src="<?php echo $row['image']?>" class="mall-thumb" onerror="this.style.display='none'"><?php }?>
              <div><b><?php echo $row['name']?></b></div>
              <div class="text-muted"><?php echo $row['remark']?></div>
              <?php $detailImageCount = count(\lib\Mall::splitDetailImages(isset($row['detail_images']) ? $row['detail_images'] : '')); if($detailImageCount > 0){?>
              <div class="text-info">详情图：<?php echo $detailImageCount?> 张</div>
              <?php }?>
            </td>
            <td><?php echo isset($deliveryTypes[$row['delivery_type']]) ? $deliveryTypes[$row['delivery_type']] : $row['delivery_type']?><?php echo $row['delivery_company']?'<br/><small>'.$row['delivery_company'].'</small>':''?></td>
            <td><?php echo $row['weight']?> / <?php echo $row['sort']?></td>
            <td><?php echo $row['status']==1?'<a class="btn btn-xs btn-success" onclick="setStatus('.$row['id'].',0)">已开启</a>':'<a class="btn btn-xs btn-warning" onclick="setStatus('.$row['id'].',1)">已关闭</a>'?></td>
            <td><a class="btn btn-xs btn-info" onclick="editframe(<?php echo $row['id']?>)">编辑</a> <a class="btn btn-xs btn-danger" onclick="delItem(<?php echo $row['id']?>)">删除</a></td>
          </tr>
          <?php }?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增商品");
	$("#form-store")[0].reset();
	$("#action").val("add");
	$("#id").val('');
	$("#weight").val('100');
	$("#sort").val('0');
	$("#status").val('1');
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.getJSON('ajax_mall.php?act=getGoods&id='+id, function(data){
		layer.close(ii);
		if(data.code == 0){
			$("#modal-store").modal('show');
			$("#modal-title").html("编辑商品");
			$("#action").val("edit");
			$("#id").val(data.data.id);
			$("#name").val(data.data.name);
			$("#image").val(data.data.image);
			$("#detail_images").val(data.data.detail_images || '');
			$("#delivery_type").val(data.data.delivery_type);
			$("#delivery_company").val(data.data.delivery_company);
			$("#weight").val(data.data.weight);
			$("#sort").val(data.data.sort);
			$("#status").val(data.data.status);
			$("#remark").val(data.data.remark);
		}else{
			layer.alert(data.msg, {icon:2});
		}
	}).fail(function(){
		layer.close(ii);
		layer.msg('服务器错误');
	});
}
function save(){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_mall.php?act=saveGoods',
		data : $("#form-store").serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1, closeBtn:false}, function(){window.location.reload()});
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
function delItem(id){
	layer.confirm('确定删除这个商品吗？', {btn:['确定','取消']}, function(){
		$.getJSON('ajax_mall.php?act=delGoods&id='+id, function(data){
			if(data.code == 0){
				window.location.reload();
			}else{
				layer.alert(data.msg, {icon:2});
			}
		});
	});
}
function setStatus(id,status){
	$.getJSON('ajax_mall.php?act=setGoodsStatus&id='+id+'&status='+status, function(data){
		if(data.code == 0){
			window.location.reload();
		}else{
			layer.alert(data.msg, {icon:2});
		}
	});
}
</script>
