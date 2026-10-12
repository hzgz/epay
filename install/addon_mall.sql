CREATE TABLE IF NOT EXISTS `pre_mall_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `detail_images` text DEFAULT NULL,
  `delivery_type` varchar(20) NOT NULL DEFAULT 'express',
  `delivery_company` varchar(60) DEFAULT NULL,
  `weight` int(11) NOT NULL DEFAULT '100',
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `remark` varchar(255) DEFAULT NULL,
  `addtime` datetime DEFAULT NULL,
  `uptime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pre_mall_rule` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `min_money` decimal(10,2) NOT NULL,
  `max_money` decimal(10,2) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `remark` varchar(255) DEFAULT NULL,
  `addtime` datetime DEFAULT NULL,
  `uptime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `money_range` (`status`,`min_money`,`max_money`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pre_mall_rule_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) unsigned NOT NULL,
  `goods_id` int(11) unsigned NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `rule_goods` (`rule_id`,`goods_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pre_mall_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mall_order_no` varchar(40) NOT NULL,
  `trade_no` char(19) DEFAULT NULL,
  `out_trade_no` varchar(150) DEFAULT NULL,
  `api_trade_no` varchar(150) DEFAULT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `rule_id` int(11) unsigned DEFAULT NULL,
  `goods_id` int(11) unsigned DEFAULT NULL,
  `goods_name_snapshot` varchar(120) NOT NULL,
  `goods_image_snapshot` varchar(255) DEFAULT NULL,
  `mall_name` varchar(120) DEFAULT NULL,
  `money` decimal(10,2) NOT NULL,
  `delivery_type` varchar(20) NOT NULL DEFAULT 'express',
  `delivery_company` varchar(60) DEFAULT NULL,
  `logistics_no` varchar(80) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0',
  `addtime` datetime DEFAULT NULL,
  `paytime` datetime DEFAULT NULL,
  `uptime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mall_order_no` (`mall_order_no`),
  KEY `trade_no` (`trade_no`),
  KEY `out_trade_no` (`out_trade_no`,`uid`),
  KEY `api_trade_no` (`api_trade_no`),
  KEY `status_addtime` (`status`,`addtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pre_mall_purchase` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_open', '0');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_match_open', '1');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_name', '');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_pay_uid', '');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_verify_open', '0');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_pass_goods_name', '1');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_order_prefix', 'MO');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_logistics_open', '0');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_default_delivery', 'express');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_logistics_prefix', 'WL');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_logistics_length', '10');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_purchase_clean_days', '0');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_purchase_paid_clean_days', '0');
INSERT IGNORE INTO `pre_config` (`k`, `v`) VALUES ('mall_purchase_clean_last', '0');
