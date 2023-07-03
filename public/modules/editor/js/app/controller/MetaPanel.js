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
 * MetaPanel Controller
 * @class Editor.controller.MetaPanel
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.MetaPanel', {
    extend: 'Ext.app.Controller',
    requires: [
        'Editor.view.quality.mqm.Fieldset',
        'Editor.view.quality.FalsePositives',
        'Editor.view.quality.SegmentQm',
        'Editor.store.quality.Segment'],
    models: ['SegmentUserAssoc'],
    messages: {
        stateIdSaved: '#UT#Der Segment Status wurde gespeichert'
    },
    refs: [{
        ref: 'metaPanel',
        selector: '#metapanel'
    }, {
        ref: 'metaQmPanel',
        selector: '#metapanel #segmentQm'
    }, {
        ref: 'metaFalPosPanel',
        selector: '#metapanel #falsePositives'
    }, {
        ref: 'metaInfoForm',
        selector: '#metapanel #metaInfoForm'
    }, {
        ref: 'segmentMeta',
        selector: '#metapanel segmentsMetapanel'
    }, {
        ref: 'leftBtn',
        selector: 'menu #goAlternateLeftBtn'
    }, {
        ref: 'rightBtn',
        selector: 'menu #goAlternateRightBtn'
    }, {
        ref: 'segmentGrid',
        selector: '#segmentgrid'
    }],
    listen: {
        component: {
            '#metapanel segmentsMetapanel': {
                segmentStateChanged: 'onSegmentStateChanged'
            },
            '#segmentgrid': {
                afterrender: 'initEditPluginHandler',
                selectionchange: 'handleSegmentSelectionChange',
                beforeedit: 'startEdit',
                canceledit: 'cancelEdit',
                edit: 'saveEdit',
                itemcontextmenu: 'onSegmentContextMenu'
            },
            '#segmentgrid segmentroweditor': {
                afterrender: 'onSegmentEditorAfterRender'
            }
        },
        controller: {
            '#Editor': {
                changeSegmentState: 'onChangeSegmentState'
            }
        },
        store: {
            '#SegmentQualities': {
                load: 'handleQualitiesLoaded'
            }
        }
    },
    /**
     * If the QM qualities are enabled
     */
    hasQmQualities: false,
    /**
     * The store holding the segments qualiies. Data source for the falsePositives panel and the segmentQm panel
     */
    qualitiesStore: null,
    /**
     * A flag specifying our editing mode. can be: 'none', 'readonly', 'edit'
     */
    editingMode: 'none',
    /**
     * Gibt die RowEditing Instanz des Grids zurück
     * @returns Editor.view.segments.RowEditing
     */
    getEditPlugin: function () {
        return this.getSegmentGrid().editingPlugin;
    },
    getQualitiesStore: function () {
        if (this.qualitiesStore == null) {
            this.qualitiesStore = Ext.create('Editor.store.quality.Segment');
        }
        return this.qualitiesStore;
    },
    initEditPluginHandler: function () {
        var me = this,
            multiEdit = me.getSegmentGrid().query('contentEditableColumn').length > 1,
            useChangeAlikes = Editor.app.authenticatedUser.isAllowed('useChangeAlikes', Editor.data.task);
        // creating the store for the segment's qualities on the first edit
        me.getQualitiesStore();
        me.getLeftBtn().setVisible(multiEdit && !useChangeAlikes);
        me.getRightBtn().setVisible(multiEdit && !useChangeAlikes);
    },
    /**
     * Editor.view.segments.RowEditing beforeedit handler, initiert das MetaPanel mit den Daten
     * @param {Object} editingPlugin
     */
    startEdit: function (editingPlugin, context) {
        var me = this;
        me.editingMode = 'edit';
        me.toggleOnEdit(true);
        me.getSegmentMeta().show();
    },

    /**
     * Toggle GUI items on editor open/close
     *
     * @param enable
     */
    toggleOnEdit: function(toggle) {
        this.getMetaPanel().query('[enableOnEdit]').forEach(item => item.setDisabled(!toggle))
    },

    /**
     * Bind handler on click-event for segmenteditor to simulate selectionchange-event for segmentgrid
     * so that right panel (terms, qualities and comments) is reloaded as if segmentgrid's selection
     * would go back to edited segment
     *
     * @param segmenteditor
     */
    onSegmentEditorAfterRender: function(segmenteditor) {
        segmenteditor.el.on({
            click: () => {
                this.getSegmentGrid().getView().select(segmenteditor.context.record)
            },
            contextmenu: (event) => {
                this.onSegmentContextMenu(null, null, null, null, event);
            },
            scope: this
        });
    },

    /**
     * Open grid with ability to apply false positive status for a certain quality and all other similar qualities
     *
     * @param view
     * @param record
     * @param dom
     * @param idx
     * @param event
     */
    onSegmentContextMenu: function(view, record, dom, idx, event) {
        var me = this, tag = event.getTarget('[data-t5qid]'), id;

        // If right-click was NOT on tag having data-t5qid attribute
        if (!tag) {

            // If right-click was made outside of some quality-tag - hide previously opened right-click grid, if any
            if (me.segmentRightClickGrid) me.segmentRightClickGrid.hide();

            return;
        }

        // Get quality id
        id = tag.getAttribute('data-t5qid');

        // Prevent native content menu from being shown
        event.preventDefault();

        // In case there is grid, destroy it. Bellow new one will be created.
        // In case the same grid is reused, multiple javascript error can occur.
        // Ex: 3 point in: https://jira.translate5.net/browse/TRANSLATE-3280
        if (me.segmentRightClickGrid) {
            me.segmentRightClickGrid.destroy();
        }

        me.segmentRightClickGrid = Ext.create({
            xtype: 'falsePositives',
            width: 277,
            shadow: false,
            floating: true,
            draggable: true,
            collapsible: true, // collapse/expand tool is hidden by css
            bind: {
                title: '{l10n.falsePositives.legend.float} <span class="x-fa fa-circle-xmark" title="{l10n.falsePositives.close}"></span>'
            },
            hide: function() {
                delete me.segmentRightClickGrid;
                this.destroy();
            },
            toggle: function(ev, d, opts) {
                if (ev.getTarget('.x-fa')) { opts.scope.hide(); }
            }
        });

        // Show grid
        me.segmentRightClickGrid.down('grid').setEmptyText(Editor.data.l10n.falsePositives.grid.emptyText);
        me.segmentRightClickGrid.showBy(tag, 't-b?', [0, 10]);

        // Pick quality by id once qualities store is loaded
        me.loadSegmentRightClickGridRow(id);
    },

    /**
     * Load certain quality-record into grid opened on right-click on quality-tag inside some segment
     */
    loadSegmentRightClickGridRow: function(id) {
        var me = this,
            data = [],
            falPosPanel = this.getMetaFalPosPanel(),
            record = (falPosPanel) ? falPosPanel.down('grid').getStore().getById(id) : null,
            store = me.segmentRightClickGrid?.down('grid')?.getStore()

        // If record is already initialized within the store
        if (record) {

            if(store){
                store.removeAll();
                store.add(record);
            }

        } else {

            // Try again in 200ms
            Ext.defer(() => me.loadSegmentRightClickGridRow(id), 200);
        }
    },

    handleSegmentSelectionChange: function(sm, selectedRecords) {

        // If no selection - return
        if (selectedRecords.length == 0) {
            return;
        }

        var me = this,
            record = selectedRecords[0],
            segmentId = record.get('id');

        // Hide segmentRightClickGrid, if it was previously opened
        if (me.segmentRightClickGrid) me.segmentRightClickGrid.hide();

        me.hasQmQualities = Editor.app.getTaskConfig('autoQA.enableQm');
        me.record = record;
        // our component controllers are listening for the load event & create their views
        me.getQualitiesStore().load({
            params: {segmentId: segmentId}
        });
        me.loadRecord(me.record);
    },

    /**
     * Starts the creation of the segment's quality related GUIs
     */
    handleQualitiesLoaded: function (store, records) {
        var me = this,
            metaFalPosPanel = me.getMetaFalPosPanel();

        // in case there is no meta panel, ignore the logic below
        if(!metaFalPosPanel){
            return;
        }

        metaFalPosPanel.loadFalsifiable(records);

        var segmentId = this.record.get('id');
        this.getMetaQmPanel().startEditing(records, segmentId, this.hasQmQualities);
    },
    /**
     * opens metapanel for readonly segments
     * @param {Editor.model.Segment} record
     */
    openReadonly: function (record) {
        var me = this,
            mp = me.getMetaPanel();
        me.editingMode = 'readonly';
        me.record = record;
        me.getSegmentMeta().hide();
        mp.enable();
    },
    /**
     * lädt die konkreten record ins Meta Panel
     * @param {Ext.data.Model} record
     */
    loadRecord: function (record) {
        // this is only done to be able in the component to detect if a change was done programmatically or user generated
        // the afterwards loading of the recordstriggers the onChange in the radio controls
        this.getSegmentMeta().setSegmentStateId(record.get('stateId'));
        this.getMetaInfoForm().loadRecord(record);
    },
    /**
     * Editor.view.segments.RowEditing edit handler, Speichert die Daten aus dem MetaPanel im record
     */
    saveEdit: function () {
        this.record.set('stateId', this.getMetaInfoForm().getValues().stateId);
        this.getMetaQmPanel().endEditing(this.hasQmQualities, true);
        this.editingMode = 'none';
        this.toggleOnEdit(false);
    },
    /**
     * Editor.view.segments.RowEditing canceledit handler
     * @hint metapanel
     */
    cancelEdit: function () {
        this.getMetaQmPanel().endEditing(this.hasQmQualities, false);
        this.editingMode = 'none';
        this.toggleOnEdit(false);
    },
    /**
     * Changes the state box by keyboard shortcut instead of mouseclick
     * we do no set the stateId before to trigger a change event
     * @param {Ext.Number} param
     */
    onChangeSegmentState: function (stateId) {
        this.getSegmentMeta().showSegmentStateId(stateId);
    },
    /**
     * Listenes for segment state changes thrown from segments metapanel view
     */
    onSegmentStateChanged: function (stateId, oldStateId) {
        var me = this;
        Ext.Ajax.request({
            url: Editor.data.restpath + 'segment/stateid',
            method: 'GET',
            params: {id: me.record.get('id'), stateId: stateId},
            success: function (response) {
                response = Ext.util.JSON.decode(response.responseText);
                if (response.success) {
                    me.record.set('stateId', stateId);
                    // commit silently, oherwise the changed state gets lost on next edit of the segment
                    me.record.commit(true);
                    Editor.MessageBox.addSuccess(me.messages.stateIdSaved);
                } else {
                    console.log("Changing segments stateId via Ajax failed!");
                    var statePanel = me.getSegmentMeta();
                    statePanel.setSegmentStateId(oldStateId);
                    statePanel.showSegmentStateId(oldStateId);
                }
            },
            failure: function (response) {
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    }
});
