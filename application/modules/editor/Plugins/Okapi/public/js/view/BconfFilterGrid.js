/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.plugins.Okapi.view.BconfFilterGrid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.plugins.Okapi.store.BconfFilterStore'
    ],
    alias: 'widget.filtergrid',
    plugins: ['gridfilters'],
    helpSection: 'useroverview',
    cls: 'actionColGrid',
    text_cols: {
        customFilterName: '#UT#Customized Okapi Filter Type',
        name: '#UT#Name',
        extensions: '#UT#Extensions',
        description: '#UT#Description',
        action: '#UT#Actions',
        notes: '#UT#Notes',
        upload: '#UT#upload',
        srx: '#UT#SRX',
        pipeline: '#UT#Pipeline',
    },
    strings: {
        configuration: '#UT#Configur Filter',
        remove: '#UT#Remove',
        copy: '#UT#Copy',
        upload: '#UT#Upload',
        addBconf: '#UT#Add Bconf',
        showDefaultFilter: '#UT#Show Okapi Defaults Filters',
        customizeFilter: '#UT#Customize Filter'
    },
    store: {
        type: 'bconfFilterStore'
    },
    viewConfig: {
        getRowClass: function(bconf){
            if(!bconf.get('editable')){
                return 'not-editable';
            }
            return '';
        }
    },
    initConfig: function(instanceConfig){
        var me = this,
            itemFilter = function(item){
                return true;
            },
            config = {
                columns: [{
                    xtype: 'gridcolumn',
                    dataIndex: 'name',
                    stateId: 'name',
                    width: 300,
                    filter: {
                        type: 'string'
                    },
                    text: me.text_cols.name
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'extensions',
                    width: 200,
                    stateId: 'extensions',
                    filter: {
                        type: 'string'
                    },
                    text: me.text_cols.extensions
                }, {
                    xtype: 'gridcolumn',
                    dataIndex: 'notes',
                    stateId: 'notes',
                    flex: 1,
                    filter: {
                        type: 'string'
                    },
                    text: me.text_cols.notes
                }, {
                    xtype: 'actioncolumn',
                    width: 3 * 28 + 8 + 28,
                    stateId: 'okapiGridActionColumn',
                    align: 'center',
                    text: me.text_cols.action,
                    items: Ext.Array.filter([{
                        tooltip: me.strings.configuration,
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f044@FontAwesome5FreeSolid',
                        handler: 'editbconf'
                    },{
                        tooltip: me.strings.configuration,
                        isAllowedFor: 'bconfEdit',
                        glyph: 'f24d@FontAwesome5FreeSolid',
                        handler: 'clonebconf'
                    }, {
                        tooltip: me.strings.remove,
                        isAllowedFor: 'bconfDelete',
                        glyph: 'f2ed@FontAwesome5FreeSolid',
                        handler: 'deletebconf'
                    }], itemFilter)
                }],
            };
        return me.callParent([config]);
    },

});