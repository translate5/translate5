
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
 * The model for a quality filter entry
 * This involves specialized checked propagation methods
 */
Ext.define('Editor.model.quality.Filter', {
    extend: 'Ext.data.Model',
    fields: [
        { name:'qid', type:'int' },
        { name:'qtype', type:'string' },
        { name:'qcount', type:'int', defaultValue:0 },
        { name:'qtotal', type:'int', defaultValue:0 },
        { name:'qcategory', type:'string', defaultValue:'---' },
        { name:'qcatidx', type:'int', defaultValue:-1 },
        { name:'qcomplete', type:'boolean', defaultValue:true },
        { name:'qfaulty', type:'boolean', defaultValue:false }
    ],
    /**
     * Note that this is a virtual root (= the first child of the store root !)
     */
    isQualityRoot: function(){
        return (this.get('qtype') == 'root');
    },
    /**
     * Means that we are no quality root nor a rubric but have connected segments
     */
    isQuality: function(){
        return (this.get('qcategory') != '' && this.get('qtype') != 'root');
    },
    isRubric: function(){
        return (this.get('qcategory') == '' && this.get('qtype') != 'root');
    },
    isEmptyRubric: function(){
        return (this.isRubric() && this.get('qtotal') == 0);
    },
    isIncomplete: function(){
        return (this.get('qcomplete') == false);
    },
    isFaulty: function(){
        return (this.get('qfaulty') == true);
    },
    propagateChecked: function(checked){
        var qualityRoot = this.isQualityRoot();
        if(qualityRoot || this.isRubric()){
            this.propagateCheckedDown(checked);
        }
        if(!qualityRoot && this.parentNode){
            this.parentNode.propagateCheckedUp(checked);
        }
    },
    /**
     * Propagates the checked state down
     */
    propagateCheckedDown: function(checked){
        if(this.childNodes && this.childNodes.length > 0){
            for(var i=0; i < this.childNodes.length; i++){
                if(!this.childNodes[i].isEmptyRubric()){
                    this.childNodes[i].set('checked', checked);
                    this.childNodes[i].propagateCheckedDown(checked);
                }
            }
        }
    },
    /**
     * Propagate the checked state up
     */
    propagateCheckedUp: function(checked){
        if(checked){
            var rubric = this.isRubric();
            if((this.isQualityRoot() || rubric) && this.childNodes && this.childNodes.length > 0){
                if(this.allChildrenChecked(rubric, (rubric ? this.internalId : null))){
                    this.set('checked', checked);
                }
            }
        } else {
            if(this.get('checked') && !this.isQuality()){
                this.set('checked', false);
            } 
        }
        if(!this.isQualityRoot() && this.parentNode){
            this.parentNode.propagateCheckedUp(checked);
        }
    },
    /**
     * Check, if our children are all checked id prevents clicking on a rubric additional check
     */
    allChildrenChecked: function(deep, id){
        if(this.childNodes && this.childNodes.length > 0){
            for(var i=0; i < this.childNodes.length; i++){
                if(!this.childNodes[i].isEmptyRubric() && id !== this.childNodes[i].internalId){
                    if(!this.childNodes[i].get('checked') || (deep && !this.childNodes[i].allChildrenChecked(deep, id))){
                        return false;
                    }
                }
            }
        }
        return true;
    }
});
