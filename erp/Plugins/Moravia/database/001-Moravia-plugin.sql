INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'basic', 'frontend', 'pluginMoraviaCustomerView');
UPDATE `Zf_configuration` SET `value`='["erp_Plugins_Moravia_Init"]', `default`='["erp_Plugins_Moravia_Init"]' WHERE `name`='runtimeOptions.plugins.active';

#initial productuon test role record
INSERT `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'customerview', 'production');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'admin', 'setaclrole', 'production');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'setaclrole', 'production');

#the default vies are visible for the pm
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'customerview', 'project');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'customerview', 'offer');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'customerview', 'bill');

CREATE TABLE `ERP_production_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderId` int(11) DEFAULT NULL,
  `endCustomer` varchar(45) DEFAULT NULL,
  `projectNameEndCustomer` varchar(255) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `submissionDate` datetime DEFAULT NULL,
  `pmCustomer` varchar(45) DEFAULT NULL,
  `preliminaryWeightedWords` decimal(19,4) DEFAULT NULL,
  `weightedWords` decimal(19,4) DEFAULT NULL,
  `hours` decimal(19,4) DEFAULT NULL,
  `handoffValue` decimal(19,4) DEFAULT NULL,
  `prNumber` varchar(45) DEFAULT NULL,
  `balanceValueCheck` tinyint(1) DEFAULT NULL,
  `handoffNumber` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderId_UNIQUE` (`orderId`),
  CONSTRAINT `fk_ERP_production_data_1` FOREIGN KEY (`orderId`) REFERENCES `ERP_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    
CREATE VIEW `ERP_order_production` AS
SELECT `ERP_order`.*,
        `production`.id as productionId,
        `production`.balanceValueCheck as balanceValueCheck,
        `production`.submissionDate as submissionDate,
        `production`.endCustomer as endCustomer,
        `production`.handoffNumber as handoffNumber,
        `production`.handoffValue as handoffValue,
        `production`.hours as hours,
        `production`.orderId as orderId,
        `production`.pmCustomer as pmCustomer,
        `production`.preliminaryWeightedWords as preliminaryWeightedWords,
        `production`.prNumber as prNumber,
        `production`.projectNameEndCustomer as projectNameEndCustomer,
        `production`.type as productionType,
        `production`.weightedWords as weightedWords
        
 FROM `ERP_order` 
 INNER JOIN `ERP_production_data` as `production` on `production`.`orderId`= `ERP_order`.`id`  
 WHERE `ERP_order`.`customerNumber`=(SELECT value from Zf_configuration where name='runtimeOptions.plugins.Moravia.moravianumber' LIMIT 1);

 INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.plugins.Moravia.moravianumber', '1', 'erp', 'plugins', '', '', '', 'string', 'Moravia customer number');
 INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.plugins.Moravia.endCustomers', '1', 'erp', 'plugins', '', '[\"Amazon\",\"Google\", \"Indeed\"]', '', 'list', 'End customer list used by production view.');
 
 CREATE TABLE `ERP_production_pmcustomers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC));
 
 INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
 VALUES ('erp', 'production', 'erp_plugins_moravia_pmcustomers', 'all');
 
 
 INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
 VALUES ('runtimeOptions.plugins.Moravia.handoffNumberCustomerConfig', '1', 'erp', 'plugins', '[]', '[]', '', 'map', 'Handoff number customer configured values for hours and word preis per language combination. Value configuration example: {\"MoraviaNumber\":[{\"source\":\"de-De\",\"target\":\"en-Gb\",\"wordprice\":1,\"hourprice\":2},{\"source\":\"en\",\"target\":\"mk\",\"wordprice\":1,\"hourprice\":2}],\"Alex\":[{\"source\":\"en\",\"target\":\"de\",\"wordprice\":1,\"hourprice\":2},{\"source\":\"en\",\"target\":\"de-De\",\"wordprice\":1,\"hourprice\":2}]}');
 
 INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
 VALUES ('runtimeOptions.plugins.Moravia.preliminaryWeightedWordsEndCustomerConfig', '1', 'erp', 'plugins', '', '{\"Google\":{\"name\":\"preliminaryWeightedWords\",\"visible\":true,\"required\":true},\"Indeed\":{\"name\":\"preliminaryWeightedWords\",\"visible\":true,\"required\":true}}', '', 'map', 'Customer specific configuration for preliminaryWeightedWords field. Valid config parametars are: visible,required and editable. The field name in each config is required.');
 
 
CREATE TABLE `ERP_production_type` (
 `id` INT NOT NULL AUTO_INCREMENT,
 `name` VARCHAR(255) NULL,
 PRIMARY KEY (`id`),
UNIQUE INDEX `name_UNIQUE` (`name` ASC));
  
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
VALUES ('erp', 'production', 'erp_plugins_moravia_type', 'all');
 
ALTER TABLE `ERP_order` 
CHANGE COLUMN `state` `state` VARCHAR(255) NOT NULL DEFAULT 'offered' ;
 
 
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'erp_plugins_moravia_production', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'admin', 'erp_plugins_moravia_production', 'all');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.plugins.Moravia.balanceValueCheckValues', '1', 'erp', 'plugins', '{\"Amazon\":\"15\",\"Google\":\"10\",\"Indeed\":\"10\"}', '{\"Amazon\":\"15\",\"Google\":\"10\",\"Indeed\":\"10\"}', '', 'map', 'Balance value check procent defined for each end customer. The sum of the \"billNetValue\" of all line items of a handoff must not exceed X percent(where the x is defined in this config per end customer) less than the hand-off value and a maximum of 25 cents greater than the hand-off value.');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'frontend', 'changeBalanceValueCheck');


INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'erp_index', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'erp_order', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'erp_ordercomment', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'erp_customer', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'erp_purchaseorder', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'erp_user', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'erp_plugins_moravia_production', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'production', 'backend', 'seeAllUsers');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'erp_plugins_moravia_pmcustomers', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'erp_plugins_moravia_type', 'all');
  