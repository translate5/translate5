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


Ext.define('Editor.view.ui.LangTagFieldFilter', {
    extend: 'Ext.grid.filters.filter.String',
    alias: 'grid.filter.langtagfield',
    require: [
        'Editor.store.admin.SelectableLanguages',
    ],
    operator: 'in',
    itemDefaults: {
        xtype:'tagfield',
        typeAhead: false,
        collapseOnSelect: false,
        displayField: 'label',
        forceSelection: true,
        anyMatch: true,
        queryMode: 'local',
        valueField: 'id',
        grow: true,
        margin: '1 2 2 2',
        maxWidth: 300,
    },
    getItemDefaults: function() {
        return Ext.merge(this.itemDefaults, {
            store: Ext.create('Editor.store.admin.SelectableLanguages')
        });
    },
    createMenu: function (){
        var me = this,
            config;

        me.callParent();

        // Remove keyup listener added by callParent() call, to prevent filter change event from
        // being triggered while user is typing some chars to search among the language tags in the dropdown
        // This was relevant for textfield-based filter but is not for tagfield-based one (i.e. the current one)
        me.inputItem.removeListener('keyup', me.onValueChange, me);

        // Instead, add that listener for 'change' event rather than for 'keyup'
        me.inputItem.on({
            scope: me,
            change: tagfield => me.onValueChange(tagfield, Ext.event.Event.prototype),
        });
    },
    setValue: function (value) {

        // Spoof empty array with null to make sure the value is falsy
        // as otherwise filter won't be deactivated
        if (Ext.isArray(value) && value.length === 0) {
            value = null;
        }

        // Call parent
        this.callParent([value]);
    }
});
