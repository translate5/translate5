
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
    strings: {
        minText:'#UT#Min. {minWidth}',
        maxText:'#UT# von {maxWidth}',
        siblingSegments: '#UT#Seg.: {siblings}'
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
        getMinWidth: function(meta) {
            // don't add messageSizeUnit here, will be used for calculating...
            return meta && meta.minWidth ? meta.minWidth : 0;
        },
        /**
         * Returns the total maxWidth according to the meta-data.
         * @returns integer
         */
        getMaxWidth: function(meta) {
            // don't add messageSizeUnit here, will be used for calculating...
            var me = this,
                maxWidth = me.getMaxWidthPerLine(meta),
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
        getMaxWidthPerLine: function(meta) {
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
        me.htmlEditor=instanceConfig.htmlEditor;

        me.htmlEditor.on({
            change:{
                fn:me.onHtmlEditorChange,
                scope:me
            },
            initialize:{
                fn:me.onHtmlEditorInitialize,
                scope:me
            }
        });
        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        return me.callParent([config]);
    },

    /**
     * Handler for html editor text change
     */
    onHtmlEditorChange:function(htmlEditor,newValue,oldValue,eOpts){
        var me=this,
            record,
            segmentLength;
        if(me.isVisible()){
            record = me.segmentRecord;
            segmentLength = htmlEditor.getTransunitLength(newValue);
            me.handleMaxNumberOfLines(htmlEditor, record, segmentLength, newValue, oldValue);
            me.updateLabel(record, segmentLength);
        }
    },
    
    /**
     * If max. number of lines are to be considered, we add line-breaks automatically. 
     */
    handleMaxNumberOfLines: function (htmlEditor, record, segmentLength, newValue, oldValue) {
        var meta = record.get('metaCache'),
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            useMaxNumberOfLines = minMaxLengthComp.useMaxNumberOfLines(meta),
            oldSegmentLength,
            maxWidthPerLine,
            newValueWithLinebreaks,
            editorBody;
        if (!useMaxNumberOfLines) {
            return;
        }
        oldSegmentLength = htmlEditor.getTransunitLength(oldValue);
        if (segmentLength <= oldSegmentLength) {
            // We don't check after content has been removed.
            return;
        }
        maxWidthPerLine = minMaxLengthComp.getMaxWidthPerLine(meta);
        
        // TODO:
        // check how to do this for multibyte / use ranges instead?:
        // newValue.substring(0, maxWidthPerLine) + ' XXX ' + newValue.substring(maxWidthPerLine);
        
        // TODO: 
        // - check for each existing line if it is within the maxWidthPerLine
        // - if not: check where the maxLength (pixel!) is in the content of the line (= html!)
        // - add the line-break there (eg. in front of the last word before the line ends)
        // - recalculate the other lines?
        // - change content in Editor without running into the whole process again
    },

    /***
     * Handler for html editor initializer, the function is called after the iframe is initialized
     * FIXME testme
     */
    onHtmlEditorInitialize:function(htmlEditor,eOpts){
        var me=this,
            editorBody=htmlEditor.getEditorBody();
        
        if(me.isVisible()){
            me.updateLabel(me.segmentRecord,htmlEditor.getTransunitLength());
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
     * Return true or false if the minmax status strip should be visible
     */
    updateSegment: function(record, fieldname){
        var me=this,
            metaCache = record.get('metaCache'),
            htmlEditor = me.up('segmentsHtmleditor'),
            fields = Editor.data.task.segmentFields(),
            field = fields.getAt(fields.findExact('name', fieldname.replace(/Edit$/, ''))),
            enabled = field && field.isTarget() && Editor.view.segments.MinMaxLength.useMinMaxWidth(metaCache);
        
        me.setVisible(enabled);
        me.segmentRecord = null;
        if(enabled){
            me.segmentRecord = record;
            me.updateLabel(me.segmentRecord, htmlEditor.getTransunitLength());
        }
        return enabled;
    },

    /**
     * Update the minmax status strip label
     */
    updateLabel: function(record, segmentLength){
        var me=this,
            meta=record.get('metaCache'),
            minMaxLengthComp = Editor.view.segments.MinMaxLength,
            useMaxNumberOfLines = minMaxLengthComp.useMaxNumberOfLines(meta),
            messageSizeUnit = minMaxLengthComp.getSizeUnit(meta),
            msgs = me.up('segmentsHtmleditor').strings,
            labelData = {
                length: segmentLength + messageSizeUnit,
                minWidth: minMaxLengthComp.getMinWidth(meta),
                maxWidth: minMaxLengthComp.getMaxWidth(meta),
                siblings: null
            },
            tplData = {
                cls: 'invalid-length'
            };

        if(labelData.minWidth <= segmentLength && segmentLength <= labelData.maxWidth) {
            tplData.cls = 'valid-length';
        }
        else {
            tplData.tip = segmentLength > labelData.maxWidth ? msgs.segmentToLong : msgs.segmentToShort;
            tplData.tip = Ext.String.format(tplData.tip, segmentLength > labelData.maxWidth ? labelData.maxWidth : labelData.minWidth);
        }
        
        if(meta && meta.siblingData) {
            var nrs = Ext.Object.getValues(meta.siblingData).map(function(item){
                return item.nr;
            });
            //show segments only if there are more then one (inclusive the current one)
            if(nrs.length > 1) {
                labelData.siblings = nrs.join(', ');
            }
        }

        if (useMaxNumberOfLines) {
            // for message
            labelData.maxWidth = meta.maxNumberOfLines + '*' + meta.maxWidth;
        }
        
        tplData.text = me.labelTpl.apply(labelData);
        me.update(tplData);
    }
});