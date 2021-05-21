
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
 * The model for a task quality entry
 */
Ext.define('Editor.model.quality.Task', {
    extend: 'Ext.data.Model',
    fields: [
        { name:'text', type:'string' },
        { name:'qid', type:'int' },
        { name:'qtype', type:'string' },
        { name:'qcategory', type:'string', defaultValue:'---' },
        { name:'qcount', type:'int', defaultValue:0 },
        { name:'qcountfp', type:'int', defaultValue:0 },
        { name:'qcomplete', type:'boolean', defaultValue:true },
        { name:'qfaulty', type:'boolean', defaultValue:false }
    ],
    isRubric: function(){
        return (this.get('qcategory') == '');
    },
    isIncomplete: function(){
        return (this.get('qcomplete') == false);
    },
    isFaulty: function(){
        return (this.get('qfaulty') == true);
    },
    /**
     * Helper to decorate the rows: a faulty rubric should be marked when it contains a faulty category
     */
    hasFaultyChildren: function(){
        for(var i=0; i < this.childNodes.length; i++){
            if(this.childNodes[i].isFaulty()){
                return true;
            }
        }
        return false;
    }
});
