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
        Term.searchTerm(ui.item.label);
        Term.disableLimit=true;
        return;
    }

    console.log("selectItem: " + ui.item.groupId);
    
    //empty term options component
    $('#searchTermsSelect').empty();

    //if there are results, show them
    if(Term.searchTermsResponse.length>0){
        $('#searchTermsSelect').show();
        showFinalResultContent();
    }
    
    console.log("selectItem: " + ui.item.groupId);
    
    Term.fillSearchTermSelect();
    //find the attributes for
    Term.findTermsAndAttributes(ui.item.groupId);
    //$("#search").val(ui.item.value);
    return false;
}
 
$("#search").autocomplete({
    source: function(request, response) {
        Term.searchTerm(request.term,function(data){
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

function getLanguageFlag(rfcLanguage) {
    rfcLanguage = rfcLanguage.toLowerCase();
    if (rfcLanguage in rfcLanguageFlags) {
        return '<img src="' + moduleFolder + 'images/flags/' + rfcLanguageFlags[rfcLanguage] + '.png" alt="' + rfcLanguage + '" title="' + rfcLanguage + '">';
    } else {
        return rfcLanguage;
    }
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

$("#searchButton" ).button({
    icon:"ui-icon-search"
}).click(function(){
	Term.disableLimit=true;
    //startAutocomplete();
    Term.searchTerm($("#search").val());
});

$('#search').keyup(function (e) {
    if (e.which == 13) {
      console.log("keyup: Enter");
      Term.disableLimit=true;
      //startAutocomplete();
      Term.searchTerm($("#search").val());
      return false;
    }
    console.log("keyup");
    Term.termAttributeContainer=[];
    termEntryAttributeContainer=[];
    Term.searchTermsResponse=[];
    Term.disableLimit=false;

    Attribute.languageDefinitionContent=[];
    Term.termGroupsCache=[];
    
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

/* ---------------------------------------------------------------------
// ------------------- handle tag fields and filters -------------------
//----------------------------------------------------------------------

The tags cannot handle values => use hidden fields, too.
(https://github.com/aehlke/tag-it/issues/266)

Example:
- dropdown:     id = "language" | text = "de" | value = "4,359"
- field-tag:    tagLabel = "language: de"
- hidden input: class = "filter language" | name = "language: de" | value = "4,359"

----------------------------------------------------------------------*/

function renderTagLabel(dropdownId, text) {
    return dropdownId + ': ' + text
}
function getDropdownId(tagLabel) {
    var dropdownId = tagLabel.split(': ',1);
    return dropdownId[0];
}

function handleAddSearchFilter(dropdownId, text, value) {
    var me = this,
        tagLabel = me.renderTagLabel(dropdownId, text);
    me.removeSearchFilter(dropdownId);
    if (value === "all") {
        return; // filterValue "all" = remove old filter only, don't set anew
    }
    me.addSearchFilter(tagLabel, dropdownId, value);
}

function addSearchFilter(tagLabel, dropdownId, value) {
    // synchronize dropdown
    $('#'+dropdownId).val(value);
    $('#'+dropdownId).selectmenu("refresh");
    // add hidden input
    $('#searchFilterTags').append('<input type="hidden" class="filter '+dropdownId+'" name="'+tagLabel+'" value="'+value+'">');
    // add tag field
    $('#searchFilterTags').tagit("createTag", tagLabel);
}

function removeSearchFilter(dropdownId) {
    var $_filterToRemove = $('#searchFilterTags .filter.'+dropdownId),
        tagLabelToRemove;
    if ($_filterToRemove.length > 0) {
        tagLabelToRemove = $_filterToRemove.attr('name');
        $("#searchFilterTags").tagit("removeTagByLabel", tagLabelToRemove);
        $_filterToRemove.remove();
    }
}

function beforeFilterTagRemoved(tagLabel) {
    var dropdownId = getDropdownId(tagLabel);
    // synchronize dropdown
    $('#'+dropdownId).val('all');
    $('#'+dropdownId).selectmenu("refresh");
    // remove hidden input
    $('#searchFilterTags input.filter.'+dropdownId).remove();
    // remove tag field: will be handled by tag-it
}

