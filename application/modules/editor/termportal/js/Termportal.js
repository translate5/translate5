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

/***
 * Helper variables
 */
var termAttributeContainer=[],
    termEntryAttributeContainer=[],
    searchTermsResponse=[],
    requestFromSearchButton=false,
    languageDefinitionContent=[],//it is used to store the description definition for language
    KEY_TERM="term",
    KEY_TERM_ATTRIBUTES="termAttributes",
    KEY_TERM_ENTRY_ATTRIBUTES="termEntryAttributes",
    termGroupsCache=[];//cache the groups results

/***
 * If the parameter 'term' is given in the URL, we start a search directly.
 */
function checkDirectSearch() {
    var givenTerm = getUrlParamValue('term');
    if (givenTerm != '') {
        $('#search').val(givenTerm);
        $('#searchButton').click();
    }
}
function getUrlParamValue(paramName) {
    // https://davidwalsh.name/query-string-javascript
    paramName = paramName.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + paramName + '=([^&#]*)');
    var results = regex.exec(window.location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};

/***
 * On dropdown select function handler
 */
var selectItem = function (event, ui) {
    
    // if an item from the autocomplete.list has been selected and clicked via mouse, "update" search results first using THIS item
    var origEvent = event;
    while (origEvent.originalEvent !== undefined){
        origEvent = origEvent.originalEvent;
    }
    if (origEvent.type == 'click'){
        event.preventDefault();
        console.log("clicked item: " + ui.item.groupId);
        searchTerm(ui.item.label);
        requestFromSearchButton=true;
        return;
    }

    console.log("selectItem: " + ui.item.groupId);
    
    //empty term options component
    $('#searchTermsSelect').empty();

    //if there are results, show them
    if(searchTermsResponse.length>0){
        $('#searchTermsSelect').show();
        showFinalResultContent();
    }
    
    console.log("selectItem: " + ui.item.groupId);
    
    fillSearchTermSelect();
    //find the attributes for
    findTermsAndAttributes(ui.item.groupId);
    //$("#search").val(ui.item.value);
    return false;
}
 
$("#search").autocomplete({
    source: function(request, response) {
        searchTerm(request.term,function(data){
            response(data);
        })
    },
    select: selectItem,
    minLength: 3,
    focus: function(event, ui) {
        if (event.which != 0) {
            event.preventDefault();
            $(this).val(ui.item.label);
            console.log("autocomplete: FOCUS " + ui.item.label);
        }
    },
    change: function() {
        $("#myText").val("").css("display", 2);
    }
});

/***
 * Find all terms and terms attributes for the given term entry id (groupId)
 * @param termGroupid
 * @returns
 */
function findTermsAndAttributes(termGroupid){
    console.log("findTermsAndAttributes() for: " + termGroupid);
    languageDefinitionContent=[];
    
    //check the cache
    if(termGroupsCache[termGroupid]){
        drawTermEntryAttributes(termGroupsCache[termGroupid].rows[KEY_TERM_ENTRY_ATTRIBUTES]);
        groupTermAttributeData(termGroupsCache[termGroupid].rows[KEY_TERM_ATTRIBUTES]);
        return;
    }
    
    $.ajax({
        url: Editor.data.termportal.restPath+"termcollection/searchattribute",
        dataType: "json",
        type: "POST",
        data: {
            'groupId':termGroupid
        },
        success: function(result){
            //store the results to the cache
            termGroupsCache[termGroupid]=result;
            
            drawTermEntryAttributes(result.rows[KEY_TERM_ENTRY_ATTRIBUTES]);
            groupTermAttributeData(result.rows[KEY_TERM_ATTRIBUTES]);
        }
    })
}

/***
 * Search the term in the language and term collection
 * @param searchString
 * @param successCallback
 * @returns
 */
function searchTerm(searchString,successCallback){
    var lng=$('#languages').val();
    if(!lng){
        lng=$("input[name='languages']:checked").val();
    }
    console.log("searchTerm() for: " + searchString);
    console.log("searchTerm() for language: " + lng);
    searchTermsResponse=[];  
    $.ajax({
        url: Editor.data.termportal.restPath+"termcollection/search",
        dataType: "json",
        type: "POST",
        data: {
            'term':searchString,
            'language':lng,
            'collectionIds':collectionIds,
            'disableLimit':requestFromSearchButton
        },
        success: function(result){
            searchTermsResponse=result.rows[KEY_TERM];
            if(successCallback){
                successCallback(result.rows[KEY_TERM]);
            }
            fillSearchTermSelect();
        }
    });
}

