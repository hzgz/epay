<?php
include("../includes/common.php");
$title='商城系统 - 金额管理';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$rules = $DB->getAll("SELECT * FROM pre_mall_rule ORDER BY sort ASC,id DESC");
$goodsList = $DB->getAll("SELECT id,name FROM pre_mall_goods ORDER BY sort ASC,id DESC");
$goodsMap = [];
foreach($goodsList as $goods){
	$goodsMap[$goods['id']] = $goods['name'];
}
$bindRows = $DB->getAll("SELECT * FROM pre_mall_rule_goods ORDER BY sort ASC,id ASC");
$ruleGoodsNames = [];
foreach($bindRows as $row){
	$ruleGoodsNames[$row['rule_id']][] = isset($goodsMap[$row['goods_id']]) ? $goodsMap[$row['goods_id']] : '#'.$row['goods_id'];
}
?>
<div class="container" style="padding-top:70px;">
  <div class="col-md-10 center-block" style="float:none;">
    <div class="modal" id="modal-store" role="dialog" aria-hidden="true" data-backdrop="static">
      <div class="modal-dialog">
        <div class="modal-content animated flipInX">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title" id="modal-title">金额区间</h4>
          </div>
          <div class="modal-body">
            <form class="form-horizontal" id="form-store">
              <input type="hidden" name="action" id="action"/>
              <input type="hidden" name="id" id="id"/>
              <div class="form-group">
                <label class="col-sm-3 control-label">区间名称</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" name="name" id="name" placeholder="例如 0-50元区间">
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">最小金额</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" name="min_money" id="min_money" placeholder="0.00">
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">最大金额</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" name="max_money" id="max_money" placeholder="50.00">
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-3 control-label">关联商品</label>
                <div class="col-sm-9">
                  <select class="form-control" name="goods_ids[]" id="goods_ids" multiple size="8">
                    <?php foreach($goodsList as $goods){?>
                    <option value="<?php echo $goods['id']?>"><?php echo $goods['name']?></option>
                    <?php }?>
                  </select>
                  <span class="help-block">按住 Ctrl 可多选，系统会在当前区间内按商品权重随机匹配。</span>
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
      <div class="panel-heading"><h3 class="panel-title">金额管理 <span class="pull-right"><a href="javascript:addframe()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i> 新增区间</a></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>ID</th><th>区间</th><th>关联商品</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
          <?php foreach($rules as $row){?>
          <tr>
            <td><?php echo $row['id']?></td>
            <td><b><?php echo $row['name']?></b><br/>¥<?php echo $row['min_money']?> - ¥<?php echo $row['max_money']?></td>
            <td><?php echo !empty($ruleGoodsNames[$row['id']]) ? implode('，', $ruleGoodsNames[$row['id']]) : '<span class="text-muted">未关联商品</span>'?></td>
            <td><?php echo $row['sort']?></td>
            <td><?php echo $row['status']==1?'<span class="label label-success">开启</span>':'<span class="label label-default">关闭</span>'?></td>
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
function clearGoodsSelected(){
	$("#goods_ids option").prop("selected", false);
}
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增区间");
	$("#form-store")[0].reset();
	clearGoodsSelected();
	$("#action").val("add");
	$("#id").val('');
	$("#sort").val('0');
	$("#status").val('1');
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.getJSON('ajax_mall.php?act=getRule&id='+id, function(data){
		layer.close(ii);
		if(data.code == 0){
			$("#modal-store").modal('show');
			$("#modal-title").html("编辑区间");
			$("#action").val("edit");
			$("#id").val(data.data.id);
			$("#name").val(data.data.name);
			$("#min_money").val(data.data.min_money);
			$("#max_money").val(data.data.max_money);
			$("#sort").val(data.data.sort);
			$("#status").val(data.data.status);
			$("#remark").val(data.data.remark);
			clearGoodsSelected();
			(data.data.goods_ids || []).forEach(function(id){
				$("#goods_ids option[value='"+id+"']").prop("selected", true);
			});
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
		url : 'ajax_mall.php?act=saveRule',
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
	layer.confirm('确定删除这个区间规则吗？', {btn:['确定','取消']}, function(){
		$.getJSON('ajax_mall.php?act=delRule&id='+id, function(data){
			if(data.code == 0){
				window.location.reload();
			}else{
				layer.alert(data.msg, {icon:2});
			}
		});
	});
}
</script>
