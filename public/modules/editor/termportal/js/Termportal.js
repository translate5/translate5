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
    if (givenText !== '') {
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
    if (origEvent.type === 'click'){
        event.preventDefault();
        console.log('clicked item: ' + ui.item.termEntryId);
        Term.searchTerm(ui.item.label);
        Term.disableLimit=true;
        return;
    }

    console.log('selectItem: ' + ui.item.termEntryId);
    
    //empty term options component
    $('#searchTermsSelect').empty();

    //if there are results, show them
    if(Term.searchTermsResponse.length>0){
        $('#searchTermsSelect').show();
        showFinalResultContent();
    }
    
    console.log('selectItem: ' + ui.item.termEntryId);

    Term.fillSearchTermSelect(ui.item.label);

    //find the attributes for
    Term.findTermsAndAttributes(ui.item.termEntryId);
    //$('#search').val(ui.item.value);
    return false;
};
 
$('#search').autocomplete({
    source: function(request, response) {
        Term.searchTerm(request.term,function(data){
            response(data);
        });
    },
    select: selectItem,
    minLength: 3,
    focus: function(event, ui) {
        if (event.which !== 0) {
            event.preventDefault();
            $(this).val(ui.item.label);
            console.log('autocomplete: FOCUS ' + ui.item.label);
        }
    },
    change: function() {
        $('#myText').val('').css('display', 2);
    }
});

function getLanguageFlag(rfcLanguage) {
	var rfcValue = rfcLanguage.toLowerCase();
    if (rfcValue in Editor.data.apps.termportal.rfcFlags) {
    	if(Editor.data.apps.termportal.rfcFlags[rfcValue] === ''){
    		return '<span class="noFlagLanguage">'+rfcLanguage+'</span>';
    	}
    	var langTextValue = '<span class="noFlagLanguage termPortalLanguageRfcLabel">'+rfcLanguage+'</span>';
        // TODO: img-html could be reused if already created before
        return  langTextValue + ' <img src="' + moduleFolder + 'images/flags/' + Editor.data.apps.termportal.rfcFlags[rfcValue] + '.png" alt="' + rfcValue + '" title="' + rfcValue + '">';
    }
    if(!rfcValue || rfcValue == ''){
    	return '<span class="noFlagLanguage">'+rfcLanguage+'</span>';
    }
    //check if it is comma separated ids
    rfcValue=rfcValue.split(',');
    if(!rfcValue || rfcValue.length === 0){
    	return '<span class="noFlagLanguage">'+rfcLanguage+'</span>';
    }
    rfcValue=rfcValue[0];
    //it is comma separated, try to find it
    if (rfcValue in Editor.data.apps.termportal.idToRfcLanguageMap) {
    	var flagRfc=Editor.data.apps.termportal.idToRfcLanguageMap[rfcValue];
    	flagRfc=flagRfc.toLowerCase();
        return '<img src="' + moduleFolder + 'images/flags/' + Editor.data.apps.termportal.rfcFlags[flagRfc] + '.png" alt="' + flagRfc + '" title="' + flagRfc + '">';
    }
    
    return '<span class="noFlagLanguage">'+rfcLanguage+'</span>';
}

$('#searchButton').button({
    icon:'ui-icon-search'
}).click(function(){
	Term.disableLimit=true;
    //startAutocomplete();
    Term.searchTerm($('#search').val());
});

$('#search').keyup(function (e) {
    if (e.which === 13) {
      console.log('keyup: Enter');
      Term.disableLimit=true;
      //startAutocomplete();
      Term.searchTerm($('#search').val());
      return false;
    }
    console.log('keyup');
    Term.termAttributeContainer=[];
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
    console.log('startAutocomplete...');
    $('#searchTermsSelect').empty();
    $('#termEntryAttributesTable').empty();
    $('#termTable').empty();
    $('#search').autocomplete('search', $('#search').val());
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
        localeParts = locale.split('-');
        locale = localeParts[0];
    }
    return locale;
}

/**
 * Add a language to the languageselect for searching terms.
 * - Checks if the language already exists.
 * - Sorts the list alphabetically.
 * - Keeps the selected option.
 * @param {String} languageId
 * @param {String} languageRfc5646
 */
