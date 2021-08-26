
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.view.segments.column.Matchrate
 * @extends Ext.grid.column.Column
 */
Ext.define('Editor.view.segments.column.Matchrate', {
    extend: 'Editor.view.segments.column.MatchrateType',
    alias: 'widget.matchrateColumn',
    mixins: ['Editor.view.segments.column.BaseMixin'],
    dataIndex: 'matchRate',
    text: 'Matchrate',
    initComponent: function() {
        var me = this;
        me.initBaseMixin();
        me.callParent(arguments);
    },
    initConfig: function(instanceConfig) {
        var me = this,
            tdCls = 'matchrateColumn',
            config = {
                tdCls: tdCls,
                editor: {
                    xtype: 'displayfield',
                    getModelData: function() {
                        return null;
                    },
                    cls: 'matchrateEdit',
                    ownQuicktip: true,
                    renderer: me.ownQuicktip(tdCls)
                },
                filter: {
                    type: 'numeric'
                }
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    renderer: function(value,meta,record) {
        return value + ' ' + this.callParent([record.get('matchRateType'),meta,record]);
    }
});