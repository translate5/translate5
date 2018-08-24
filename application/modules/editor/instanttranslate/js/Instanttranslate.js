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

var editIdleTimer = null,
    selectedEngineDomainCode = undefined,
    translateTextResponse = '';

/* --------------- start and terminate translations  ------------------------ */
$('#sourceText').bind('keyup', function() {
    // start instant (sic!) translation automatically
    startTimerForInstantTranslation();
});
$('#translationSubmit').click(function(){
    // start translation manually via button
    startTranslation();
    return false;
});
$('#instantTranslationIsOff').click(function(){
    startTranslation();
});
function startTimerForInstantTranslation() {
    terminateTranslation();
    if ($('#instantTranslationIsOn').is(":visible")) {
        editIdleTimer = setTimeout(function() {
            startTranslation();
        }, 200);
    }
}
function startTranslation() {
    // translate a file?
    if ($('#sourceText').not(":visible") && $('#sourceFile').is(":visible")) {
        alert('TODO...'); // TODO: translate files
        return;
    }
    // otherwise: translate Text
    if ($('#sourceText').val().length === 0) {
        $("#sourceIsText").addClass('source-text-error');
        hideTranslations();
        return;
    }
    terminateTranslation();
    translateText();
}
function terminateTranslation() {
    clearTimeout(editIdleTimer);
    editIdleTimer = null;
}

/* --------------- languages and MT-engines --------------------------------- */
$('#mtEngines').on('selectmenuchange', function() {
    var engineId = $(this).val();
    setSingleMtEngineById(engineId);
});
function setSingleMtEngineById(engineId) {
    var mtEngine = machineTranslationEngines[engineId];
    if(machineTranslationEngines[engineId] === undefined) {
        showMtEngineSelectorError('selectMt');
        return;
    }
    selectedEngineDomainCode = machineTranslationEngines[engineId].domainCode;
    clearMtEngineSelectorError();
    $("#sourceLocale").val(mtEngine.source).selectmenu("refresh");
    $("#targetLocale").val(mtEngine.target).selectmenu("refresh");
    $("#sourceLocale").click(); // render locale-lists!
    if ($('#sourceText').val().length > 0) {
        startTranslation(); // Google stops instant translations only for typing, not after changing the source- or target-language
    }
}
function renderMtEnginesAsAvailable() {
    var mtIdsAvailable,
        mtOptionList = [],
        sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val();
    if (sourceLocale == '-' || targetLocale == '-') {
        $('#mtEngines').selectmenu("widget").hide();
        return;
    }
    mtIdsAvailable = getMtEnginesAccordingToLanguages();
    switch(mtIdsAvailable.length) {
        case 0:
            $('#mtEngines').selectmenu("widget").hide();
            selectedEngineDomainCode = undefined;
            showMtEngineSelectorError('noMatchingMt');
            break;
        case 1:
            $('#mtEngines').selectmenu("widget").hide();
            setSingleMtEngineById(mtIdsAvailable[0]); // selectedEngineDomainCode is set there
            showTranslations();
            break;
        default:
            selectedEngineDomainCode = undefined;
            if (mtIdsAvailable.length > 1) {
                mtOptionList.push("<option id='PlsSelect'>"+mtIdsAvailable.length+" "+translatedStrings['foundMt']+":</option>");
                for (i = 0; i < mtIdsAvailable.length; i++) {
                    mtOptionList.push("<option value='" + mtIdsAvailable[i] + "'>" + machineTranslationEngines[mtIdsAvailable[i]].name + "</option>");
                }
                $('#mtEngines').find('option').remove().end();
                $('#mtEngines').append(mtOptionList.join(""));
                $('#mtEngines').selectmenu("refresh");
                $('#mtEngines').selectmenu("widget").show();
            }
    }
}
function getMtEnginesAccordingToLanguages() {
    var sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val(),
        mtIdsAvailable = [],
        engineId,
        mtEngineToCheck,
        langIsOK = function(langMT,langSet){
            if (langMT === langSet) {
                return true;
            }
            if (langSet === '-') {
                return true;
            }
            return false;
        };
    for (engineId in machineTranslationEngines) {
        if (machineTranslationEngines.hasOwnProperty(engineId)) {
            mtEngineToCheck = machineTranslationEngines[engineId];
            if (langIsOK(mtEngineToCheck.source,sourceLocale) && langIsOK(mtEngineToCheck.target,targetLocale)) {
                mtIdsAvailable.push(engineId); 
            }
        }
    }
    return mtIdsAvailable;
}

