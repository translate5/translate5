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

Ext.define("Editor.plugins.Okapi.view.filter.BConfGrid", {
    extend: "Ext.grid.Panel",
    requires: [
        "Editor.plugins.Okapi.view.filter.BConfGridController",
        "Editor.plugins.Okapi.store.BconfStore",
    ],
    alias: "widget.okapiFilterGrid",
    plugins: ["gridfilters"],
    itemId: "okapifilterGrid",
    controller: "bconfGridController",
    stateId: "okapifilterGrid",
    stateful: true,
    cls: "okapifilterGrid",
    title: "#UT#Dateiformatkonvertierung",
    helpSection: "useroverview",
    glyph: "f1c9@FontAwesome5FreeSolid",
    height: "100%",
    layout: {
        type: "fit",
    },
    text_cols: {
        name: "#UT#Name",
        extensions: "#UT#Extensions",
        description: "#UT#Description",
        action: "#UT#Actions",
        upload: "#UT#upload",
        srx: "#UT#SRX",
        pipeline: "#UT#Pipeline",
    },
    strings: {
        edit: "#UT#Edit",
        remove: "#UT#Remove",
        copy: "#UT#Copy",
        upload: "#UT#Upload",
        addBconf: "#UT#Add Bconf",
        uploadBconf: "#UT#Upload Bconf",
        searchText: "#UT#Search",
        searchEmptyText: "#UT#Search Bconf",
        export: "#UT#Export",
    },
    store: {
        type: "bconfStore",
    },
    viewConfig: {
        getRowClass: function (bconf) {
            if (!bconf.get("editable")) {
                return "not-editable";
            }
            return "";
        },
    },
    initConfig: function (instanceConfig) {
        var me = this,
            itemFilter = function (item) {
                return true;
            },
            config = {
                columns: [
                    {
                        xtype: "gridcolumn",
                        width: 200,
                        dataIndex: "name",
                        stateId: "name",
                        filter: {
                            type: "string",
                        },
                        text: me.text_cols.name,
                    },
                    {
                        xtype: "gridcolumn",
                        width: 200,
                        dataIndex: "extensions",
                        stateId: "extensions",
                        filter: {
                            type: "string",
                        },
                        text: me.text_cols.extensions,
                    },
                    {
                        xtype: "gridcolumn",
                        width: 300,
                        dataIndex: "description",
                        stateId: "description",
                        filter: {
                            type: "string",
                        },
                        text: me.text_cols.description,
                    },
                    {
                        xtype: "actioncolumn",
                        stateId: "okapiGridActionColumn",
                        align: "center",
                        dataIndex: "default",
                        width: 200,
                        text: me.text_cols.action,
                        items: [
                            {
                                tooltip: me.strings.edit,
                                isAllowedFor: "bconfEdit",
                                glyph: "f044@FontAwesome5FreeSolid",
                                handler: "editbconf",
                                isDisabled:'getActionStatus'
                            },
                            {
                                tooltip: me.strings.remove,
                                isAllowedFor: "bconfDelete",
                                glyph: "f2ed@FontAwesome5FreeSolid",
                                handler: "deletebconf",
                                isDisabled:'getActionStatus'
                            },
                            {
                                tooltip: me.strings.copy,
                                isAllowedFor: "bconfCopy",
                                margin: "0 0 0 10px",
                                glyph: "f24d@FontAwesome5FreeSolid",
                                handler: "copybconf",
                            },
                            {
                                tooltip: me.strings.export,
                                isAllowedFor: "bconfDelete",
                                glyph: "f56e@FontAwesome5FreeSolid",
                                handler: "exportbconf",
                            },
                        ],
                    },
                    {
                        xtype: "actioncolumn",
                        align: "center",
                        text: me.text_cols.srx,
                        items: [
                                {
                                    tooltip: me.strings.upload,
                                    isAllowedFor: "bconfEdit",
                                    glyph: "f093@FontAwesome5FreeSolid",
                                    bind: {
                                        hidden: '{default}'
                                    }
                                },
                                {
                                    tooltip: me.strings.export,
                                    isAllowedFor: "bconfDelete",
                                    glyph: "f56e@FontAwesome5FreeSolid",
                                },
                            ]
                    },
                    {
                        xtype: "actioncolumn",
                        align: "center",
                        width: 150,
                        text: me.text_cols.pipeline,
                        items: Ext.Array.filter(
                            [
                                {
                                    tooltip: me.strings.upload,
                                    isAllowedFor: "bconfEdit",
                                    glyph: "f093@FontAwesome5FreeSolid",
                                },
                                {
                                    tooltip: me.strings.export,
                                    isAllowedFor: "bconfDelete",
                                    glyph: "f56e@FontAwesome5FreeSolid",
                                },
                            ],
                            itemFilter
                        ),
                    },
                ],
                dockedItems: [
                    {
                        xtype: "toolbar",
                        dock: "top",
                        items: [
                            {
                                xtype: "button",
                                glyph: "f067@FontAwesome5FreeSolid",
                                text: me.strings.addBconf,
                                tooltip: me.strings.addBconf,
                                handler: "addNewFilterSet",
                            },
                            {
                                xtype: "button",
                                glyph: "f093@FontAwesome5FreeSolid",
                                text: me.strings.uploadBconf,
                                tooltip: me.strings.uploadBconf,
                            },
                            {
                                xtype: "textfield",
                                width: 300,
                                margin: "0 0 0 20px",
                                fieldLabel: me.strings.searchText,
                                emptyText: me.strings.searchEmptyText,
                                listeners:{
                                    change:'filterByText'
                                }
                            },
                        ],
                    },
                ],
            };
        return me.callParent([config]);
    },
});
