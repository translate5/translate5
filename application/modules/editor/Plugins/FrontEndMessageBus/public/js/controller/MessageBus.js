
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

/**#@++
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * @class Editor.plugins.ChangeLog.controller.Changelog
 * @extends Ext.app.Controller
 */
Ext.define('Editor.plugins.FrontEndMessageBus.controller.MessageBus', {
    extend: 'Ext.app.Controller',
    listen: {
        component: {
            '#segmentgrid' : {
                render: 'onSegmentGridRender',
            }
        }
    },
    onSegmentGridRender: function(){
        return; //FIXME prepare that socket server is only triggered for simultaneous usage, for beta testing we enable socket server just for each task 
        var bus = Editor.app.getController('Editor.plugins.FrontEndMessageBus.controller.MultiUserUsage');
        if(Editor.data.task.get('usageMode') === Editor.model.admin.Task.USAGE_MODE_SIMULTANEOUS){
            bus.activate();
        }
        else {
            bus.deactivate();
        }
    }
});