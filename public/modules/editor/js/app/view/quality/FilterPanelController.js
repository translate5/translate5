
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
 * View Controller for the quality filter panel
 */
Ext.define('Editor.view.quality.FilterPanelController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.qualityFilterPanel',
    delayedChange: null,
    /**
     * When the view is expanded we load/reload the store
     */
    onBeforeExpand: function(){
        this.loadStore();
    },
    /**
     * When the view is collapsed we unload store to be clean
     */
    onCollapse: function(){
        this.unloadStore();
    },
    /**
     * When the view is unloaded we unload store to be clean
     */
    onRemoved: function(){
        this.unloadStore();
    },
    /**
     * When the state is applied we load the store. This is neccessary to catch an initially open qualities panel when a task is edited. TODO: isn't there a better event ?
     */
    onBeforeStateRestore: function(view, state){
        if(state.hasOwnProperty('collapsed') && state.collapsed === false){
            this.loadStore();
        }
    },
    /**
     * Loads the qualities before the panel is expanded or if a uncollapsed state is applied (to catch an initially open panel)
     */
    loadStore: function(){
        var me = this;
        this.getView().getStore().load({
            callback: function(){
                me.storeLoaded();
            }
        });
    },
    /**
     * Handles showing the loaded store & firing the filter event
     */
    storeLoaded: function(){
        this.getView().getStore().getRootNode().expand();
        var me = this;
        me.delayedChange = new Ext.util.DelayedTask(function(){
            me.delayedChange = null;
            me.updateFilter();
        });
        me.delayedChange.delay(250);
    },
    /**
     * Unloads the qualities after the panel is collapsed
     */
    unloadStore: function(){
        this.getView().getStore().getRootNode().removeAll(false);
    },
    /**
     * Prevents an item being checked when it has no qualities
     * QUIRK: the "qroot" item (= All categories) can not be activated/deactivated more then once. Why ? Bug in ExtJS check propagation ?
     */
    onBeforeCheckChange (record, checkedState, e){
        if(record.get('qcount') == 0){
            return false;
        }
    },
    /**
     * Called for each checkbox when it is chnaged, This makes a delayed update neccessaray to unify multiple changes to one
     */
    onCheckChange: function(record, checkedState, e){
        if(this.delayedChange == null){
            var me = this;
            me.delayedChange = new Ext.util.DelayedTask(function(){
                me.delayedChange = null;
                me.updateFilter();
            });
            me.delayedChange.delay(50);
        }
    },
    /**
     * Updates the list of filtered segment Ids and fires the filter changed event
     */
    updateFilter: function(){
        var segmentIds = [];
        Ext.Array.each(this.getView().getChecked(), function(record){
            Ext.Array.push(segmentIds, record.get('segmentIds'));
        });
        segmentIds = Ext.Array.unique(segmentIds);
        this.fireEvent('qualityFilterChanged', segmentIds);
        console.log('qualityFilterChanged', segmentIds);
    }
});
