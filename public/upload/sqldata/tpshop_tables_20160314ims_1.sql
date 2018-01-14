# -----------------------------------------------------------
# Description:备份的数据表[结构] tp_coupon
# Description:备份的数据表[数据] tp_coupon
# Time: 2016-03-14 10:58:54
# -----------------------------------------------------------
# SQLFile Label：#1
# -----------------------------------------------------------


# 表的结构 tp_coupon 
DROP TABLE IF EXISTS `tp_coupon`;
CREATE TABLE `tp_coupon` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '优惠券名字',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发放类型 1 按订单发放 2 注册 3 邀请 4 按用户发放',
  `money` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '优惠券金额',
  `condition` decimal(6,2) NOT NULL DEFAULT '0.00' COMMENT '使用条件',
  `send_start_time` int(11) DEFAULT NULL COMMENT '发放开始时间',
  `send_end_time` int(11) DEFAULT NULL COMMENT '发放结束时间',
  `use_start_time` int(11) DEFAULT NULL COMMENT '使用开始时间',
  `use_end_time` int(11) DEFAULT NULL COMMENT '使用结束时间',
  `use_num` int(6) DEFAULT '0' COMMENT '已使用数量',
  `add_time` int(11) DEFAULT NULL COMMENT '添加时间',
  `send_num` int(6) DEFAULT '0' COMMENT '发放数量(按用户发放类型)',
  `min_order` decimal(6,2) DEFAULT '0.00' COMMENT '订单下限(按订单发放) 当订单达到次金额才发放',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 ;



# 转存表中的数据：tp_coupon 
INSERT INTO `tp_coupon` VALUES ('1','注册优惠券','2','50.00','200.00','1449840218','1449842218','','1449843218','0','1449840218','0','0.00');
INSERT INTO `tp_coupon` VALUES ('2','订单满100优惠券','1','20.00','100.00','1449840218','1449842218','','1449843218','0','1449840218','0','0.00');
INSERT INTO `tp_coupon` VALUES ('3','按用户类型发放','4','30.00','100.00','1449840218','1449842218','','1449843218','0','1449840218','50','0.00');
INSERT INTO `tp_coupon` VALUES ('4','邀请优惠券','3','40.00','100.00','1449840218','1449842218','','1449843218','0','1449840218','0','0.00');
INSERT INTO `tp_coupon` VALUES ('6','达到','1','50.00','50.00','1450022400','1450281600','','1450540800','0','','0','0.00');
INSERT INTO `tp_coupon` VALUES ('7','用户阿萨飒飒','4','500.00','500.00','1450022400','1450022400','','1450368000','0','','0','0.00');
