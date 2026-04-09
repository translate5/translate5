
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

Ext.define('Editor.view.LanguageResources.ImportCollectionWindow', {
    extend: 'Editor.view.LanguageResources.ImportTmWindow',
    alias: 'widget.importCollectionWindow',
    itemId: 'importCollectionWindow',
    strings: {
        title: '#UT#Import TBX file',
        file: '#UT#TBX file',
        importTbx: '#UT#Import additional TermCollection data as TBX file and add the data to the already existing TermCollection',
        importTbxType: '#UT#Please use a TBX file!',
        importSuccess: '#UT#The file has been uploaded. The import is now running in the background.',
        save: '#UT#Import',
        cancel: '#UT#Cancel',
        mergeTerms:'#UT#Merge term entries',
        deleteTermEntriesDate:'#UT#Delete terminology entries that have not been edited since',
        deleteTermEitriesImport:'#UT#Delete term entries older than current import',
        helpButtonTooltip:'#UT#Info about TermCollection',
        deleteTermProposals:'#UT#Delete proposals not edited since',
        deleteTermProposalsImport:'#UT#Delete proposals older than current import',
        collectionUploadTooltip:'#UT#Allowed file formats: TBX or a ZIP file containing one or multiple TBX files.'
    },
    tools:[{
        type:'help',
        handler:function(){
            window.open('https://confluence.translate5.net/display/TAD/Term+Collection#TermCollection-Importtermstothetermcollection ','_blank');
        }
    }],
    constructor: function (instanceConfig) {
        var me = this,
            config = {
                title:me.strings.title,
                height : 460,
                tools:[{
                    type:'help',
                    tooltip:me.strings.helpButtonTooltip,
                    handler:function(){
                        window.open('https://confluence.translate5.net/display/TAD/Term+Collection#TermCollection-Importtermstothetermcollection ','_blank');
                    }
                }]
        };

        if (instanceConfig) {
            me.self.getConfigurator().merge(me, config, instanceConfig);
        }
        me.callParent([config]);
        me.down('form').add({
            xtype:'checkbox',
            fieldLabel: me.strings.mergeTerms,
            itemId:'mergeTerms',
            name:'mergeTerms',
            value:false
        },{
            xtype:'datefield',
            fieldLabel: me.strings.deleteTermEntriesDate,
            itemId:'deleteTermsLastTouchedOlderThan',
            name:'deleteTermsLastTouchedOlderThan',
            listeners:{
	            change:function(field,newValue){
	        		me.handleFieldPairDisable('deleteTermsOlderThanCurrentImport',newValue);
	        	}
            }
        },{
            xtype:'checkbox',
            fieldLabel: me.strings.deleteTermEitriesImport,
            itemId:'deleteTermsOlderThanCurrentImport',
            name:'deleteTermsOlderThanCurrentImport',
            value:false,
            listeners:{
	            change:function(field,newValue){
	        		me.handleFieldPairDisable('deleteTermsLastTouchedOlderThan',newValue);
	        	}
            }
        },{
            xtype:'datefield',
            fieldLabel: me.strings.deleteTermProposalsDate,
            itemId:'deleteProposalsLastTouchedOlderThan',
            name:'deleteProposalsLastTouchedOlderThan',
            inputValue:true,
            value:false,
            listeners:{
            	change:function(field,newValue){
            		me.handleFieldPairDisable('deleteProposalsOlderThanCurrentImport',newValue);
            	}
            }
        },{
            xtype:'checkbox',
            fieldLabel: me.strings.deleteTermProposalsImport,
            itemId:'deleteProposalsOlderThanCurrentImport',
            name:'deleteProposalsOlderThanCurrentImport',
            inputValue:true,
            value:false,
            listeners:{
            	change:function(field,newValue){
            		me.handleFieldPairDisable('deleteProposalsLastTouchedOlderThan',newValue);
            	}
            }
        });
        var uploadField = me.down('filefield[name="tmUpload"]');
        uploadField.regex=/\.(tbx|zip)$/i;
        uploadField.labelClsExtra = 'lableInfoIcon';
        uploadField.autoEl = { tag: 'div', 'data-qtip': me.strings.collectionUploadTooltip};
    },
    /**
     * loads the record into the form
     * @param record
     */
    loadRecord: function(record) {
        var me=this;
        me.setTitle(me.strings.title+': ' + Ext.String.htmlEncode(record.get('name')));
        me.languageResourceRecord = record;
    },
    
    /***
     * Disable or enable the given field based on the pair value
     */
    handleFieldPairDisable:function(field,value){
		var form=this.down('form').getForm(),
			pairField=form.findField(field);
		pairField.setDisabled(value);
    }
});