
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
 * @class Editor.plugins.pluginFeasibilityTest.view.Panel
 * @extends Ext.panel.Panel
 */
Ext.define('Editor.plugins.mtComparEval.view.Panel', {
    extend : 'Ext.panel.Panel',
    alias : 'widget.mtComparEvalPanel',
    title : 'MT-ComparEval',
    frame: true,
    padding: 10,
    items:[{
        xtype: 'container',
        html: 'Task not sent yet to MT-ComparEval',
        itemId: 'resultBox'
    },{
        xtype: 'button',
        itemId: 'sendto',
        text: 'Send Task to MT-ComparEval'
    },{
        xtype: 'container',
        html: '<h2>Usage</h2>\
            MT-ComparEval is a third-party tool integrated in translate5 as a plug-in.<br />\
            <br />\
            MT-ComparEval is a tool for comparison and evaluation of machine<br />\
            translations. It allows users to compare translations according to<br />\
            several criteria.<br />\
            <br />\
            To use MT-ComparEval in combination with translate5, you need a<br />\
            translate5-task with at least 3 different different translations for the<br />\
            same source. The first translation is handled as reference language in<br />\
            MT-ComparEval and all following translations as output of different<br />\
            MT-engines.<br />\
            <br />\
            Please search <a href="http://confluence.translate5.net">http://confluence.translate5.net</a> for information on<br />\
            importing multi-column CSV-files and the MT-ComparEval plug-in.'
    }]
});