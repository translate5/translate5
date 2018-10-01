
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
        file: '#UT#TBX-Datei',
        title: '#UT#TBX Datei importieren',
        importTbx: '#UT#Weitere Term-Collection Daten in Form einer TBX Datei importieren und dem bestehenden Term-Collection hinzufügen',
        importTbxType: '#UT#Bitte verwenden Sie eine TBX Datei!',
        importSuccess: '#UT#Weitere Term-Collection Daten erfolgreich importiert!',
        save: '#UT#Speichern',
        cancel: '#UT#Abbrechen',
        mergeTerms:'#UT#Merge terms',
        deleteTermEntriesDate:'#UT#Delete old term entries',
        deleteTermEitriesImport:'#UT#Delete entries older then import'
    },

    constructor: function (config) {
        var me=this;
        me.callParent(arguments);
        me.down('form').add({
            xtype:'checkbox',
            fieldLabel: me.strings.mergeTerms,
            itemId:'mergeTerms',
            name:'mergeTerms',
            value:true
        },{
            xtype:'datefield',
            fieldLabel: me.strings.deleteTermEntriesDate,
            itemId:'deleteEntriesModifiedOlderThan',
            name:'deleteEntriesModifiedOlderThan',
        },{
            xtype:'checkbox',
            fieldLabel: me.strings.deleteTermEitriesImport,
            itemId:'deleteEntriesOlderThanCurrentImport',
            name:'deleteEntriesOlderThanCurrentImport',
            value:false
        });
        me.down('filefield[name="tmUpload"]').regex=/\.tbx$/i;
    },
    /**
     * loads the record into the form
     * @param record
     */
    loadRecord: function(record) {
        this.tmmtRecord = record;
    }
});