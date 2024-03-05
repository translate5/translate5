/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

Ext.define('Editor.plugins.MatchAnalysis.view.AnalysisPanelViewController', {
    extend: 'Ext.app.ViewController',
    alias: 'controller.matchAnalysisPanel',

    onMatchAnalysisPanelActivate:function(){
        var me = this,
            view = me.getView(),
            grid = view && view.down('matchAnalysisGrid'),
            store = grid && grid.getStore();

        //save only when taskGuid given to prevent E1103 editor_Plugins_MatchAnalysis_Exception
        store && 'taskGuid' in store.getProxy().getExtraParams() && store.load();
    }
});