/* --------------- locales-list (source, target) ---------------------------- */
$('#sourceLocale').on('click', function() {
    renderLocalesAsAvailable('accordingToSourceLocale');
});
/**
 * Include languages for source/target only if there is an Mt-Engine available 
 * for the resulting source-target-combination.
 * - accordingToSourceLocale: sourceLocale is set, targetLocales are rendered
 * - accordingToTargetLocale: targetLocale is set, sourceLocales are rendered
 * - if the selected text is 'Clear both lists', then both lists are rendered
 * - if the selected text is 'Show all available for...', then this list is rendered
 * @param string accordingTo ('accordingToSourceLocale'|'accordingToTargetLocale')
 */
function renderLocalesAsAvailable(accordingTo) {
    var sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val(),
        selectedText = (accordingTo === 'accordingToSourceLocale') ? $( "#sourceLocale option:selected" ).text() : $( "#targetLocale option:selected" ).text(),
        localesAvailable = [],
        sourceLocalesAvailable = [],
        targetLocalesAvailable = [],
        sourceLocaleOptions = [],
        targetLocaleOptions = [],
        selectedLocale,
        source = {},
        target = {};
    $('#mtEngineSelectorError').hide();
    if (sourceLocale != '-' && targetLocale != '-') {
        // This means that the user has chosen a final combination of source AND target. The list contains only the selected locale and the "clear"-options.
        sourceLocalesAvailable.push(sourceLocale);
        targetLocalesAvailable.push(targetLocale);
        source = {hasPlsChoose: false, hasClearBoth: true,  hasShowAllAvailable: true,  localeForReference: targetLocale, selectedValue: sourceLocale};
        target = {hasPlsChoose: false, hasClearBoth: true,  hasShowAllAvailable: true,  localeForReference: sourceLocale, selectedValue: targetLocale};
    } else if (selectedText === translatedStrings['clearBothLists']) {
        // This means a "reset" of one or both the lists:
        sourceLocalesAvailable = mtSourceLanguageLocales;
        targetLocalesAvailable = mtTargetLanguageLocales;
        source = {hasPlsChoose: true,  hasClearBoth: false, hasShowAllAvailable: false, localeForReference: '',            selectedValue: '-'};
        target = {hasPlsChoose: true,  hasClearBoth: false, hasShowAllAvailable: false, localeForReference: '',            selectedValue: '-'};
    } else {
        if (selectedText === translatedStrings['showAllAvailableFor']+' '+targetLocale) {
            accordingTo = 'accordingToTargetLocale';
        } else if (selectedText === translatedStrings['showAllAvailableFor']+' '+sourceLocale) {
            accordingTo = 'accordingToSourceLocale';
        } 
        // "Default": update the locales according to what is set on the other side
        selectedLocale = (accordingTo === 'accordingToSourceLocale') ? sourceLocale : targetLocale;
        localesAvailable = getLocalesAccordingToReference (accordingTo, selectedLocale);
        if (accordingTo === 'accordingToSourceLocale') {
            sourceLocalesAvailable.push(sourceLocale);
            targetLocalesAvailable = localesAvailable;
            source = {hasPlsChoose: false, hasClearBoth: true, hasShowAllAvailable: false, localeForReference: '',        selectedValue: sourceLocale};
            target = {hasPlsChoose: true,  hasClearBoth: true, hasShowAllAvailable: false, localeForReference: '',        selectedValue: ''};
        } else {
            sourceLocalesAvailable = localesAvailable;
            targetLocalesAvailable.push(targetLocale);
            source = {hasPlsChoose: true,  hasClearBoth: true, hasShowAllAvailable: false, localeForReference: '',        selectedValue: ''};
            target = {hasPlsChoose: false, hasClearBoth: true, hasShowAllAvailable: false, localeForReference: '',        selectedValue: targetLocale};
        }
    }
    // render lists as set now:
    if (sourceLocalesAvailable.length > 0) {
        source.localeList = sourceLocalesAvailable;
        sourceLocaleOptions = renderLocaleOptionList(source);
        refreshSelectList('sourceLocale', sourceLocaleOptions, source.selectedValue);
    }
    if (targetLocalesAvailable.length > 0) {
        target.localeList = targetLocalesAvailable;
        targetLocaleOptions = renderLocaleOptionList(target);
        refreshSelectList('targetLocale', targetLocaleOptions, target.selectedValue);
    }
}
function getLocalesAccordingToReference (accordingTo, selectedLocale) {
    var localesAvailable = [],
        engineId,
        mtEngineToCheck,
        mtEngineLocaleSet,
        mtEngineLocaleToAdd;
    for (engineId in machineTranslationEngines) {
        if (machineTranslationEngines.hasOwnProperty(engineId)) {
            mtEngineToCheck = machineTranslationEngines[engineId];
            mtEngineLocaleSet = (accordingTo === 'accordingToSourceLocale') ? mtEngineToCheck.source : mtEngineToCheck.target;
            if (mtEngineLocaleSet === selectedLocale) {
                mtEngineLocaleToAdd = (accordingTo === 'accordingToSourceLocale') ? mtEngineToCheck.target : mtEngineToCheck.source;
                if ($.inArray(mtEngineLocaleToAdd, localesAvailable) === -1) {
                    localesAvailable.push(mtEngineLocaleToAdd); 
                }
            }
        }
    }
    return localesAvailable;
}
function renderLocaleOptionList(list) {
    var localeOptionsList = []
        option = {};
    if (list.hasPlsChoose) {
        option = {};
        option.value = '-';
        option.name = translatedStrings['pleaseChoose'] + ' ('+list.localeList.length+'):';
        localeOptionsList.push(option);
    }
    if (list.hasClearBoth) {
        option = {};
        option.value = '-';
        option.name = translatedStrings['clearBothLists'];
        localeOptionsList.push(option);
    }
    if (list.hasShowAllAvailable) {
        option = {};
        option.value = '-';
        option.name = translatedStrings['showAllAvailableFor']+' '+list.localeForReference;
        localeOptionsList.push(option);
    }
    for (i = 0; i < list.localeList.length; i++) {
        option = {};
        option.value = list.localeList[i];
        option.name = list.localeList[i];
        localeOptionsList.push(option);
    }
    return localeOptionsList;
}
function refreshSelectList(selectListId, options, selectedOptionValue) {
    var optionList = [];
    for (i = 0; i < options.length; i++) {
        optionList.push("<option value='" + options[i].value + "'>" + options[i].name + "</option>");
    }
    $('#'+selectListId).find('option').remove().end();
    $('#'+selectListId).append(optionList.join(""));
    if (selectedOptionValue != '') {
        $('#'+selectListId).val(selectedOptionValue);
    }
    $('#'+selectListId).selectmenu("refresh");
    $('#'+selectListId).selectmenu("widget").show();
}

