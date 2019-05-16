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

function addSearchFilter(tagLabel, filterValue, filterName) {
    // TODO: handle selections of filterValue "all" (= remove filter)
    if ($('#searchFilterTags input[name="'+filterName+'"].filter').length) {
        var tagLabelOld = $('#searchFilterTags input[name="'+filterName+'"].filter').val();
        $("#searchFilterTags").tagit("removeTagByLabel", tagLabelOld);
        $('#searchFilterTags input[name="'+filterName+'"].filter').remove();
    }
    $('#searchFilterTags').tagit("createTag", tagLabel);
    $('#searchFilterTags').append('<input type="hidden" value="'+filterValue+'" name="'+filterName+'" class="filter '+filterName+' '+tagLabel+'">');
}

function showFinalResultContent() {
    $('#finalResultContent').show();
    setSizesInFinalResultContent();
}
