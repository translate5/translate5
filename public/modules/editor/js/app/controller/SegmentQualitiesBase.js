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
 * FIXME Code in this class was extracted from Editor.plugins.SpellCheck.controller.Editor
 * Needs to be refactored with Editor.plugins.SpellCheck.controller.Editor
 */
Ext.define('Editor.controller.SegmentQualitiesBase', {
    extend: 'Ext.app.Controller',

    requires:[
        'Editor.util.dom.Manipulation',
        'Editor.view.quality.FalsePositivesController'
    ],

    mixins: [
        'Editor.util.Range',
        'Editor.util.SegmentEditor',
    ],

    refs: [
        {
            ref: 'segmentGrid',
            selector: '#segmentgrid',
        },
    ],

    listen: {
        component: {
            'segmentsHtmleditor': {
                initialize: 'initEditor',
            },
            'contentEditableColumn': {
                render: 'onEditableColumnRender',
            },
        },
    },

    statics: {
        // quality-Node
        NODE_NAME_MATCH: 'span',
        // CSS-Classes for the quality-Node
        CSS_CLASSNAME_MATCH: 't5quality',
        // CSS-Classes for error-types
        // Attributes for the quality-Node
        ATTRIBUTE_ACTIVEMATCHINDEX: 'data-quality-activeMatchIndex',
        ATTRIBUTE_QUALITY_ID: 'data-t5qid',
        ATTRIBUTE_QUALITY_FALSEPOSITIVE: 'data-t5qfp',
        ATTRIBUTE_QUALITY_FALSEPOSITIVE_TIP: 'data-qtip',
    },

    /**
     * Initialize Editor in general and language-support.
     */
    initEditor: function(editor) {
        this.editor = editor;
    },

    applyQualityStylesForRecord: function (store, rec, operation) {
        let grid = this.getSegmentGrid(),
            view = grid.down('tableview'),
            field,
            cellNode,
            rowSelector = '#' + view.id + '-record-' + rec.internalId,
            colSelector;

        // If we're here due to source-prop is updated - return
        if (rec.get('sourceUpdated')) {
            return;
        }

        // Foreach quality type that can appear inside the segment
        for (const type of Editor.data.quality.types) {

            // Get qualities data
            let data = rec.get(type.field);

            // Foreach field where qualities were detected
            for (field in data) {

                // Get
                for (let columnPostfix of type.columnPostfixes) {

                    // Build grid cell column selector
                    colSelector = '[data-columnid="' + field + columnPostfix + '"] .x-grid-cell-inner';

                    // Get grid cell node
                    cellNode = document.querySelector(rowSelector + ' ' + colSelector);

                    // If we're going to apply termtagger-qualities styles
                    if (type.field === 'termTagger') {

                        // Foreach quality - apply false positive styles
                        for (const quality of data[field]) {
                            Editor.view.quality.FalsePositivesController.applyFalsePositiveStyle(quality.id, quality.falsePositive);
                        }

                    // Else if we're going to apply styles for other quality types (only spellcheck-qualities currently)
                    } else {

                        // Apply matches
                        this.applyCustomMatches(cellNode, data[field]);
                    }
                }
            }
        }
    },

    /* structure of a match:

        {
            "content": " potrebu",
            "matchIndex": 0,
            "range": {
              "start": 165,
              "end": 173
            },
            "message": "Najdena morebitna napaka pri črkovanju.",
            "replacements": [
              "Krnčevega",
              "krnečega"
            ],
            "infoURLs": [],
            "cssClassErrorType": "t5misspelling",
            "id": 296556,
            "falsePositive": 0
          }

        */
    applyCustomMatches: function (cellNode, matches) {
        if (!cellNode || !this.editor) {
            return;
        }

        var match, decorationProps,
            domManipulation = Ext.create('Editor.util.dom.Manipulation'),
            // full definition of internal-tags and with which placeholders they are sent
            // CRUCIAL: more qualified classes must come first !
            // see MittagQI\Translate5\Plugins\SpellCheck\Segment\Check
            ignored = [
                { "tag": "div", "classes": ["newline", "internal-tag"], "placeholder": "\n" },
                { "tag": "div", "classes": ["space", "internal-tag"], "placeholder": " " },
                { "tag": "div", "classes": ["tab", "internal-tag"], "placeholder": "\t" },
                { "tag": "div", "classes": ["nbsp", "internal-tag"], "placeholder": " " },
                { "tag": "div", "classes": ["char", "internal-tag"], "placeholder": "□" },
                { "tag": "div", "classes": ["internal-tag"], "placeholder": '' },
                { "tag": "del", "classes": [], "placeholder": '' },
            ];

        for(var i = 0; i < matches.length; i++){
            match = matches[i];
            decorationProps = this.createQualityHighlightProps(match, i);
            domManipulation
                .selectIndices(cellNode, match.range.start, match.range.end, ignored)
                .decorate(decorationProps.tagName, decorationProps.classes, decorationProps.attributes);
        }
    },

    onEditableColumnRender: function (column) {
        let me = this;
        column.renderer = function (value, meta, record, rowIndex, colIndex, store) {
            setTimeout(function () {
                // in case no segments grid exist, ignore the logic below.
                // this can happen i the user leaves the task, and the timeout callback kicks in
                if(!me.getSegmentGrid()){
                    return;
                }
                if (me.getSegmentGrid().editingPlugin.context) {
                    return;
                }

                me.applyQualityStylesForRecord(store, record);
            }, 50);
            return value;
        };
    },

    /**
     * Create the element-properties for a Quality-Match of the given index.
     * For match-specific data, get the data from the tool.
     *
     * @param {integer} index
     * @param {Object} matches
     *
     * @returns {Object}
     */
    createQualityHighlightProps: function(match, index){
        var props = {
            tagName: this.self.NODE_NAME_MATCH,
            classes: [ this.self.CSS_CLASSNAME_MATCH, match.cssClassErrorType ],
            attributes: {}
        };
        // activeMatchIndex
        props.attributes[this.self.ATTRIBUTE_ACTIVEMATCHINDEX] = index;
        props.attributes[this.self.ATTRIBUTE_QUALITY_ID] = match.id;
        props.attributes[this.self.ATTRIBUTE_QUALITY_FALSEPOSITIVE] = match.falsePositive ? 'true' : 'false';
        if (!match.falsePositive) {
            props.attributes[this.self.ATTRIBUTE_QUALITY_FALSEPOSITIVE_TIP] = Editor.data.l10n.falsePositives.hover;
        }
        // create and return node-props
        return props;
    }
});
