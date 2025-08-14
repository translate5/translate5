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
 * This component implements all features regarding minLength & maxLength & maxNumberLines for segments
 * It either displays the min/max length state and props if min/max limits are set for the segment or a simple character counter in case the counter is active otherwise
 * IMPORTANT: When opening a segment for editing with a line-number restriction, linebreaks are automatically inserted at positions prone for a linebreak
 */
Ext.define('Editor.view.segments.MinMaxLength', {
    extend: 'Ext.Component',
    alias: 'widget.segment.minmaxlength',
    itemId: 'segmentMinMaxLength',
    cls: 'segment-min-max',
    tpl: '<div class="{cls}" data-qtip="{tip}">{text}</div>',
    hidden: true,
    mixins: [
        'Editor.util.Range',
        'Editor.util.HtmlCleanup'
    ],
    requires: [
        'Editor.util.HtmlCleanup'
    ],
    strings: {
        segmentToShort: '#UT#Der Segmentinhalt ist zu kurz! Mindestens {0} Zeichen müssen vorhanden sein.',
        segmentToLong: '#UT#Der Segmentinhalt ist zu lang! Maximal {0} Zeichen sind erlaubt.',
        segmentTooManyLines: '#UT#Der Segmentinhalt enthält zu viele Zeilenumbrüche; maximal {0} Zeilen sind erlaubt.',
        segmentLinesTooLong: '#UT#Nicht alle Zeilen im Segmentinhalt sind unter der maximal erlaubten Länge ({0}).',
        segmentLinesTooShort: '#UT#Nicht alle Zeilen im Segmentinhalt erreichen die minimal erforderte Länge ({0}).',
        min: '#UT#min',
        max: '#UT#max',
        line: '#UT#Zeile',
        lines: '#UT#Zeilen',
        current: '#UT#Aktuell',
        together: '#UT#insgesamt',
        siblingSegments: '#UT#Seg.: {siblings}',
        numberOfChars: '#UT#Anzahl Zeichen'
    },
    lengthstatus: {
        segmentLengthValid: 'segmentLengthValid',
        segmentToShort: 'segmentToShort',
        segmentToLong: 'segmentToLong',
        segmentTooManyLines: 'segmentTooManyLines',
        segmentLinesTooLong: 'segmentLinesTooLong',
        segmentLinesTooShort: 'segmentLinesTooShort',
    },

    segmentRecord: null,
    segmentMeta: null,
    segmentFileId: null,

    /**
     * @var {Editor.view.segments.new.EditorNew}
     */
    editor: null,

    /**
     * flag that distinguishes between character-counter and min/max length mode
     */
    isWordCount: true,

    initComponent: function () {
        // 3 Zeilen * 200px; Aktuell: 4 Zeilen, insgesamt 599px
        // 3 lines * 200px; Current: 4 lines, together 599px

        this.labelTpl = new Ext.XTemplate(
            '<tpl if="target">', '{target}', '; ', '</tpl>',
            '<tpl if="current">', this.strings.current, ': {current}', '</tpl>',
            '<tpl if="siblings">', '; (', this.strings.siblingSegments, ')', '</tpl>'
        );

        return this.callParent(arguments);
    },

    initConfig: function (instanceConfig) {
        const me = this,
            config = {};

        me.editor = instanceConfig.htmlEditor;
        me.editor.on('editorDataChanged', me.onHtmlEditorChange, me);

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }

        return me.callParent([config]);
    },

    /**
     * Handler for html editor text change
     */
    onHtmlEditorChange: function () {
        if (!this.isVisible()) {
            return;
        }

        if (this.segmentRecord === null) {
            return;
        }

        this.correctNumberOfLinesInEditor(this.editor.editor.getRawData());

        if (this.isWordCount) {
            return this.updateNumCharsLabel();
        }

        this.updateMinMaxLabel();
    },

    /**
     * Updates the
     */
    updateNumCharsLabel: function () {
        const editorMarkup = this.editor.editor.getRawData(),
            numChars = this.getLength(editorMarkup, {});

        this.update({
            cls: 'char-count x-grid-item-selected',
            tip: '',
            text: this.strings.numberOfChars + ': <strong>' + numChars + '</strong>'
        });
    },

    /**
     * Update the minmax status strip label
     */
    updateMinMaxLabel: function () {
        const sizeUnit = this.getSizeUnit(this.segmentMeta),
            useLines = this.shouldUseMaxNumberOfLines(this.segmentMeta),
            segmentMinWidth = this.getMinWidthForSegment(this.segmentMeta),
            segmentMaxWidth = this.getMaxWidthForSegment(this.segmentMeta),
            lineMinWidth = this.getMinWidthForSingleLine(this.segmentMeta),
            lineMaxWidth = this.getMaxWidthForSingleLine(this.segmentMeta),
            editorContent = this.editor.editor.getRawData(),
            totalLength = this.getTransunitLength(editorContent),
            lengthStatus = this.getMinMaxLengthStatus(editorContent, this.segmentFileId),
            tplData = {cls: 'invalid-length'};

        // decoration, normally invalid and valid if the status is accordingly
        if (lengthStatus.includes(this.lengthstatus.segmentLengthValid)) {
            tplData.cls = 'valid-length';
        }

        const labelData = {current: (totalLength + sizeUnit), target: '', siblings: null};

        if (useLines) {
            const errors = [],
                allLines = this.getLinesAndLength(editorContent, this.segmentMeta, this.segmentFileId);
            let addTotal = true;

            labelData.target = this.segmentMeta.maxNumberOfLines + ' ' + this.strings.lines + ' * ';

            if (lineMinWidth > 0) {
                labelData.target += this.strings.min + '. ' + lineMinWidth + sizeUnit;
            }

            if (lineMaxWidth !== Number.MAX_SAFE_INTEGER && lineMaxWidth > 0) {
                if (lineMinWidth > 0) {
                    labelData.target += ', ';
                }
                labelData.target += this.strings.max + '. ' + lineMaxWidth + sizeUnit;
            }

            labelData.current = allLines.length + ' ' + this.strings.lines;
            if (lengthStatus.includes(this.lengthstatus.segmentLinesTooLong) || lengthStatus.includes(this.lengthstatus.segmentLinesTooShort)) {
                for (let i = 0; i < allLines.length; i++) {
                    if (allLines[i].lineWidth > lineMaxWidth || allLines[i].lineWidth < lineMinWidth) {
                        errors.push(this.strings.line + ' ' + (i + 1) + ': ' + allLines[i].lineWidth + sizeUnit);
                    }
                }
                if (errors.length > 0) {
                    labelData.current += ', ' + errors.join(', ');
                    addTotal = false;
                }
            }

            if (addTotal) {
                labelData.current += ', ' + this.strings.together + ' ' + totalLength + sizeUnit;
            }
        } else {
            if (segmentMinWidth > 0) {
                labelData.target += this.strings.min + ': ' + segmentMinWidth + sizeUnit;
            }

            if (segmentMaxWidth !== Number.MAX_SAFE_INTEGER && segmentMaxWidth > 0) { // = not set; lines can be "endless" (= which is MAX_SAFE_INTEGER)
                if (labelData.target !== '') {
                    labelData.target += ', ';
                }
                labelData.target += this.strings.max + ': ' + segmentMaxWidth + sizeUnit;
            }
        }

        // tooltip
        tplData.tip = this.renderErrorMessage(lengthStatus);

        // siblings
        if (this.segmentMeta && this.segmentMeta.siblingData) {
            const nrs = Object.values(this.segmentMeta.siblingData).map(function (item) {
                return item.nr;
            });

            //show segments only if there are more than one (inclusive the current one)
            if (nrs.length > 1) {
                labelData.siblings = nrs.join(', ');
            }
        }

        // just a beautification
        if (labelData.target !== '') {
            labelData.target = labelData.target.charAt(0).toUpperCase() + labelData.target.slice(1);
        }

        tplData.text = this.labelTpl.apply(labelData);

        this.update(tplData);
    },

    /**
     * Render the error-message according to the segment's length status.
     * @param {Array} segmentLengthStatus
     * @param {Object} meta
     * @returns {String}
     */
    renderErrorMessage: function (segmentLengthStatus) {
        const me = this,
            messages = me.strings;

        let minWidthForSegment,
            maxWidthForSegment,
            maxWidthForLine,
            minWidthForLine,
            errorMsg = [];

        for (let i = 0; i < segmentLengthStatus.length; i++) {
            switch (segmentLengthStatus[i]) {
                case me.lengthstatus.segmentToShort:
                    minWidthForSegment = this.getMinWidthForSegment(this.segmentMeta);
                    errorMsg.push(Ext.String.format(messages.segmentToShort, minWidthForSegment));
                    break;
                case me.lengthstatus.segmentToLong:
                    maxWidthForSegment = this.getMaxWidthForSegment(this.segmentMeta);
                    errorMsg.push(Ext.String.format(messages.segmentToLong, maxWidthForSegment));
                    break;
                case me.lengthstatus.segmentTooManyLines:
                    errorMsg.push(Ext.String.format(messages.segmentTooManyLines, this.segmentMeta.maxNumberOfLines));
                    break;
                case me.lengthstatus.segmentLinesTooLong:
                    maxWidthForLine = this.getMaxWidthForSingleLine(this.segmentMeta);
                    errorMsg.push(Ext.String.format(messages.segmentLinesTooLong, maxWidthForLine));
                    break;
                case me.lengthstatus.segmentLinesTooShort:
                    minWidthForLine = this.getMinWidthForSingleLine(this.segmentMeta);
                    errorMsg.push(Ext.String.format(messages.segmentLinesTooShort, minWidthForLine));
                    break;
                default: // = segmentLengthValid
                    errorMsg = [];
            }
        }

        return errorMsg.join('<br>');
    },

    //region Auto-update edited content

    /**
     * If max. number of lines are to be considered, we add line-breaks automatically.
     */
    correctNumberOfLinesInEditor: function (rawData) {
        const disabled = !Editor.app.getTaskConfig('lengthRestriction.automaticNewLineAdding');

        if (disabled) {
            // Don't add line-breaks if the feature is disabled.
            return;
        }

        if (!this.shouldUseMaxNumberOfLines(this.segmentMeta)) {
            return;
        }

        const linesWithLength = this.getLinesAndLength(rawData, this.segmentMeta, this.segmentFileId);

        if (linesWithLength.length >= this.segmentMeta.maxNumberOfLines || linesWithLength.length === 0) {
            // Don't add further line-breaks when maxNumberOfLines has been reached already.
            return;
        }

        const maxWidthPerLine = this.getMaxWidthForSingleLine(this.segmentMeta);
        let length = 0;

        for (let i = 0; i < linesWithLength.length; i++) {
            const line = linesWithLength[i];

            if (line.lineWidth <= maxWidthPerLine) {
                length += line.lineFullWidth + 1;
                continue;
            }

            const dom = RichTextEditor.stringToDom(line.textInLine);
            const insertionPoint = this.calculateInsertionPoint(dom, maxWidthPerLine);

            if (!insertionPoint) {
                length += line.lineFullWidth + 1;
                continue;
            }

            const replaceWhitespace = !!Editor.app.getTaskConfig('lengthRestriction.newLineReplaceWhitespace');

            Editor.app.getController('Editor').insertWhitespaceNewline(
                {},
                {},
                length + insertionPoint.offset,
                replaceWhitespace
            );

            return;
        }
    },

    calculateInsertionPoint: function(node, maxLength) {
        let count = 0; // Count in units (characters or pixels)
        let positionForBreak = 0;
        let lastValidBreak = null; // Last valid position for a break
        const _this = this;

        const traverseNodes = function (node) {
            // Iterate over all child nodes
            for (const child of node.childNodes) {
                if (child.nodeType === Node.TEXT_NODE) {
                    const text = child.textContent;
                    const words = text.match(/\S+|\s/g) || []; // Split by whitespace

                    for (const part of words) {
                        const length = _this.getLength(
                            RichTextEditor.stringToDom(
                                /\s+/.test(part) ? part.replace(' ', '&nbsp;') : part
                            ),
                            _this.segmentMeta
                        );
                        positionForBreak += part.length;

                        // If part is whitespace, consider it a potential break point
                        if (count + length > maxLength && lastValidBreak !== null) {
                            return lastValidBreak;
                        }

                        if (/\s+/.test(part)) {
                            lastValidBreak = { node: child, offset: positionForBreak };
                        }

                        count += length;
                        if (count > maxLength) {
                            return lastValidBreak; // Return the last valid break point
                        }
                    }
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    const result = traverseNodes(child);

                    if (result) {
                        return result;
                    }
                }
            }

            return null; // No valid break point found within this node
        };

        return traverseNodes(node);
    },
    //endregion Auto-update edited content

    /**
     * Returns the lines (= objects with their text and length) for the given content and meta.
     * @param {String} htmlToCheck
     * @param {Object} meta
     * @param {integer} fileId
     * @returns {Array<{textInLine: String, lineWidth: integer}>}
     */
    getLinesAndLength: function (htmlToCheck, meta, fileId) {
        const lines = [];
        const dom = RichTextEditor.stringToDom(htmlToCheck);

        if (dom.querySelectorAll('.newline').length === 0) {
            // = one single line only
            lines.push({
                textInLine: dom.innerHTML,
                lineWidth: this.getLength(dom, meta),
                lineFullWidth: this.getLength(dom, {}, true),
                startPosition: 0,
            });

            return lines;
        }

        const linebreakNodes = dom.querySelectorAll('.newline');
        let previousLineBreak;
        let lineBreak;
        for (let i = 0; i <= linebreakNodes.length; i++) {
            lineBreak = linebreakNodes[i];

            if (lineBreak && this.editor.editor.getTagsConversion().isTrackChangesDelNode(lineBreak.parentNode)) {
                continue;
            }

            const range = document.createRange();
            switch (true) {
                case (i === 0):
                    // = first line
                    range.setStart(dom, 0);
                    range.setEndBefore(lineBreak);
                    break;

                case (i === linebreakNodes.length):
                    // = last line
                    range.setStartAfter(previousLineBreak);
                    range.setEnd(dom, dom.childNodes.length);
                    break;

                default:
                    range.setStartAfter(previousLineBreak);
                    range.setEndBefore(lineBreak);
            }

            previousLineBreak = lineBreak;

            const div = document.createElement('div');
            div.appendChild(range.extractContents().cloneNode(true));
            const contentInLine = div.innerHTML;
            lines.push({
                textInLine: contentInLine,
                lineWidth: this.getLength(div, meta),
                lineFullWidth: this.getLength(div, {}, true),
                linebreak: linebreakNodes[i],
            });
        }

        return lines;
    },

    /**
     * Is the min/max width active according to the meta-data?
     * @returns boolean
     */
    shouldUseMinMaxWidth: function (meta) {
        return meta && (meta.minWidth !== null || meta.maxWidth !== null);
    },

    /**
     * Is the maximum number of lines to be considered according to the meta-data?
     * @returns boolean
     */
    shouldUseMaxNumberOfLines: function (meta) {
        return !!(meta && meta.maxNumberOfLines); // meta.maxNumberOfLines = null if not set
    },

    /**
     * Returns the minWidth according to the meta-data.
     * @returns integer
     */
    getMinWidthForSegment: function (meta) {
        // don't add messageSizeUnit here, will be used for calculating...
        return meta && meta.minWidth ? meta.minWidth : 0;
    },

    /**
     * Returns the total maxWidth according to the meta-data
     * (and according to the number of lines, if set).
     * @returns integer
     */
    getMaxWidthForSegment: function (meta) {
        // don't add messageSizeUnit here, will be used for calculating...
        const useMaxNumberOfLines = this.shouldUseMaxNumberOfLines(meta);

        let maxWidth = this.getMaxWidthForSingleLine(meta);

        if (useMaxNumberOfLines) {
            maxWidth = meta.maxNumberOfLines * meta.maxWidth;
        }

        return maxWidth;
    },

    /**
     * Returns the maxWidth for a single line according to the meta-data.
     * @returns integer|false
     */
    getMaxWidthForSingleLine: function (meta) {
        if (!this.shouldUseMaxNumberOfLines(meta)) {
            return Number.MAX_SAFE_INTEGER;
        }

        // don't add messageSizeUnit here, will be used for calculating...
        return meta && meta.maxWidth ? meta.maxWidth : Number.MAX_SAFE_INTEGER;
    },

    /**
     * Returns the minWidth for a single line according to the meta-data.
     * @returns integer|false
     */
    getMinWidthForSingleLine: function (meta) {
        if (!this.shouldUseMaxNumberOfLines(meta)) {
            return false;
        }

        // don't add messageSizeUnit here, will be used for calculating...
        return meta && meta.minWidth ? meta.minWidth : 0;
    },

    /**
     * Returns the size-unit according to the meta-data.
     * @returns String
     */
    getSizeUnit: function (meta) {
        return (meta.sizeUnit === RichTextEditor.PixelMapping.SIZE_UNIT_FOR_PIXELMAPPING) ? 'px' : '';
    },

    /**
     * Checks if MinMax is enabled,
     * sets the segmnet-data accordingly
     * and returns true or false if the minmax status strip should be visible.
     */
    updateSegment: function (record, fieldname) {
        const me = this,
            fields = Editor.data.task.segmentFields(),
            field = fields.getAt(fields.findExact('name', fieldname.replace(/Edit$/, ''))),
            minMaxEnabled = field && field.isTarget() && this.shouldUseMinMaxWidth(record.get('metaCache')),
            counterCheckBox = minMaxEnabled ? [] : Ext.ComponentQuery.query('#segmentgrid segmentsToolbar #showHideCharCounter');

        this.isWordCount = (counterCheckBox.length > 0) && counterCheckBox[0].checked;
        this.setVisible(minMaxEnabled || this.isWordCount);
        this.resetSegmentData();

        if (minMaxEnabled || this.isWordCount) {
            this.setSegmentData(record);
            this.onHtmlEditorChange();
        }

        return (minMaxEnabled || this.isWordCount);
    },

    resetSegmentData: function () {
        this.segmentRecord = null;
        this.segmentMeta = null;
        this.segmentFileId = null;
    },

    setSegmentData: function (record) {
        this.segmentRecord = record;
        this.segmentMeta = record.get('metaCache');
        this.segmentFileId = record.get('fileId');
    },

    /**
     * @param {String} htmlToCheck
     * @param {Object} meta
     * @param {integer} fileId
     * @returns {Array} segmentLengthValid|segmentToShort|segmentToLong|segmentTooManyLines|segmentLinesTooLong|segmentLinesTooShort
     */
    getMinMaxLengthStatus: function (htmlToCheck) {
        if (!this.shouldUseMinMaxWidth(this.segmentMeta)) {
            return [this.lengthstatus.segmentLengthValid];
        }

        const useMaxNumberOfLines = this.shouldUseMaxNumberOfLines(this.segmentMeta);

        // maxWidth and/or minWidth are set EITHER for the trans-unit OR for the lines
        if (useMaxNumberOfLines) {
            return this.getMinMaxLengthStatusForLines(htmlToCheck, this.segmentMeta, this.segmentFileId);
        } else {
            return this.getMinMaxLengthStatusForSegment(htmlToCheck, this.segmentMeta);
        }
    },

    /**
     * @param {String} htmlToCheck
     * @param {Object} meta
     * @returns {Array} segmentToShort|segmentToLong|segmentLengthValid
     */
    getMinMaxLengthStatusForSegment: function (htmlToCheck, meta) {
        const minWidth = this.getMinWidthForSegment(meta),
            maxWidth = this.getMaxWidthForSegment(meta);

        // The segment might be part of a trans-unit that has been
        // split to multiple segments. This will be considered here:
        const transunitLength = this.getTransunitLength(htmlToCheck);

        if (transunitLength < minWidth) {
            return [this.lengthstatus.segmentToShort];
        }

        if (transunitLength > maxWidth) {
            return [this.lengthstatus.segmentToLong];
        }

        return [this.lengthstatus.segmentLengthValid];
    },

    /**
     * @param {String} htmlToCheck
     * @param {Object} meta
     * @param fileId
     * @returns {Array} segmentTooManyLines|segmentLinesTooLong|segmentLinesTooShort|segmentLengthValid
     */
    getMinMaxLengthStatusForLines: function (htmlToCheck, meta, fileId) {
        const me = this,
            lengthstatus = [],
            maxWidth = this.getMaxWidthForSingleLine(meta),
            minWidth = this.getMinWidthForSingleLine(meta);
        let line;

        const allLines = me.getLinesAndLength(htmlToCheck, meta, fileId);

        if (allLines.length > meta.maxNumberOfLines) {
            lengthstatus.push(me.lengthstatus.segmentTooManyLines);
        }

        for (let i = 0; i < allLines.length; i++) {
            if (lengthstatus.includes(me.lengthstatus.segmentLinesTooLong) && lengthstatus.includes(me.lengthstatus.segmentLinesTooShort)) {
                break;
            }

            line = allLines[i];

            if (maxWidth && line.lineWidth > maxWidth && !lengthstatus.includes(me.lengthstatus.segmentLinesTooLong)) {
                lengthstatus.push(me.lengthstatus.segmentLinesTooLong);
                continue;
            }

            if (minWidth && line.lineWidth < minWidth && !lengthstatus.includes(me.lengthstatus.segmentLinesTooShort)) {
                lengthstatus.push(me.lengthstatus.segmentLinesTooShort);
            }
        }

        return (lengthstatus.length === 0) ? [me.lengthstatus.segmentLengthValid] : lengthstatus;
    },

    //region Length measurement
    /**
     * Get the character count of the segment text + sibling segment lengths, without the tags in it (whitespace tags evaluated to their length)
     * @param {String} text optional, if omitted use currently stored value
     * @return {integer} returns the transunit length
     */
    getTransunitLength: function (text) {
        const meta = this.segmentMeta,
            field = this.dataIndex;

        if (!Ext.isString(text)) {
            text = "";
        }

        //add the length of the text itself
        let textLength = this.getLength(RichTextEditor.stringToDom(this.cleanDeleteTags(text)), meta);

        //only the segment length + the tag lengths:
        this.lastSegmentLength = textLength;

        let additionalLength = 0;

        //add the length of the sibling segments (min max length is given for the whole transunit, not each mrk tag
        if (meta && meta.siblingData) {
            for (const [id, data] of Object.entries(meta.siblingData)) {
                if (this.segmentRecord.get('id') === parseInt(id)) {
                    continue;
                }

                if (data.length && data.length[field]) {
                    additionalLength += data.length[field];
                }
            }
        }

        //add additional string length of transunit to the calculation
        if (meta && meta.additionalUnitLength) {
            additionalLength += meta.additionalUnitLength;
        }

        //add additional string length of mrk (after mrk) to the calculation
        if (meta && meta.additionalMrkLength) {
            additionalLength += meta.additionalMrkLength;
        }

        //return 30; // for testing
        return additionalLength + textLength;
    },

    /**
     * Return the text's length either based on pixelMapping or as the number of code units in the text.
     * @param {HTMLElement} dom
     * @param {Object} meta
     * @param {boolean} keepDeletions
     * @return {integer}
     */
    getLength: function (dom, meta, keepDeletions = false) {
        const isPixel = (meta && meta.sizeUnit === RichTextEditor.PixelMapping.SIZE_UNIT_FOR_PIXEL_MAPPING);
        const pixelMapping = new RichTextEditor.PixelMapping(this.editor.editor.font);
        const tagsConversion = this.editor.editor.getTagsConversion();

        let length = 0;

        const traverseNodes = function (node) {
            // Iterate over all child nodes
            for (const child of node.childNodes) {
                if (child.nodeType === Node.TEXT_NODE) {
                    let text = child.textContent;
                    //replace &nbsp; with space
                    text = text.replace('&nbsp;', ' ');
                    //remove characters with 0 lengths:
                    text = text.replace(/[\u200B\uFEFF]/g, '');

                    let result;

                    if (isPixel) {
                        // ----------- pixel-based -------------
                        result = pixelMapping.getPixelLength(text);
                    } else {
                        // ----------- char-based -------------
                        result = text.length;
                    }

                    length += result;
                }

                if (
                    child.nodeType === Node.ELEMENT_NODE &&
                    tagsConversion.isTrackChangesDelNode(child) &&
                    !keepDeletions
                ) {
                    continue;
                }

                if (child.nodeType === Node.ELEMENT_NODE && tagsConversion.isInternalTagNode(child)) {
                    //for performance reasons the pixellength is precalculated on converting the div span to img tags
                    const attr = (isPixel ? 'data-pixellength' : 'data-length'),
                        result = parseInt(child.getAttribute(attr) || "0");

                    //data-length is -1 if no length provided
                    if (result > 0) {
                        length += result;
                    }
                }

                if (child.nodeType === Node.ELEMENT_NODE) {
                    const result = traverseNodes(child);

                    if (result) {
                        length += result;
                    }
                }
            }
        };

        traverseNodes(dom);

        return length;
    },
    //endregion Length measurement
});