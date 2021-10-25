--
-- Dumping data for table `Zf_acl_rules`
--

LOCK TABLES `Zf_acl_rules` WRITE;
INSERT INTO `Zf_acl_rules` VALUES (null,'erp','pm','erp_customer','all');
INSERT INTO `Zf_acl_rules` VALUES (null,'erp','pm','erp_index','all');
INSERT INTO `Zf_acl_rules` VALUES (null,'erp','pm','erp_order','all');
INSERT INTO `Zf_acl_rules` VALUES (null,'erp','pm','erp_ordercomment','all');
INSERT INTO `Zf_acl_rules` VALUES (null,'erp','pm','erp_purchaseorder','all');
INSERT INTO `Zf_acl_rules` VALUES (null,'erp','pm','erp_purchaseordercomment','all');
INSERT INTO `Zf_acl_rules` VALUES (null,'erp','pm','erp_user','all');
INSERT INTO `Zf_acl_rules` VALUES (null,'erp','pm','erp_vendor','all');
UNLOCK TABLES;

UPDATE `Zf_configuration` SET value = '<div class="erp-back-link"><a href="/erp">zurück zum ERP</a></div>' WHERE `name` = 'runtimeOptions.editor.branding';

