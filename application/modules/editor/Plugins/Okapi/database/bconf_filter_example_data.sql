INSERT INTO `LEK_okapi_bconf` (`id`, `name`, `customer_id`, `default`, `extensions`, `description`) VALUES
    (1, 'Okapi_open', '12', 1, 'doc,txt', 'This is the default filter which is not editable'),
    (3, 'Okapi_open', '12', 0, 'doc,txt', 'This is the custom filter.'),
    (4, 'Okapi_open', '12', 0, 'doc,txt', 'This is the custom filter.');

INSERT INTO `LEK_okapi_bconf_filter` (`id`, `okapiId`, `configId`, `okapiName`, `mime`, `default`, `name`, `notes`, `extensions`, `configuration`, `codeId`) VALUES
     (2, 1, 'okf_simplification-xmlResources', 'XML (Simplified resources)', 'text/xml', b'1', 'XML (Simplified resources)', 'Configuration for extracting resources from an XML file. Resources are simplified.', '.xml', '#v1\r\nmimeType=new\r\nfileNames=*.tmx,*.xlf,*.xlff\r\nconfigIds=okf_tmx,okf_xliff,okf_xliff', NULL),
     (4, 3, 'okf_simplification-xmlResources', 'XML (Simplified resources)', 'text/xml', b'1', 'XML (Simplified resources)', 'Configuration for extracting resources from an XML file. Resources are simplified.', '.xml', 'net.sf.okapi.lib.preprocessing.filters.simplification.SimplificationFilter', NULL),
     (5, 1, 'okf_simplification-xmlResources1', 'XML (Simplified resources)', 'text/xml', b'1', 'XML (Simplified resources)', 'Configuration for extracting resources from an XML file. Resources are simplified.', '.doc,pdf', '#v1\r\nmimeType=new\r\nfileNames=*.tmx,*.xlf,*.xlff\r\nconfigIds=okf_tmx,okf_xliff,okf_xliff', NULL);