/***
 * Fill up the term option component with the search results
 * @returns
 */
function fillSearchTermSelect(){
    if(!searchTermsResponse){
        
        $('#error-no-results').show();
        
        console.log("fillSearchTermSelect: nichts gefunden");
        
        if(requestFromSearchButton){
            requestFromSearchButton=false;
        }
        
        $("#finalResultContent").hide();
        return;
    }
    
    $('#error-no-results').hide();
    $('#searchTermsSelect').empty();
    console.log("fillSearchTermSelect: " + searchTermsResponse.length + " Treffer");

    if(requestFromSearchButton){
        console.log("fillSearchTermSelect: requestFromSearchButton");
        
        requestFromSearchButton=false;
        
        //if only one record, find the attributes and display them
        if(searchTermsResponse.length===1){
            console.log("fillSearchTermSelect: only one record => find the attributes and display them");
            findTermsAndAttributes(searchTermsResponse[0].groupId);
        }
        
        if(searchTermsResponse.length>0){
            showFinalResultContent();
        }
        
    }
    
    if(!$('#searchTermsSelect').is(":visible")){
        return;
    }
    
    if(searchTermsResponse.length > 1){
        $("#resultTermsHolder").hide();
    }
    
    //fill the term component with the search results
    $.each(searchTermsResponse, function (i, item) {
        $('#searchTermsSelect').append(
                $('<li>').attr('data-value', item.groupId).attr('class', 'ui-widget-content').append(
                        $('<div>').attr('class', 'ui-widget').append(item.desc)
            ));
    });
    
    if ($('#searchTermsSelect').hasClass('ui-selectable')) {
        $("#searchTermsSelect").selectable("destroy");
    }
    
    $("#searchTermsSelect").selectable();

    $("#searchTermsSelect li").mouseenter(function() {
        $(this).addClass('ui-state-hover');
      });
    $("#searchTermsSelect li").mouseleave(function() {
        $(this).removeClass('ui-state-hover');
      });
    $("#searchTermsSelect").on( "selectableselecting", function( event, ui ) {
        $(ui.selecting).addClass('ui-state-active');
    });
    $("#searchTermsSelect").on( "selectableunselecting", function( event, ui ) {
        $(ui.unselecting).removeClass('ui-state-active');
    });
    $("#searchTermsSelect").on( "selectableselected", function( event, ui ) {
        $(ui.selected).addClass('ui-state-active');
    });
    
    if(searchTermsResponse.length==1){
        $("#searchTermsSelect li:first-child").addClass('ui-state-active').addClass('ui-selected');
    }
    
    // "reset" search form
    $("#search").autocomplete( "search", $("#search").val('') );
}

/***
 * Fill the termAttributeContainer grouped by term.
 * 
 * @param data
 * @returns
 */
function groupTermAttributeData(data){
    if(!data || data.length<1){
        return;
    }
    
    
    termAttributeContainer=[];
    var oldKey="",
        newKey="",
        count=-1;
    
    for(var i=0;i<data.length;i++){
        newKey = data[i].termId;
        if(newKey !=oldKey){
            count++;
            termAttributeContainer[count]=[];
        }        
        termAttributeContainer[count].push(data[i]);
        oldKey = data[i].termId
    }
    
    //merge the childs
    termAttributeContainer.forEach(function(termData,index) {
        termAttributeContainer[index]=groupChildData(termData);
    });
    
    //draw the term groups
    drawTermGroups();
}

function getLanguageFlag(rfcLanguage) {
    rfcLanguage = rfcLanguage.toLowerCase();
    if (rfcLanguage in rfcLanguageFlags) {
        return '<img src="' + moduleFolder + 'images/flags/' + rfcLanguageFlags[rfcLanguage] + '.png" alt="' + rfcLanguage + '" title="' + rfcLanguage + '">';
    } else {
        return rfcLanguage;
    }
}

/***
 * Draw the tearm groups
 * @returns
 */
