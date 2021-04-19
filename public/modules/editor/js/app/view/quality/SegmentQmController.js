
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

/**
 * View Controller for the segments QM qualities
 * handles the clicking on the qualities which lead to an immediate change of the segments QM
 */
Ext.define('Editor.view.quality.SegmentQmController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.segmentQm',
    /**
     * Handler to sync the new state with the server
     */
    onQmChanged: function(checkbox, checked){
        var params = { segmentId: checkbox.segmentId, categoryIndex: checkbox.inputValue, qmaction: (checked ? 'add' : 'remove') };
        Ext.Ajax.request({
            url: Editor.data.restpath+'quality/segmentqm',
            method: 'GET',
            params: params,
            success: function(response){
                // response will return a segments model in case of a succesful "add", a model containing only the id in case of "remove"
                response = Ext.util.JSON.decode(response.responseText);
                if(!response.success){
                    checkbox.suspendEvent('change');
                    checkbox.setValue(!checked);
                    checkbox.resumeEvent('change');
                    console.log('Updating the quality failed: ', response);
                } else {
                    var store = Ext.getStore('SegmentQualities'), record;
                    if(response.action == 'remove'){
                        record = store.getById(response.row.id);
                        if(record){
                            store.remove(record);
                        }
                    } else if(response.action == 'add'){
                        record = Ext.create('Editor.model.quality.Segment', response.row);
                        record = store.add(record);
                    }
                }
            },
            failure: function(response){
                Editor.app.getController('ServerException').handleException(response);
            }
        });
    }
});
