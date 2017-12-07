
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
    tpl: '<div class="{0}" data-qtip="{1}">{2}</div>',
    hidden:true,
    strings:{
        minTooltip:'#UT#Minimale Länge: {0};',
        maxTooltip:'#UT#Maximale Länge: {0};',
        segmentBellowLimit:'#UT#Der Segmentinhalt ist zu kurz! Mindestens {0} Zeichen müssen vorhanden sein.',
        segmentOverLimit:'#UT#Der Segmentinhalt ist zu lang! Maximal {0} Zeichen sind erlaubt.',
    },
    /***
     * Segment model record
     */
    segmentRecord:null,
    
    /***
     * Hold the active error message (when the segment is over the limit or bellow)
     */
    activeErroMessage:null,
    
    
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
            cls = 'invalid-length',
            tooltipText = [];

        if(me.isCharactersInBorder(charactersCount,minWidth,maxWidth)){
            cls = 'valid-length'
        }
        //if min or max is null do not display the tooltip
        if(minWidth) {
            tooltipText.push(Ext.String.format(me.strings.minTooltip,minWidth));
        }
        if(maxWidth) {
            tooltipText.push(Ext.String.format(me.strings.maxTooltip,maxWidth));
        }
        me.lookupTpl('tpl').overwrite(me.getEl(),[
            cls,
            tooltipText.join('<br/>'),
            charactersCount
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
        var me=this;
        if(minWidth===null){
            minWidth=0;
        }
        if(maxWidth===null){
            maxWidth=Number.MAX_SAFE_INTEGER;
        }
        
        //if the character count is bellow the limit, add custom error message.
        if(charactersCount<minWidth){
            me.activeErroMessage=Ext.String.format(me.strings.segmentBellowLimit,minWidth);
            return false;
        }
      //if the character count is over the limit, add custom error message.
        if(charactersCount>maxWidth){
            me.activeErroMessage=Ext.String.format(me.strings.segmentOverLimit,maxWidth);
            return false;
        }
        return true;
    },

    /**
     * Check if the segment character number is within the defined borders
     */
    isSegmentLengthInRange:function(segmentText){
        var me=this;
        
        if(!me.segmentRecord){
            return true;
        }
        
        var metaCache=me.segmentRecord.get('metaCache');
        
        if(metaCache.minWidth===null && metaCache.maxWidth===null){
            return true;
        }
        //get the characters length and is segment saveble
        var charactersLength=me.getSegmentCharactersCount(segmentText),
            saveSegment=me.isCharactersInBorder(charactersLength,metaCache.minWidth,metaCache.maxWidth);
        
        return saveSegment;
    },

    /***
     * Remove the unneeded html tags from the segment
     */
    cleanTextHtmlTags:function(segmentText){
        var res=segmentText.replace(/<del[^>]*>.*?<\/del>/ig,'');//clean del tag
        var div = document.createElement("div");
        div.innerHTML = res;
        var text = div.textContent || div.innerText || "";
        div=null;
        return text;
    }

    
});