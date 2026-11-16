-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-07-05 15:54:36
-- 服务器版本： 5.7.44-log
-- PHP 版本： 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `allinpay_zhuli_p`
--

-- --------------------------------------------------------

--
-- 表的结构 `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转存表中的数据 `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$czEwm9nqstJL5PN/FXqv5OtT4Ck4O/SE066R6txLz1K2b272NwjTy', '2025-06-12 10:49:21');

-- --------------------------------------------------------

--
-- 表的结构 `bills_items`
--

CREATE TABLE `bills_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL COMMENT '归属账单项目',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '归属用户',
  `customer_tag` varchar(64) NOT NULL COMMENT '客户标识（如房号/学号/手机号/自定义）',
  `customer_name` varchar(100) DEFAULT NULL COMMENT '客户姓名（可选）',
  `amount` decimal(10,2) NOT NULL COMMENT '应收金额',
  `remark` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1正常 0禁用/软删除',
  `pay_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付状态：0未支付，1已支付',
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `bills_orders`
--

CREATE TABLE `bills_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL COMMENT '账单项目ID',
  `item_id` int(10) UNSIGNED DEFAULT NULL COMMENT '账单明细ID（预设账单则有，临时输入则无）',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '归属用户',
  `merchant_id` int(10) UNSIGNED NOT NULL COMMENT '归属商户',
  `order_no` varchar(64) NOT NULL COMMENT '平台订单号',
  `amount` decimal(10,2) NOT NULL COMMENT '金额',
  `pay_channel` varchar(16) NOT NULL COMMENT '支付渠道(微信/支付宝/云闪付)',
  `payer_info` varchar(128) DEFAULT NULL COMMENT '付款人信息（可选）',
  `status` varchar(16) NOT NULL DEFAULT 'pending' COMMENT '订单状态',
  `paid_at` datetime DEFAULT NULL COMMENT '支付完成时间',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `trxid` varchar(64) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `payinfo` varchar(255) DEFAULT NULL COMMENT '通联H5聚合支付链接',
  `customer_tag` varchar(64) DEFAULT NULL COMMENT '客户标识（冗余，便于快速检索）',
  `customer_name` varchar(100) DEFAULT NULL COMMENT '客户姓名（冗余）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `bills_projects`
--

CREATE TABLE `bills_projects` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '归属用户',
  `merchant_id` int(10) UNSIGNED NOT NULL COMMENT '绑定商户',
  `name` varchar(100) NOT NULL COMMENT '项目名称',
  `code` varchar(32) NOT NULL COMMENT '项目短码（唯一，便于生成链接/二维码）',
  `desc` varchar(255) DEFAULT NULL COMMENT '项目描述',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1正常 0禁用/软删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `codes`
--

CREATE TABLE `codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '归属用户',
  `merchant_id` int(10) UNSIGNED NOT NULL COMMENT '绑定商户',
  `name` varchar(50) NOT NULL COMMENT '卡台名称',
  `code` varchar(32) NOT NULL COMMENT '短码（唯一）',
  `pay_type` varchar(32) NOT NULL DEFAULT 'allinpay' COMMENT '支付通道',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1正常 0禁用/软删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `code_orders`
--

CREATE TABLE `code_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `code_id` int(10) UNSIGNED NOT NULL COMMENT '关联收款码',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '归属用户',
  `merchant_id` int(10) UNSIGNED NOT NULL COMMENT '归属商户',
  `order_no` varchar(64) NOT NULL COMMENT '平台订单号',
  `amount` decimal(10,2) NOT NULL COMMENT '金额',
  `pay_channel` varchar(16) NOT NULL COMMENT '支付渠道(微信/支付宝)',
  `payer_info` varchar(128) DEFAULT NULL COMMENT '付款人信息（可选）',
  `status` varchar(16) NOT NULL DEFAULT 'pending' COMMENT '订单状态',
  `paid_at` datetime DEFAULT NULL COMMENT '支付完成时间',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `trxid` varchar(64) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL COMMENT '付款备注'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `commonpay_channels`
--

CREATE TABLE `commonpay_channels` (
  `id` int(10) UNSIGNED NOT NULL,
  `channel` varchar(64) NOT NULL COMMENT '接口唯一标识，供API对接用',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '接口名称',
  `merchant_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '商户ID',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1启用 0禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `commonpay_logs`
--

CREATE TABLE `commonpay_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `pay_order_no` varchar(40) NOT NULL,
  `op_type` varchar(32) NOT NULL COMMENT '操作类型：notify/manual_query/create等',
  `op_content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `commonpay_orders`
--

