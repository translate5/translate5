
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
        var me=this;
        if(me.isVisible()){
            me.updateLabel(me.segmentRecord,htmlEditor.getTransunitLength(newValue));
        }
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
            enabled = field && field.isTarget() && (metaCache && (metaCache.minWidth !== null || metaCache.maxWidth !== null));
        
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
            messageSizeUnit = (meta.sizeUnit === Editor.view.segments.PixelMapping.SIZE_UNIT_FOR_PIXELMAPPING) ? 'px' : '',
            msgs = me.up('segmentsHtmleditor').strings,
            labelData = {
                length: segmentLength + messageSizeUnit,
                minWidth: meta && meta.minWidth ? meta.minWidth : 0, // don't add messageSizeUnit here, will be used for calculating...
                maxWidth: meta && meta.maxWidth ? meta.maxWidth : Number.MAX_SAFE_INTEGER, // don't add messageSizeUnit here, will be used for calculating...
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
        
        tplData.text = me.labelTpl.apply(labelData);
        me.update(tplData);
    }
});