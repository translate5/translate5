/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Config editor class for fixed map configurations. In those kind of configs, only the values are editable ans the user
 * can not add new or remove existing records.
 */
Ext.define('Editor.view.admin.config.type.FixedMap', {
    extend: 'Editor.view.admin.config.type.SimpleMap',
    requires: [
        'Editor.view.admin.config.type.FixedMapController'
    ],
    controller: 'configTypeFixedMap',

    /**
     * Static must be overridden and can not be inherited
     */
    statics: {
        getConfigEditor: function (record) {
            var win = new this(record.isModel ? {record: record} : record);
            win.show();

            //prevent cell editing:
            return null;
        },
        getJsonFieldEditor: function (config) {
            var win = new this(config);
            win.show();

            //prevent cell editing:
            return null;
        },
        renderer: function (value) {
            var res = [];
            Ext.Object.each(value, function (key, item) {
                item = item.toString();
                if (key === item) {
                    res.push(item);
                } else {
                    res.push(key + '-' + item);
                }
            });
            return res.join('; ');
        }
    },

    initConfig: function (instanceConfig) {
        instanceConfig.hideTbar = true;
        return this.callParent(arguments);
    }
});
