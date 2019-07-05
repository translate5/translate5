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
 * If a text is already given, we start a search directly.
 */
function checkDirectSearch() {
    var givenText = $('#search').val();
    if (givenText != '') {
        $('#searchButton').click();
    }
}

function clearResults () {
    console.log('clearResults');
    $('#searchTermsSelect').empty();
    $('#error-no-results').hide();
    $('#warning-new-source').hide();
    $('#searchTermsHelper .skeleton').show();
}

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

    Term.fillSearchTermSelect(ui.item.label);
    
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
    	if(rfcLanguageFlags[rfcLanguage]==''){
    		return rfcLanguage;
    	}
        // TODO: img-html could be reused if already created before
        return '<img src="' + moduleFolder + 'images/flags/' + rfcLanguageFlags[rfcLanguage] + '.png" alt="' + rfcLanguage + '" title="' + rfcLanguage + '">';
    }
    return rfcLanguage;
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
    
    $('#error-no-results').hide();
    $('#searchTermsHelper').find('.proposal-txt').text(proposalTranslations['addTermEntryProposal']);
    $('#searchTermsHelper').find('.proposal-btn').prop('title', proposalTranslations['addTermEntryProposal']);
    $('#searchTermsSelect').empty();
    $('#termEntryAttributesTable').empty();
    $('#termTable').empty();
});

$('#instantTranslateButton').on('touchstart click',function(){
    window.parent.loadIframe('instanttranslate',Editor.data.termportal.restPath+'instanttranslate');
});

function startAutocomplete(){
    console.log("startAutocomplete...");
    $('#searchTermsSelect').empty();
    $('#termEntryAttributesTable').empty();
    $('#termTable').empty();
    $("#search").autocomplete( "search", $("#search").val());
}

function showFinalResultContent() {
    $('#resultTermsHolder').show();
    setSizesInFinalResultContent();
}

/**
 * Handle 'de-DE' vs. 'de' according to showSublanguages-config.
 * @param {String} locale
 * @returns {String}
 */
function checkSubLanguage(locale) {
    var localeParts;
    if (!Editor.data.instanttranslate.showSublanguages) {
        localeParts = locale.split("-");
        locale = localeParts[0];
    }
    return locale;
}

/* ---------------------------------------------------------------------
// ------------------- handle tag fields and filters -------------------
//----------------------------------------------------------------------

The tags cannot handle values => use hidden fields, too.
(https://github.com/aehlke/tag-it/issues/266)

Example:
- dropdown:     id = "client" | text = "client1" | value = "123"
- field-tag:    tagLabel = "client: client1"
- hidden input: class = "filter client" | name = "client: client1" | value = "123"

----------------------------------------------------------------------*/

/**
 * Render tagLabel from selected dropdown (e.g. "client1").
 * This is also used as name for corresponding hidden input.
 * @param {String} text
 * @returns {String}
 */
function renderTagLabel(text) {
    // Using the text only works as long the filtered never have the same text.
    return text;
}

/**
 * When a user has selected something from the dropdown, we 
 * - reset the dropdown (they only serve for choosing, not as chosen selection)
 * - add the tag-field
 * - add the hidden field
 * @param {String} dropdownId
 * @param {String} text
 * @param {String} value
 */
function addSearchFilter(dropdownId, text, value, index) {
    var tagLabel = this.renderTagLabel(text),
        $_searchFilterTags = $("#searchFilterTags");
    // reset dropdown
    $('#'+dropdownId).val('none');
    $('#'+dropdownId).selectmenu("refresh");
    // add hidden input
    if ($_searchFilterTags.children('input[name="'+tagLabel+'"][value="'+value+'"].filter.'+dropdownId).length === 0) {
        $_searchFilterTags.append('<input type="hidden" class="filter '+dropdownId+'" name="'+tagLabel+'" value="'+value+'" data-index="'+index+'">');
    }
    // add tag field
    $_searchFilterTags.tagit("createTag", tagLabel);
}

/**
 * When a user removes a tag-field, we also need to remove it's corresponding hidden input.
 * The tag-field itself will be removed by the tag-it-library.
 * @param {String} tagLabel
 */