CREATE TABLE `commonpay_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `channel_id` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '支付接口ID',
  `merchant_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `pay_order_no` varchar(40) NOT NULL COMMENT '平台支付订单号',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subject` varchar(64) NOT NULL DEFAULT '' COMMENT '商品名称',
  `pay_channel` varchar(8) NOT NULL DEFAULT '' COMMENT '支付方式 W01微信/A01支付宝/U01云闪付',
  `status` varchar(16) NOT NULL DEFAULT 'pending' COMMENT 'pending/paid/fail/closed',
  `notify_url` varchar(255) NOT NULL DEFAULT '' COMMENT '业务回调地址',
  `return_url` varchar(255) NOT NULL DEFAULT '' COMMENT '前端回跳地址',
  `user_id` varchar(64) NOT NULL DEFAULT '' COMMENT '业务用户ID',
  `biz_sys` varchar(50) NOT NULL DEFAULT '' COMMENT '业务系统标识',
  `biz_order_no` varchar(64) NOT NULL DEFAULT '' COMMENT '业务订单号',
  `extra` varchar(255) NOT NULL DEFAULT '' COMMENT '扩展字段',
  `allinpay_trxid` varchar(64) DEFAULT NULL COMMENT '通联交易号',
  `notify_result` varchar(255) DEFAULT NULL COMMENT '业务系统回调响应',
  `paid_at` datetime DEFAULT NULL COMMENT '支付完成时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `forms`
--

CREATE TABLE `forms` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '归属用户',
  `merchant_id` int(10) UNSIGNED NOT NULL COMMENT '绑定商户',
  `name` varchar(50) NOT NULL COMMENT '表单名称',
  `code` varchar(32) NOT NULL COMMENT '短码（唯一）',
  `desc` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1正常 0禁用/软删除'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `form_fields`
--

CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `label` varchar(60) NOT NULL,
  `field_type` enum('text','number','select') NOT NULL DEFAULT 'text',
  `options` varchar(255) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `sort` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `form_orders`
--

CREATE TABLE `form_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `form_id` int(10) UNSIGNED NOT NULL COMMENT '关联表单',
  `user_id` int(10) UNSIGNED NOT NULL COMMENT '归属用户',
  `merchant_id` int(10) UNSIGNED NOT NULL COMMENT '归属商户',
  `order_no` varchar(64) NOT NULL COMMENT '平台订单号',
  `amount` decimal(10,2) NOT NULL COMMENT '金额',
  `pay_channel` varchar(16) NOT NULL COMMENT '支付渠道(微信/支付宝/云闪付)',
  `status` varchar(16) NOT NULL DEFAULT 'pending' COMMENT '订单状态',
  `paid_at` datetime DEFAULT NULL COMMENT '支付完成时间',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `trxid` varchar(64) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `payinfo` varchar(255) DEFAULT NULL COMMENT '通联H5聚合支付链接',
  `field_values` text COMMENT '表单内容JSON'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `merchants`
--

CREATE TABLE `merchants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `pay_type` varchar(20) NOT NULL,
  `appid` varchar(64) NOT NULL,
  `cusid` varchar(64) NOT NULL,
  `priKey` text NOT NULL COMMENT '商户私钥PEM文本内容',
  `extra_config` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `api_key` varchar(64) DEFAULT NULL COMMENT 'API密钥'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 表的索引 `bills_items`
--
ALTER TABLE `bills_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `customer_tag` (`customer_tag`);

--
-- 表的索引 `bills_orders`
--
ALTER TABLE `bills_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `merchant_id` (`merchant_id`),
  ADD KEY `customer_tag` (`customer_tag`);

--
-- 表的索引 `bills_projects`
--
ALTER TABLE `bills_projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 表的索引 `codes`
--
ALTER TABLE `codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `merchant_id` (`merchant_id`),
  ADD KEY `code_2` (`code`);

--
-- 表的索引 `code_orders`
--
ALTER TABLE `code_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `code_id` (`code_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `merchant_id` (`merchant_id`),
  ADD KEY `order_no_2` (`order_no`);

--
-- 表的索引 `commonpay_channels`
--
ALTER TABLE `commonpay_channels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_channel` (`channel`);

--
-- 表的索引 `commonpay_logs`
--
ALTER TABLE `commonpay_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pay_order_no` (`pay_order_no`);

--
-- 表的索引 `commonpay_orders`
--
ALTER TABLE `commonpay_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pay_order_no` (`pay_order_no`),
  ADD KEY `idx_biz_order_no` (`biz_order_no`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 表的索引 `form_fields`
--
ALTER TABLE `form_fields`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `form_orders`
--
ALTER TABLE `form_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `form_id` (`form_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `merchant_id` (`merchant_id`);

--
-- 表的索引 `merchants`
--
ALTER TABLE `merchants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD UNIQUE KEY `api_key_2` (`api_key`),
  ADD KEY `user_id` (`user_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `bills_items`
--
ALTER TABLE `bills_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `bills_orders`
--
ALTER TABLE `bills_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `bills_projects`
--
ALTER TABLE `bills_projects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `codes`
--
ALTER TABLE `codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `code_orders`
--
ALTER TABLE `code_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `commonpay_channels`
--
ALTER TABLE `commonpay_channels`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `commonpay_logs`
--
ALTER TABLE `commonpay_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `commonpay_orders`
--
ALTER TABLE `commonpay_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `forms`
--
ALTER TABLE `forms`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `form_fields`
--
ALTER TABLE `form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `form_orders`
--
ALTER TABLE `form_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `merchants`
--
ALTER TABLE `merchants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 限制导出的表
--

--
-- 限制表 `merchants`
--
ALTER TABLE `merchants`
  ADD CONSTRAINT `merchants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
