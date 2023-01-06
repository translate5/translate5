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
            target,
            cellNode,
            matches;

        let qualityTargets = Editor.data.quality.types;

        for (const field of qualityTargets) {
            let data = rec.get(field.field);

            for (target in data) {
                for (let columnPostfix of field.columnPostfixes) {
                    data[target].forEach(function (item) {
                        item.range.containerNode = document.querySelector(
                            '#' + view.id + '-record-' + rec.internalId
                            + ' [data-columnid="' + target + columnPostfix + '"] .x-grid-cell-inner'
                        );
                    });
                    cellNode = data[target][0].range.containerNode;
                    matches = data[target];
                    this.applyCustomMatches(cellNode, matches, operation === 'cancelled');
                }
            }
        }
    },

    applyCustomMatches: function (cellNode, matches, skipMindDelTags) {
        if (!cellNode) {
            return;
        }

        let me = this,
            rangeForMatch,
            documentFragmentForMatch,
            qualityHighlightNode;

        // apply the matches (iterate in reverse order; otherwise the ranges get lost due to DOM-changes "in front of them")
        me.cleanUpNode(cellNode);
        rangeForMatch = rangy.createRange(cellNode);
        Ext.Array.each(matches, function (match, index) {
            if (!skipMindDelTags) {
                me.mindTags(match);
            }

            rangeForMatch.moveToBookmark(match.range);
            rangeForMatch = me.cleanBordersOfCharacterbasedRange(rangeForMatch);
            documentFragmentForMatch = rangeForMatch.extractContents();
            qualityHighlightNode = me.createQualityHighlightNode(index, matches);
            qualityHighlightNode.appendChild(documentFragmentForMatch);
            rangeForMatch.insertNode(qualityHighlightNode);
        }, me, true);
    },

    mindTags: function (match) {
        let shift,
            html = new Editor.util.HtmlCleanup().cleanHtmlTags(
                match.range.containerNode.innerHTML
                    .replace(/title="[^"]+"/g, (attr) => {
                        return attr.replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    })
                    .replace(/<([0-9]+)\/>/g, '&lt;$1/&gt;'), '<del>'
            ).replace(/&lt;/g, '<').replace(/&gt;/g, '>'),
            tagm,
            tags = [],
            tag, start,
            end,
            debug = false;

        // Create backup for initial offsets
        if (!('backup' in match)) match.backup = {
            start: match.range.start + 0,
            end: match.range.end + 0
        }

        // Debug
        if (debug) {
            console.log('html before', match.range.containerNode.innerHTML);
            console.log('html after', html);
        }

        // Get regexp iterator containing matches
        tagm = html.matchAll(/(?<del><del.*?>)(.+?)<\/del>|(?<white><[0-9]+\/>)|(?<other><\/?[^>]+>)/g);

        // Get array of matches for further use to be more handy
        while (tag = tagm.next()) {
            if (tag.value) {
                tags.push(tag.value);
            } else {
                break;
            }
        }

        // Debug
        if (debug) {
            console.log(tags);
            console.log('match.backup.start', match.backup.start);
        }

        // Shortcuts
        start = match.backup.start + 0;
        end = match.backup.end + 0;

        // Foreach tag
        for (let i = 0; i < tags.length; i++) {

            // Debug
            if (debug) console.log('tag#', i, 'was index', tags[i].index);

            // Reduce current tag match index (offset position) by cutting off html stuff of previous tags to make
            // it possible to rely on that index (offset position) while spell check styles coords calculation
            for (let j = 0; j < i; j++) {

                // If it's one of the del-tags
                if (tags[j].groups.del) {
                    tags[i].index -= tags[j][0].length - tags[j][2].length;
                }
            }

            // Debug
            if (debug) console.log('tag#', i, 'now index', tags[i].index, 'start is', start);

            // If current tag appears before the word having quality-error
            if (tags[i].index <= start) {

                // Debug
                if (debug) console.log('tag#', i, 'both start and end will be shifted');

                // If it's one of the whitespace-tags
                if (tags[i][2] === undefined) {

                    // Shift quality coords to the right, by whitespace-tag's outerHTML length,
                    // which is = 4 in most cases, as whitespace tags look like <1/>, <2/> etc
                    shift = tags[i][0].length;

                    // Else if it's one of the del-tags
                } else {

                    // Shift quality coords to the right, by del-tags content length
                    shift = tags[i][2].length;
                }

                // Debug
                if (debug) console.log('tag#', i, 'start was', start, 'start now', start + shift);

                // Do shift
                start += shift;
                end += shift;

            // Else if current tag appears after the position where the word
            // having quality-error begins, but before the position where the word ends
            } else if (tags[i].index <= end) {

                // Debug
                if (debug) console.log('tag#', i, 'end will be shifted only, end is', end);

                // If it's one of the whitespace-tags
                if (tags[i][2] === undefined) {

                    // Shift quality coords to the right, by whitespace-tag's outerHTML length,
                    // which is = 4 in most cases, as whitespace tags look like <1/>, <2/> etc
                    shift = tags[i][0].length;

                // Else if it's one of the del-tags
                } else {

                    // Shift quality coords to the right, by del-tags content length
                    shift = tags[i][2].length;
                }

                // Debug
                if (debug) console.log('tag#', i, 'end was', end, 'end now', end + shift);

                // Do shift
                end += shift;
            }
        }

        // Debug
        if (debug) console.log(html, 'was', [match.backup.start, match.backup.end], 'shifted by ', start - match.backup.start);

        // Update offsets
        match.range.start = start;
        match.range.end = end;

        // Debug
        if (debug) console.log(html, 'now', [match.range.start, match.range.end]);
    },

    onEditableColumnRender: function (column) {
        let me = this;
        column.renderer = function (value, meta, record, rowIndex, colIndex, store) {
            setTimeout(function () {
                if (me.getSegmentGrid().editingPlugin.context) {
                    return;
                }

                me.applyQualityStylesForRecord(store, record);
            }, 50);
            return value;
        };
    },

    /**
     * Create and return a new node for Quality-Match of the given index.
     * For match-specific data, get the data from the tool.
     *
     * @param {integer} index
     * @param {Object} matches
     *
     * @returns {Object}
     */
    createQualityHighlightNode: function(index, matches){
        let me = this,
            match = matches ? matches[index] : me.allMatches[index],
            nodeElParams = { tag: me.self.NODE_NAME_MATCH };
        // CSS-class(es)
        nodeElParams['cls'] = me.self.CSS_CLASSNAME_MATCH + ' ' + match.cssClassErrorType;
        // activeMatchIndex
        nodeElParams[me.self.ATTRIBUTE_ACTIVEMATCHINDEX] = index;
        nodeElParams[me.self.ATTRIBUTE_QUALITY_ID] = match.id;
        nodeElParams[me.self.ATTRIBUTE_QUALITY_FALSEPOSITIVE] = match.falsePositive ? 'true' : 'false';
        // create and return node
        return Ext.DomHelper.createDom(nodeElParams);
    },
});
