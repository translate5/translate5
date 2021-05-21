/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @class Editor.view.segments.column.IsRepeated
 * @extends Ext.grid.column.Column
 */
Ext.define('Editor.view.segments.column.IsRepeated', {
    extend: 'Ext.grid.column.Column',
    alias: 'widget.isRepeatedColumn',
    mixins: ['Editor.view.segments.column.BaseMixin'],
    dataIndex: 'isRepeated',
    text: '#UT#Mit Wiederholungen',
    bind: {
        disabled: '{!taskHasDefaultLayout}',
        hidden: '{!taskHasDefaultLayout}'
    },
    strings: {
        ttip: '#UT#Änderungen des Wiederholungsstatus durch Segmentänderungen werden nicht sofort angezeigt, die Segmente müssen neugeladen werden!',
        filter: {
            none: '#UT#Segmente ohne Wiederholungen',
            source: '#UT#Segmente mit Wiederholungen nur in der Quellsprache',
            target: '#UT#Segmente mit Wiederholungen nur in der Zielsprache',
            both: '#UT#Segmente mit Wiederholungen in der Quell- und Zielsprache',
        },
        col: {
            none: '#UT#-',
            source: '#UT#Nur Quelle',
            target: '#UT#Nur Ziel',
            both: '#UT#Beides'
        }
    },
    initComponent: function () {
        var me = this;
        me.initBaseMixin();
        me.callParent(arguments);
    },
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                tooltip: me.strings.ttip,
                editor: {
                    xtype: 'displayfield',
                    cls: 'isRepeated'
                },
                filter: {
                    type: 'list',
                    labelField: 'label',
                    phpMode: false,
                    options: [{
                        id: 0,
                        label: me.strings.filter.none
                    },{
                        id: 1,
                        label: me.strings.filter.source
                    },{
                        id: 2,
                        label: me.strings.filter.target
                    },{
                        id: 3,
                        label: me.strings.filter.both
                    }]
                }
            };
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    renderer: function(value,context,record){
        var me = this,
            str = me.down('isRepeatedColumn').strings.col;

        switch(value) {
            case 3:
                return str.both;
            case 2:
                return str.target;
            case 1:
                return str.source;
            default:
                return str.none;
        }
    }
});