/***
 * Return the domain code(if exist) of the set engine or false
 * @returns
 */
function getSelectedEngineDomainCode(){
    if(selectedEngineDomainCode === undefined) {
        return false; 
    }
    return selectedEngineDomainCode;
}

/* --------------- translation ---------------------------------------------- */
/***
 * Send a request for translation, this will return translation result for each associated language resource
 * INFO: in the current implementation only result from sdlcloud language will be returned
 * @returns
 */
function translateText(){
    if (getSelectedEngineDomainCode() === false) {
        console.log('translateText stopped; getSelectedEngineDomainCode FALSE!');
        return;
    }
    console.log('translateText mit domainCode: ' + getSelectedEngineDomainCode() + ' / source: ' + $("#sourceLocale").val() + ' / target: ' + $("#targetLocale").val() + ' / text: ' + $('#sourceText').val());
    $('#translations').html(renderTranslationContainer());
    showTranslations();
    $.ajax({
        statusCode: {
            500: function() {
                hideTranslations();
                showMtEngineSelectorError('serverErrorMsg500');
                }
        },
        url: REST_PATH+"instanttranslate/translate",
        dataType: "json",
        data: {
            'source':$("#sourceLocale").val(),
            'target':$("#targetLocale").val(),
            'domainCode':getSelectedEngineDomainCode(),
            'text':$('#sourceText').val()
        },
        success: function(result){
            clearMtEngineSelectorError(); // TODO: this repeats showTranslations from line 312
        	translateTextResponse = result.rows;
        	fillTranslation($("#mtEngines").val());
        }
    })
}

