
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

Ext.define('Editor.plugins.Okapi.view.filterDetails.BconfFilterWindow', {
    extend: 'Ext.window.Window',
    requires: [
        'Editor.plugins.Okapi.view.filterDetails.BconfFilterWindowController',
        'Editor.plugins.Okapi.view.filterDetails.BconfFilterGrid'
    ],
    autoDestroy: true,
    controller: 'bconfFilterWindowController',
    maximized: true,
    autoHeight: true,
    autoScroll: true,
    modal: true,
    header: true,
    closeAction: 'destroy',
    layout: 'fit',
    items: [{
        xtype: 'bconfFilterGrid',
        title: 'filter',
        margin: 5,
        width: 800
    }
        // {
        //     xtype: 'panel',
        //     title: 'option',
        //     margin: 5,
        //     split: true,
        //     width:600,
        //     height:600,
        //     collapsible: true,
        //     region:'center',
        //     collapseDirection: 'left',
        // }
    ]
});