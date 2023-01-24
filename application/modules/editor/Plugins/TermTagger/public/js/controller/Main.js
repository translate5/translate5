
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

/**
 */
Ext.define('Editor.plugins.TermTagger.controller.Main', {
    extend: 'Ext.app.Controller',
    requires: ['Editor.plugins.TermTagger.view.TermPortlet'],

    listen: {
        component: {
            '#metapanel #metaTermPanel': {
                afterrender: 'initMetaTermHandler'
            },
            '#segmentgrid': {
                selectionchange: 'handleSegmentSelectionChange',
                beforeedit: 'startEdit'
            },
            '#metaInfoForm': {
                afterrender: 'metaInfoFormAfterRenderHandler'
            }
        }
    },

    refs: [ {
        ref: 'metaTermPanel',
        selector: '#metapanel #metaTermPanel'
    }, {
        ref: 'segmentMeta',
        selector: '#metapanel segmentsMetapanel'
    }],

    /***
     *
     */
    metaInfoFormAfterRenderHandler: function (form){
        var tp = form.up('#metapanel').down('terminologyPanel');

        if (Editor.data.task.get('terminologie')) {
            tp.add({xtype: 'termPortalTermPortlet'});
            tp.show();
        }
    },

    initMetaTermHandler: function () {
        this.getMetaTermPanel().getEl().on('click', function (e, span) {
            if (!Ext.DomQuery.is(span, 'span.term')) {
                return;
            }
            var range;
            e.stopPropagation();
            e.preventDefault();
            if (document.selection) {
                document.selection.empty();
                range = document.body.createTextRange();
                range.moveToElementText(span);
                range.select();
            } else if (window.getSelection) {
                window.getSelection().removeAllRanges();
                range = document.createRange();
                range.selectNode(span);
                window.getSelection().addRange(range);
            }
        });
    },

    /**
     * @param {Ext.selection.Model} sm current selection model of
     * @param {Array} selectedRecords
     */
    handleSegmentSelectionChange: function (sm, selectedRecords) {
        if (selectedRecords.length == 0) {
            return;
        }
        this.loadTermPanel(selectedRecords[0].get('id'));
    },

    /**
     * @param {Object} editingPlugin
     */
    startEdit: function (editingPlugin, context) {
        var me = this,
            record = context.record,
            segmentId = record.get('id');

        me.loadTermPanel(segmentId);
    },

    /**
     * @param {Integer} segmentId for which the terms should be loaded
     */
    loadTermPanel: function (segmentId) {
        var me = this,
            panel = me.getMetaTermPanel();

        if( !panel || !Editor.data.task.get('terminologie') ){
            return;
        }
        if ( !panel.html) {
            panel.getLoader().load({
                params: {id: segmentId},
                callback: function () {
                    me.getSegmentMeta() && me.getSegmentMeta().updateLayout();
                }
            });
        }
    },

});
