#is user able in erp module to use editor specific components/features
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'editor', 'frontend', 'editorEditTask');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'frontend', 'editorEditTask');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'frontend', 'editorEditUser');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'backend', 'customerAdministration');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp', 'pm', 'frontend', 'customerAdministration');