
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

Ext.define('Editor.view.admin.user.AssocViewModel', {
    extend: 'Ext.app.ViewModel',
    alias: 'viewmodel.adminUserAssoc',

    data:{
        selectedCustomer : false,
        selectedAssocRecord : false
    },

    strings: {
        editInfo: '#UT#Wählen Sie einen Eintrag in der Tabelle aus um diesen zu bearbeiten!',
        segmentrangeError: '#UT#Nicht zugewiesene Segmente',
        translator: '#UT#Übersetzer',
        translatorCheck: '#UT#Zweiter Lektor',
        reviewer: '#UT#Lektor',
        visitor: '#UT#Besucher',
        allEditedByUsers:'#UT#(alle Segmente können von allen Nutzern editiert werden)',
        canNotBeEditedByUsers:'#UT#(können derzeit von niemandem bearbeitet werden)'
    },

    stores: {
        users: {
            source: 'admin.Users'
        },
        workflowSteps: Ext.create('Editor.store.admin.WorkflowSteps',{ useAssignableSteps:true }),
        workflow: Ext.create('Editor.store.admin.Workflow'),
        userAssoc:{
            model:'Editor.model.admin.UserAssocDefault',
            remoteFilter: true,
            pageSize: false,
            /*setFilters:function(filters){
                // ignore the firing on empty value
                if(filters && !filters.value){
                    this.loadData([],false);
                    return;
                }
                this.superclass.superclass.setFilters.apply(this, [filters]);
            },
             */
            filters:{
                property: 'customerId',
                operator:"eq",
                value:'{selectedCustomer}'
            }
        }
    }
});