function drawTermGroups(){
    if(!termAttributeContainer || termAttributeContainer.length<1){
        return;
    }
    $('#termTable').empty();
    $("#resultTermsHolder").show();
    var count=0,
        rfcLanguage;
    termAttributeContainer.forEach(function(attribute) {
        rfcLanguage = getLanguageFlag(attribute[0].language);
        
        //check if the term contains attribute with status icon
        var statusIcon=checkTermStatusIcon(attribute);
        
        $('#termTable').append( '<h3 data-term-value="'+attribute[0].desc+'">'+ rfcLanguage + ' ' + attribute[0].desc +' '+(statusIcon ? statusIcon : '') + '</h3><div>' + this.drawTermAttributes(count) + '</div>' );
        count++;
    });
    if ($('#termTable').hasClass('ui-accordion')) {
        $('#termTable').accordion('refresh');
    } else {
        $("#termTable").accordion({
            active: false,
            collapsible: true,
            heightStyle: "content"
        });
    }
    
    //find the selected item form the search result and expand it
    $.each($("#searchTermsSelect li"), function (i, item) {
        if($(item).hasClass('ui-state-active')){
            $('#termTable').accordion({
                active:false
            });
            
            $.each($("#termTable h3"), function (i, termitem) {
                if(termitem.dataset.termValue === item.textContent){
                    $('#termTable').accordion({
                        active:i
                    });
                }
            });
            
        }
    });
    
    setSizesInFinalResultContent();
}

/***
 * Draw the term entry groups
 * @param entryAttribute
 * @returns
 */
function drawTermEntryAttributes(entryAttribute){
    if(!entryAttribute || entryAttribute.length<1){
        return;
    }
    $('#termAttributeTable').empty();
    $("#resultTermsHolder").show();
    
    entryAttribute = groupChildData(entryAttribute);
    
    entryAttribute.forEach(function(attribute) {
        var drawData=handleAttributeDrawData(attribute);
        $('#termAttributeTable').append(drawData);
    });
}

/***
 * Render term attributes by given term
 * 
 * @param termId
 * @returns {String}
 */
function drawTermAttributes(termId){
    var attributes=termAttributeContainer[termId]
        html = '',
        tmpLang=attributes[0].language;
    
    if(languageDefinitionContent[tmpLang]){
        html +=languageDefinitionContent[tmpLang];
    }
    
    attributes.forEach(function(attribute) {
        html +=handleAttributeDrawData(attribute);
    });
    return html;
}

/***
 * Group childs by parent id to the nodes
 * 
 * @param list
 * @returns
 */
function groupChildData(list) {
    var map = {}, node, roots = [], i,labelTrans;
    for (i = 0; i < list.length; i += 1) {
        map[list[i].attributeId] = i; // initialize the map
        list[i].children = []; // initialize the children
    }
    
    for (i = 0; i < list.length; i += 1) {
        node = list[i];
        labelTrans = $.grep(attributeLabels, function(e){ return e.id == node.labelId; });
        
        node.headerText=null;
        if(labelTrans.length==1 && labelTrans[0].labelText){
            node.headerText=labelTrans[0].labelText;
        }
        if (node.parentId !== null) {
            // if you have dangling branches check that map[node.parentId] exists
            list[map[node.parentId]].children.push(node);
        } else {
            roots.push(node);
        }
    }
    return roots;
}

/***
 * Find child's for the attribute, and build the data in needed structure
 *  
 * @param attribute
 * @returns html
 */
function handleAttributeDrawData(attribute){
    var html="";
    
    if(!attribute.attributeId){
        return noExistingAttributes; // see /application/modules/editor/views/scripts/termportal/index.phtml
    }
    
    switch(attribute.name) {
        case "transac":
            
            
            var header=handleAttributeHeaderText(attribute,true);
            
            html += '<h4 class="ui-widget-header ui-corner-all">' + header + '</h4>';
            
            if(attribute.children.length>0){
                var childData=[];
                attribute.children.forEach(function(child) {
                    //get the header text
                    childDataText=handleAttributeHeaderText(child,true);
                    
                    var attVal=getAttributeValue(child);
                    
                    //the data tag is displayed as first in this group
                    if(child.name ==="date"){
                        childData.unshift('<p>' + childDataText + ' ' + attVal+'</p>')
                        return true;
                    }
                    childData.push('<p>' + childDataText + ' ' + attVal+'</p>')
                });
                html+=childData.join('');
            }
            break;
        case "descrip":
            
            var attVal=getAttributeValue(attribute);

            var flagContent="";
            //add the flag for the definition in the term entry attributes
            if(attribute.attrType=="definition" && attribute.language){
                flagContent=" "+getLanguageFlag(attribute.language);
            }

            var headerText="";
            
            if(flagContent && flagContent!=""){
                headerText =handleAttributeHeaderText(attribute,false)+flagContent;
            }else{
                headerText =handleAttributeHeaderText(attribute)+":";
            }
            
            html='<h4 class="ui-widget-header ui-corner-all">' + headerText + '</h4>' + '<p>' + attVal + '</p>';
            
            //if it is definition on language level, get store the data in variable so it is displayed also on term language level
            if(attribute.attrType=="definition" && attribute.language){
                languageDefinitionContent[attribute.language]="";
                if(attribute.children.length>0){
                    attribute.children.forEach(function(child) {
                        html+=handleAttributeDrawData(child);
                    });
                }
                
                //remove the flag from the html which will be displayed in the term
                languageDefinitionContent[attribute.language]=html.replace(flagContent,'');
            }
            
            break;
        default:
            
            var attVal=getAttributeValue(attribute);
            
            var headerText = handleAttributeHeaderText(attribute,true);
            
            html='<h4 class="ui-widget-header ui-corner-all">' + headerText + '</h4>' + '<p>' + attVal + '</p>';
            break;
    }
    return html;
}

