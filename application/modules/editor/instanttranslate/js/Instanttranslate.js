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
    DEFAULT_FILE_EXT='txt',
    uploadedFiles,//Variable to store uploaded files
    selectedEngineId = undefined,
    translateTextResponse = '',
    latestTranslationInProgressID = false,
    latestTextToTranslate = '',
    instantTranslationIsActive = true,
    chosenSourceIsText = true;

/* --------------- set, unset and get the selected mtEngine  ---------------- */

function setMtEngine(engineId) {
    selectedEngineId = engineId;
    console.log('selectedEngineId: ' + selectedEngineId);
}
function unsetMtEngine() {
    console.log('unsetMtEngine');
    selectedEngineId = undefined;
}
/***
 * Return the id of the set engine (if set) or false
 * @returns
 */
function getSelectedEngineId(){
    if(selectedEngineId === undefined) {
        return false; 
    }
    return selectedEngineId;
}
/***
 * Return the name of the set engine (if set) or false
 * @returns
 */
function getSelectedEngineName(){
    var engineId = getSelectedEngineId();
    if(engineId === false || machineTranslationEngines[engineId] === undefined || machineTranslationEngines[engineId].name === undefined) {
        return false; 
    }
    return machineTranslationEngines[engineId].name;
}
/***
 * Return the domain code(if exist) of the set engine or false
 * @returns
 */
function getSelectedEngineDomainCode(){
    var engineId = getSelectedEngineId();
    if(engineId === false || machineTranslationEngines[engineId] === undefined || machineTranslationEngines[engineId].domainCode === undefined) {
        return false; 
    }
    return machineTranslationEngines[engineId].domainCode;
}
/***
 * Returns an array with the allowed file-types for the set engine or false if none.
 * @returns
 */
function getSelectedEngineFileTypes() {
    var engineId = getSelectedEngineId(),
        mtEngine = machineTranslationEngines[engineId]
        extensionsFileTranslation = Editor.data.languageresource.fileExtension,
        extensionsKey = mtEngine.source+","+mtEngine.target;
    if(extensionsFileTranslation.hasOwnProperty(extensionsKey)){
        return extensionsFileTranslation[extensionsKey];
    }
    return false;
}

/*  -------------------------------------------------------------------------
 * ORDER OF DISPLAY FOR THE USER:
 * (1) languages are selected from the locales-lists (renderLocalesAsAvailable)
 * (2) every change in the language-selection starts a check if/how many mtEngines 
 *     are available (renderMtEnginesAsAvailable)
 * (3) As soon as there is exactly ONE mtEngine available or set by the user, source- and 
 *     (a) translation-forms are shown (showTranslationFormsAsReady)
 *     (b) mtEngine is set
 *  ------------------------------------------------------------------------- */

