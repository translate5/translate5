
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
 * Store for Editor.model.admin.TaskUserAssocs
 * @class Editor.store.admin.TaskUserAssocs
 * @extends Ext.data.Store
 */
Ext.define('Editor.store.admin.TaskUserTrackings', {
    // ********************************************************************
    // CAUTION: the data here is anonymized already if set so for the task!
    // ********************************************************************
    extend : 'Ext.data.Store',
    model: 'Editor.model.admin.TaskUserTracking',
    autoLoad: true,
    /**
     * returns the (anonymized) username for the user with the given trackingId
     * @param {string|integer} trackingId
     * @return {string}
     */
    getUserName: function(trackingId) {
        var me = this,
            trackedUser,
            userName = '';
        if(Ext.isString(trackingId)){
            trackingId = parseInt(trackingId);
        }
        trackedUser = me.getById(trackingId);
        if(!trackedUser) {
            // FIXME: this is just a workaround (and still works only with a second mouseover)
            me.reload();
            trackedUser = me.getById(trackingId);
        }
        if(trackedUser) {
            userName = trackedUser.get('userName');
        }
        return userName;
    },
    /**
     * returns the trackingId for the user with the given userName.
     * If there is no entry for the user in the tracking-table,
     * the username is returned.
     * @param {string} userName
     * @param {string} taskGuid
     * @return {integer|string}
     */
    getTrackingId: function(userName, taskGuid) {
        var me = this,
            trackedUser,
            trackingId = userName,
            allItems = me.data.items;
        Ext.Array.each(allItems, function(data, index, allItemsItSelf) {
            if(data.get('userName') == userName && data.get('taskGuid') == taskGuid) {
                trackingId = data.get('id');
                return false; // break loop
            }
        });
        return trackingId;
    }
});