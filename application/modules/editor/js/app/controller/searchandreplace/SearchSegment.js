
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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class SearchSegment
 * @extends Ext.app.Controller
 */
Ext.define('Editor.controller.searchandreplace.SearchSegment', {
    extend : 'Ext.app.Controller',
    
    /***
     * 1. prepare the string
     * -- clean the allready existing mark tags
     * -- remove content between del tags
     * 
     */
    
    searchString:null,
    searchStringOriginal:null,
    searchKey:null,
    searchStringArray:[],
    matchIndexes:[],

    cleanString:function(){
        var me=this;
        
        //clean the mark tags if there are some
        me.searchString.replace(/<mark[^>]*>+|<\/mark>/g, "");
        
        //me.searchString.replace(/<del(\s[^>]*)?>.*?<\/del>/ig, "");
    },
    
    disassemble:function(){
        var me=this,
            hit=0,
            ignoreString='',
            hitString='';
        
        console.log(me.searchString);
        for(var i=0;i<me.searchString.length;i++){
            var tmpChar=me.searchString[i];
            if(tmpChar==="<"){
                hit++;
                if(hitString!==""){
                    me.searchStringArray.push(hitString);
                    hitString="";
                    ignoreString+=tmpChar;
                    continue;
                }
            }
            if(tmpChar===">"){
                hit--;
                if(hit===0){
                    ignoreString+=tmpChar;
                    me.searchStringArray.push(ignoreString);
                    ignoreString="";
                    continue;
                }
            }
            if(hit>0){
                ignoreString+=tmpChar;
            }else{
                hitString+=tmpChar;
            }
        }
console.log(me.searchStringArray);       
        var outputString="";
        for(var i=0;i<me.searchStringArray.length;i++){
            var tmpString=me.searchStringArray[i];
            if(!tmpString.startsWith('<')){
                outputString+=tmpString;
            }
        }
        
        me.searchString=outputString;
        
        console.log(me.searchString);
    },
    
    findSearch:function(){
        var me=this,
            searchRegExp = new RegExp(me.searchKey, 'g');

        while (match = searchRegExp.exec(me.searchString)) {
          //console.log(match.index + ' ' + patt.lastIndex);
            me.matchIndexes.push([match.index,searchRegExp.lastIndex]);
        }
        
        console.log(me.matchIndexes);
    },
    
    assemble:function(){
        var me=this,
            markPointer=0,
            offset=0,
            startAdded=false;
            
        
        for(var i=0;i<me.searchStringArray.length;){
            var item=me.searchStringArray[i],
                itemLength=item.length,
                markLocation=me.matchIndexes[markPointer],
                markStart=markLocation[0],
                markEnd=markLocation[1];
            
            
            debugger;
            if(!startAdded){
                if(item.startsWith('<')){
                    offset+=itemLength;
                    i++;
                    continue;
                }
                if(markStart>itemLength){
                    offset+=itemLength;
                    i++;
                    continue;
                }
                
                item=item.substr(0, markStart-1) + "<mark>" + item.substr(markStart-1);
                //me.searchStringArray[i]=item;
                me.searchStringOriginal=me.searchStringOriginal.substr(0, offset+markStart-1) + "<mark>" + me.searchStringOriginal.substr(offset+markStart-1);
                offset+=6;
                startAdded=true;
            }else{
                if(item.startsWith('<')){
                    offset+=itemLength;
                    i++;
                    continue;
                }
                if(markEnd>itemLength){
                    offset+=itemLength;
                    i++;
                    continue;
                }
                item=item.substr(0, markEnd-1) + "<mark>" + item.substr(markEnd-1);
                //me.searchStringArray[i]=item;
                me.searchStringOriginal=me.searchStringOriginal.substr(0, offset+markEnd-1) + "<mark>" + me.searchStringOriginal.substr(offset+markEnd-1);
                offset+=6;
                startAdded=false;
                markPointer++;
            }
            return;
            //THERE IS A PROVLEM IN THE ALGO, WHEN DO I NEED TO SWITHC TO THE NEXT ITEM, AND HOW TO FIND THE INDEX
            debugger;
            var tmpString="";
            if(!startAdded){
                if(item.startsWith('<')){
                    i++;
                    continue;
                }
                tmpString+=item;
                
                if(!tmpString[markStart] || tmpString[markStart]<0){
                    i++;
                    continue;
                }
                
                item=item.substr(0, markStart-1) + "<mark>" + item.substr(markStart-1);
                //me.searchStringArray[i]=item;
                me.searchStringOriginal=me.searchStringOriginal.substr(0, offset+markStart-1) + "<mark>" + me.searchStringOriginal.substr(offset+markStart-1);
                offset+=6;
                startAdded=true;
            }else{
                if(item.startsWith('<')){
                    i++;
                    continue;
                }
                tmpString+=item;
                
                if(!tmpString[markEnd] || tmpString[markEnd]<0){
                    i++;
                    continue;
                }
                
                item=item.substr(0, markEnd-1) + "<mark>" + item.substr(markEnd-1);
                //me.searchStringArray[i]=item;
                offset+=6;
                startAdded=false;
                markPointer++;
            }
        }
        
        console.log(me.searchStringOriginal);
    },
    
    search:function(searchString,searchKey){
        var me=this;
        
        //reset all variables
        me.reset();
        
        me.searchString=searchString;
        me.searchStringOriginal=searchString;
        me.searchKey=searchKey;
        
        me.cleanString();
        debugger;
        me.disassemble();
        me.findSearch();
        me.assemble();
        return;
        debugger;
        var str = 'CSS code formatter CSS code compressor alex new mitrev ace ';
        var searchRegExp = new RegExp(searchString, 'g');

        while (match = searchRegExp.exec(str)) {
          console.log(match.index + ' ' + patt.lastIndex);
        }
        
    },
    
    reset:function(){
        var me=this;
        me.searchString=null;
        me.searchKey=null;
        me.searchStringArray=[];
        me.matchIndexes=[];
    }
    
});
    