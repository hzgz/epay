<?php
namespace lib;

use Exception;

class Mall
{
    public static function getConfig()
    {
        global $conf;
        return [
            'mall_open' => intval($conf['mall_open']),
            'mall_match_open' => intval($conf['mall_match_open']),
            'mall_name' => trim((string)$conf['mall_name']),
            'mall_pay_uid' => intval($conf['mall_pay_uid']),
            'mall_verify_open' => intval($conf['mall_verify_open']),
            'mall_pass_goods_name' => intval($conf['mall_pass_goods_name']),
            'mall_order_prefix' => trim((string)$conf['mall_order_prefix']),
            'mall_logistics_open' => intval($conf['mall_logistics_open']),
            'mall_default_delivery' => trim((string)$conf['mall_default_delivery']),
            'mall_logistics_prefix' => trim((string)$conf['mall_logistics_prefix']),
            'mall_logistics_length' => intval($conf['mall_logistics_length']),
            'mall_purchase_clean_days' => intval($conf['mall_purchase_clean_days']),
            'mall_purchase_paid_clean_days' => intval($conf['mall_purchase_paid_clean_days']),
        ];
    }

    public static function ensureSchema()
    {
        global $DB, $CACHE, $conf;

        if (!empty($conf['addon_mall_schema']) && intval($conf['addon_mall_schema']) >= 4) {
            return;
        }

        $table = $DB->getColumn("SHOW TABLES LIKE 'pre_mall_goods'");
        if (!$table) {
            return;
        }

        $exists = $DB->getRow("SHOW COLUMNS FROM pre_mall_goods LIKE 'detail_images'");
        if (!$exists) {
            $DB->exec("ALTER TABLE `pre_mall_goods` ADD COLUMN `detail_images` text DEFAULT NULL AFTER `image`");
        }

        $purchaseTable = $DB->getColumn("SHOW TABLES LIKE 'pre_mall_purchase'");
        if (!$purchaseTable) {
            $DB->exec("CREATE TABLE IF NOT EXISTS `pre_mall_purchase` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `shop_order_no` varchar(40) NOT NULL,
  `trade_no` char(19) DEFAULT NULL,
  `out_trade_no` varchar(150) DEFAULT NULL,
  `api_trade_no` varchar(150) DEFAULT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `goods_id` int(11) unsigned DEFAULT NULL,
  `goods_name_snapshot` varchar(120) NOT NULL,
  `goods_image_snapshot` varchar(255) DEFAULT NULL,
  `mall_name` varchar(120) DEFAULT NULL,
  `buyer_name` varchar(60) NOT NULL,
  `buyer_phone` varchar(32) NOT NULL,
  `buyer_email` varchar(120) DEFAULT NULL,
  `buyer_address` varchar(255) NOT NULL,
  `buyer_remark` varchar(255) DEFAULT NULL,
  `pay_type` varchar(20) NOT NULL DEFAULT 'alipay',
  `type_id` int(11) unsigned DEFAULT NULL,
  `money` decimal(10,2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `clientip` varchar(45) DEFAULT NULL,
  `addtime` datetime DEFAULT NULL,
  `paytime` datetime DEFAULT NULL,
  `uptime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shop_order_no` (`shop_order_no`),
  KEY `trade_no` (`trade_no`),
  KEY `out_trade_no` (`out_trade_no`,`uid`),
  KEY `status_addtime` (`status`,`addtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        self::ensureConfigDefault('mall_pay_uid', '');
        self::ensureConfigDefault('mall_verify_open', '0');
        self::ensureConfigDefault('mall_purchase_clean_days', '0');
        self::ensureConfigDefault('mall_purchase_paid_clean_days', '0');
        self::ensureConfigDefault('mall_purchase_clean_last', '0');

        saveSetting('addon_mall_schema', 4);
        if (isset($CACHE)) {
            $CACHE->clear();
        }
        $conf['addon_mall_schema'] = 4;
    }

    protected static function ensureConfigDefault($key, $value)
    {
        global $DB;
        $exists = $DB->getRow("SELECT v FROM pre_config WHERE k=:k LIMIT 1", [':k' => $key]);
        if (!$exists) {
            saveSetting($key, $value);
        }
    }

    public static function splitDetailImages($text)
    {
        $text = trim((string)$text);
        if ($text === '') {
            return [];
        }
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $items = [];
        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $items[] = $line;
        }
        return array_values(array_unique($items));
    }

    public static function normalizeDetailImages($text)
    {
        $items = self::splitDetailImages($text);
        return empty($items) ? '' : implode("\n", $items);
    }

    public static function getPayUid($config = null)
    {
        if ($config === null) {
            $config = self::getConfig();
        }
        return intval($config['mall_pay_uid']);
    }

    public static function isVerifyOpen($config = null)
    {
        if ($config === null) {
            $config = self::getConfig();
        }
        return self::isOpen($config) && intval($config['mall_verify_open']) === 1;
    }

    public static function hasCaptchaConfig()
    {
        global $conf;
        $captchaId = trim((string)$conf['captcha_id']);
        $captchaKey = trim((string)$conf['captcha_key']);
        if ($captchaId === '') {
            return false;
        }
        if ((string)$conf['captcha_version'] === '1') {
            return true;
        }
        return $captchaKey !== '';
    }

    public static function getPurchaseCleanDays($config = null)
    {
        if ($config === null) {
            $config = self::getConfig();
        }
        $days = intval($config['mall_purchase_clean_days']);
        if ($days < 7 || $days > 30) {
            return 0;
        }
        return $days;
    }

    public static function getPaidPurchaseCleanDays($config = null)
    {
        if ($config === null) {
            $config = self::getConfig();
        }
        $days = intval($config['mall_purchase_paid_clean_days']);
        if ($days < 7 || $days > 30) {
            return 0;
        }
        return $days;
    }

    public static function runDailyMaintenance()
    {
        global $conf;

        $config = self::getConfig();
        $unpaidDays = self::getPurchaseCleanDays($config);
        $paidDays = self::getPaidPurchaseCleanDays($config);
        if ($unpaidDays < 7 && $paidDays < 7) {
            return;
        }
        $lastTime = intval(isset($conf['mall_purchase_clean_last']) ? $conf['mall_purchase_clean_last'] : 0);
        if ($lastTime > 0 && $lastTime >= strtotime(date('Y-m-d 00:00:00'))) {
            return;
        }
        try {
            if ($unpaidDays >= 7) {
                self::cleanupExpiredPurchases($unpaidDays, 'unpaid');
            }
            if ($paidDays >= 7) {
                self::cleanupExpiredPurchases($paidDays, 'paid');
            }
            saveSetting('mall_purchase_clean_last', (string)time());
            $conf['mall_purchase_clean_last'] = time();
        } catch (Exception $e) {
        }
    }

    public static function getShopPayTypes($uid = null)
    {
        global $DB;

        if ($uid === null || intval($uid) < 1) {
            $uid = self::getPayUid();
        }
        $uid = intval($uid);
        $result = [
            'wxpay' => ['id' => 0, 'name' => '微信支付', 'showname' => '微信支付', 'available' => false],
            'alipay' => ['id' => 0, 'name' => '支付宝', 'showname' => '支付宝', 'available' => false],
        ];
        if ($uid < 1) {
            return $result;
        }

        $userrow = $DB->getRow("SELECT uid,gid FROM pre_user WHERE uid=:uid LIMIT 1", [':uid' => $uid]);
        if (!$userrow) {
            return $result;
        }
        $types = \lib\Channel::getTypes($uid, intval($userrow['gid']));
        foreach ($types as $row) {
            if (!isset($row['name']) || !isset($result[$row['name']])) {
                continue;
            }
            $result[$row['name']]['id'] = intval($row['id']);
            $result[$row['name']]['showname'] = !empty($row['showname']) ? $row['showname'] : $result[$row['name']]['showname'];
            $result[$row['name']]['available'] = true;
        }
        return $result;
    }

    public static function getGoodsSellPrice($goodsId)
    {
        global $DB;
        $row = $DB->getRow("SELECT MAX(B.max_money) max_money FROM pre_mall_rule_goods A INNER JOIN pre_mall_rule B ON A.rule_id=B.id WHERE A.goods_id=:goods_id AND B.status=1", [
            ':goods_id' => intval($goodsId),
        ]);
        $money = round(floatval(isset($row['max_money']) ? $row['max_money'] : 0), 2);
        return $money > 0 ? $money : 0;
    }

    public static function getShopOrderStatusList()
    {
        return [
            0 => '待支付',
            1 => '已支付',
            2 => '已退款',
            3 => '已冻结',
            4 => '预授权',
        ];
    }

    public static function getShopOrderStatusText($status)
    {
        $list = self::getShopOrderStatusList();
        return isset($list[$status]) ? $list[$status] : '未知状态';
    }

    public static function createShopPurchaseOrder($goodsId, array $form, $clientIp = '')
    {
        global $DB, $siteurl;

        $config = self::getConfig();
        if (!self::isOpen($config)) {
            throw new Exception('商城功能未开启');
        }

        $payUid = self::getPayUid($config);
        if ($payUid < 1) {
            throw new Exception('请先在商城系统基本设置中配置收款商户ID');
        }
        if (self::isVerifyOpen($config) && !self::hasCaptchaConfig()) {
            throw new Exception('商城暂未开启验证');
        }

        $merchant = $DB->getRow("SELECT uid,gid FROM pre_user WHERE uid=:uid LIMIT 1", [':uid' => $payUid]);
        if (!$merchant) {
            throw new Exception('收款商户ID不存在');
        }

        $goods = $DB->getRow("SELECT * FROM pre_mall_goods WHERE id=:id AND status=1 LIMIT 1", [':id' => intval($goodsId)]);
        if (!$goods) {
            throw new Exception('当前商品不存在或已下架');
        }

        $money = self::getGoodsSellPrice($goods['id']);
        if ($money <= 0) {
            throw new Exception('当前商品未配置可购买金额');
        }

        $payType = trim((string)(isset($form['pay_type']) ? $form['pay_type'] : ''));
        $availableTypes = self::getShopPayTypes($payUid);
        if (!isset($availableTypes[$payType]) || !$availableTypes[$payType]['available']) {
            throw new Exception('当前支付方式暂不可用');
        }

        $buyerName = trim((string)(isset($form['buyer_name']) ? $form['buyer_name'] : ''));
        $buyerPhone = trim((string)(isset($form['buyer_phone']) ? $form['buyer_phone'] : ''));
        $buyerEmail = trim((string)(isset($form['buyer_email']) ? $form['buyer_email'] : ''));
        $buyerAddress = trim((string)(isset($form['buyer_address']) ? $form['buyer_address'] : ''));
        $buyerRemark = trim((string)(isset($form['buyer_remark']) ? $form['buyer_remark'] : ''));

        if ($buyerName === '') {
            throw new Exception('请填写姓名');
        }
        if ($buyerPhone === '') {
            throw new Exception('请填写联系电话');
        }
        if (strlen($buyerPhone) < 6 || strlen($buyerPhone) > 32) {
            throw new Exception('联系电话格式不正确');
        }
        if ($buyerEmail === '') {
            throw new Exception('请填写邮箱');
        }
        if (!filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('邮箱格式不正确');
        }
        if ($buyerAddress === '') {
            throw new Exception('请填写邮寄地址');
        }

        $shopOrderNo = self::generateShopOrderNo();
        $tradeNo = self::generateTradeNo();
        $outTradeNo = $shopOrderNo;
        $returnUrl = $siteurl . 'shop/order.php?order_no=' . urlencode($shopOrderNo);
        $notifyUrl = $siteurl . 'shop/notify.php?order_no=' . urlencode($shopOrderNo);
        $domain = getdomain($returnUrl);
        $mallName = $config['mall_name'] ?: null;
        $typeId = intval($availableTypes[$payType]['id']);

        $DB->beginTransaction();
        try {
            $insertPurchase = $DB->insert('mall_purchase', [
                'shop_order_no' => $shopOrderNo,
                'trade_no' => $tradeNo,
                'out_trade_no' => $outTradeNo,
                'api_trade_no' => null,
                'uid' => $payUid,
                'goods_id' => intval($goods['id']),
                'goods_name_snapshot' => $goods['name'],
                'goods_image_snapshot' => $goods['image'],
                'mall_name' => $mallName,
                'buyer_name' => $buyerName,
                'buyer_phone' => $buyerPhone,
                'buyer_email' => $buyerEmail ?: null,
                'buyer_address' => $buyerAddress,
                'buyer_remark' => $buyerRemark ?: null,
                'pay_type' => $payType,
                'type_id' => $typeId,
                'money' => sprintf('%.2f', $money),
                'status' => 0,
                'clientip' => $clientIp ?: null,
                'addtime' => 'NOW()',
                'uptime' => 'NOW()',
            ]);
            if (!$insertPurchase) {
                throw new Exception('创建商城购买订单失败');
            }

            $insertOrder = $DB->exec("INSERT INTO `pre_order` (`trade_no`,`out_trade_no`,`uid`,`tid`,`addtime`,`name`,`money`,`notify_url`,`return_url`,`domain`,`ip`,`status`) VALUES (:trade_no, :out_trade_no, :uid, 6, NOW(), :name, :money, :notify_url, :return_url, :domain, :clientip, 0)", [
                ':trade_no' => $tradeNo,
                ':out_trade_no' => $outTradeNo,
                ':uid' => $payUid,
                ':name' => $goods['name'],
                ':money' => sprintf('%.2f', $money),
                ':notify_url' => $notifyUrl,
                ':return_url' => $returnUrl,
                ':domain' => $domain,
                ':clientip' => $clientIp,
            ]);
            if (!$insertOrder) {
                throw new Exception('创建支付订单失败');
            }

            $DB->commit();
        } catch (Exception $e) {
            $DB->rollBack();
            throw $e;
        }

        return [
            'shop_order_no' => $shopOrderNo,
            'trade_no' => $tradeNo,
            'type_id' => $typeId,
            'money' => sprintf('%.2f', $money),
            'pay_url' => $siteurl . 'submit2.php?typeid=' . $typeId . '&trade_no=' . $tradeNo,
            'order_url' => $returnUrl,
        ];
    }

    public static function getShopOrderDetail($orderNo = '', $tradeNo = '')
    {
        global $DB;

        $orderNo = trim((string)$orderNo);
        $tradeNo = trim((string)$tradeNo);
        if ($orderNo === '' && $tradeNo === '') {
            return null;
        }

        $sql = "SELECT A.*,B.status pay_status,B.endtime,B.api_trade_no api_trade_no_real,B.type,B.channel,C.showname typeshowname FROM pre_mall_purchase A LEFT JOIN pre_order B ON A.trade_no=B.trade_no LEFT JOIN pre_type C ON A.type_id=C.id WHERE ";
        $params = [];
        if ($orderNo !== '') {
            $sql .= "A.shop_order_no=:shop_order_no";
            $params[':shop_order_no'] = $orderNo;
        } else {
            $sql .= "A.trade_no=:trade_no";
            $params[':trade_no'] = $tradeNo;
        }
        $sql .= " LIMIT 1";
        $row = $DB->getRow($sql, $params);
        if (!$row) {
            return null;
        }
        $row['api_trade_no_show'] = !empty($row['api_trade_no_real']) ? $row['api_trade_no_real'] : $row['api_trade_no'];
        $row['status_show'] = self::getShopOrderStatusText(isset($row['pay_status']) && $row['pay_status'] !== null ? $row['pay_status'] : $row['status']);
        return $row;
    }

    public static function cleanupMallOrders($days)
    {
        global $DB;

        $days = intval($days);
        if ($days < 1) {
            throw new Exception('清理天数不能为空');
        }
        $beforeTime = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $tradeRows = $DB->getAll("SELECT trade_no FROM `pre_order` WHERE addtime<:before_time", [':before_time' => $beforeTime]);
        $tradeNos = [];
        foreach ($tradeRows as $row) {
            if (!empty($row['trade_no'])) {
                $tradeNos[] = $row['trade_no'];
            }
        }
        $result = self::deleteSystemOrdersByTradeNos($tradeNos);
        $orphanMallOrderCount = $DB->exec("DELETE A FROM `pre_mall_order` A LEFT JOIN `pre_order` B ON A.trade_no=B.trade_no WHERE A.addtime<:before_time AND (A.trade_no IS NULL OR A.trade_no='' OR B.trade_no IS NULL)", [':before_time' => $beforeTime]);
        $orphanPurchaseCount = $DB->exec("DELETE A FROM `pre_mall_purchase` A LEFT JOIN `pre_order` B ON A.trade_no=B.trade_no WHERE A.addtime<:before_time AND (A.trade_no IS NULL OR A.trade_no='' OR B.trade_no IS NULL)", [':before_time' => $beforeTime]);
        $result['before_time'] = $beforeTime;
        $result['mall_order_count'] += intval($orphanMallOrderCount);
        $result['purchase_count'] += intval($orphanPurchaseCount);
        return $result;
    }

    protected static function normalizeIntIds(array $ids)
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    protected static function normalizeTradeNos(array $tradeNos)
    {
        $list = [];
        foreach ($tradeNos as $tradeNo) {
            $tradeNo = trim((string)$tradeNo);
            if ($tradeNo !== '') {
                $list[] = $tradeNo;
            }
        }
        return array_values(array_unique($list));
    }

    protected static function buildInParams(array $values, $prefix, &$params)
    {
        $placeholders = [];
        foreach (array_values($values) as $index => $value) {
            $key = ':' . $prefix . $index;
            $placeholders[] = $key;
            $params[$key] = $value;
        }
        return implode(',', $placeholders);
    }

    protected static function deleteLinkedTradeBundles(array $tradeNos)
    {
        global $DB;

        $tradeNos = self::normalizeTradeNos($tradeNos);
        if (empty($tradeNos)) {
            return ['order_count' => 0, 'mall_order_count' => 0, 'purchase_count' => 0];
        }
        $params = [];
        $in = self::buildInParams($tradeNos, 'trade_no_', $params);
        $mallOrderCount = $DB->exec("DELETE FROM `pre_mall_order` WHERE trade_no IN ({$in})", $params);
        $purchaseCount = $DB->exec("DELETE FROM `pre_mall_purchase` WHERE trade_no IN ({$in})", $params);
        $orderCount = $DB->exec("DELETE FROM `pre_order` WHERE trade_no IN ({$in})", $params);
        return [
            'order_count' => intval($orderCount),
            'mall_order_count' => intval($mallOrderCount),
            'purchase_count' => intval($purchaseCount),
        ];
    }

    protected static function deleteMallOrdersByIdsOnly(array $ids)
    {
        global $DB;

        $ids = self::normalizeIntIds($ids);
        if (empty($ids)) {
            return 0;
        }
        $params = [];
        $in = self::buildInParams($ids, 'mall_order_id_', $params);
        return intval($DB->exec("DELETE FROM `pre_mall_order` WHERE id IN ({$in})", $params));
    }

    protected static function deletePurchasesByIdsOnly(array $ids)
    {
        global $DB;

        $ids = self::normalizeIntIds($ids);
        if (empty($ids)) {
            return 0;
        }
        $params = [];
        $in = self::buildInParams($ids, 'mall_purchase_id_', $params);
        return intval($DB->exec("DELETE FROM `pre_mall_purchase` WHERE id IN ({$in})", $params));
    }

    protected static function cleanupPurchaseRecordsByStatus($days, array $statuses)
    {
        global $DB;

        $days = intval($days);
        if ($days < 7 || $days > 30) {
            throw new Exception('购买记录清理天数只能设置 7 到 30 天');
        }
        $statuses = array_values(array_unique(array_map('intval', $statuses)));
        if (empty($statuses)) {
            return ['before_time' => '', 'purchase_count' => 0, 'order_count' => 0, 'mall_order_count' => 0];
        }
        $beforeTime = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $params = [':before_time' => $beforeTime];
        $statusIn = self::buildInParams($statuses, 'purchase_status_', $params);
        $rows = $DB->getAll("SELECT id,trade_no FROM `pre_mall_purchase` WHERE status IN ({$statusIn}) AND addtime<:before_time", $params);
        $tradeNos = [];
        $orphanIds = [];
        foreach ($rows as $row) {
            if (!empty($row['trade_no'])) {
                $tradeNos[] = $row['trade_no'];
            } else {
                $orphanIds[] = intval($row['id']);
            }
        }
        $result = self::deleteLinkedTradeBundles($tradeNos);
        $result['purchase_count'] += self::deletePurchasesByIdsOnly($orphanIds);
        $result['before_time'] = $beforeTime;
        return $result;
    }

    public static function cleanupExpiredPurchases($days, $statusType = 'unpaid')
    {
        $statuses = ($statusType === 'paid' || $statusType === 1 || $statusType === true) ? [1] : [0];
        return self::cleanupPurchaseRecordsByStatus($days, $statuses);
    }

    public static function deleteSystemOrdersByTradeNos(array $tradeNos)
    {
        return self::deleteLinkedTradeBundles($tradeNos);
    }

    public static function deleteMallOrders(array $ids)
    {
        global $DB;

        $ids = self::normalizeIntIds($ids);
        if (empty($ids)) {
            return ['order_count' => 0, 'mall_order_count' => 0, 'purchase_count' => 0];
        }
        $params = [];
        $in = self::buildInParams($ids, 'selected_mall_order_', $params);
        $rows = $DB->getAll("SELECT id,trade_no FROM `pre_mall_order` WHERE id IN ({$in})", $params);
        $tradeNos = [];
        $orphanIds = [];
        foreach ($rows as $row) {
            if (!empty($row['trade_no'])) {
                $tradeNos[] = $row['trade_no'];
            } else {
                $orphanIds[] = intval($row['id']);
            }
        }
        $result = self::deleteLinkedTradeBundles($tradeNos);
        $result['mall_order_count'] += self::deleteMallOrdersByIdsOnly($orphanIds);
        return $result;
    }

    public static function deletePurchaseRecords(array $ids, $deletePendingOrders = true)
    {
        $ids = self::normalizeIntIds($ids);
        if (empty($ids)) {
            return ['purchase_count' => 0, 'order_count' => 0, 'mall_order_count' => 0];
        }
        global $DB;

        $params = [];
        $in = self::buildInParams($ids, 'selected_purchase_', $params);
        $rows = $DB->getAll("SELECT id,trade_no FROM `pre_mall_purchase` WHERE id IN ({$in})", $params);
        $tradeNos = [];
        $orphanIds = [];
        foreach ($rows as $row) {
            if (!empty($row['trade_no'])) {
                $tradeNos[] = $row['trade_no'];
            } else {
                $orphanIds[] = intval($row['id']);
            }
        }
        if ($deletePendingOrders) {
            $result = self::deleteLinkedTradeBundles($tradeNos);
        } else {
            $result = ['purchase_count' => 0, 'order_count' => 0, 'mall_order_count' => 0];
        }
        $result['purchase_count'] += self::deletePurchasesByIdsOnly($orphanIds);
        return $result;
    }

    public static function isOpen($config = null)
    {
        if ($config === null) {
            $config = self::getConfig();
        }
        return intval($config['mall_open']) === 1;
    }

    public static function isMatchOpen($config = null)
    {
        if ($config === null) {
            $config = self::getConfig();
        }
        return self::isOpen($config) && intval($config['mall_match_open']) === 1;
    }

    public static function isLogisticsOpen($config = null)
    {
        if ($config === null) {
            $config = self::getConfig();
        }
        return self::isOpen($config) && intval($config['mall_logistics_open']) === 1;
    }

    public static function prepareCreate($uid, $outTradeNo, $money, $originName)
    {
        global $DB;

        $config = self::getConfig();
        $result = [
            'enabled' => false,
            'name' => $originName,
            'mall_name' => '',
            'mall_order_no' => '',
            'goods_id' => 0,
            'goods_name' => $originName,
            'goods_image' => '',
            'delivery_type' => $config['mall_default_delivery'] ?: 'express',
            'delivery_company' => '',
        ];
        if (!self::isMatchOpen($config)) {
            return $result;
        }

        $result['enabled'] = true;
        $money = round(floatval($money), 2);
        $row = self::findReusableOrderRow($uid, $outTradeNo, $money);
        if ($row) {
            $result['mall_order_no'] = $row['mall_order_no'];
            $result['goods_id'] = intval($row['goods_id']);
            $result['goods_name'] = $row['goods_name_snapshot'];
            $result['goods_image'] = $row['goods_image_snapshot'];
            $result['delivery_type'] = $row['delivery_type'];
            $result['delivery_company'] = $row['delivery_company'];
            $result['mall_name'] = $row['mall_name'];
            $result['name'] = self::shouldPassGoodsName($config) ? $row['goods_name_snapshot'] : $originName;
            return $result;
        }

        $rule = self::matchRule($money);
        if (!$rule) {
            throw new Exception('商城金额区间未匹配到可用规则');
        }
        $goodsList = self::getRuleGoodsList($rule['id']);
        if (!$goodsList) {
            throw new Exception('商城金额区间未关联可用商品');
        }
        $goods = self::pickGoods($goodsList, $uid, $outTradeNo, $money);
        $mallOrderNo = self::generateMallOrderNo($config['mall_order_prefix']);
        $mallName = $config['mall_name'];
        $insertData = [
            'mall_order_no' => $mallOrderNo,
            'trade_no' => null,
            'out_trade_no' => $outTradeNo,
            'api_trade_no' => null,
            'uid' => $uid,
            'rule_id' => $rule['id'],
            'goods_id' => $goods['id'],
            'goods_name_snapshot' => $goods['name'],
            'goods_image_snapshot' => $goods['image'],
            'mall_name' => $mallName ?: null,
            'money' => sprintf('%.2f', $money),
            'delivery_type' => $goods['delivery_type'] ?: ($config['mall_default_delivery'] ?: 'express'),
            'delivery_company' => $goods['delivery_company'],
            'status' => 0,
            'addtime' => 'NOW()',
            'uptime' => 'NOW()',
        ];
        if (!$DB->insert('mall_order', $insertData)) {
            throw new Exception('创建商城订单失败');
        }

        $result['mall_order_no'] = $mallOrderNo;
        $result['goods_id'] = intval($goods['id']);
        $result['goods_name'] = $goods['name'];
        $result['goods_image'] = $goods['image'];
        $result['delivery_type'] = $insertData['delivery_type'];
        $result['delivery_company'] = $goods['delivery_company'];
        $result['mall_name'] = $mallName;
        $result['name'] = self::shouldPassGoodsName($config) ? $goods['name'] : $originName;
        return $result;
    }

    public static function bindTradeNo($uid, $outTradeNo, $tradeNo, $name, $money)
    {
        global $DB;
        if (!self::isOpen()) {
            return;
        }

        $row = self::findReusableOrderRow($uid, $outTradeNo, $money);
        if (!$row && !self::isMatchOpen()) {
            return;
        }
        if (!$row) {
            $config = self::getConfig();
            $DB->insert('mall_order', [
                'mall_order_no' => self::generateMallOrderNo($config['mall_order_prefix']),
                'trade_no' => $tradeNo,
                'out_trade_no' => $outTradeNo,
                'uid' => $uid,
                'goods_name_snapshot' => $name,
                'goods_image_snapshot' => null,
                'mall_name' => $config['mall_name'] ?: null,
                'money' => sprintf('%.2f', floatval($money)),
                'delivery_type' => $config['mall_default_delivery'] ?: 'express',
                'status' => 0,
                'addtime' => 'NOW()',
                'uptime' => 'NOW()',
            ]);
            return;
        }
        $DB->update('mall_order', [
            'trade_no' => $tradeNo,
            'goods_name_snapshot' => $name,
            'money' => sprintf('%.2f', floatval($money)),
            'uptime' => 'NOW()',
        ], ['id' => $row['id']]);
    }

    public static function syncPaid($tradeNo, $apiTradeNo = null, $endTime = null, $status = 1)
    {
        global $DB;
        if (!self::isOpen()) {
            return;
        }
        $data = [
            'status' => intval($status),
            'uptime' => 'NOW()',
        ];
        if (!empty($apiTradeNo)) {
            $data['api_trade_no'] = $apiTradeNo;
        }
        if (!empty($endTime)) {
            $data['paytime'] = $endTime;
        } else {
            $data['paytime'] = 'NOW()';
        }
        $DB->update('mall_order', $data, ['trade_no' => $tradeNo]);
        $DB->update('mall_purchase', $data, ['trade_no' => $tradeNo]);
    }

    public static function syncOrderStatus($tradeNo, $status)
    {
        global $DB;
        if (!self::isOpen()) {
            return;
        }
        $DB->update('mall_order', ['status' => intval($status), 'uptime' => 'NOW()'], ['trade_no' => $tradeNo]);
        $DB->update('mall_purchase', ['status' => intval($status), 'uptime' => 'NOW()'], ['trade_no' => $tradeNo]);
    }

    public static function updateApiTradeNo($tradeNo, $apiTradeNo)
    {
        global $DB;
        if (!self::isOpen()) {
            return;
        }
        if (empty($apiTradeNo)) {
            return;
        }
        $DB->update('mall_order', ['api_trade_no' => $apiTradeNo, 'uptime' => 'NOW()'], ['trade_no' => $tradeNo]);
        $DB->update('mall_purchase', ['api_trade_no' => $apiTradeNo, 'uptime' => 'NOW()'], ['trade_no' => $tradeNo]);
    }

    public static function applySiteName($tradeNo)
    {
        global $conf, $DB;
        $mallName = $DB->findColumn('mall_order', 'mall_name', ['trade_no' => $tradeNo], 'id DESC');
        if (empty($mallName)) {
            $mallName = $DB->findColumn('mall_purchase', 'mall_name', ['trade_no' => $tradeNo], 'id DESC');
        }
        if (!empty($mallName)) {
            $conf['sitename'] = $mallName;
        }
    }

    public static function getStatusList()
    {
        return [
            0 => '未支付',
            1 => '已支付',
            2 => '已退款',
            3 => '已冻结',
            4 => '预授权',
        ];
    }

    public static function getStatusText($status)
    {
        $list = self::getStatusList();
        return isset($list[$status]) ? $list[$status] : '未知';
    }

    public static function getDeliveryTypes()
    {
        return [
            'express' => '快递物流',
            'virtual' => '虚拟发货',
            'none' => '无需发货',
        ];
    }

    public static function getRuleGoodsIds($ruleId)
    {
        global $DB;
        $rows = $DB->getAll("SELECT goods_id FROM pre_mall_rule_goods WHERE rule_id=:rule_id ORDER BY sort ASC,id ASC", [':rule_id' => $ruleId]);
        $result = [];
        foreach ($rows as $row) {
            $result[] = intval($row['goods_id']);
        }
        return $result;
    }

    public static function saveRuleGoods($ruleId, $goodsIds)
    {
        global $DB;
        $DB->delete('mall_rule_goods', ['rule_id' => $ruleId]);
        if (!is_array($goodsIds)) {
            return;
        }
        $sort = 0;
        foreach ($goodsIds as $goodsId) {
            $goodsId = intval($goodsId);
            if ($goodsId < 1) {
                continue;
            }
            $sort++;
            $DB->insert('mall_rule_goods', [
                'rule_id' => $ruleId,
                'goods_id' => $goodsId,
                'sort' => $sort,
            ]);
        }
    }

    public static function validateRuleRange($ruleId, $minMoney, $maxMoney)
    {
        global $DB;
        $minMoney = round(floatval($minMoney), 2);
        $maxMoney = round(floatval($maxMoney), 2);
        if ($minMoney > $maxMoney) {
            throw new Exception('最小金额不能大于最大金额');
        }
        $rows = $DB->getAll("SELECT id,min_money,max_money FROM pre_mall_rule WHERE id<>:id", [':id' => intval($ruleId)]);
        foreach ($rows as $row) {
            if ($minMoney <= floatval($row['max_money']) && $maxMoney >= floatval($row['min_money'])) {
                throw new Exception('金额区间不能重叠');
            }
        }
    }

    public static function generateLogisticsNo()
    {
        $config = self::getConfig();
        if (!self::isLogisticsOpen($config)) {
            throw new Exception('物流单号功能未开启');
        }
        $prefix = $config['mall_logistics_prefix'] ?: 'WL';
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $prefix);
        if ($prefix === '') {
            $prefix = 'WL';
        }
        $length = intval($config['mall_logistics_length']);
        if ($length < 6) {
            $length = 10;
        }
        $maxPrefixLen = max(1, 80 - $length);
        if (strlen($prefix) > $maxPrefixLen) {
            $prefix = substr($prefix, 0, $maxPrefixLen);
        }
        $pool = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $body = '';
        $max = strlen($pool) - 1;
        for ($i = 0; $i < $length; $i++) {
            $body .= $pool[random_int(0, $max)];
        }
        return $prefix . $body;
    }

    protected static function shouldPassGoodsName($config)
    {
        return intval($config['mall_pass_goods_name']) === 1;
    }

    protected static function matchRule($money)
    {
        global $DB;
        return $DB->getRow("SELECT * FROM pre_mall_rule WHERE status=1 AND min_money<=:money AND max_money>=:money ORDER BY sort ASC,id ASC LIMIT 1", [
            ':money' => sprintf('%.2f', $money),
        ]);
    }

    protected static function getRuleGoodsList($ruleId)
    {
        global $DB;
        return $DB->getAll("SELECT A.*,B.sort bind_sort FROM pre_mall_goods A INNER JOIN pre_mall_rule_goods B ON A.id=B.goods_id WHERE B.rule_id=:rule_id AND A.status=1 ORDER BY B.sort ASC,A.sort ASC,A.id ASC", [
            ':rule_id' => intval($ruleId),
        ]);
    }

    protected static function pickGoods($goodsList, $uid, $outTradeNo, $money)
    {
        $seed = md5($uid . '|' . $outTradeNo . '|' . sprintf('%.2f', $money));
        $number = hexdec(substr($seed, 0, 8));
        $weights = [];
        $total = 0;
        foreach ($goodsList as $goods) {
            $weight = intval($goods['weight']);
            if ($weight < 1) {
                $weight = 1;
            }
            $weights[] = $weight;
            $total += $weight;
        }
        if ($total < 1) {
            return $goodsList[0];
        }
        $point = $number % $total;
        $cursor = 0;
        foreach ($goodsList as $index => $goods) {
            $cursor += $weights[$index];
            if ($point < $cursor) {
                return $goods;
            }
        }
        return $goodsList[0];
    }

    protected static function generateMallOrderNo($prefix = '')
    {
        global $DB;
        $prefix = trim($prefix);
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $prefix);
        if ($prefix === '') {
            $prefix = 'MO';
        }
        if (strlen($prefix) > 22) {
            $prefix = substr($prefix, 0, 22);
        }
        do {
            $mallOrderNo = $prefix . date('YmdHis') . random_int(1000, 9999);
            $exists = $DB->findColumn('mall_order', 'id', ['mall_order_no' => $mallOrderNo]);
        } while ($exists);
        return $mallOrderNo;
    }

    protected static function generateShopOrderNo($prefix = 'SO')
    {
        global $DB;
        $prefix = trim($prefix);
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $prefix);
        if ($prefix === '') {
            $prefix = 'SO';
        }
        if (strlen($prefix) > 22) {
            $prefix = substr($prefix, 0, 22);
        }
        do {
            $shopOrderNo = $prefix . date('YmdHis') . random_int(1000, 9999);
            $exists = $DB->findColumn('mall_purchase', 'id', ['shop_order_no' => $shopOrderNo]);
        } while ($exists);
        return $shopOrderNo;
    }

    protected static function generateTradeNo()
    {
        global $DB;
        do {
            $tradeNo = date('YmdHis') . rand(11111, 99999);
            $exists = $DB->findColumn('order', 'trade_no', ['trade_no' => $tradeNo]);
        } while ($exists);
        return $tradeNo;
    }

    protected static function findReusableOrderRow($uid, $outTradeNo, $money)
    {
        global $DB;
        $row = $DB->getRow("SELECT A.*,B.status pay_status,B.addtime order_addtime,B.money order_money FROM pre_mall_order A LEFT JOIN pre_order B ON A.trade_no=B.trade_no WHERE A.uid=:uid AND A.out_trade_no=:out_trade_no AND A.is_delete=0 ORDER BY A.id DESC LIMIT 1", [
            ':uid' => intval($uid),
            ':out_trade_no' => $outTradeNo,
        ]);
        if (!$row) {
            return null;
        }
        if (empty($row['trade_no'])) {
            if (!empty($row['addtime']) && time() - strtotime($row['addtime']) >= 864000) {
                return null;
            }
            return $row;
        }
        if (empty($row['order_addtime']) || time() - strtotime($row['order_addtime']) >= 864000) {
            return null;
        }
        if (isset($row['pay_status']) && intval($row['pay_status']) > 0) {
            return null;
        }
        if (!empty($row['order_money']) && round(floatval($row['order_money']), 2) != round(floatval($money), 2)) {
            return null;
        }
        return $row;
    }
}
