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
    chosenSourceIsText = true,
    fileTypesAllowedAndAvailable = [];

/***
 * Stores an array with the allowed file-types according to the available engines.
 */
function setFileTypesAllowedAndAvailable() {
    var engineId,
        mtEngine,
        extensionsFileTranslation = Editor.data.languageresource.fileExtension,
        extensionsKey,
        filesTypesForLanguageCombination;
    for (engineId in machineTranslationEngines) {
        if(machineTranslationEngines.hasOwnProperty(engineId)){
            mtEngine = machineTranslationEngines[engineId];
            extensionsKey = mtEngine.source+","+mtEngine.target;
            if(extensionsFileTranslation.hasOwnProperty(extensionsKey)){
                filesTypesForLanguageCombination = [];
                if(fileTypesAllowedAndAvailable[extensionsKey] === -1) {
                    filesTypesForLanguageCombination = fileTypesAllowedAndAvailable[extensionsKey];
                }
                extensionsFileTranslation[extensionsKey].forEach(function(fileType) {
                    if(filesTypesForLanguageCombination.indexOf(fileType) === -1) {
                        filesTypesForLanguageCombination.push(fileType);
                    }
                  });
                if(filesTypesForLanguageCombination.length > 0) {
                    var item = {'sourceLocale': mtEngine.source,
                                'targetLocale': mtEngine.target,
                                'filyTypes': filesTypesForLanguageCombination};
                    fileTypesAllowedAndAvailable[extensionsKey] = item;
                }
            }
        }
    }
}
/**
 * Which fileTypes are allowed for the current language-combination?
 * @returns array
 */
function getAllowedFileTypes() {
    var sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val(),
        filesTypesForLanguageCombination,
        addFileTypes,
        allowedFileTypes = [],
        allowedFileTypesUnique;
    for (var key in fileTypesAllowedAndAvailable) {
        if (fileTypesAllowedAndAvailable.hasOwnProperty(key)) {
            filesTypesForLanguageCombination = fileTypesAllowedAndAvailable[key];
            addFileTypes = false;
            if (sourceLocale == '-' && targetLocale == '-') {
                addFileTypes = true;
            } else if (targetLocale == '-' && filesTypesForLanguageCombination.sourceLocale === sourceLocale) {
                addFileTypes = true;
            } else if (sourceLocale == '-' && filesTypesForLanguageCombination.targetLocale === targetLocale) {
                addFileTypes = true;
            } else if (filesTypesForLanguageCombination.sourceLocale === sourceLocale && filesTypesForLanguageCombination.targetLocale === targetLocale) {
                addFileTypes = true;
            }
            if (addFileTypes) {
                allowedFileTypes = allowedFileTypes.concat(filesTypesForLanguageCombination.filyTypes);
            }
        }
    }
    allowedFileTypesUnique = allowedFileTypes.filter(function(elem, index, self) {
        return index == self.indexOf(elem);
    });
    return allowedFileTypesUnique;
}
/***
 * Check if files can be translated for the current language-combination.
 * @returns boolean
 */
function fileTranslationIsPossible() {
    var sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val(),
        filesTypesForLanguageCombination,
        fileTranslationIsPossible = false;
    if (sourceLocale == '-' && targetLocale == '-') {
        fileTranslationIsPossible = true;
    } else {
        for (var key in fileTypesAllowedAndAvailable) {
            if (fileTypesAllowedAndAvailable.hasOwnProperty(key)) {
                filesTypesForLanguageCombination = fileTypesAllowedAndAvailable[key];
                if (targetLocale == '-' && filesTypesForLanguageCombination.sourceLocale === sourceLocale) {
                    fileTranslationIsPossible = true;
                } else if (sourceLocale == '-' && filesTypesForLanguageCombination.targetLocale === targetLocale) {
                    fileTranslationIsPossible = true;
                } else if (filesTypesForLanguageCombination.sourceLocale === sourceLocale && filesTypesForLanguageCombination.targetLocale === targetLocale) {
                    fileTranslationIsPossible = true;
                }
            }
            if (fileTranslationIsPossible) break;
        }
    }
    return fileTranslationIsPossible;
}
/***
 * If files are allowed for the current language-combination, show text accordingly.
 */
