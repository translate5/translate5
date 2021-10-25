--
-- Enabling dbupdater for role pm only.
--

UPDATE Zf_acl_rules SET `role` = 'pm' WHERE `module` = 'default' AND `role` = 'noRights' AND `resource` = 'database' AND `right` = 'all';