
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/
Ext.define('Editor.view.segments.MinMaxLength', {
    extend: 'Ext.Component',
    alias: 'widget.segment.minmaxlength',
    itemId:'segmentMinMaxLength',
    cls: 'segment-min-max',
    tpl: '<div class="{cls}" data-qtip="{tip}">{text}</div>',
    hidden:true,
    mixins: ['Editor.util.Range'],
    strings: {
        minText:'#UT#Min. {minWidth}',
        maxText:'#UT# von {maxWidth}',
        siblingSegments: '#UT#Seg.: {siblings}',
        segmentToShort:'#UT#Der Segmentinhalt ist zu kurz! Mindestens {0} Zeichen müssen vorhanden sein.',
        segmentToLong:'#UT#Der Segmentinhalt ist zu lang! Maximal {0} Zeichen sind erlaubt.',
        segmentTooManyLines: '#UT#Der Segmentinhalt enthält zu viele Zeilenumbrüche; maximal {0} Zeilen sind erlaubt.',
        segmentLinesTooLong: '#UT#Nicht alle Zeilen im Segmentinhalt sind unter der maximal erlaubten Länge ({0}).',
        segmentLinesTooShort: '#UT#Nicht alle Zeilen im Segmentinhalt erreichen die minimal erforderte Länge ({0}).',
        lines: '#UT#Zeilen',
        current: '#UT#Aktuell',
        together: '#UT#insgesamt',
    },
    lengthstatus: {
        segmentLengthValid: 'segmentLengthValid',
        segmentToShort: 'segmentToShort',
        segmentToLong: 'segmentToLong',
        segmentTooManyLines: 'segmentTooManyLines',
        segmentLinesTooLong: 'segmentLinesTooLong',
        segmentLinesTooShort: 'segmentLinesTooShort',
    },
    statics: {
        /**
         * Is the min/max width active according to the meta-data?
         * @returns bool
         */
        useMinMaxWidth: function(meta) {
            return meta && (meta.minWidth !== null || meta.maxWidth !== null);
        },
        /**
         * Is the maximum number of lines to be considered according to the meta-data?
         * @returns bool
         */
        useMaxNumberOfLines: function(meta) {
            var isPixel = meta && (meta.sizeUnit === Editor.view.segments.PixelMapping.SIZE_UNIT_FOR_PIXELMAPPING);
            return isPixel && !!meta.maxNumberOfLines; // meta.maxNumberOfLines = null if not set
        },
        /**
         * Returns the minWidth according to the meta-data.
         * @returns integer
         */
        getMinWidthForSegment: function(meta) {
            // don't add messageSizeUnit here, will be used for calculating...
            return meta && meta.minWidth ? meta.minWidth : 0;
        },
        /**
         * Returns the total maxWidth according to the meta-data
         * (and according to the number of lines, if set).
         * @returns integer
         */
        getMaxWidthForSegment: function(meta) {
            // don't add messageSizeUnit here, will be used for calculating...
            var me = this,
                maxWidth = me.getMaxWidthForSingleLine(meta),
                useMaxNumberOfLines = me.useMaxNumberOfLines(meta);
            if (useMaxNumberOfLines) {
                maxWidth = meta.maxNumberOfLines * meta.maxWidth;
            }
            return maxWidth;
        },
        /**
         * Returns the maxWidth for a single line according to the meta-data.
         * @returns integer|false
         */
        getMaxWidthForSingleLine: function(meta) {
            if (!this.useMaxNumberOfLines) {
                return false;
            }
            // don't add messageSizeUnit here, will be used for calculating...
            return meta && meta.maxWidth ? meta.maxWidth : Number.MAX_SAFE_INTEGER;
        },
        /**
         * Returns the minWidth for a single line according to the meta-data.
         * @returns integer|false
         */
        getMinWidthForSingleLine: function(meta) {
            if (!this.useMaxNumberOfLines) {
                return false;
            }
            // don't add messageSizeUnit here, will be used for calculating...
            return meta && meta.minWidth ? meta.minWidth : 0;
        },
        /**
         * Returns the size-unit according to the meta-data.
         * @returns integer
         */
        getSizeUnit: function(meta) {
            return (meta.sizeUnit === Editor.view.segments.PixelMapping.SIZE_UNIT_FOR_PIXELMAPPING) ? 'px' : '';
        }
    },
    /***
     * Segment model record
     */
    segmentRecord:null,

    /**
     * @var {Object}
     */
    segmentMeta: null,

    /**
     * @var {Integer}
     */
    segmentFileId: null,
    
    /**
     * {Editor.view.segments.HtmlEditor}
     */
    editor: null,
    
    /**
     * @var {Object} bookmarkForCaret
     */
    bookmarkForCaret: null,
    
    /**
     * 
     */
    initComponent : function() {
        var str = this.strings;
        //If there is only max length: 10 of 12
        //If there is only min length: 12 (Min. 10)
        //If both are given: 10 of 12 (Min. 10)
        //same with sibling segments: 
        //If there is only max length: 10 of 12 (Seg.: 23, 24, 25)
        //If there is only min length: 12 (Min. 10; Seg.: 23, 24, 25)
        //If both are given: 10 of 12 (Min. 10; Seg.: 23, 24, 25)
        
        this.labelTpl = new Ext.XTemplate(
            '{length}',
            '<tpl if="maxWidth">',
                str.maxText,
            '</tpl>',
            '<tpl if="minWidth || siblings">',
                ' (',
                '<tpl if="minWidth">',
                    str.minText,
                '</tpl>',
                '<tpl if="minWidth && siblings">',
                    '; ',
                '</tpl>',
                '<tpl if="siblings">',
                    str.siblingSegments,
                '</tpl>',
                ')',
            '</tpl>'
        );
        return this.callParent(arguments);
    },
    
    initConfig : function(instanceConfig) {
        var me=this,
            config = {};
        me.editor=instanceConfig.htmlEditor;
        
        Editor.app.getController('Editor').on({
            afterInsertWhitespace:{
                fn:me.handleAfterInsertWhitespace,
                scope:me
            },
            afterDragEnd: {
                fn:me.onHtmlEditorDragEnd,
                scope:me
            }
        });

        me.editor.on({
            change:{
                fn:me.onHtmlEditorChange,
                scope:me
            },
            initialize:{
                fn:me.onHtmlEditorInitialize,
                scope:me
            },
            afterInsertMarkup:{
                fn:me.onHtmlEditorChange,
                scope:me
            }
        });
        
        Ext.ComponentQuery.query('#segmentgrid')[0].on({
            beforeedit: {
                fn:me.resetSegmentData,
                scope:me
            }
        });
        
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Handler for html editor initializer, the function is called after the iframe is initialized
     * FIXME testme
     */
    onHtmlEditorInitialize:function(){
        var me=this;
        if(me.isVisible()){
            me.updateLabelForEditor(me.editor.getTransunitLength(me.segmentRecord.get('targetEdit')));
        }

        if(!Editor.controller.SearchReplace){
            return;
        }

        var searchReplace=Editor.app.getController('SearchReplace');

        //listen to the editorTextReplaced evend from search and replace
        //so the character count is triggered when text is replaced with search and replace
        searchReplace.on({
            editorTextReplaced:function(newInnerHtml){
                me.onHtmlEditorChange(null,newInnerHtml);
            }
        });
    },

    /**
     * Set data according to the segment record.
     */
    setSegmentData: function(record){
        var me = this;
        me.segmentRecord = record;
        me.segmentMeta = record.get('metaCache');
        me.segmentFileId = record.get('fileId');
    },    

    /**
     * Reset data for segment record.
     */
    resetSegmentData: function(){
        var me = this;
        me.segmentRecord = null;
        me.segmentMeta = null;
        me.segmentFileId = null;
    },

    /**
     * Checks if MinMax is enabled,
     * sets the segmnet-data accordingly
     * and returns true or false if the minmax status strip should be visible.
     */
    updateSegment: function(record, fieldname){
        var me=this,
            fields = Editor.data.task.segmentFields(),
            field = fields.getAt(fields.findExact('name', fieldname.replace(/Edit$/, ''))),
            enabled = field && field.isTarget() && Editor.view.segments.MinMaxLength.useMinMaxWidth(record.get('metaCache'));
        
        me.setVisible(enabled);
        me.resetSegmentData();

        if(enabled){
            me.setSegmentData(record);
            me.onHtmlEditorChange(me.editor, me.segmentRecord.get('target'), '');
            me.updateLabelForEditor(me.editor.getTransunitLength(me.segmentRecord.get('target')));
        }
        return enabled;
    },

    /**
     * Handler for html editor text change
     */
    onHtmlEditorChange:function(){
        var me = this;
        if(!me.isVisible()){
            return;
        }
        if(this.segmentRecord === null){
            return;
        }
        me.handleMaxNumberOfLinesInEditor();
        me.updateLabelForEditor();
    },

    /**
     * Handler for html editor drag and drop change
     */
    onHtmlEditorDragEnd:function(){
        var me = this;
        if(me.isVisible()){
            // here without handleMaxNumberOfLinesInEditor: if the user has moved the linebreak intentionally, we don't touch that
            me.updateLabelForEditor();
        }
    },
    
    /**
     * If max. number of lines are to be considered, we add line-breaks automatically. 
     */
    handleMaxNumberOfLinesInEditor: function () {
        var me = this,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            useMaxNumberOfLines = minMaxLengthComp.useMaxNumberOfLines(me.segmentMeta),
            editorBody,
            maxWidthPerLine,
            i,
            allLines,
            line;
        
        if (!useMaxNumberOfLines) {
            return;
        }
        
        editorBody = me.editor.getEditorBody();
        allLines = me.getLinesAndLength(editorBody.innerHTML, me.segmentMeta, me.segmentFileId);
        
        if (allLines.length >= (me.segmentMeta.maxNumberOfLines)) {
            // Don't add further line-breaks when maxNumberOfLines has been reached already.
            return;
        }
        
        maxWidthPerLine = minMaxLengthComp.getMaxWidthForSingleLine(me.segmentMeta);
        
        for (i = 0; i < allLines.length; i++) {
            line = allLines[i];
            me.handleMaxWidthForLineInEditor(line.textInLine, line.lineWidth, maxWidthPerLine);
        }
    },
    
    /**
     * If the text is longer than allowed: Add a line-break before (if possible).
     * @param {String} textInLine
     * @param {Integer} lineWidth
     * @param {Integer} maxWidthPerLine
     * 
     */
    handleMaxWidthForLineInEditor: function (htmlInLine, lineWidth, maxWidthPerLine) {
        var me = this,
            allLines,
            div,
            textInLine,
            editorBody,
            range,
            wordsInLine,
            i,
            textToCheck = '',
            textToCheckWidth,
            textForLine = '',
            options,
            sel;
        
        if (lineWidth <= maxWidthPerLine) {
            return;
        }

        editorBody = me.editor.getEditorBody();
        allLines = me.getLinesAndLength(editorBody.innerHTML, me.segmentMeta, me.segmentFileId);
        if (allLines.length >= (me.segmentMeta.maxNumberOfLines)) {
            return;
        }

        div = document.createElement('div');
        div.innerHTML = htmlInLine;
        textInLine = div.textContent || div.innerText || "";
        wordsInLine = textInLine.split(/(\s+)/);
        for (i = 0; i < wordsInLine.length; i++) {
            textToCheck += wordsInLine[i];
            textToCheckWidth = me.editor.getLength(textToCheck, me.segmentMeta, me.segmentFileId);
            if (textToCheckWidth <= maxWidthPerLine) {
                textForLine = textToCheck;
            } else {
                if (!textForLine.replace(/\s/g, '').length) {
                    textForLine = textInLine;
                }
                if(me.editor.getLength(textForLine, me.segmentMeta, me.segmentFileId) > maxWidthPerLine) {
                    // eg if the single word in the line is too long
                    return;
                }
                me.bookmarkForCaret = me.getPositionOfCaret();
                range = rangy.createRange();
                range.selectNodeContents(editorBody);
                options = {
                        wholeWordsOnly: false,
                        withinRange: range
                };
                range.findText(textForLine, options);
                if (textForLine !== range.toString()) {
                    // textForLine: " Dies ist..."
                    // editorBody Html: " Dies ist..."
                    // editorBody Text: "Dies ist..."
                    // => try again without the whitespace at the beginning:
                    range.findText(textForLine.trim(), options);
                    if (textForLine.trim() !== range.toString()) {
                        return;
                    }
                }
                range.collapse(false);
                sel = rangy.getSelection(editorBody);
                sel.setSingleRange(range);
                me.fireEvent('insertNewline');
                return;
            }
        }
    },

    /**
     * After a new line is added, we need to restore where the user was currently typing.
     */
    handleAfterInsertWhitespace: function() {
        var me = this;
        me.setPositionOfCaret(me.bookmarkForCaret);
    },

    /**
     * Update the minmax status strip label
     */
    updateLabelForEditor: function(){
        var me=this,
            segmentLengthTotal,
            segmentLengthStatus = [],
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            useMaxNumberOfLines = minMaxLengthComp.useMaxNumberOfLines(me.segmentMeta),
            messageSizeUnit = minMaxLengthComp.getSizeUnit(me.segmentMeta),
            minWidthForSegment = minMaxLengthComp.getMinWidthForSegment(me.segmentMeta),
            maxWidthForSegment = minMaxLengthComp.getMaxWidthForSegment(me.segmentMeta),
            maxWidthForLine = minMaxLengthComp.getMaxWidthForSingleLine(me.segmentMeta),
            minWidthForLine = minMaxLengthComp.getMinWidthForSingleLine(me.segmentMeta),
            labelData,
            tplData,
            editorBody = me.editor.getEditorBody(),
            editorContent = editorBody.innerHTML,
            allLines,
            line,
            i,
            errors = [],
            errorMsg;
        
        segmentLengthTotal = me.editor.getTransunitLength(editorContent);
        segmentLengthStatus = me.getMinMaxLengthStatus(editorContent,me.segmentMeta, me.segmentFileId);
        
        labelData = {
            length: segmentLengthTotal + messageSizeUnit,
            minWidth: minWidthForSegment,
            maxWidth: maxWidthForSegment,
            siblings: null
        };
        tplData = {
            cls: 'invalid-length'
        };
        
        // tplData
        if (segmentLengthStatus.includes(me.lengthstatus.segmentLengthValid)) {
            tplData.cls = 'valid-length';
        }
        
        // 3 Zeilen * 200px; Aktuell: 4 Zeilen, insgesamt 599px
        // 3 lines * 200px; Current: 4 lines, together 599px
        
        // labelData
        if (maxWidthForSegment === Number.MAX_SAFE_INTEGER) {
            labelData.maxWidth = ''; // = not set; lines can be "endless" (= which is MAX_SAFE_INTEGER)
        }
        if (useMaxNumberOfLines) {
            allLines = me.getLinesAndLength(editorContent, me.segmentMeta, me.segmentFileId);
            labelData.maxWidth = me.segmentMeta.maxNumberOfLines + '*' + maxWidthForLine;
            if (segmentLengthStatus.includes(me.lengthstatus.segmentTooManyLines)) {
                allLines = me.getLinesAndLength(editorContent, me.segmentMeta, me.segmentFileId);
                errorMsg = '; ' + allLines.length + ' ' + me.strings.lines;
                labelData.maxWidth = labelData.maxWidth + errorMsg;
            }
            if (segmentLengthStatus.includes(me.lengthstatus.segmentLinesTooLong)) {
                for (i = 0; i < allLines.length; i++) {
                    line = allLines[i];
                    if (line.lineWidth > maxWidthForLine) {
                        errors.push((i+1) + ': ' + line.lineWidth);
                    }
                }
                errorMsg = (errors.length === 0) ? '' : ('; ' + errors.join('; '));
                labelData.maxWidth = labelData.maxWidth + errorMsg;
            }
            if (segmentLengthStatus.includes(me.lengthstatus.segmentLinesTooShort)) {
                for (i = 0; i < allLines.length; i++) {
                    line = allLines[i];
                    if (line.lineWidth < minWidthForLine) {
                        errors.push((i+1) + ': ' + line.lineWidth);
                    }
                }
                errorMsg = (errors.length === 0) ? '' : ('; ' + errors.join('; '));
                labelData.minWidth = minWidthForSegment + errorMsg;
            }
        }
        
        // tooltip
        tplData.tip = me.renderErrorMessage(segmentLengthStatus, me.segmentMeta);
        
        
        // siblings
        if(me.segmentMeta && me.segmentMeta.siblingData) {
            var nrs = Ext.Object.getValues(me.segmentMeta.siblingData).map(function(item){
                return item.nr;
            });
            //show segments only if there are more then one (inclusive the current one)
            if(nrs.length > 1) {
                labelData.siblings = nrs.join(', ');
            }
        }
        
        tplData.text = me.labelTpl.apply(labelData);
        me.update(tplData);
    },
    
    // ********************************************************************
    // Helpers
    // ********************************************************************
    
    
    /**
     * @param {String} htmlToCheck
     * @param {Object} meta
     * @returns {Array} segmentLengthValid|segmentToShort|segmentToLong|segmentTooManyLines|segmentLinesTooLong|segmentLinesTooShort
     */
    getMinMaxLengthStatus: function(htmlToCheck, meta, fileId) {
        var me = this,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            useMaxNumberOfLines = minMaxLengthComp.useMaxNumberOfLines(me.segmentMeta);
        // maxWidth and/or minWidth are set EITHER for the trans-unit OR for the lines
        if (useMaxNumberOfLines) {
            return me.getMinMaxLengthStatusForLines(htmlToCheck, meta, fileId);
        } else {
            return me.getMinMaxLengthStatusForSegment(htmlToCheck, meta);
        }
    },
    
    /**
     * @param {String} htmlToCheck
     * @param {Object} meta
     * @returns {Array} segmentToShort|segmentToLong|segmentLengthValid
     */
    getMinMaxLengthStatusForSegment: function(htmlToCheck, meta) {
        var me = this,
            transunitLength,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            minWidth = minMaxLengthComp.getMinWidthForSegment(meta),
            maxWidth = minMaxLengthComp.getMaxWidthForSegment(meta);
        
        // The segment might be part of a trans-unit that has been
        // split to multiple segments. This will be considered here:
        transunitLength = me.editor.getTransunitLength(htmlToCheck);
        
        if(transunitLength < minWidth) {
            return [me.lengthstatus.segmentToShort];
        }
        
        if(transunitLength > maxWidth) {
            return [me.lengthstatus.segmentToLong];
        }
        
        return [me.lengthstatus.segmentLengthValid];
    },
    
    /**
     * @param {String} htmlToCheck
     * @param {Object} meta
     * @returns {Array} segmentTooManyLines|segmentLinesTooLong|segmentLinesTooShort|segmentLengthValid
     */
    getMinMaxLengthStatusForLines: function(htmlToCheck, meta, fileId) {
        var me = this,
            lengthstatus = [],
            allLines,
            i,
            line,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            maxWidth = minMaxLengthComp.getMaxWidthForSingleLine(meta),
            minWidth = minMaxLengthComp.getMinWidthForSingleLine(meta);
        
        allLines = me.getLinesAndLength(htmlToCheck, meta, fileId);
        
        if (allLines.length > meta.maxNumberOfLines) {
            lengthstatus.push(me.lengthstatus.segmentTooManyLines);
        }
        
        for (i = 0; i < allLines.length; i++) {
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
                continue;
            }
        }
        
        return (lengthstatus.length === 0) ? [me.lengthstatus.segmentLengthValid] : lengthstatus;
    },
    
    /**
     * Returns the lines (= objects with their text and length) for the given content and meta.
     * @param {String} htmlToCheck
     * @param {Object} meta
     * @param {Integer} fileId
     * @Returns {Array}
     */
    getLinesAndLength: function (htmlToCheck, meta, fileId) {
        var me = this,
            rangeWrapper,
            linebreakNodes,
            i,
            lines = [],
            textInLine,
            lineWidth,
            range = rangy.createRange();
        
        rangeWrapper = Ext.dom.Helper.createDom('<div>'+htmlToCheck+'</div>');
        range.selectNodeContents(rangeWrapper);
        linebreakNodes = range.getNodes([1], function(node) {
            return /newline/.test(node.className);
        });
        if (linebreakNodes.length === 0) {
            // = one single line only
            textInLine = range.toHtml();
            lineWidth = me.editor.getLength(textInLine, meta, fileId);
            lines.push({textInLine:textInLine, lineWidth:lineWidth});
        } else {
            for (i = 0; i <= linebreakNodes.length; i++) {
                switch(true) {
                  case (i===0):
                    // = first line
                    range.selectNodeContents(rangeWrapper);
                    range.setEndBefore(linebreakNodes[i]);
                    break;
                  case (i===linebreakNodes.length): 
                    // = last line
                    range.selectNodeContents(rangeWrapper);
                    range.setStartAfter(linebreakNodes[i-1]);
                    break;
                  default:
                    range.setStartAfter(linebreakNodes[i-1]);
                    range.setEndBefore(linebreakNodes[i]);
                } 
                textInLine = range.toHtml();
                lineWidth = me.editor.getLength(textInLine, meta, fileId);
                lines.push({textInLine:textInLine, lineWidth:lineWidth});
            }
        }
        // additionalUnitLength and additionalMrkLength are not considered here because
        // maxNumberOfLines should onlybe used without <mrk... in the trans-unit
        return lines;
    },
    
    /**
     * Render the error-message according to the segment's length status.
     * @param {Array} segmentLengthStatus
     * @param {Object} meta
     * @returns {String}
     */
    renderErrorMessage: function (segmentLengthStatus, meta) {
        var me = this,
            errorMsg = [],
            msgs = me.strings,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            minWidthForSegment,
            maxWidthForSegment,
            maxWidthForLine,
            minWidthForLine,
            i;
        for (i = 0; i < segmentLengthStatus.length; i++) {
            switch(segmentLengthStatus[i]) {
                case me.lengthstatus.segmentToShort:
                    minWidthForSegment = minMaxLengthComp.getMinWidthForSegment(meta);
                    errorMsg.push(Ext.String.format(msgs.segmentToShort, minWidthForSegment));
                  break;
                case me.lengthstatus.segmentToLong:
                    maxWidthForSegment = minMaxLengthComp.getMaxWidthForSegment(meta);
                    errorMsg.push(Ext.String.format(msgs.segmentToLong, maxWidthForSegment));
                  break;
                case me.lengthstatus.segmentTooManyLines:
                    errorMsg.push(Ext.String.format(msgs.segmentTooManyLines, meta.maxNumberOfLines));
                  break;
                case me.lengthstatus.segmentLinesTooLong:
                    maxWidthForLine = minMaxLengthComp.getMaxWidthForSingleLine(meta);
                    errorMsg.push(Ext.String.format(msgs.segmentLinesTooLong, maxWidthForLine));
                  break;
                case me.lengthstatus.segmentLinesTooShort:
                    minWidthForLine = minMaxLengthComp.getMinWidthForSingleLine(meta);
                    errorMsg.push(Ext.String.format(msgs.segmentLinesTooShort, minWidthForLine));
                  break;
                default: // = segmentLengthValid
                    errorMsg = [];
            }
        }
        return errorMsg.join('<br>');
    }
});