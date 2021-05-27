
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
 * TODO FIXME: The state sent to the server when refreshing the store is pretty dirty since it is an encoded string of props. This is due to the eveolutionary extension of it's functionality. The current complexity would require a JSON model ... 
 */
Ext.define('Editor.view.quality.FilterPanelController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.qualityFilterPanel',
    delayedChange: null,
    panelShown: false,
    preventNextFilterUpdate: false, // prevents the next filter update
    currentFilterVal: null,
    qualitiesChanged: false,
    listen: {
        controller: {
            '#Segments': {
                segmentEditSaved: 'onSegmentSaved',
                segmentEditCanceled: 'onSegmentCanceled'
            }
        },
        store: {
            '#FilterQualities': {
                load: 'onFilterStoreLoaded'
            },
            '#SegmentQualities': {
                add: 'onSegQualStoreChanged',
                remove: 'onSegQualStoreChanged',
                update: 'onSegQualStoreChanged'
            }
        }
    },
    /**
     * When the view is expanded we load/reload the store
     */
    onBeforeExpand: function(){
        // if a filter was already set this is not the initial opening and we need to keep the persistence of this filter
        if(this.currentFilterVal){
            this.loadFilteredStrore(this.currentFilterVal);
        } else {
            this.loadStore();
        }
    },
    /**
     * When the view is collapsed we unload store to be clean
     */
    onCollapse: function(){
        this.unloadStore(false);
    },
    /**
     * When the view is unloaded we unload store to be clean
     */
    onRemoved: function(){
        this.unloadStore(false);
    },
    /**
     * When the state is applied we load the store. This is neccessary to catch an initially open qualities panel when a task is edited. TODO: isn't there a better event ?
     */
    onBeforeStateRestore: function(view, state){
        if(state.hasOwnProperty('collapsed') && state.collapsed === false){
            var filterMode = Editor.app.getController('Quality').getFilterMode();
            if(filterMode == 'all'){
                this.loadStore();
            } else {
                this.loadFilteredStrore('NONE|' + filterMode);
            }
        }
    },    
    /**
     * Changes the filter-mode initiated by the radio on top
     */
    onFilterModeChanged: function(comp, newVal, oldVal){
        if(newVal != oldVal){
            // We have to set the filter mode explicitly here because the listener from the global Quality controller may be called after this listener
            this.loadFilteredStrore(this.getFilterValue(true, newVal));
        }
    },
    /**
     * TODO AUTOQA: implement
     * Opens the re-analysis dialog
     */
    onAnalysisButtonClick: function(btn){
        console.log('onAnalysisButtonClick: ', btn);
    },
    /**
     * Called on saving of segments (incl. alikes). We refresh our store then without updating the filtered grid if we are visible /show qualities
     */
    onSegmentSaved: function(grid, record){
        if(this.panelShown){
            // the "segmentEditSaved" event it seems does not cover the time e.g. the checking of the segment state needs in conjunction with language resources that must be requested
            // this is just a very dirty attempt to cover this, obiously we have a race-condition. The good thing is, it' will result in a outdated view only ...
            var me = this, matchRateType = (record) ? record.get('matchRateType') : null;
            if(matchRateType && (matchRateType.indexOf(';tm;') > -1 || matchRateType.indexOf(';mt;') > -1)){ // this evaluation is pretty dirty but nothing bad happens when it fails
                me.delayedChange = new Ext.util.DelayedTask(function(){
                    me.refreshFilteredStore();
                });
                me.delayedChange.delay(250);
            } else {
                me.refreshFilteredStore();
            }
        }
    },
    /**
     * Called on a canceled segment edit
     */
    onSegmentCanceled: function(){
        // after a cancled edit we only refresh if we are shown and the store has changed
        if(this.qualitiesChanged){
            this.refreshFilteredStore();
        }
    },
    /**
     * Prevents an item being checked when it has no qualities
     */
    onBeforeCheckChange (record, checked, e){
        if(record.isEmpty()){
            return false;
        }
    },
    /**
     * Called for each checkbox when it is Changed, This makes a delayed update neccessaray to unify multiple changes to one
     */
    onCheckChange: function(record, checked, e){
        record.propagateChecked(checked);
        if(this.delayedChange == null){
            var me = this;
            me.delayedChange = new Ext.util.DelayedTask(function(){
                me.delayedChange = null;
                me.updateFilter(true);
            });
            me.delayedChange.delay(50);
        }
    },
    /**
     * Handles showing the loaded store & firing the filter event
     */
    onFilterStoreLoaded: function(store){
        var me = this, view = this.getView();
        store.getRoot().expand();
        view.afterLoad();
        me.delayedChange = new Ext.util.DelayedTask(function(){
            me.delayedChange = null;
            me.updateFilter(true);
        });
        me.delayedChange.delay(250);
        this.panelShown = true;
    },
    /**
     * Listen's to changes in the segments qualities store
     * These changes could originate from the segmentQm or the falsePositives panel
     * We have to refresh the qualities filterPanel if changes occured (if we are shown)
     */
    onSegQualStoreChanged: function(store){
        this.qualitiesChanged = this.panelShown;
    },
    /**
     * Loads the qualities before the panel is expanded or if a uncollapsed state is applied (to catch an initially open panel)
     */
    loadStore: function(){
        this.getView().getStore().load();
    },
    /**
     * Reloads the store and keeps the current selection
     */
    loadFilteredStrore: function(filterVal){
        this.getView().getStore().load({
            params: {
                currentstate: filterVal
            }
        });
    },
    /**
     * Refreshes the view by reloading the store with the current filter (only when we're visible...)
     */
    refreshFilteredStore: function(){
        if(this.panelShown){
            this.preventNextFilterUpdate = true;
            this.loadFilteredStrore(this.getFilterValue(true, null));
        }
        // we can dismiss this in any case, if we're not visible, fresh data will be loaded in any case
        this.qualitiesChanged = false;
    },
    /**
     * Unloads the qualities after the panel is collapsed
     */
    unloadStore: function(doUpdateGrid){
        this.getView().getStore().removeAll(false);
        if(doUpdateGrid){
            this.fireEvent('qualityFilterChanged', '');
        }
        this.panelShown = false;
    },
    
    /**
     * Fires the filter update event
     */
    updateFilter: function(doUpdateGrid){
        if(this.preventNextFilterUpdate){
            this.preventNextFilterUpdate = false;
        } else if(doUpdateGrid) {
            this.fireEvent('qualityFilterChanged', this.getFilterValue(false, null));
        }
    },
    /**
     * Unchecks all Checkboxes
     */
    uncheckAll: function(){
        if(this.panelShown){
            Ext.Array.each(this.getView().getChecked(), function(record){
                record.set('checked', false);
            });
        }
        this.currentFilterVal = null;
    },
    /**
     * Creates the filter value which encodes the checked qualities as well as the current filter mode
     * Note, that for filtering the grid we do not add entries for checked rubrics while for a reload of the filter panel we do.
     * Also note, that en empty selection is marked with 'NONE', see backend code
     */
    getFilterValue: function(forStoreReload, newModeVal){
        var checkedVals = [],
            modeVal = (!newModeVal) ? Editor.app.getController('Quality').getFilterMode() : newModeVal;
        // retrieve all checked filters. When reloading the store, this has to cover the rubrics as well
        Ext.Array.each(this.getView().getChecked(), function(record){
            // send the rubrics only for a store reload
            if(record.isCategory() || forStoreReload){
                checkedVals.push(record.getTypeCatKey());
            }
        });
        if(checkedVals.length > 0){
            checkedVals = Ext.Array.unique(checkedVals); // just in Case
        }
        this.currentFilterVal = (checkedVals.length > 0) ? (checkedVals.join(',') + '|' + modeVal) : ('NONE|' + modeVal);
        // CRUCIAL: if we generate values for the segment Controller we must return an empty value in case nothing is checked
        // the segments controller will manage only the two states 'not filtered' = empty value or 'filtered= = value with all filters
        if(!forStoreReload && checkedVals.length == 0){
            return '';
        }
        // if we reload the store we want the state of the expanded/collapsed nodes to be persistent and thus send them as well
        // this will not apply when changing the filter mode, it is quite worrying to have stuff invisible when switching back from false negative mode
        if(forStoreReload && !newModeVal){
            var collapsedVals = [];
            Ext.Array.each(this.getView().getStore().getRoot().getCollapsedChildren(), function(record){
                collapsedVals.push(record.getTypeCatKey());
            });
            return (collapsedVals.length == 0) ? (this.currentFilterVal + '|NONE') : (this.currentFilterVal + '|' + collapsedVals.join(','));
        }
        return this.currentFilterVal;
    }
});
