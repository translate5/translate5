
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
        title: '#UT#TBX Datei importieren',
        file: '#UT#TBX-Datei',
        importTbx: '#UT#Weitere Term-Collection Daten in Form einer TBX Datei importieren und dem bestehenden Term-Collection hinzufügen',
        importTbxType: '#UT#Bitte verwenden Sie eine TBX Datei!',
        importSuccess: '#UT#Weitere Term-Collection Daten erfolgreich importiert!',
        save: '#UT#Importieren',
        cancel: '#UT#Abbrechen',
        mergeTerms:'#UT#Termeinträge verschmelzen',
        deleteTermEntriesDate:'#UT#Terme löschen, deren letzte Berührung länger her ist als',
        deleteTermEitriesImport:'#UT#Termeinträge löschen älter als aktueller Import',
        helpButtonTooltip:'#UT#Info zum Term-Collection',
        deleteTermProposals:'#UT#Vorschläge löschen, deren letzte Berührung länger her ist als',
        deleteTermProposalsImport:'#UT#Vorschläge löschen älter als aktueller Import'
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
            value:true
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
        me.down('filefield[name="tmUpload"]').regex=/\.tbx$/i;
    },
    /**
     * loads the record into the form
     * @param record
     */
    loadRecord: function(record) {
        this.languageResourceRecord = record;
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