/* --------------- locales-list (source, target) ---------------------------- */
/**
 * Include languages for source/target only if there is an Mt-Engine available 
 * for the resulting source-target-combination.
 * - accordingToSourceLocale: sourceLocale is set, targetLocales are rendered
 * - accordingToTargetLocale: targetLocale is set, sourceLocales are rendered
 * - reset: starts the same process as for 'Clear both lists'
 * - if the selected text is 'Clear both lists', then both lists are rendered
 * - if the selected text is 'Show all available for...', then this list is rendered
 * @param string accordingTo ('accordingToSourceLocale'|'accordingToTargetLocale'|'reset')
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
    clearAllErrorMessages();
    if (sourceLocale != '-' && targetLocale != '-') {
        // This means that the user has chosen a final combination of source AND target. The list contains only the selected locale and the "clear"-options.
        sourceLocalesAvailable.push(sourceLocale);
        targetLocalesAvailable.push(targetLocale);
        localesAvailable = getLocalesAccordingToReference ('accordingToTargetLocale', targetLocale);
        if (localesAvailable.length == 1) {
            source = {hasPlsChoose: false, hasClearBoth: true,  hasShowAllAvailable: false, localeForReference: targetLocale, selectedValue: sourceLocale};
        } else {
            source = {hasPlsChoose: false, hasClearBoth: true,  hasShowAllAvailable: true,  localeForReference: targetLocale, selectedValue: sourceLocale};
        }
        localesAvailable = getLocalesAccordingToReference ('accordingToSourceLocale', sourceLocale);
        if (localesAvailable.length == 1) {
            target = {hasPlsChoose: false, hasClearBoth: true,  hasShowAllAvailable: false, localeForReference: sourceLocale, selectedValue: targetLocale};
        } else {
            target = {hasPlsChoose: false, hasClearBoth: true,  hasShowAllAvailable: true,  localeForReference: sourceLocale, selectedValue: targetLocale};
        }
    } else if (accordingTo === 'reset' || selectedText === translatedStrings['clearBothLists']) {
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
            if (localesAvailable.length == 1) {
                target = {hasPlsChoose: false, hasClearBoth: true, hasShowAllAvailable: false, localeForReference: '',    selectedValue: localesAvailable[0]};
            } else {
                target = {hasPlsChoose: true,  hasClearBoth: true, hasShowAllAvailable: false, localeForReference: '',    selectedValue: ''};
            }
        } else {
            sourceLocalesAvailable = localesAvailable;
            targetLocalesAvailable.push(targetLocale);
            if (localesAvailable.length == 1) {
                source = {hasPlsChoose: false, hasClearBoth: true, hasShowAllAvailable: false, localeForReference: '',    selectedValue: localesAvailable[0]};
            } else {
                source = {hasPlsChoose: true,  hasClearBoth: true, hasShowAllAvailable: false, localeForReference: '',    selectedValue: ''};
            }
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
    renderMtEnginesAsAvailable();
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
    localesAvailable.sort();
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

/* --------------- languages and MT-engines --------------------------------- */
$('#mtEngines').on('selectmenuchange', function() {
    var engineId = $(this).val();
    setSingleMtEngineById(engineId);
});
function setSingleMtEngineById(engineId) {
    var fileTypes;
    if(machineTranslationEngines[engineId] === undefined) {
        showMtEngineSelectorError('selectMt');
        return;
    }
    setMtEngine(engineId);
    $('#selectedMtEngine').html(translatedStrings['selectedMtEngine'] + ':<br>'+getSelectedEngineName());
    clearAllErrorMessages();
    showTranslationFormsAsReady();
    if ($('#sourceText').val().length > 0) {
        startTranslation(); // Google stops instant translations only for typing, not after changing the source- or target-language
    }
    fileTypes = getSelectedEngineFileTypes();
    if(fileTypes !== false){
        fileTypes = fileTypes.join();
        $("#sourceIsText").html(translatedStrings['enterText']+' <span class="change-source-type">'+translatedStrings["orTranslateFile"]+' (' + fileTypes +').</span>');
        $("#sourceIsFile").html(translatedStrings['uploadFile']+' (' + fileTypes + ') <span class="change-source-type">'+translatedStrings["orTranslateText"]+'</span>');
    }else{
    	$("#sourceIsText").html(translatedStrings["enterText"]+':');
        $("#sourceIsFile").html(translatedStrings['uploadFile']+' <span class="change-source-type">'+translatedStrings["orTranslateText"]+'</span>');
    }
}
function renderMtEnginesAsAvailable() {
    var mtIdsAvailable,
        mtOptionList = [],
        sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val();
    if (sourceLocale == '-' || targetLocale == '-') {
        unsetMtEngine();
        showLanguageSelectsOnly();
        return;
    }
    mtIdsAvailable = getMtEnginesAccordingToLanguages();
    switch(mtIdsAvailable.length) {
        case 0:
            unsetMtEngine();
            showLanguageSelectsOnly();
            showMtEngineSelectorError('noMatchingMt');
            break;
        case 1:
            setSingleMtEngineById(mtIdsAvailable[0]);
            break;
        default:
            unsetMtEngine();
            showLanguageSelectsOnly();
            // But now also show mtEngine-Select:
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

/* --------------- start and terminate translations  ------------------------ */
$('#sourceText').bind('keyup', function() {
    // start instant (sic!) translation automatically
    if($('#sourceText').val().length > 0 && $('#sourceText').val() === latestTextToTranslate) {
        console.log('latest key-event did not change the text (eg arrow-keys, ...)');
        return;
    }
    startTimerForInstantTranslation();
});
$('#translationSubmit').click(function(){
    // start translation manually via button
    startTranslation();
    return false;
});
$('#instantTranslationIsOff').click(function(){
    if ($('#sourceText').not(":visible") && $('#sourceFile').is(":visible") && $('#sourceFile').val() == "") {
        return;
    }
    if ($('#sourceText').val() === latestTextToTranslate) {
        return;
    }
    startTranslation();
});
function startTimerForInstantTranslation() {
    terminateTranslation();
    if (instantTranslationIsActive) {
        editIdleTimer = setTimeout(function() {
            startTranslation();
        }, 200);
    }
}
function startTranslation() {
    var textToTranslate,
        translationInProgressID;
    // translate a file?
    if ($('#sourceText').not(":visible") && $('#sourceFile').is(":visible")) {
        startLoadingState();
        requestFileTranslate();
        return;
    }
    // otherwise: translate Text
    if ($('#sourceText').val().length === 0) {
        // no text given
        $("#sourceIsText").addClass('source-text-error');
        $('#translations').hide();
        return;
    }
    terminateTranslation();
    textToTranslate = $('#sourceText').val();
    translationInProgressID = new Date().getTime();
    // store both for comparison on other places
    latestTextToTranslate = textToTranslate;
    latestTranslationInProgressID = translationInProgressID;
    // start translation
    translateText(textToTranslate,translationInProgressID);
}

function terminateTranslation() {
    clearTimeout(editIdleTimer);
    editIdleTimer = null;
    latestTranslationInProgressID = false;
}

// Add events
$('#sourceFile').on('change', prepareUpload);

// Grab the files and set them to uploadedFiles
function prepareUpload(event){
    var fileName,
        fileType,
        fileTypesAllowed = getSelectedEngineFileTypes(),
        fileTypesErrorList = [];
    clearAllErrorMessages();
    uploadedFiles = event.target.files;
    $.each(uploadedFiles, function(key, value){
        fileName = value.name;
        fileType = fileName.substr(fileName.lastIndexOf('.')+1,fileName.length);
        if (fileTypesAllowed.indexOf(fileType) === -1) {
            fileTypesErrorList.push(fileType);
        }
    });
    if (fileTypesErrorList.length > 0) {
        showSourceError(translatedStrings['notAllowed'] + ': ' + fileTypesErrorList.join());
        return;
    }
    if (instantTranslationIsActive) {
        startTranslation();
    }
}

/* --------------- translation ---------------------------------------------- */
/***
 * Send a request for translation, this will return translation result for each associated language resource
 * INFO: in the current implementation only result from sdlcloud language will be returned
 * @returns
 */
function translateText(textToTranslate,translationInProgressID){
    if (getSelectedEngineDomainCode() === false) {
        console.log('translateText stopped; getSelectedEngineDomainCode FALSE!');
        return;
    }
    console.log(translationInProgressID + ': translateText mit domainCode: ' + getSelectedEngineDomainCode() + ' / source: ' + $("#sourceLocale").val() + ' / target: ' + $("#targetLocale").val() + ' / text: ' + textToTranslate);
    $('#translations').html(renderTranslationContainer());
    startLoadingSign();
    var translateRequest = $.ajax({
        statusCode: {
            500: function() {
                hideTranslations();
                showMtEngineSelectorError('serverErrorMsg500');
            }
        },
        url: REST_PATH+"instanttranslate/translate",
        dataType: "json",
        data: {
            'source':getIsoByRfcLanguage($("#sourceLocale").val()),
            'target':getIsoByRfcLanguage($("#targetLocale").val()),
            'domainCode':getSelectedEngineDomainCode(),
            'text':textToTranslate
        },
        success: function(result){
            if (translationInProgressID != latestTranslationInProgressID) {
                console.log('===> ' +translationInProgressID + ': result is not valid any more; not applied.');
                return;
            }
            if (result.errors !== undefined && result.errors != '') {
                console.log('===> ' +translationInProgressID + ': error');
                showTranslationError(result.errors);
            } else {
                clearAllErrorMessages();
                translateTextResponse = result.rows;
                console.log('===> ' +translationInProgressID + ': apply translation');
                fillTranslation(translationInProgressID);
            }
            stopLoadingSign();
        }
    });
}

function renderTranslationContainer() {
    var translationsContainer = '',
        engineId = getSelectedEngineId(),
        engineName = getSelectedEngineName();
    if (engineId !== false && engineName != false) {
        //for https://jira.translate5.net/browse/TRANSLATE-1390 :
        //translationsContainer += '<h2>' + translatedStrings['machineTranslation'] + ' (' + engineName + ')</h2>';
        translationsContainer += '<div class="copyable">';
        translationsContainer += '<div class="translation-result" id="'+engineId+'"></div>';
        translationsContainer += '<span class="copyable-copy" title="'+translatedStrings['copy']+'"><span class="ui-icon ui-icon-copy"></span></span>';
        translationsContainer += '</div>';
        translationsContainer += '<div id="translationError'+engineId+'" class="instant-translation-error ui-state-error ui-corner-all"></div>';
    }
    return translationsContainer;
}

function fillTranslation() {
    var engineId = getSelectedEngineId();
    if (engineId !== false && document.getElementById(engineId) !== null) {
        $('#'+engineId).html(translateTextResponse);
    }
}

/***
 * Request a file translate for the curent uploaded file
 * @returns
 */
function requestFileTranslate(){

    // Create a formdata object and add the files
    var data = new FormData(),
        ext=getFileExtension();
    
    console.dir(uploadedFiles);
    
    $.each(uploadedFiles, function(key, value){
        data.append(key, value);
    });
    
    if(getSelectedEngineDomainCode()){
    	data.append('domainCode', getSelectedEngineDomainCode());
    }
    
    data.append('from', getIsoByRfcLanguage($("#sourceLocale").val()));
    data.append('to', getIsoByRfcLanguage($("#targetLocale").val()));
    
    //when no extension in the file is found, use default file extension
    data.append('fileExtension', ext != "" ? ext : DEFAULT_FILE_EXT);
    
    $.ajax({
        url:REST_PATH+"instanttranslate/file",
        type: 'POST',
        data: data,
        cache: false,
        dataType: 'json',
        processData: false, // Don't process the files
        contentType: false, // Set content type to false as jQuery will tell the server its a query string request
        success: function(data, textStatus, jqXHR){
            if(typeof data.error === 'undefined'){
                getDownloadUrl(data.fileId);
            }else{
                // Handle errors here
                showSourceError('ERRORS: ' + data.error);
                console.log('ERRORS: ' + data.error);
                stopLoadingState();
            }
        },
        error: function(jqXHR, textStatus, errorThrown)
        {
            // Handle errors here
            showSourceError('ERRORS: ' + textStatus);
            console.log('ERRORS: ' + textStatus);
            stopLoadingState();
        }
    });
}

/***
 * Request a download url for given sdl file id
 * @param fileId
 * @returns
 */
function getDownloadUrl(fileId){
    $.ajax({
        statusCode: {
            500: function() {
                hideTranslations();
                showMtEngineSelectorError('serverErrorMsg500');
                }
        },
        url: REST_PATH+"instanttranslate/url",
        dataType: "json",
        data: {
            'fileId':fileId
        },
        success: function(result){
            //when no url from server is provided, the file is not ready yet
            if(result.downloadUrl==""){
                setTimeout(function(){ getDownloadUrl(fileId); }, 1000);
                return;
            }
            downloadFile(result.downloadUrl);
        }
    })
}

/***
 * Download the file from the sdl url.
 * @param url
 * @returns
 */
function downloadFile(url){
    var hasFileExt=getFileExtension()!="",
        fullFileName=hasFileExt ? getFileName():(getFileName()+"."+getFileExtension()),
        newTab = window.open("about:blank", "translatedFile"),
        newTabHref = REST_PATH+"instanttranslate/download?fileName="+fullFileName+"&url="+url;
    if(newTab == null) { // window.open does not work in Chrome
        window.location.href = newTabHref;
    } else {
        newTab.location = newTabHref;
    }
    stopLoadingState();
}


/***
 * Get the file name of the uploaded file
 * @returns
 */
function getFileName(){
    //return without fakepath
    return $('#sourceFile').val().replace(/.*(\/|\\)/, '');;
}

/***
 * Get file extension of the uploaded file
 * @returns
 */
function getFileExtension(){
    var fileName=getFileName(),
        found = fileName.lastIndexOf('.') + 1,
        ext = (found > 0 ? fileName.substr(found) : "");
    return ext;
}

/***
 * Return iso language code for the given rfc language code
 * @param rfcCode
 * @returns
 */
function getIsoByRfcLanguage(rfcCode){
    return Editor.data.languageresource.rfcToIsoLanguage[rfcCode];
}

/* --------------- toggle instant translation ------------------------------- */
$('.instant-translation-toggle').click(function(){
    $('.instant-translation-toggle').toggle();
    instantTranslationIsActive = !instantTranslationIsActive;
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
        $('#translations').html('');
        $('#translations').hide();
    } else {
        $("#sourceIsText").removeClass('source-text-error');
        $('#translations').show();
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
function showLanguageSelectsOnly() {
    $('#mtEngines').selectmenu("widget").hide();
    $('#translationSubmit').hide();
    hideSource();
    hideTranslations();
}
function showTranslationFormsAsReady() {
    $('#mtEngines').selectmenu("widget").hide();
    showSource();
    showTranslations();
}
/* --------------- show/hide: helpers --------------------------------------- */
function showSource() {
    var fileTypes = getSelectedEngineFileTypes();
    $('#sourceContent').show();
    if (chosenSourceIsText || fileTypes === false) {
        $('.show-if-source-is-text').show();
        $('.show-if-source-is-file').hide();
        $('#translations').show();
        $("#sourceIsText").removeClass('source-text-error');
        $('#sourceText').focus();
    } else {
        $('.show-if-source-is-text').hide();
        $('.show-if-source-is-file').show();
        $('#translations').hide();
    }
}
function hideSource() {
    $('#sourceContent').hide();
    $('#sourceIsText').hide();
    $('.source-toggle').hide();
}
function showTranslations() {
    if ($('#sourceText').val().length === 0) {
        $('#translations').html('');
    }
    $('#translations').show();
    $('#selectedMtEngine').show();
    $('#translationSubmit').show();
    if (instantTranslationIsActive) {
        $('#instantTranslationIsOn').show();
        $('#instantTranslationIsOff').hide();
    } else {
        $('#instantTranslationIsOn').hide();
        $('#instantTranslationIsOff').show();
    }
}
function hideTranslations() {
    $('#translations').hide();
    $('#selectedMtEngine').hide();
    $('#translationSubmit').hide();
    $('#instantTranslationIsOn').hide();
    $('#instantTranslationIsOff').hide();
}
/* --------------- show/hide: errors --------------------------------------- */
function showMtEngineSelectorError(errorMode) {
    $('#mtEngineSelectorError').html(translatedStrings[errorMode]).show();
    $('#translationSubmit').hide();
    if ($('#sourceText').val().length === 0) {
        hideSource();
    }
}
function showTranslationError(errorText) {
    var engineId = getSelectedEngineId(),
        divId = 'translationError'+engineId;
    debugger;
    if (engineId !== false && document.getElementById(divId) !== null) {
        $('#'+divId).html(errorText).show();
    }
}
function showSourceError(errorText) {
    $('#sourceError').html(errorText).show();
}
function clearAllErrorMessages() {
    $('.instant-translation-error').hide();
}
/* --------------- show/hide: loading spinner ------------------------------- */
function startLoadingSign() {
    $('#translations').hide();
    $('#loadingSpinnerIndicator').show();
}
function stopLoadingSign() {
    $('#translations').show();
    $('#loadingSpinnerIndicator').hide();
}
function startLoadingState() {
    $('#loadingSpinnerLayer').show();
}
function stopLoadingState() {
    $('#loadingSpinnerLayer').hide();
}

