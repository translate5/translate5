
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

/**
 * 
 */
Ext.define('Editor.view.quality.FilterPanel', {
    extend: 'Ext.tree.Panel',
    requires: [
        'Editor.view.quality.FilterPanelController',
    ],    
    controller: 'qualityFilterPanel',
    alias: 'widget.qualityFilterPanel',
    itemId:'qualityFilterPanel',
    store: 'FilterQualities',
    title : "#UT#Qualitätssicherung",
    rootVisible: false,
    useArrows: true,

    strings:{
          
    },
    columns: [{
        xtype: 'treecolumn',
        iconCls: 'x-tree-noicon',
        dataIndex:'text',
        sortable: true,
        flex: 1
    }],
    // we catch the beforestaterestore event to load the store when the panel is initially open
    // Note, that in the global qualities controller Editor.controller.Quality there is a store unload on the global editorViewportClosed event. This cannot be done here since this view is destroyed before that event ...
    listeners: {
        beforeexpand: function(comp){
            comp.loadStore();
        },
        collapse: function(comp){
            comp.unloadStore();
        },
        beforestaterestore: function(comp, state){
            if(state.hasOwnProperty('collapsed') && state.collapsed === false){
                comp.loadStore();
            }
        }
    },
    initConfig : function(instanceConfig) {
        var config = { title: this.title };
        if (instanceConfig) {
            this.self.getConfigurator().merge(this, config, instanceConfig);
        }
        return this.callParent([config]);
    },
    /**
     * Loads the qualities before the panel is expanded or if a uncollapsed state is applied (to catch an initiallay open panel)
     */
    loadStore: function(){
        this.store.load({
            manualLoaded: true,
            scope: this.store,
            callback: function(){ 
                this.getRootNode().expand();
             }
        });
    },
    /**
     * Unloads the qualities after the panel is collapsed
     */
    unloadStore: function(){
        this.store.getRootNode().removeAll(false);
    },
    /*
    initComponent: function() {
        Ext.applyIf(this, {
          viewConfig: {
            singleSelect: true
          }
        });
        this.callParent(arguments);
    },
    */
    // since we do update the rows manually we do not want a dirty-marker ... an API to set an item to not be dirty would be better
    viewConfig:{
        markDirty: false
    }
});