function setTextForSource() {
    var textForSourceIsText = Editor.data.languageresource.translatedStrings['enterText'],
        textForSourceIsFile = '',
        allowedFileTypes;
    if (fileTranslationIsPossible()) {
        allowedFileTypes = getAllowedFileTypes();
        
        textForSourceIsText += ' <span class="change-source-type">';
        textForSourceIsText += Editor.data.languageresource.translatedStrings['orTranslateFile'];
        textForSourceIsText += ' (' + allowedFileTypes.join(', ') + ')';
        textForSourceIsText += '</span>';

        textForSourceIsFile = Editor.data.languageresource.translatedStrings['uploadFile'];
        textForSourceIsFile += ' (' + allowedFileTypes.join(', ') + ')';
        textForSourceIsFile += ' <span class="change-source-type">';
        textForSourceIsFile += Editor.data.languageresource.translatedStrings['orTranslateText'];
        textForSourceIsFile += '</span>';
    } else {
        chosenSourceIsText = true;
        showSource();
    }
    $("#sourceIsText").html(textForSourceIsText);
    $("#sourceIsFile").html(textForSourceIsFile);
}

/*  -------------------------------------------------------------------------
 * ORDER OF DISPLAY FOR THE USER:
 * (1) languages are selected from the locales-lists (renderLocalesAsAvailable)
 * (2) every change in the language-selection starts a check if/how many mtEngines 
 *     are available (renderMtEnginesAsAvailable)
 * (3) As soon as there is exactly ONE mtEngine available:
 *     - source- and target-language are set
 *     - (if text is already entered:) translation starts
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
    latestTextToTranslate = ''; // when the language changes, former translations are not valid any longer = the text hasn't been translated already.
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
    } else if (accordingTo === 'reset' || selectedText === Editor.data.languageresource.translatedStrings['clearBothLists']) {
        // This means a "reset" of one or both the lists:
        sourceLocalesAvailable = mtSourceLanguageLocales;
        targetLocalesAvailable = mtTargetLanguageLocales;
        source = {hasPlsChoose: true,  hasClearBoth: false, hasShowAllAvailable: false, localeForReference: '',            selectedValue: '-'};
        target = {hasPlsChoose: true,  hasClearBoth: false, hasShowAllAvailable: false, localeForReference: '',            selectedValue: '-'};
    } else {
        if (selectedText === Editor.data.languageresource.translatedStrings['showAllAvailableFor']+' '+targetLocale) {
            accordingTo = 'accordingToTargetLocale';
        } else if (selectedText === Editor.data.languageresource.translatedStrings['showAllAvailableFor']+' '+sourceLocale) {
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
    // if fileUpload is possible for currently chosen languages, show text accordingly.
    setTextForSource();
    // start translation?
    if (sourceLocalesAvailable.length == 1 && targetLocalesAvailable.length == 1) {
        $('#translationSubmit').show();
        startTimerForInstantTranslation();
    } else {
        $('#translationSubmit').hide();
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
    localesAvailable.sort();
    return localesAvailable;
}
function renderLocaleOptionList(list) {
    var localeOptionsList = []
        option = {};
    if (list.hasPlsChoose) {
        option = {};
        option.value = '-';
        option.name = Editor.data.languageresource.translatedStrings['pleaseChoose'] + ' ('+list.localeList.length+'):';
        localeOptionsList.push(option);
    }
    if (list.hasClearBoth) {
        option = {};
        option.value = '-';
        option.name = Editor.data.languageresource.translatedStrings['clearBothLists'];
        localeOptionsList.push(option);
    }
    if (list.hasShowAllAvailable) {
        option = {};
        option.value = '-';
        option.name = Editor.data.languageresource.translatedStrings['showAllAvailableFor']+' '+list.localeForReference;
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

/* --------------- start translation instantly or manually: events  --------- */