function beforeFilterTagRemoved(tagLabel) {
    // remove hidden input
    $('#searchFilterTags input.filter' ).each(function( index, el ) {
        if (el.name == tagLabel) {
            el.remove();
        }
    });
    // remove tag field: will be handled by tag-it
}

/**
 * Don't show filtered items in the dropdown.
 */
function removeFilteredItemsFromDropdowns(dropdownId) {
    $( '#searchFilterTags input.filter.'+dropdownId).each(function( index, el ) {
        $('#'+dropdownId+'-menu li:eq('+$(el).attr('data-index')+')').addClass('isfiltered');
    });
}
/**
 * When a filtered item is removed from the tag-field, it must be re-added to the dropdown.
 */
function addFilteredItemToDropdown(tagLabel) {
    var $el,
        dropdownId;
    $('input.filter[name="'+tagLabel+'"]').each(function( index, el ) {
        $el = $(el);
        $el.removeClass('filter');
        dropdownId = $el.attr('class');
        $el.addClass('filter');
        $('#'+dropdownId+'-menu li:eq('+$el.attr('data-index')+')').removeClass('isfiltered');
    });
}

/**
 * Only show termCollections belonging to the selected clients.
 */
function checkFilterDependencies() {
    var selectedClients = [],
        clientsForCollection = [],
        showCollection,
        tagLabel;
    $('#searchFilterTags input.filter.client').each(function( index, el ) {
        selectedClients.push(el.value);
    });
    if(selectedClients.length === 0) {
        $('#collection option:disabled').attr('disabled', false);
        $('#collection').selectmenu("refresh");
        return;
    }
    $('#collection option' ).each(function( index, el ) {
        if (el.value == undefined || el.value == 'none') {
            return; // "continue"
        }
        showCollection = false;
        clientsForCollection = collectionsClients[el.value];
        jQuery.each(clientsForCollection, function( i, val ) {
            // is any of the collection's customers currently selected?
            if(selectedClients.indexOf(val.toString()) != -1) {
                showCollection = true;
                return false; // "break"
            }
          });
        // (not) disable select-item
        $(this).attr('disabled', !showCollection);
        // remove from tag-field
        if(!showCollection) {
            tagLabel = $(this).text();
            if ($("#searchFilterTags").tagit("assignedTags").indexOf(tagLabel) != -1) {
                $("#searchFilterTags").tagit("removeTagByLabel", tagLabel);
            }
        }
    });
    $('#collection').selectmenu("refresh");
}

/**
 * Show placeholder if no tag-field exists, hide otherwise.
 */
function handlePlaceholder() {
    var $_searchFilterTags = $("#searchFilterTags");
    if ($_searchFilterTags.tagit("assignedTags").length > 0) {
        $_searchFilterTags.data("ui-tagit").tagInput.attr("placeholder", "");
    } else {
        $_searchFilterTags.data("ui-tagit").tagInput.attr("placeholder", searchFilterPlaceholderText);
    }
}

/**
 * Return all the Term-Collections that are set in the tag field.
 * @returns {Array}
 */
function getFilteredCollections() {
    var $_filteredCollections = $('#searchFilterTags input.filter.collection'),
        filteredCollections = [];
    if ($_filteredCollections.length === 0 && this.getFilteredClients().length > 0) {
        // user has not selected any collections, but client(s) => use only those collections that belong to the client(s)
        $_filteredCollections = $("#collection option:enabled");
    }
    $_filteredCollections.each(function( index, el ) {
        if (el.value != 'none') {
            filteredCollections.push(el.value);
        }
    });
    return filteredCollections;
}

/**
 * Return all the clients that are set in the tag field.
 * @returns {Array}
 */
function getFilteredClients() {
    var filteredClients = [];
    $( '#searchFilterTags input.filter.client' ).each(function( index, el ) {
        filteredClients.push(el.value);
    });
    return filteredClients;
}

/**
 * Return all the prcoessStats that are set in the tag field.
 * @returns {Array}
 */
function getFilteredProcessStats() {
    var filteredProcessStats = [];
    $( '#searchFilterTags input.filter.processStatus' ).each(function( index, el ) {
        filteredProcessStats.push(el.value);
    });
    return filteredProcessStats;
}
