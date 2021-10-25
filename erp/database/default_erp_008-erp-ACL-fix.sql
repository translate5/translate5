--
-- Enabling accessing all PM users for other PM users
--

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp','pm','backend','seeAllUsers');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('erp','admin','backend','seeAllUsers');