function renderTranslationContainer() {
    var translationsContainer = '';
    translationsContainer += '<div class="copyable">';
    translationsContainer += '<div class="translation-result" id="'+$("#mtEngines").val()+'"></div>';
    translationsContainer += '<span class="copyable-copy" title="'+translatedStrings['copy']+'"><span class="ui-icon ui-icon-copy"></span></span>';
    translationsContainer += '</div>';
    return translationsContainer;
}

function fillTranslation(engineId) {
    $('#'+engineId).html(translateTextResponse);
}

/* --------------- toggle instant translation ------------------------------- */
$('.instant-translation-toggle').click(function(){
    $('.instant-translation-toggle').toggle();
});

/* --------------- clear source --------------------------------------------- */
$(".clearable").each(function() {
    // idea from https://stackoverflow.com/a/6258628
    var elInp = $(this).find("#sourceText"),
        elCle = $(this).find(".clearable-clear");
    elInp.on("input", function(){
        elCle.toggle(!!this.value);
        elInp.focus();
    });
    elCle.on("touchstart click", function(e) {
        e.preventDefault();
        elInp.val("").trigger("input");
    });
});

/* --------------- count characters ----------------------------------------- */
$('#sourceText').on("input focus", function(){
    var sourceTextLength = $(this).val().length;
    $('#countedCharacters').html(sourceTextLength);
    if (sourceTextLength === 0) {
        $(".clearable-clear").hide();
        hideTranslations();
    } else {
        $("#sourceIsText").removeClass('source-text-error');
    }
});

/* --------------- copy translation ----------------------------------------- */
$('#translations').on('touchstart click','.copyable-copy',function(){
    var textToCopy = $(this).closest('.copyable').find('.translation-result').text();
    // https://stackoverflow.com/a/33928558
    if (window.clipboardData && window.clipboardData.setData) {
        // IE specific code path to prevent textarea being shown while dialog is visible.
        return clipboardData.setData("Text", textToCopy); 
    } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
        var textarea = document.createElement("textarea");
        textarea.textContent = textToCopy;
        textarea.style.position = "fixed";  // Prevent scrolling to bottom of page in MS Edge.
        document.body.appendChild(textarea);
        textarea.select();
        try {
            return document.execCommand("copy");  // Security exception may be thrown by some browsers.
        } catch (ex) {
            console.warn("Copy to clipboard failed.", ex);
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    }
});

/* --------------- show/hide ------------------------------------------------ */
function clearMtEngineSelectorError() {
    $('#mtEngineSelectorError').hide();
    showSourceText();
    showTranslations();
}
function showMtEngineSelectorError(errorMode) {
    $('#mtEngineSelectorError').html(translatedStrings[errorMode]).show();
    $('#translationSubmit').hide();
    if ($('#sourceText').val().length === 0) {
        hideSourceText();
    }
}
function showSourceText() {
    $('#sourceContent').show();
    $('#sourceIsText').show();
}
function hideSourceText() {
    $('#sourceContent').hide();
    $('#sourceIsText').hide();
}
function showTranslations() {
    if ($('#sourceText').val().length === 0) {
        $('#translations').html('');
    }
    $('#translations').show();
    $('#translationSubmit').show();
    // = "default":
    $('#instantTranslationIsOn').show();
    $('#instantTranslationIsOff').hide();
}
function hideTranslations() {
    $('#translations').hide();
}

/* --------------- "toggle" source (text/file) ------------------------------ */
$('.source-toggle span').click(function(){
    $('.source-toggle').toggle();
    if ($('#sourceIsText').is(":visible")) {
        $('.show-if-source-is-text').show();
        $('.show-if-source-is-file').hide();
        showTranslations();
        $("#sourceIsText").removeClass('source-text-error');
        $('#sourceText').focus();
    } else {
        $('.show-if-source-is-text').hide();
        $('.show-if-source-is-file').show();
        hideTranslations();
    }
});