//start instant (sic!) translation automatically
$('#sourceText').bind('keyup', function() {
    if($('#sourceText').val().length > 0 && $('#sourceText').val() === latestTextToTranslate) {
        return;
    }
    startTimerForInstantTranslation();
});

//instantly after uploading a file: grab the files and set them to uploadedFiles
//(if instantTranslation is on, the translation will start, too)
$('#sourceFile').on('change', grabUploadedFiles);

//start translation manually via button
$('.click-starts-translation').click(function(event){
    if ($('#sourceText').not(":visible") && $('#sourceFile').is(":visible")) {
        startFileTranslation();
    } else {
        startTranslation();
    }
    return false;
});

/* --------------- prepare file-translations  ------------------------------- */

function grabUploadedFiles(event){
    uploadedFiles = event.target.files; 
    if (instantTranslationIsActive) {
        startFileTranslation();
    }
}

function startFileTranslation(){
    var fileName,
        fileType,
        fileTypesAllowed = getAllowedFileTypes(),
        fileTypesErrorList = [];
    if ($('#sourceFile').val() == "") {
        showSourceError(Editor.data.languageresource.translatedStrings['uploadFileNotFound']);
        return;
    }
    clearAllErrorMessages();
    $.each(uploadedFiles, function(key, value){
        fileName = value.name;
        fileType = fileName.substr(fileName.lastIndexOf('.')+1,fileName.length);
        if (fileTypesAllowed.indexOf(fileType) === -1) {
            fileTypesErrorList.push(fileType);
        }
    });
    if (fileTypesErrorList.length > 0) {
        showSourceError(Editor.data.languageresource.translatedStrings['notAllowed'] + ': ' + fileTypesErrorList.join());
        return;
    }
    startTranslation();
}

