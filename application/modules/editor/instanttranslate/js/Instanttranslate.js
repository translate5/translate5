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

/* --------------- start instant (sic!) translation automatically --------------- */
var editIdleTimer = null,
    translationInProgressID = false,
    sourceTextValue;

$('#sourceText').bind('keyup', function() {
    terminateTranslation();
    var str = $(this).val();
    if (str.length > 0) {
        editIdleTimer = setTimeout(function() {
            sourceTextValue = str;
            startTranslation();
        }, 500);
    }
});

function terminateTranslation() {
    console.log('terminateTranslation.');
    clearTimeout(editIdleTimer);
    editIdleTimer = null;
    translationInProgressID = false;
}

function startTranslation() {
    var translationsContent = '';
    translationInProgressID = Date.now();
    console.log('startTranslation (' + translationInProgressID + ') for: ' + sourceTextValue);
    translationsContent += '<div class="copyable">';
    translationsContent += '<div class="translation-result">translation 1 for ' + sourceTextValue + '</div>';
    translationsContent += '<span class="copyable-copy"><span class="ui-icon ui-icon-copy"></span></span>';
    translationsContent += '</div>';
    translationsContent += '<div class="copyable">';
    translationsContent += '<div class="translation-result">translation 2 for ' + sourceTextValue + '</div>';
    translationsContent += '<span class="copyable-copy"><span class="ui-icon ui-icon-copy"></span></span>';
    translationsContent += '</div>';
    translationsContent += '<div class="copyable">';
    translationsContent += '<div class="translation-result">translation 3 for ' + sourceTextValue + '</div>';
    translationsContent += '<span class="copyable-copy"><span class="ui-icon ui-icon-copy"></span></span>';
    translationsContent += '</div>';
    $('#translations').html(translationsContent);
}

/* --------------- selecting languages and MT-engines ----------------------- */
$('#mtEngineSelector input[name="mtEngines"]:radio').change(function() {
    var mtId = this.id;
    enableMtEnginesAsAvailable();
    setSingleMtEngineById(mtId);
});
function setSingleMtEngineById(mtId) {
    var mtEngine = machineTranslationEngines[mtId];
    $("#"+mtId).prop("checked",true).button("refresh");
    $('#errorNoMtFound').hide();
    $('#messageMultipleMtFound').hide();
    $("#sourceLocale").val(mtEngine.source).selectmenu("refresh");
    $("#targetLocale").val(mtEngine.target).selectmenu("refresh");
}
function enableMtEnginesAsAvailable() {
    var mtIdsAvailable = getMtEnginesAccordingToLanguages();
    $("#mtEngineSelector input:radio[name='mtEngines']").prop( "disabled", true );
    $("#mtEngineSelector input:radio[name='mtEngines']").prop("checked",false);
    for (var i in mtIdsAvailable) {
        var mtId = mtIdsAvailable[i];
        $("#"+mtId).button("enable").button("refresh");
      }
    $("#mtEngineSelector input:radio[name='mtEngines']").checkboxradio("refresh");
    if (mtIdsAvailable.length === 0) {
        $('#errorNoMtFound').show();
        $('#messageMultipleMtFound').hide();
        return;
    }
    if (mtIdsAvailable.length === 1) {
        setSingleMtEngineById(mtId);
        return;
    }
    if (mtIdsAvailable.length > 1) {
        $('#errorNoMtFound').hide();
        $('#messageMultipleMtFound').show();
        return;
    }
}
function getMtEnginesAccordingToLanguages() {
    var sourceLocale = $("#sourceLocale").val(),
        targetLocale = $("#targetLocale").val(),
        mtIdsAvailable = [],
        mtId,
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
    for (mtId in machineTranslationEngines) {
        if (machineTranslationEngines.hasOwnProperty(mtId)) {
            mtEngineToCheck = machineTranslationEngines[mtId];
            if (langIsOK(mtEngineToCheck.source,sourceLocale) && langIsOK(mtEngineToCheck.target,targetLocale)) {
                mtIdsAvailable.push(mtId); 
            }
        }
    }
    return mtIdsAvailable;
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
        $('#translations').html('');
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
$(".copyable").each(function() {
    var elCopy = $(this).find(".copyable-copy");
    elCopy.on("touchstart click", function(e) {
        var content = $(this).closest('.copyable').find('.translation-result').text();
        alert("TODO: copy '" + content + "'"); // TODO
    });
});