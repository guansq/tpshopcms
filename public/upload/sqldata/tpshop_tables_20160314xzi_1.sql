# -----------------------------------------------------------
# Description:备份的数据表[结构] ecs_account_log
# Description:备份的数据表[数据] ecs_account_log
# Time: 2016-03-14 19:34:11
# -----------------------------------------------------------
# SQLFile Label：#1
# -----------------------------------------------------------


# 表的结构 ecs_account_log 
DROP TABLE IF EXISTS `ecs_account_log`;
CREATE TABLE `ecs_account_log` (
  `log_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL,
  `user_money` decimal(10,2) NOT NULL,
  `frozen_money` decimal(10,2) NOT NULL,
  `rank_points` mediumint(9) NOT NULL,
  `pay_points` mediumint(9) NOT NULL,
  `change_time` int(10) unsigned NOT NULL,
  `change_desc` varchar(255) NOT NULL,
  `change_type` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=74 DEFAULT CHARSET=utf8 ;



# 转存表中的数据：ecs_account_log 
INSERT INTO `ecs_account_log` VALUES ('4','1','50000.00','0.00','0','0','1242140811','50','2');
INSERT INTO `ecs_account_log` VALUES ('6','1','-400.00','0.00','0','0','1242142274','支付订单 2009051298180','99');
INSERT INTO `ecs_account_log` VALUES ('7','1','-975.00','0.00','0','0','1242142324','支付订单 2009051255518','99');
INSERT INTO `ecs_account_log` VALUES ('8','1','0.00','0.00','960','960','1242142390','订单 2009051255518 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('9','1','0.00','0.00','385','385','1242142432','订单 2009051298180 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('10','1','-2310.00','0.00','0','0','1242142549','支付订单 2009051267570','99');
INSERT INTO `ecs_account_log` VALUES ('11','1','0.00','0.00','2300','2300','1242142589','订单 2009051267570 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('12','1','-5989.00','0.00','0','0','1242142681','支付订单 2009051230249','99');
INSERT INTO `ecs_account_log` VALUES ('13','1','-8610.00','0.00','0','0','1242142808','支付订单 2009051276258','99');
INSERT INTO `ecs_account_log` VALUES ('14','1','0.00','0.00','0','-1','1242142910','参加夺宝奇兵夺宝奇兵之夏新N7 ','99');
INSERT INTO `ecs_account_log` VALUES ('15','1','0.00','0.00','0','-1','1242142935','参加夺宝奇兵夺宝奇兵之诺基亚N96 ','99');
INSERT INTO `ecs_account_log` VALUES ('16','1','0.00','0.00','0','100000','1242143867','奖励','2');
INSERT INTO `ecs_account_log` VALUES ('17','1','-10.00','0.00','0','0','1242143920','支付订单 2009051268194','99');
INSERT INTO `ecs_account_log` VALUES ('18','1','0.00','0.00','0','-17000','1242143920','支付订单 2009051268194','99');
INSERT INTO `ecs_account_log` VALUES ('19','1','0.00','0.00','-960','-960','1242144185','由于退货或未发货操作，退回订单 2009051255518 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('20','1','975.00','0.00','0','0','1242144185','由于取消、无效或退货操作，退回支付订单 2009051255518 时使用的预付款','99');
INSERT INTO `ecs_account_log` VALUES ('21','1','0.00','0.00','960','960','1242576445','订单 2009051719232 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('23','1','-13806.60','0.00','0','0','1242976699','支付订单 2009052224892','99');
INSERT INTO `ecs_account_log` VALUES ('24','1','0.00','0.00','14045','14045','1242976740','订单 2009052224892 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('25','1','0.00','0.00','-2300','-2300','1245045334','由于退货或未发货操作，退回订单 2009051267570 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('26','1','2310.00','0.00','0','0','1245045334','由于取消、无效或退货操作，退回支付订单 2009051267570 时使用的预付款','99');
INSERT INTO `ecs_account_log` VALUES ('27','1','0.00','0.00','17044','17044','1245045443','订单 2009061585887 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('28','1','17054.00','0.00','0','0','1245045515','1','99');
INSERT INTO `ecs_account_log` VALUES ('29','1','0.00','0.00','-17044','-17044','1245045515','由于退货或未发货操作，退回订单 2009061585887 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('30','1','-3196.30','0.00','0','0','1245045672','支付订单 2009061525429','99');
INSERT INTO `ecs_account_log` VALUES ('31','1','-1910.00','0.00','0','0','1245047978','支付订单 2009061503335','99');
INSERT INTO `ecs_account_log` VALUES ('32','1','0.00','0.00','1900','1900','1245048189','订单 2009061503335 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('33','1','0.00','0.00','-1900','-1900','1245048212','由于退货或未发货操作，退回订单 2009061503335 赠送的积分','99');
INSERT INTO `ecs_account_log` VALUES ('34','1','1910.00','0.00','0','0','1245048212','由于取消、无效或退货操作，退回支付订单 2009061503335 时使用的预付款','99');
INSERT INTO `ecs_account_log` VALUES ('35','1','-500.00','0.00','0','0','1245048585','支付订单 2009061510313','99');