/* --------------- prepare, start and terminate translations  --------------- */
function startTimerForInstantTranslation() {
    terminateTranslation();
    if (instantTranslationIsActive && $("#sourceLocale").val() != '-' && $("#targetLocale").val() != '-') {
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
    if ($('#sourceText').val() === latestTextToTranslate) {
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

/* --------------- translation ---------------------------------------------- */
/***
 * Send a request for translation, this will return translation result for each associated language resource
 * INFO: in the current implementation only result from sdlcloud language will be returned
 * @returns
 */
function translateText(textToTranslate,translationInProgressID){
    startLoadingSign();
    var translateRequest = $.ajax({
        statusCode: {
            500: function() {
                hideTranslations();
                showMtEngineSelectorError('serverErrorMsg500');
            }
        },
        url: Editor.data.restpath+"instanttranslateapi/translate",
        dataType: "json",
        data: {
            'source':$("#sourceLocale").val(),
            'target':$("#targetLocale").val(),
            'text':textToTranslate
        },
        success: function(result){
            if (translationInProgressID != latestTranslationInProgressID) {
                return;
            }
            if (result.errors !== undefined && result.errors != '') {
                showTranslationError(result.errors);
            } else {
                clearAllErrorMessages();
                translateTextResponse = result.rows;
                fillTranslation();
            }
            stopLoadingSign();
        },
        fail: function(xhr, textStatus, errorThrown){
            debugger;
        }
    });
}

function fillTranslation() {
    var translationHtml = '',
        resultHtml = '',
        fuzzyMatch,
        infoText,
        term,
        termStatus,
        metaData,
        resultData;
    $.each(translateTextResponse, function(serviceName, resource){
        resultHtml = '';
        $.each(resource, function(resourceName, allResults){
            $.each(allResults, function(key, result){
                if (result['target'] != '') {
                    fuzzyMatch = {};
                    if (result['sourceDiff'] != undefined) {
                        fuzzyMatch = {'matchRate': result['matchrate'], 
                                      'sourceDiff': result['sourceDiff']}
                    }
                    infoText = '';
                    term = '';
                    termStatus = '';
                    if (result['metaData'] != undefined) {
                        metaData = result['metaData'];
                        if(metaData['definition'] != undefined) {
                            infoText = metaData['definition'];
                        }
                        if(metaData['term'] != undefined) {
                            term = metaData['term'];
                        }
                        if(metaData['status'] != undefined) {
                            termStatus = metaData['status'];
                        }
                    }
                    resultData = {'engineId': result['languageResourceid'],
                                  'fuzzyMatch': fuzzyMatch,
                                  'infoText': infoText,
                                  'resourceName': resourceName,
                                  'serviceName': serviceName,
                                  'term': term,
                                  'termStatus': termStatus,
                                  'translationText': result['target']
                                  };
                    resultHtml += renderTranslationContainer(resultData);
                }
            });
        });
        if (resultHtml != '') {
            translationHtml += resultHtml;
        }
    });
    $('#translations').html(translationHtml);
    showTranslations();
}

function renderTranslationContainer(resultData) {
    var translationsContainer = '';
    
    translationsContainer += '<h4>';
    translationsContainer += resultData.resourceName + ' (' + resultData.serviceName + ')';
    translationsContainer += '<span class="loadingSpinnerIndicator"><img src="'+Editor.data.publicModulePath+'images/loading-spinner.gif"/></span>';
    translationsContainer += '</h4>';
    
    if (resultData.fuzzyMatch.sourceDiff != undefined) {
        var fuzzyMatchTranslatedString = Editor.data.languageresource.translatedStrings['attentionFuzzyMatch'].replace("{0}", resultData.fuzzyMatch.matchRate);
        translationsContainer += '<div class="translation-sourcediff" title="'+Editor.data.languageresource.translatedStrings['differenceIsHighlighted']+'">'+fuzzyMatchTranslatedString+': ';
        translationsContainer += '<span class="translation-sourcediff-content">' + resultData.fuzzyMatch.sourceDiff + '</span>';
        translationsContainer += '</div>';
    }
    
    translationsContainer += '<div class="copyable">';
    translationsContainer += '<div class="translation-result" id="'+resultData.engineId+'">'+resultData.translationText+'</div>';
    translationsContainer += '<span class="copyable-copy" title="'+Editor.data.languageresource.translatedStrings['copy']+'"><span class="ui-icon ui-icon-copy"></span></span>';
    if (resultData.term != '') {
        translationsContainer += '<span class="term-info" id="'+resultData.term+'" title="'+Editor.data.languageresource.translatedStrings['openInTermPortal']+'"><span class="ui-icon ui-icon-info"></span></span>';
    }
    if (resultData.termStatus != '') {
        translationsContainer += '<span class="term-status">'+renderTermStatusIcon(resultData.termStatus)+'</span>';
    }
    translationsContainer += '</div>';
    
    if (resultData.infoText != '') {
        translationsContainer += '<div class="translation-infotext">'+resultData.infoText+'</div>';
    }
    
    translationsContainer += '<div id="translationError'+resultData.engineId+'" class="instant-translation-error ui-state-error ui-corner-all"></div>';
    return translationsContainer;
}

/***
 * Render the image-html for TermStatus.
 * @param string termStatus
 * @returns string
 */
function renderTermStatusIcon(termStatus){
    var termStatusHtml = '', 
        status = 'unknown', 
        map = Editor.data.termStatusMap,
        labels = Editor.data.termStatusLabel,
        label;
    if(map[termStatus]) {
        status = map[termStatus];
        label = labels[status]+' ('+termStatus+')';
        termStatusHtml += '<img src="' + Editor.data.publicModulePath + 'images/termStatus/'+status+'.png" alt="'+label+'" title="'+label+'"> ';
    }
    return termStatusHtml;
}

/***
 * Request a file translate for the curent uploaded file
 * @returns
 */
function requestFileTranslate(){

    // Create a formdata object and add the files
    var data = new FormData(),
        ext=getFileExtension();
    
    $.each(uploadedFiles, function(key, value){
        data.append(key, value);
    });
    
    data.append('source', $("#sourceLocale").val());
    data.append('target',$("#targetLocale").val());
    
    //when no extension in the file is found, use default file extension
    data.append('fileExtension', ext != "" ? ext : DEFAULT_FILE_EXT);
    
    $.ajax({
        url:Editor.data.restpath+"instanttranslateapi/file",
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
                stopLoadingState();
            }
        },
        error: function(jqXHR, textStatus, errorThrown)
        {
            // Handle errors here
            showSourceError('ERRORS: ' + textStatus);
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
        url: Editor.data.restpath+"instanttranslateapi/url",
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
        newTabHref = Editor.data.restpath+"instanttranslateapi/download?fileName="+fullFileName+"&url="+url;
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

/* --------------- toggle instant translation ------------------------------- */
$('.instant-translation-toggle').click(function(){
    $('.instant-translation-toggle').toggle();
    instantTranslationIsActive = !instantTranslationIsActive;
    clearAllErrorMessages();
});

/* --------------- clear source --------------------------------------------- */
$(".clearable").each(function() {
    // idea from https://stackoverflow.com/a/6258628
    var elInp = $(this).find("#sourceText"),
        elCle = $(this).find(".clearable-clear");
    elInp.on("input", function(){
        elCle.toggle(!!this.value);
        latestTextToTranslate = '';
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

/* --------------- open TermPortal ------------------------------------------ */
$('#translations').on('touchstart click','.term-info',function(){
    window.open(Editor.data.restpath+"termportal?term="+$(this).attr('id'), '_blank');
});

/* --------------- show/hide: helpers --------------------------------------- */
function showLanguageSelectsOnly() {
    $('#translationSubmit').hide();
    hideTranslations();
}
function showSource() {
    $('#sourceContent').show();
    showInstantTranslationOffOn();
    if (chosenSourceIsText || fileTranslationIsPossible() === false) {
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
function showTranslations() {
    if ($('#sourceText').val().length === 0) {
        $('#translations').html('');
    }
    $('#translations').show();
    $('#translationSubmit').show();
    showInstantTranslationOffOn();
}
function hideTranslations() {
    $('#translations').hide();
    $('#translationSubmit').hide();
    $('#instantTranslationIsOn').hide();
    $('#instantTranslationIsOff').hide();
}
function showInstantTranslationOffOn() {
    if (instantTranslationIsActive) {
        $('#instantTranslationIsOn').show();
        $('#instantTranslationIsOff').hide();
    } else {
        $('#instantTranslationIsOn').hide();
        $('#instantTranslationIsOff').show();
    }
}
/* --------------- show/hide: errors --------------------------------------- */
function showMtEngineSelectorError(errorMode) {
    $('#mtEngineSelectorError').html(Editor.data.languageresource.translatedStrings[errorMode]).show();
    $('#translationSubmit').hide();
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
    $("#sourceIsText").removeClass('source-text-error');
}
/* --------------- show/hide: loading spinner ------------------------------- */
// 'sign' = show indicator in addition to content (currently used for text-translations)
// 'state' = shown layer upon content (currently used for file-translations)
function startLoadingSign() {
    if ($('#translations').is(":visible")) {
        $('#translations').find('.loadingSpinnerIndicator').show();
    } else {
        $('#target').children('.loadingSpinnerIndicator').show();
    }
}
function stopLoadingSign() {
    $('.loadingSpinnerIndicator').hide();
}
function startLoadingState() {
    $('.loadingSpinnerLayer').show();
}
function stopLoadingState() {
    $('.loadingSpinnerLayer').hide();
}

