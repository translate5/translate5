
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
 * @class Editor.util.messageBus.EventDomain
 */
Ext.define('Editor.util.messageBus.EventDomain', {
    extend: 'Ext.app.EventDomain',
    singleton: true,
    
    type: 'messagebus',
    
    idMatchRe: /^\#/,
    
    constructor: function() {
        var me = this;
        me.callParent();
        me.monitor(Editor.util.messageBus.MessageBus);
    },

    /**
     * Selektor must be #BUSID CHANNEL or * to match
     */
    match: function(target, selector) {
        if (selector === '*') {
            return true;
        }
        selector = selector.split(/ +/);
        if ('#'+target.getBusId() !== selector[0]) {
            return false;
        }
        //selector count = 1: only #busId was given, therefore the currentChannel must be empty to match (events directly from bus instance)
        //selector count > 1: a channel was given, so we have to compare agains the currentChannel of the target
        return selector.length === 1 && !target.currentChannel || selector[1] === target.currentChannel;
    }
});