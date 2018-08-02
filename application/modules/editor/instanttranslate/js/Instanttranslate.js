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
function startTimerForInstantTranslation() {
    terminateTranslation();
    if ($('#instantTranslationIsOn').is(":visible")) {
        editIdleTimer = setTimeout(function() {
            startTranslation();
        }, 200);
    }
}
function startTranslation() {
    terminateTranslation();
    if ($('#sourceText').val().length > 0) {
        translateText();
    }
}
function terminateTranslation() {
    clearTimeout(editIdleTimer);
    editIdleTimer = null;
}

/* --------------- selecting languages and MT-engines ----------------------- */
$('#mtEngines').on('selectmenuchange', function() {
    var engineId = $(this).val();
    setSingleMtEngineById(engineId);
});
function setSingleMtEngineById(engineId) {
    var mtEngine = machineTranslationEngines[engineId];
    if(machineTranslationEngines[engineId] === 'undefined') {
        return;
    }
    clearMtEngineSelectorError();
    $("#sourceLocale").val(mtEngine.source).selectmenu("refresh");
    $("#targetLocale").val(mtEngine.target).selectmenu("refresh");
    startTranslation(); // Google stops instant translations only for typing, not after changing the source- or target-language
}
function enableMtEnginesAsAvailable() {
    var mtIdsAvailable = getMtEnginesAccordingToLanguages(),
        mtOptionList = [];
    if (mtIdsAvailable.length === 0) {
        $('#mtEngines').selectmenu("widget").hide();
        showMtEngineSelectorError('noMatchingMt');
        return;
    }
    $('#mtEngines').find('option').remove().end();
    mtOptionList.push("<option id='PlsSelect'>"+mtIdsAvailable.length+" "+translatedStrings['foundMt']+":</option>");
    for (i = 0; i < mtIdsAvailable.length; i++) {
        mtOptionList.push("<option value='" + mtIdsAvailable[i] + "'>" + machineTranslationEngines[mtIdsAvailable[i]].name + "</option>");
    }
    $('#mtEngines').append(mtOptionList.join(""));
    $("#mtEngines").selectmenu("refresh");
    $('#mtEngines').selectmenu("widget").show();
    if (mtIdsAvailable.length === 1) {
        $('#PlsSelect').remove().end();
        $("#mtEngines").selectmenu("refresh");
        setSingleMtEngineById(mtIdsAvailable[0]);
        $('#translationSubmit').show();
        return;
    }
    if (mtIdsAvailable.length > 1) {
        showMtEngineSelectorError('selectMt');
        return;
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


/***
 * Send a request for translation, this will return translation result for each associated language resource
 * INFO: in the current implementation only result from sdlcloud language will be returned
 * @returns
 */
function translateText(){
    if (getSelectedEngineCode() === false) {
        return;
    }
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
            'domainCode':getSelectedEngineCode(),
            'text':$('#sourceText').val()
        },
        success: function(result){
        	translateTextResponse = result.rows;
        	fillTranslation();
        }
    })
}

function fillTranslation() {
    var translationsContent = '';
    translationsContent += '<div class="copyable">';
    translationsContent += '<div class="translation-result">' + translateTextResponse + '</div>';
    translationsContent += '<span class="copyable-copy"><span class="ui-icon ui-icon-copy"></span></span>';
    translationsContent += '</div>';
    /*
    translationsContent += '<div class="copyable">';
    translationsContent += '<div class="translation-result">translation 2...</div>';
    translationsContent += '<span class="copyable-copy"><span class="ui-icon ui-icon-copy"></span></span>';
    translationsContent += '</div>';
    translationsContent += '<div class="copyable">';
    translationsContent += '<div class="translation-result">translation 3...</div>';
    translationsContent += '<span class="copyable-copy"><span class="ui-icon ui-icon-copy"></span></span>';
    translationsContent += '</div>';
    */
    $('#translations').html(translationsContent);
    showTranslations();
}

/***
 * Return the domain code(if exist) of the selected engine or false
 * @returns
 */
function getSelectedEngineCode(){
	var engineId=$("#mtEngines").val();
	if(machineTranslationEngines[engineId] === undefined) {
	    return false; 
	}
	return machineTranslationEngines[engineId].domainCode;
}

/* --------------- toggle instant translation ------------------------------- */
$('.instant-translation-toggle').click(function(){
    $('.instant-translation-toggle').toggle();
});

/* --------------- clear source --------------------------------------------- */
$(".clearable").each(function() {
    // idea fom https://stackoverflow.com/a/6258628
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
$('#sourceText').on("input", function(){
    $('#countedCharacters').html($(this).val().length);
});

/* --------------- copy translation ----------------------------------------- */
$('#translations').on('click','.copyable-copy',function(){
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
    $('#translationSubmit').show();
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
}
function hideSourceText() {
    $('#sourceContent').hide();
}
function showTranslations() {
    $('#translations').show();
}
function hideTranslations() {
    $('#translations').hide();
}