/***
 * Get the value from the attribute. Replace the line break with br tag.
 * @param attribute
 * @returns
 */
function getAttributeValue(attribute){
    var attVal=attribute.attrValue ? attribute.attrValue : "";
    //if it is a date attribute, handle the date format
    if(attribute.name=="date"){
        var dateFormat='dd.mm.yy';
        if(SESSION_LOCALE=="en"){
            dateFormat='mm/dd/yy';
        }
        return $.datepicker.formatDate(dateFormat, new Date(attVal*1000));
    }
    if (attribute.attrType == "processStatus" && attVal == "finalized") {
        return '<img src="' + moduleFolder + 'images/tick.png" alt="finalized" title="finalized">';
    }else if(attribute.attrType == "processStatus" && attVal == "provisionallyProcessed"){
    	return "-";
    } else {
        return attVal.replace(/$/mg,'<br>');
    }
}
/***
 * Build the attribute text, based on if headerText (db translation for the attribute) is provided
 * @param attribute: entry or term attribute
 * @param addColon: add colon on the end of the header text
 * @returns
 */
function handleAttributeHeaderText(attribute,addColon){
    
    var attVal=getAttributeValue(attribute);
    
    var noHeaderName=attribute.name + (attribute.attrType ? (" "+attribute.attrType) : "");
    
    //if no headerText use attribute name + if exist attribute type
    var headerText=attribute.headerText ? attribute.headerText :  noHeaderName;
    
    return headerText+ (addColon ? ":" : "");
}

/***
 * Check the term status icon in the term attributes.
 * Return the image html if such an attribute is found
 * @param attribute
 * @returns
 */
function checkTermStatusIcon(attribute){
    var retVal="", 
        status = 'unknown', 
        map = Editor.data.termStatusMap,
        labels = Editor.data.termStatusLabel,
        label;
    $.each($(attribute), function (i, attr) {
        var statusIcon=getAttributeValue(attr),
        	cmpStr='<img src="';
        if(statusIcon && statusIcon.slice(0, cmpStr.length) == cmpStr){
            retVal+=statusIcon;
        }
    });
    if(map[attribute[0].termStatus]) {
        status = map[attribute[0].termStatus];
    }
    //FIXME encoding of the string!
    label = labels[status]+' ('+attribute[0].termStatus+')';
    retVal += ' <img src="' + moduleFolder + 'images/termStatus/'+status+'.png" alt="'+label+'" title="'+label+'">';
;
    return retVal;
}

$("#searchButton" ).button({
    icon:"ui-icon-search"
}).click(function(){
    requestFromSearchButton=true;
    //startAutocomplete();
    searchTerm($("#search").val());
});

$('#search').keyup(function (e) {
    if (e.which == 13) {
      console.log("keyup: Enter");
      requestFromSearchButton=true;
      //startAutocomplete();
      searchTerm($("#search").val());
      return false;
    }
    console.log("keyup");
    termAttributeContainer=[];
    termEntryAttributeContainer=[];
    searchTermsResponse=[];
    requestFromSearchButton=false;

    languageDefinitionContent=[];
    termGroupsCache=[];
    
    $('#finalResultContent').hide();
    $('#searchTermsSelect').empty();
    $('#termAttributeTable').empty();
    $('#termTable').empty();
});

$('#instantTranslateButton').on('touchstart click',function(){
    window.parent.loadIframe('instanttranslate',Editor.data.termportal.restPath+'instanttranslate');
});

function startAutocomplete(){
    console.log("startAutocomplete...");
    $('#finalResultContent').hide();
    $('#searchTermsSelect').empty();
    $('#termAttributeTable').empty();
    $('#termTable').empty();
    $("#search").autocomplete( "search", $("#search").val());
}

function showFinalResultContent() {
    $('#finalResultContent').show();
    setSizesInFinalResultContent();
}
