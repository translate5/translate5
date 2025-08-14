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

Ext.define('Editor.plugins.TermTagger.controller.Main', {
    extend: 'Ext.app.Controller',
    requires: ['Editor.plugins.TermTagger.view.TermPortlet'],

    listen: {
        component: {
            '#metapanel termPortalTermPortlet': {
                afterrender: 'initMetaTermHandler'
            },
            '#segmentgrid': {
                selectionchange: 'handleSegmentSelectionChange',
                beforeedit: 'startEdit',
            },
            '#metaInfoForm': {
                afterrender: 'metaInfoFormAfterRenderHandler',
            },
            '#t5Editor': {
                afterInstantiateEditor: 'onEditorInstantiate',
            },
        },
        controller: {
            '#Segments': {
                beforeSaveCall: 'onSegmentSaved'
            },
        },
    },

    refs: [
        {
            ref: 'metaTermPanel',
            selector: '#metapanel termPortalTermPortlet'
        },
        {
            ref: 'segmentMeta',
            selector: '#metapanel segmentsMetapanel'
        },
        {
            ref: 'segmentGrid',
            selector: '#segmentgrid'
        }
    ],

    onEditorInstantiate: function (editor) {
        editor.editor.registerModifier(
            RichTextEditor.EditorWrapper.EDITOR_EVENTS.DATA_CHANGED,
            (rawData, actions) => this._cleanTermOnTypingInside(rawData, actions, editor.editor.getTagsConversion()),
            1
        );
    },

    /***
     *
     */
    metaInfoFormAfterRenderHandler: function (form) {
        const tp = form.up('#metapanel').down('terminologyPanel');

        if (Editor.data.task.get('terminologie')) {
            tp.add({xtype: 'termPortalTermPortlet'});
        } else {
            if (!tp.collapsed) {
                tp.collapse();
            }
            tp.hide();
        }
    },

    initMetaTermHandler: function () {
        this.getMetaTermPanel().getEl().on('click', function (e, span) {
            if (!Ext.DomQuery.is(span, 'span.term')) {
                return;
            }
            let range;
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
        if (selectedRecords.length === 0) {
            return;
        }
        this.loadTermPanel(selectedRecords[0].get('id'));
    },

    /**
     * @param {Object} editingPlugin
     * @param {Object} context
     */
    startEdit: function (editingPlugin, context) {
        var me = this,
            record = context.record,
            segmentId = record.get('id');

        me.loadTermPanel(segmentId);
    },

    /**
     * @param {integer} segmentId for which the terms should be loaded
     */
    loadTermPanel: function (segmentId) {
        const me = this,
            panel = me.getMetaTermPanel();

        if (!panel || !Editor.data.task.get('terminologie')) {
            return;
        }

        if (!panel.html) {
            panel.getLoader().load({
                params: {id: segmentId},
                callback: function () {
                    me.getSegmentMeta() && me.getSegmentMeta().updateLayout();
                }
            });
        }
    },

    /**
     * Reload term portlet due to detected terms might change on segment change
     *
     * @param segment
     */
    onSegmentSaved: function(segment) {
        var segmentGrid = this.getSegmentGrid();

        // Since this is called in segment save context, and it can happen that the editor view port is already destroyed
        // we need to check if the segment grid is still available
        if(!segmentGrid){
            return;
        }

        var  selectedSegmentId = segmentGrid.getSelection().pop()?.getId();

        // If selection was changed after save-request started - do nothing
        if (selectedSegmentId && selectedSegmentId !== segment.getId()) {
            return;
        }

        // Reload termportlet
        this.loadTermPanel(segment.getId());
    },

    _cleanTermOnTypingInside(rawData, actions, tagsConversion) {
        if (!actions.length) {
            return [rawData, 0];
        }

        const doc = RichTextEditor.stringToDom(rawData);

        for (const action of actions) {
            if (!action.type) {
                continue;
            }

            this._processNodes(doc, action, tagsConversion);
        }

        return [doc.innerHTML, actions[0].position];
    },

    _processNodes: function (doc, action, tagsConversion) {
        const _this = this;
        const position = action.position;
        let pointer = 0;

        function traverseNodes(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                const isWithinNode = pointer + node.length >= position;
                const isInserting = action.type === RichTextEditor.EditorWrapper.ACTION_TYPE.INSERT;
                const isDeletingSpellCheck = (
                    action.type === RichTextEditor.EditorWrapper.ACTION_TYPE.REMOVE
                    && action.content.length
                    && tagsConversion.isTermNode(action.content[0].toDom())
                );

                if (
                    isWithinNode
                    && tagsConversion.isTermNode(node.parentNode)
                    && (isInserting || isDeletingSpellCheck)
                ) {
                    _this._unwrapTermNode(node.parentNode);

                    return true;
                }

                if (
                    isWithinNode
                    && node.nextSibling
                    && tagsConversion.isTermNode(node.nextSibling)
                    && (
                        action.type === RichTextEditor.EditorWrapper.ACTION_TYPE.REMOVE
                        && action.content.length > 1
                        && tagsConversion.isTermNode(action.content[action.content.length - 1].toDom())
                    )
                ) {
                    _this._unwrapTermNode(node.nextSibling);

                    return true;
                }

                pointer += node.length;

                return false;
            }

            if (tagsConversion.isTag(node)) {
                pointer++;

                return false;
            }

            if (node.nodeType === Node.ELEMENT_NODE) {
                for (let child of node.childNodes) {
                    const changed = traverseNodes(child);

                    // This is done to prevent endless recursion
                    if (changed) {
                        return true;
                    }
                }
            }

            return false;
        }

        traverseNodes(doc);
    },

    _unwrapTermNode: function (node) {
        const insertFragment = document.createRange().createContextualFragment(node.innerHTML);
        node.parentNode.insertBefore(insertFragment, node);
        node.parentNode.removeChild(node);
    }
});