function addLanguageToSelect(languageId,languageRfc5646) {
    var $langSel = $('#language'), 
        selected, opts_list,
        langExist=false;
    
    //check if the given language exist as option in the sellect
    $('#language option').each(function() {
    	var allVals=$(this).val().split(',');
    	if(langExist){
    		return false;
    	}
    	langExist=$.inArray(languageId,allVals)!== -1;
    });
    
    //do not add the language as option if the language exist as sellect
    if (langExist) {
        return;
    }
    
    $langSel.append('<option value="'+languageId+'">'+languageRfc5646+'</option>');
    // https://stackoverflow.com/a/26232541
    selected = $langSel.val();
    opts_list = $langSel.find('option');
    opts_list.sort(function(a, b) { return $(a).text() > $(b).text() ? 1 : -1; });
    $langSel.html('').append(opts_list);
    $langSel.val(selected);
    $langSel.selectmenu('refresh');
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
 * Make the filter field not editable. Adding filter tags should only be possible
 * through the drop-downs. Yet of course remove tags by clicking on them should
 * still be possible (=> tagit's "readonly" is not sufficient here).
 */
$('#searchFilterTags').keydown(function () {
    return false;
});

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
 * @param {Integer} index (= in dropdown-select-list)
 */
function addSearchFilter(dropdownId, text, value, index) {
    var tagLabel = this.renderTagLabel(text),
        $searchFilterTags = $('#searchFilterTags');
    // reset dropdown
    $('#'+dropdownId).val('none');
    $('#'+dropdownId).selectmenu('refresh');
    // add hidden input
    if ($searchFilterTags.children('input[name="'+tagLabel+'"][value="'+value+'"].filter.'+dropdownId).length === 0) {
        $searchFilterTags.append('<input type="hidden" class="filter '+dropdownId+'" name="'+tagLabel+'" value="'+value+'" data-index="'+index+'">');
    }
    // add tag field
    $searchFilterTags.tagit('createTag', tagLabel);
}

/**
 * When a user removes a tag-field, we also need to remove it's corresponding hidden input.
 * The tag-field itself will be removed by the tag-it-library.
 * @param {String} tagLabel
 */
function beforeFilterTagRemoved(tagLabel) {
    // remove hidden input
    $('#searchFilterTags input.filter' ).each(function( index, el ) {
        if (el.name === tagLabel) {
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
        $('#collection').selectmenu('refresh');
        return;
    }
    $('#collection option' ).each(function( index, el ) {
        if (el.value === undefined || el.value === 'none') {
            return; // "continue"
        }
        showCollection = false;
        clientsForCollection = collectionsClients[el.value];
        jQuery.each(clientsForCollection, function( i, val ) {
            // is any of the collection's customers currently selected?
            if(selectedClients.indexOf(val.toString()) !== -1) {
                showCollection = true;
                return false; // "break"
            }
          });
        // (not) disable select-item
        $(this).attr('disabled', !showCollection);
        // remove from tag-field
        if(!showCollection) {
            tagLabel = $(this).text();
            if ($('#searchFilterTags').tagit('assignedTags').indexOf(tagLabel) !== -1) {
                $('#searchFilterTags').tagit('removeTagByLabel', tagLabel);
            }
        }
    });
    $('#collection').selectmenu('refresh');
}

/**
 * Show placeholder if no tag-field exists, hide otherwise.
 */
function handlePlaceholder() {
    var $searchFilterTags = $('#searchFilterTags');
    if ($searchFilterTags.tagit('assignedTags').length > 0) {
        $searchFilterTags.data('ui-tagit').tagInput.attr('placeholder', '');
    } else {
        $searchFilterTags.data('ui-tagit').tagInput.attr('placeholder', searchFilterPlaceholderText);
    }
}

/**
 * Return all the Term-Collections that are set in the tag field.
 * @returns {Array}
 */
function getFilteredCollections() {
    var $filteredCollections = $('#searchFilterTags input.filter.collection'),
        filteredCollectionsValues = [];
    if ($filteredCollections.length === 0 && this.getFilteredClients().length > 0) {
        // user has not selected any collections, but client(s) => use only those collections that belong to the client(s)
        $filteredCollections = $('#collection option:enabled');
    }
    $filteredCollections.each(function( index, el ) {
        if (el.value !== 'none') {
            filteredCollectionsValues.push(el.value);
        }
    });
    return filteredCollectionsValues;
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

/***
 * Show simple info message
 * @param message
 * @param title
 * @returns
 */
function showInfoMessage(message,title){
	var buttons={};
	buttons['Ok']=function(){
	    $(this).dialog('close');
	};
	// Define the Dialog and its properties.
	return $('#infoDialogWindow').dialog({
	    resizable: false,
	    modal: true,
	    title: title,
	    height: 300,
	    width: 450,
	    buttons:buttons
	}).text(message);
}
