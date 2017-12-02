
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
    tpl: '<div style="background-color:{0};" data-qtip="{1}"><strong style="margin:0.3em;">{2}</strong></div>',
    hidden:true,
    strings:{
        minTooltip:'#UT#Minimale Länge: {0};',
        maxTooltip:'#UT#Maximale Länge: {0};',
        segmentNotInRange:'#UT#Das Segment ist nicht innerhalb der definierten Zeichenlänge'
    },
    /***
     * Segment model record
     */
    segmentRecord:null,
    initConfig : function(instanceConfig) {
        var me=this,
            config = {
            };
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
            me.updateLabel(me.segmentRecord,me.getSegmentCharactersCount(newValue));
        }
    },

    /***
     * Handler for html editor initializer, the function is called after the iframe is initialized
     */
    onHtmlEditorInitialize:function(htmlEditor,eOpts){
        var me=this;
        if(me.isVisible()){
            me.updateLabel(me.segmentRecord,me.getSegmentCharactersCount(htmlEditor.getValue()));
        }
    },

    /**
     * Return true or false if the minmax status strip should be visible
     */
    handleElementVisible:function(record){
        var me=this,
            metaCache=record.get('metaCache'),
            charactersCount=me.getSegmentCharactersCount(me.htmlEditor.getValue());
        
        if(metaCache.minWidth===null && metaCache.maxWidth===null){
            return false;
        }
        return true;
    },

    /***
    * Set the segment record
    */
    setSegmentRecord:function(record){
        var me=this;
        me.segmentRecord=null;
        if(me.isVisible()){
            me.segmentRecord=record;
        }
    },

    /**
     * Update the minmax status strip label
     */
    updateLabel:function(record,charactersCount){
        var me=this,
            metaCache=record.get('metaCache'),
            minWidth=metaCache.minWidth,
            maxWidth=metaCache.maxWidth,
            cssColor='#ff4242',
            tooltipText='';

        if(me.isCharactersInBorder(charactersCount,minWidth,maxWidth)){
            cssColor='#24f324'
        }
        //if min or max is null do not display the tooltip
        tooltipText+=minWidth!==null ? Ext.String.format(me.strings.minTooltip,minWidth) : "";
        tooltipText+=maxWidth!==null ? Ext.String.format(me.strings.maxTooltip,maxWidth) : "";
        me.lookupTpl('tpl').overwrite(me.getEl(),[
            cssColor,
            tooltipText,
            "  "+charactersCount+"  "
        ]);
    },

    /**
     * Get the character count of the segment text, without the tags in it
     */
    getSegmentCharactersCount:function(segmentText){
        var me=this;
        if(segmentText===null){
            segmentText=me.htmlEditor.getValueAndUnMarkup();
        }
        segmentText=me.cleanTextHtmlTags(segmentText);
        return segmentText.length;
    },

    /**
     * Check if the given number of characters is in between allowed range
     */
    isCharactersInBorder:function(charactersCount,minWidth,maxWidth){
        if(minWidth===null){
            minWidth=0;
        }
        if(maxWidth===null){
            maxWidth=Number.MAX_SAFE_INTEGER;
        }
        return charactersCount>=minWidth && charactersCount<=maxWidth;
    },

    /**
     * Check if the segment character number is within the defined borders
     */
    checkSegmentLength:function(record){
        var me=this,
            metaCache=record.get('metaCache');
        
        if(metaCache.minWidth===null && metaCache.maxWidth===null){
            return true;
        }
        //get the characters length and is segment saveble
        var charactersLength=me.getSegmentCharactersCount(null),
            saveSegment=me.isCharactersInBorder(charactersLength,metaCache.minWidth,metaCache.maxWidth);

        if(!saveSegment){
            Editor.MessageBox.addWarning(me.strings.segmentNotInRange);
            return false;
        }
        return saveSegment;
    },

    /***
     * Remove the unneeded html tags from the segment
     * 
     * 
     * TODO: probably wrong segment content text is passed to be mesured, check this
     */
    cleanTextHtmlTags:function(segmentText){
        var res1=segmentText.replace(/<\/?bpt[^>]*>/ig,'');
        res1=res1.replace(/<\/?ept[^>]*>/ig,'');
        res1=res1.replace(/<\/?img[^>]*>/ig,'');//clean images tag
        res1=res1.replace(/<\/?div[^>]*>/ig,'');//clean mqm tag
        res1=res1.replace(/<del[^>]*>.*?<\/del>/ig,'');//clean del tag
        res1=res1.replace(/<\/?ins[^>]*>/ig,'');//clean ins tag
        res1=res1.replace(/<\/?span[^>]*>/ig,'');//clean term tag
        //res1=Ext.util.Format.htmlEncode(res1);//FIXME this is a buggy functin
        //this is the only function which does the job
        var doc = new DOMParser().parseFromString(res1, "text/html");
        res1=doc.documentElement.textContent;
        doc=null;
        return res1;
    }

    
});