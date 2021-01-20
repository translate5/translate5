
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

Ext.define('Editor.view.admin.config.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.admin.config.GridViewModel',
        'Editor.view.admin.config.GridViewController'
    ],
    controller: 'adminConfigGrid',
    viewModel:{
        type:'adminConfigGrid',
    },
    alias: 'widget.adminConfigGrid',
    itemId: 'adminConfigGrid',
    glyph: 'xf085@FontAwesome5FreeSolid',
    store:'admin.Config',
    layout: {
        type: 'fit'
    },
    selModel: 'cellmodel',
    plugins: [
        'gridfilters',
        {
            ptype: 'cellediting',
            clicksToEdit: 1
        }
    ],
    strings: {
        title:'#UT#Konfiguration',
        id:'#UT#Value',
        name:'#UT#Code ID',
        guiName:'#UT#Kurzer Name',
        value:'#UT#Wert',
        editRecordTooltip:'#UT#Konfiguration bearbeiten',
        saveRecordTooltip:'#UT#Speichern',
        description:'#UT#Beschreibung',
        reloadBtn: '#UT#Aktualisieren',
        overwriteOrigin : '#UT#Auf ebene gesetzt',
        configActiveColumn:'#UT#Aktiviert',
        configDeactiveColumn:'#UT#Deaktiviert',
        updateConfigSuccessMessage:'#UT#Konfiguration gespeichert',
        instanceConfigChangeMessageBoxText:'#UT#Die Änderung wird beim nächsten Login wirksam. Dies gilt auch für andere derzeit eingeloggte Benutzer.',
        collapseAll:'#UT#Alles zuklappen',
        expandAll:'#UT#Alles aufklappen',
        toolbarFilter:'#UT#Suche',
        overwriteLevelList:'#UT#Überschreibbar auf Ebene:',
        readOnlyFilter:'#UT#Schreibgeschützte Konfigurationen sichtbar:'
    },
    
    listeners:{
        edit:'onConfigEdit',
        activate:'onGridActivate'
    },
    
    viewConfig : {
        getRowClass: function (record) {
            if(record.get('isReadOnly')){
                return 'disabled-row';
            }
            return '';
        },
        listeners : {
            groupexpand : 'onGroupExpand'
        }
    },
    
    /***
     * Extra params property used for store proxy binding 
     * How to use:
     * {
            xtype: 'adminConfigGrid',
            bind:{
                extraParams:{
                    taskGuid : '{projectTaskSelection.taskGuid}'
                }
            }
        }
     */
    extraParams : [],
    publishes: {
        //publish this field so it is bindable
        extraParams: true
    },
    
    /***
     * allow the store extra params to be configurable on grid level. This will enable flexible loads via binding
     */
    setExtraParams:function(newExtra){
        if(!newExtra){
            return;
        }
        
        //check for empty values, and remove them. Loads for empty values is not required
        Ext.Object.each(newExtra, function(key, value, myself) {
            if(!value || value === "" || value == undefined){
                delete newExtra[key]
            }
        });
        
        if(Ext.Object.getSize(newExtra) < 1){
            this.getStore().removeAll(true);
            return;
        }
        
        var me=this,
            store = me.getStore(),
            existing = store.getProxy().getExtraParams(),
            merged = Ext.Object.merge(existing, newExtra);
        store.getProxy().setExtraParams(merged);
        store.load({
            callback:function(){
                me.getStore().addFilter({
                    property: 'isReadOnly',
                    value   : false
                });
                me.getController().onCollapseAll();
                me.getController().handleHasReadOnly();
            }
        });
    },
    
    initConfig: function(instanceConfig) {
        var me = this,
            config = {
                title:me.strings.title,
                features: [{
                    ftype: 'grouping',
                    startCollapsed: true,
                    groupHeaderTpl: '{name} ({rows.length})'
                }],
                columns: [{
                    xtype: 'gridcolumn',
                    dataIndex: 'id',
                    hidden:true,
                    filter: {
                        type: 'number'
                    },
                    text: me.strings.id
                },{
                    xtype: 'gridcolumn',
                    flex:0.4,
                    dataIndex: 'guiName',
                    cellWrap: true,
                    renderer: me.guiNameCellRenderer,
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.guiName
                },{
                    xtype: 'gridcolumn',
                    dataIndex: 'value',
                    flex:0.2,
                    tdCls:'grid-cell-text-center',
                    getEditor:me.getEditorConfig,
                    renderer:me.getValueRenderer,
                    scope:me,
                    text: me.strings.value
                },{
                    xtype: 'gridcolumn',
                    flex:0.2,
                    cellWrap: true,
                    tdCls:'grid-cell-text-center',
                    dataIndex: 'name',
                    filter: {
                        type: 'string'
                    },
                    text: me.strings.name
                },{
                    xtype: 'gridcolumn',
                    hidden:true,
                    flex:0.2,
                    dataIndex: 'origin',
                    text: me.strings.overwriteOrigin
                }],
                dockedItems: [{
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [{
                        xtype: 'button', 
                        glyph: 'f2f1@FontAwesome5FreeSolid',
                        handler:function(){
                            me.getStore().reload();
                            me.getController().handleHasReadOnly();
                        },
                        text: me.strings.reloadBtn
                    },{
                        xtype: 'button', 
                        glyph: 'f068@FontAwesome5FreeSolid',
                        handler:'onCollapseAll',
                        text: me.strings.collapseAll
                    },{
                        xtype: 'button', 
                        glyph: 'f067@FontAwesome5FreeSolid',
                        handler:'onExpandAll',
                        text: me.strings.expandAll
                    },me.strings.toolbarFilter,{
                        xtype: 'textfield',
                        name: 'searchField',
                        itemId: 'searchField',
                        hideLabel: true,
                        width: 200
                    },{
                        xtype: 'label',
                        text:me.strings.readOnlyFilter,
                        bind:{
                            visible:'{hasReadOnly}'
                        }
                    },{
                        xtype: 'checkbox',
                        name: 'showReadOnly',
                        bind:{
                            visible:'{hasReadOnly}'
                        },
                        checked:false,
                        itemId: 'showReadOnly'
                    }],
                }]
            };
        if (instanceConfig) {
        	config=me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },
    
    /***
     * 
     */
    getEditorConfig:function(record){
        var me=this,
            hasDefaults = record.get('defaults').length>0,
            config={
                xtype: 'textfield',
                name: 'value',
                value:record.get('value')
            };
        if(record.get('isReadOnly') && record.get('isReadOnly')==true){
            return false; 
        }
        switch(record.get('type')){
            case 'int':
            case 'integer':
                config={
                    xtype: 'numberfield',
                    name: 'value',
                    value:record.get('value'),
                };
                break;
            case 'string':
                if(hasDefaults){
                    config={
                        xtype: 'combo',
                        name: 'value',
                        store:record.get('defaults'),
                        value:record.get('value'),
                        queryMode: 'local',
                        typeAhead: false
                        //filterPickList: true
                    };
                }
                break;
            case 'boolean':
            case 'bool':
                config={
                    xtype: 'combo',
                    name: 'value',
                    displayField: 'value',
                    valueField: 'id',
                    store:Ext.create('Ext.data.Store', {
                        fields: ['id', 'value'],
                        data : [
                            {"id":"false", "value":me.up('grid').strings.configDeactiveColumn},
                            {"id":"true", "value":me.up('grid').strings.configActiveColumn},
                        ]
                    }),
                    value:record.get('value'),
                    queryMode: 'local',
                    typeAhead: false
                    //filterPickList: true
                };
                break;
            case 'list':
                config={
                  xtype: 'tagfield',
                  name: 'value',
                  store:hasDefaults ? record.get('defaults') : [],
                  value:record.get('value'),
                  typeAhead: true,
                  queryMode: 'local',
                  filterPickList: false,
                  triggerOnClick: true,
                  createNewOnBlur: !hasDefaults,
                  createNewOnEnter: !hasDefaults,
                  triggerAction: 'all',
                  growMax: 150
              };
            break;
        }
        return Ext.create('Ext.grid.CellEditor', {
            field:Ext.create(config),
            completeOnEnter: false
        });
    },
    
    /***
     * Grid value cell renderer
     */
    getValueRenderer:function (value, metaData, record) {
        var me=this,
            isValueChanged = record.get('default') != value,
            returnValue = value;
        switch (record.get('type')) {
            case 'boolean': // bool
                var defaultVal = !/^(?:f(?:alse)?|no?|0+)$/i.test(record.get('default')) && !!record.get('default');
                isValueChanged = defaultVal !== value;
                if(value == true){
                    returnValue = me.strings.configActiveColumn;
                }
                if(value == false){
                    returnValue = me.strings.configDeactiveColumn;
                }
            break;
            case 'map':
                if(Ext.isObject(value)) {
                    returnValue = Ext.JSON.encode(value);
                }
            break;
        }
        //mark the value with bold if the value is different as the default value
        if(isValueChanged && returnValue){
            returnValue = '<b>'+returnValue+'</b>';
        }
        return returnValue;
    },
    
    /***
     * Cell renderer for the guiName cell.
     * TODO: use template
     */
    guiNameCellRenderer:function(value, meta, record) {
        var me=this,
            desc = record && record.get('description'),
            html = ['<b>'],
            levels = [],
            labelLevel = 0,
            recordLevel = 0,
            ignoreLevels = [record.CONFIG_LEVEL_USER,record.CONFIG_LEVEL_INSTANCE,record.CONFIG_LEVEL_SYSTEM];//"don't render" levels
        
        html.push(value);
        html.push('</b>');
        html.push('</br>');
        
        if(desc){
            html.push('<i>');
            html.push(desc);
            html.push('</i>');
            html.push('</br>');
        }
        
        Ext.Object.each(Editor.data.frontend.config.configLabelMap, function(property, v){
            try {
                labelLevel = parseInt(property);
                recordLevel = parseInt(record.get('level'));
                if(labelLevel<=recordLevel && !Ext.Array.contains(ignoreLevels,labelLevel)){//ignore user level in the list
                    levels.push(v);
                }
            } catch (e) {
                Ext.Logger.warn("Unable to parse levels: ["+labelLevel+"],["+recordLevel+"]")
            }
        });
        
        if(levels.length>0){
            html.push('<i>');
            html.push('<small>');
            html.push(me.strings.overwriteLevelList);
            html.push(' ');
            html.push(levels.join(", "));
            html.push('</small>');
            html.push('</i>');
            html.push('</br>');
        }
        
        return html.join("");
    },
});