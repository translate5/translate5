
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

Ext.define('Editor.view.admin.config.Grid', {
    extend: 'Ext.grid.Panel',
    requires: [
        'Editor.view.admin.config.GridViewModel',
        'Editor.view.admin.config.GridViewController',
        'Editor.view.admin.config.type.SimpleMap',
        'Editor.view.admin.config.type.FixedMap',
        'Editor.view.admin.config.type.JsonEditor',
        'Editor.view.admin.config.type.TaskHtmlExport'
    ],
    controller: 'adminConfigGrid',
    viewModel:{
        type:'adminConfigGrid'
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
        configChangeReloadMessageBoxText:'#UT#Die Änderung wird beim nächsten Login wirksam. Dies gilt auch für andere derzeit eingeloggte Benutzer.',
        collapseAll:'#UT#Alles zuklappen',
        expandAll:'#UT#Alles aufklappen',
        toolbarFilter:'#UT#Suche',
        overwriteLevelList:'#UT#Überschreibbar auf Ebene:',
        readOnlyFilter:'#UT#Schreibgeschützte Konfigurationen sichtbar:',
        configLocales:{
            default:'#UT#Default',
            aria:'#UT#Dunkles Layout (Aria)',
            triton:'#UT#Standard Layout (Triton)'
        }
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
    taskGuid: null,
    extraParams : [],
    /**
     * Container for the custom type instances
     * @var Object
     */
    customTypes: {},
    publishes: {
        //publish this field so it is bindable
        extraParams: true
    },
    border: 0,
    /***
     * allow the store extra params to be configurable on grid level. This will enable flexible loads via binding
     * This function only expects and handles extraParams with valid taskGuid or customerId as parameter.
     */
    setExtraParams:function(newExtra){
        if(!newExtra){
            return;
        }
        
        //check for empty values, and remove them. Loading the store when the taskGuid is empty is not required
        Ext.Object.each(newExtra, function(key, value, myself) {
            if(!value || value === "" || value == undefined){
                delete newExtra[key]
            }
        });
        
        this.taskGuid = null;
        
        //if it is an empty object, removes(local only) all unfiltered items from the store. Filtered records will not be removed
        if(Ext.Object.getSize(newExtra) < 1){
            this.getStore().removeAll(true);
            return;
        }
        
        var me = this,
            store = me.getStore(),
            controller = me.getController(),
            cbShowReadOnly = me.down('#showReadOnly'),
            showReadonlyConfig = cbShowReadOnly == null ? true : cbShowReadOnly.checked;
        
        store.setExtraParams(newExtra);
        
        if(newExtra.taskGuid){
            me.taskGuid = newExtra.taskGuid;
        }
        
        store.load({
            callback:function(){
                if(!store || !controller){
                    return;
                }
                controller.handleReadonlyConfig(showReadonlyConfig);
                controller.onCollapseAll();
                controller.handleHasReadOnly();
            }
        });
    },
    /**
     * Called when import is finished to show a potentially changed view
     */
    refreshForTask: function(taskGuid){
        var store = this.getStore();
        if(store && this.taskGuid && this.taskGuid == taskGuid && store.isLoaded() && !store.isLoading()){
            store.load();
        }
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
                columns: [
                    {
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
                    enableOverflow: true,
                    items: [{
                        xtype: 'textfield',
                        name: 'searchField',
                        itemId: 'searchField',
                        emptyText: me.strings.toolbarFilter,
                        checkChangeBuffer:500,
                        hideLabel: true,
                        width: 300,
                        minWidth: 100,
                        flex: 1,
                    },{
                        xtype: 'checkbox',
                        name: 'showReadOnly',
                        bind:{
                            visible:'{hasReadOnly}'
                        },
                        checked:false,
                        boxLabel: me.strings.readOnlyFilter,
                        itemId: 'showReadOnly'
                    },{
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
                    },
                    {
                        xtype: 'tbspacer',
                        flex: 1.6,
                    }],
                }]
            };
        if (instanceConfig) {
        	config=me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    getDefaultsStore: function(defaults, translations) {
        if(defaults.length === 0){
            return [];
        }

        let defaultsData = [];

        // If defaults list is a json-text
        if (defaults.join(',').match('^{[^{}]*?}$')) {

            // Decode json
            let json = Ext.JSON.decode(defaults.join(','));

            // Foreach prop inside json
            for (let prop in json) {
                defaultsData.push({
                    "id" : prop,
                    "value" : json[prop]
                });
            }

            // Else
        } else {
            // check if there is translation for the default values
            Ext.Array.each(defaults, function(name) {
                defaultsData.push({
                    "id" : name,
                    "value" : translations[name] !== undefined ? translations[name] : name
                });
            });
        }

        return Ext.create('Ext.data.Store', {
            fields: ['id', 'value'],
            data : defaultsData
        });
    },
    
    /***
     * 
     */
    getEditorConfig:function(record){
        var me= this,
            grid = me.up('grid'),
            defaults = record.get('defaults'),
            hasDefaults = defaults.length>0,
            defaultsStore = grid.getDefaultsStore(defaults, grid.strings.configLocales),
            config={
                xtype: 'textfield',
                name: 'value',
                value:record.get('value')
            };
        if(record.get('isReadOnly')){
            return false; 
        }

        switch(record.get('type')){
            case 'float':
            case 'int':
            case 'integer':
                config={
                    xtype: 'numberfield',
                    name: 'value',
                    value:record.get('value')
                };
                break;
            case 'string':
                if(hasDefaults){
                    config={
                        xtype: 'combo',
                        name: 'value',
                        store: defaultsStore,
                        displayField: 'value',
                        valueField: 'id',
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
                            {"id":"true", "value":me.up('grid').strings.configActiveColumn}
                        ]
                    }),
                    value:record.get('value'),
                    queryMode: 'local',
                    typeAhead: false
                    //filterPickList: true
                };
                break;
            case 'list':
            case 'regexlist':
            case 'xpathlist':
                config = {
                    xtype: 'tagfield',
                    name: 'value',
                    store: defaultsStore,
                    value: record.get('value'),
                    displayField: 'value',
                    valueField: 'id',
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

        if(!Ext.isEmpty(record.get('typeClassGui'))) {
            return grid.getCustomType(record.get('typeClassGui')).getConfigEditor(record);
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
        let me=this,
            isValueChanged = record.get('default') !== value,
            defaultsStore = me.getDefaultsStore(record.get('defaults'), me.strings.configLocales),
            returnValue = value;
           
        switch (record.get('type')) {
            case 'boolean': // bool
                var defaultVal = !/^(?:f(?:alse)?|no?|0+)$/i.test(record.get('default')) && !!record.get('default');
                isValueChanged = defaultVal !== value;
                if(value === true){
                    returnValue = me.strings.configActiveColumn;
                }
                if(value === false){
                    returnValue = me.strings.configDeactiveColumn;
                }
            break;
            case 'list':
                if(Ext.isArray(value)) {
                    if(defaultsStore.isStore) {
                        let result = [];
                        Ext.Array.each(value, function(idx){
                            let rec = defaultsStore.getById(idx);
                            result.push(rec ? rec.get('value') : idx);
                        });
                        returnValue = result.join(', ');
                    }
                    else {
                        returnValue = value.join(', ');
                    }
                }
                break;
            case 'string':
                if (record.get('defaults').join(',').match('^{[^{}]*?}$')) {
                    returnValue = Ext.JSON.decode(record.get('defaults').join(','))[value];
                }
            break;
            case 'map':
                if(Ext.isObject(value)) {
                    returnValue = Ext.JSON.encode(value);
                }
            break;
        }

        if(!Ext.isEmpty(record.get('typeClassGui'))) {
             returnValue = this.getCustomType(record.get('typeClassGui')).renderer(value, metaData, record);
        }

        // if the value of the config is defined as translatable locale, use the translation for display
        if(me.strings.configLocales[returnValue] !== undefined){
            returnValue = me.strings.configLocales[returnValue];
        }

        returnValue = Ext.String.htmlEncode(returnValue);

        //mark the value with bold if the value is different as the default value
        if(isValueChanged && returnValue){
            returnValue = '<b>'+returnValue+'</b>';
        }

        return returnValue;
    },

    /**
     * returns the instance to the custom type
     * @param [String} type
     * @returns {*}
     */
    getCustomType: function(type) {
        if(!this.customTypes[type]) {
            this.customTypes[type] = Ext.ClassManager.get(type);
        }
        return this.customTypes[type];
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
            desc = Ext.String.htmlEncode(desc);
            html.push(this.makeURLsClickable(desc));
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

    /**
     * renders URLs as HTML
     * @private
     */
    makeURLsClickable: function(str) {
        if (!str || str.indexOf('://') < 0) {
            return str;
        }
        return str.replace(/https?:\/\/\w\S+/g, function(url){
            let end = url.match(/[.;]+$/);
            if (end) {
                end = end[0];
                url = url.substring(0, url.length-end.length);
            }
            return '<a href="'+url+'" target=_blank>'+url+'</a>'+(end?end:'');
        });
    }

});