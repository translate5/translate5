
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
        lines: '#UT#Zeilen',
    },
    lengthstatus: {
        segmentLengthValid: 'segmentLengthValid',
        segmentToShort: 'segmentToShort',
        segmentToLong: 'segmentToLong',
        segmentTooManyLines: 'segmentTooManyLines',
        segmentLinesTooLong: 'segmentLinesTooLong',
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
            return meta && meta.maxNumberOfLines && (meta.maxNumberOfLines > 1);
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
         * @returns integer
         */
        getMaxWidthForSingleLine: function(meta) {
            // don't add messageSizeUnit here, will be used for calculating...
            return meta && meta.maxWidth ? meta.maxWidth : Number.MAX_SAFE_INTEGER;
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
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /***
     * Handler for html editor initializer, the function is called after the iframe is initialized
     * FIXME testme
     */
    onHtmlEditorInitialize:function(htmlEditor,eOpts){
        var me=this,
            metaCache;
        
        if(me.isVisible()){
            metaCache = me.segmentRecord.get('metaCache');
            me.updateLabel(metaCache,me.editor.getTransunitLength(me.segmentRecord.get('targetEdit')));
        }

        if(!Editor.controller.SearchReplace){
            return;
        }

        var searchReplace=Editor.app.getController('SearchReplace');

        //listen to the editorTextReplaced evend from search and replace
        //so the character count is triggered when text is replaced with search and replace
        searchReplace.on({
            editorTextReplaced:function(newInnerHtml){
                me.onHtmlEditorChange(null,newInnerHtml); // TODOO test
            }
        });
    },

    /**
     * Handler for html editor text change and afterInsertMarkup
     * @param {Editor.view.segments.HtmlEditor} htmlEditor
     * @param {String|Object} newValue
     * @param {String} oldValue (optional)
     */
    onHtmlEditorChange:function(htmlEditor,newValue,oldValue = ''){
        var me=this,
            metaCache,
            newValueIsString = (typeof newValue === 'string' || newValue instanceof String),
            editorBody;
        if(!me.isVisible()){
            return;
        }
        if (!newValueIsString) { // = afterInsertMarkup 
            editorBody = me.editor.getEditorBody();
            newValue = editorBody.innerHTML;
        }
        metaCache = me.segmentRecord.get('metaCache');
        me.handleMaxNumberOfLines(metaCache, newValue, oldValue);
        me.updateLabel(metaCache, me.editor.getTransunitLength(newValue));
    },

    /**
     * Handler for html editor drag and drop change
     * @param {Editor.view.segments.HtmlEditor} htmlEditor
     * @param {String} newValue
     */
    onHtmlEditorDragEnd:function(htmlEditor,newValue){
        var me=this,
            metaCache;
        if(me.isVisible()){
            metaCache = me.segmentRecord.get('metaCache');
            me.updateLabel(metaCache, me.editor.getTransunitLength(newValue));
        }
    },
    
    /**
     * Returns the lines (= objects with their text and length) of the given text.
     * @param {String} segmentText
     * @Returns {Array}
     */
    getLinesAndLength: function (segmentText, meta) {
        var me = this,
            rangeWrapper,
            linebreakNodes,
            i,
            lines = [],
            textInLine,
            lineWidth,
            range = rangy.createRange();
        
        rangeWrapper = Ext.dom.Helper.createDom('<div>'+segmentText+'</div>');
        range.selectNodeContents(rangeWrapper);
        linebreakNodes = range.getNodes([1], function(node) {
            return /newline/.test(node.className);
        });
        if (linebreakNodes.length === 0) {
            // = one single line only
            textInLine = range.toHtml();
            lineWidth = me.editor.getLength(textInLine, meta);
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
                textInLine = range.toString();
                lineWidth = me.editor.getLength(textInLine, meta);
                lines.push({textInLine:textInLine, lineWidth:lineWidth});
            }
        }
        return lines;
    },
    
    /**
     * If max. number of lines are to be considered, we add line-breaks automatically. 
     */
    handleMaxNumberOfLines: function (metaCache, newValue, oldValue = '') {
        var me = this,
            meta = metaCache,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            useMaxNumberOfLines = minMaxLengthComp.useMaxNumberOfLines(meta),
            newSegmentLengthTotal,
            oldSegmentLengthTotal,
            maxWidthPerLine,
            i,
            allLines,
            line;
        
        if (!useMaxNumberOfLines) {
            return;
        }
        
        newSegmentLengthTotal = me.editor.getTransunitLength(newValue);
        oldSegmentLengthTotal = me.editor.getTransunitLength(oldValue);
        if (newSegmentLengthTotal <= oldSegmentLengthTotal) {
            return;
        }
        
        allLines = me.getLinesAndLength(newValue, meta);
        
        if (allLines.length >= (meta.maxNumberOfLines)) {
            // Don't add further line-breaks when maxNumberOfLines has been reached already.
            return;
        }
        
        maxWidthPerLine = minMaxLengthComp.getMaxWidthForSingleLine(meta);
        
        for (i = 0; i < allLines.length; i++) {
            line = allLines[i];
            me.handleMaxWidthForLine(line.textInLine, line.lineWidth, maxWidthPerLine);
        }
    },
    
    /**
     * If the text is longer than allowed: Add a line-break before (if possible).
     * @param {String} textInLine
     * @param {Integer} lineWidth
     * @param {Integer} maxWidthPerLine
     * 
     */
    handleMaxWidthForLine: function (textInLine, lineWidth, maxWidthPerLine) {
        var me = this,
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
        range = rangy.createRange();
        range.selectNodeContents(editorBody);
        options = {
                wholeWordsOnly: false,
                withinRange: range
        };
        
        wordsInLine = textInLine.split(' ');
        for (i = 0; i < wordsInLine.length; i++) {
            if (i>0) {
                textToCheck += ' ';
            }
            textToCheck += wordsInLine[i];
            if (textToCheck === '') {
                continue;
            }
            textToCheckWidth = me.editor.getLength(textToCheck);
            if (textToCheckWidth <= maxWidthPerLine) {
                textForLine = textToCheck;
            } else {
                if (!textForLine.replace(/\s/g, '').length) {
                    textForLine = textInLine;
                }
                if(me.editor.getLength(textForLine) > maxWidthPerLine) {
                    return;
                }
                me.bookmarkForCaret = me.getPositionOfCaret();
                range.findText(textForLine, options);
                range.collapse(false);
                sel = rangy.getSelection(editorBody);
                sel.setSingleRange(range);
                me.fireEvent('insertNewline');
                return;
            }
        }
    },

    /**
     * After the new line is added, we need to restore where the user was currently typing.
     */
    handleAfterInsertWhitespace: function() {
        var me = this;
        me.setPositionOfCaret(me.bookmarkForCaret);
    },

    /**
     * Return true or false if the minmax status strip should be visible
     */
    updateSegment: function(record, fieldname){
        var me=this,
            metaCache = record.get('metaCache'),
            fields = Editor.data.task.segmentFields(),
            field = fields.getAt(fields.findExact('name', fieldname.replace(/Edit$/, ''))),
            enabled = field && field.isTarget() && Editor.view.segments.MinMaxLength.useMinMaxWidth(metaCache);
        
        me.setVisible(enabled);
        me.segmentRecord = null;
        if(enabled){
            me.segmentRecord = record;
            me.updateLabel(metaCache, me.editor.getTransunitLength(me.segmentRecord.get('targetEdit')));
        }
        return enabled;
    },
    
    /**
     * @param {Object} meta
     * @param {String} segmentText
     * @returns {String} segmentLengthValid|segmentToShort|segmentToLong|segmentTooManyLines|segmentLinesTooLong
     */
    getMinMaxLengthStatus: function(meta, segmentText) {
        var me = this,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            useMaxNumberOfLines = minMaxLengthComp.useMaxNumberOfLines(meta);
        if (useMaxNumberOfLines) {
            return me.getMinMaxLengthStatusForLines(segmentText);
        } else {
            return me.getMinMaxLengthStatusForSegment(segmentText);
        }
    },
    
    /**
     * @param {String} segmentText
     * @returns {String} segmentToShort|segmentToLong|segmentLengthValid
     */
    getMinMaxLengthStatusForSegment: function(segmentText) {
        var me = this,
            transunitLength,
            meta = me.segmentRecord && me.segmentRecord.get('metaCache'),
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            minWidth = minMaxLengthComp.getMinWidthForSegment(meta),
            maxWidth = minMaxLengthComp.getMaxWidthForSegment(meta);
        
        // The segment might be part of a trans-unit that has been
        // split to multiple segments. This will be considered here:
        transunitLength = me.editor.getTransunitLength(segmentText);
        
        if(transunitLength < minWidth) {
            return me.lengthstatus.segmentToShort;
        }
        
        if(transunitLength > maxWidth) {
            return me.lengthstatus.segmentToLong;
        }
        
        return me.lengthstatus.segmentLengthValid;
    },
    
    /**
     * @param {String} segmentText
     * @returns {String} segmentTooManyLines|segmentLinesTooLong|segmentLengthValid
     */
    getMinMaxLengthStatusForLines: function(segmentText) {
        var me = this,
            allLines,
            i,
            line,
            meta = me.segmentRecord && me.segmentRecord.get('metaCache'),
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            maxWidth = minMaxLengthComp.getMaxWidthForSingleLine(meta);
        
        allLines = me.getLinesAndLength(segmentText, meta);
        
        if (allLines.length > meta.maxNumberOfLines) {
            return me.lengthstatus.segmentTooManyLines;
        }
        
        for (i = 0; i < allLines.length; i++) {
            line = allLines[i];
            if (line.lineWidth > maxWidth) {
                return me.lengthstatus.segmentLinesTooLong;
            }
        }
        
        return me.lengthstatus.segmentLengthValid;
    },

    /**
     * Update the minmax status strip label
     */
    updateLabel: function(meta, segmentLengthTotal){
        var me=this,
            segmentLengthStatus,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            messageSizeUnit = minMaxLengthComp.getSizeUnit(meta),
            minWidthForSegment = minMaxLengthComp.getMinWidthForSegment(meta),
            maxWidthForSegment = minMaxLengthComp.getMaxWidthForSegment(meta),
            maxWidthForLine = minMaxLengthComp.getMaxWidthForSingleLine(meta),
            labelData = {
                length: segmentLengthTotal + messageSizeUnit,
                minWidth: minWidthForSegment,
                maxWidth: maxWidthForSegment,
                siblings: null
            },
            tplData = {
                cls: 'invalid-length'
            },
            editorBody = me.editor.getEditorBody(),
            allLines,
            line,
            i,
            errors = [],
            errorMsg;
        
        segmentLengthStatus = me.getMinMaxLengthStatus(meta, editorBody.innerHTML);
        
        if (segmentLengthStatus === me.lengthstatus.segmentLengthValid) {
            tplData.cls = 'valid-length';
        }
        
        if (segmentLengthStatus === me.lengthstatus.segmentLinesTooLong) {
            allLines = me.getLinesAndLength(editorBody.innerHTML, meta);
            for (i = 0; i < allLines.length; i++) {
                line = allLines[i];
                if (line.lineWidth > maxWidthForLine) {
                    errors.push((i+1) + ': ' + line.lineWidth);
                }
            }
            errorMsg = (errors.length === 0) ? '' : ('; ' + errors.join('; '));
            labelData.maxWidth = maxWidthForSegment + errorMsg;
        }
        
        if (segmentLengthStatus === me.lengthstatus.segmentTooManyLines) {
            allLines = me.getLinesAndLength(editorBody.innerHTML, meta);
            errorMsg = '; ' + allLines.length + me.strings.lines;
            labelData.maxWidth = maxWidthForSegment + errorMsg;
        }
        
        tplData.tip = me.renderErrorMessage(meta, segmentLengthStatus);
        
        if(meta && meta.siblingData) {
            var nrs = Ext.Object.getValues(meta.siblingData).map(function(item){
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
    
    /**
     * Render the error-message according to the segment's length status.
     * @param {Object} meta
     * @param {String} segmentLengthStatus
     * @returns {String}
     */
    renderErrorMessage: function (meta, segmentLengthStatus) {
        var me = this,
            errorMsg,
            msgs = me.strings,
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            minWidthForSegment = minMaxLengthComp.getMinWidthForSegment(meta),
            maxWidthForSegment = minMaxLengthComp.getMaxWidthForSegment(meta),
            maxWidthForLine = minMaxLengthComp.getMaxWidthForSingleLine(meta);
        switch(segmentLengthStatus) {
            case me.lengthstatus.segmentToShort:
                errorMsg = Ext.String.format(msgs.segmentToShort, minWidthForSegment);
              break;
            case me.lengthstatus.segmentToLong:
                errorMsg = Ext.String.format(msgs.segmentToLong, maxWidthForSegment);
              break;
            case me.lengthstatus.segmentTooManyLines:
                errorMsg = Ext.String.format(msgs.segmentTooManyLines, meta.maxNumberOfLines);
              break;
            case me.lengthstatus.segmentLinesTooLong:
                errorMsg = Ext.String.format(msgs.segmentLinesTooLong, maxWidthForLine);
              break;
            default: // = segmentLengthValid
                errorMsg = '';
        }
        return errorMsg;
    }
});