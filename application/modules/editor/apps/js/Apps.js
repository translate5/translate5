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
var activeIframe=[];

/***
 * Load translate5 app as iframe.
 */
function loadIframe(appName,url,params){
    var iframeId=appName+'Iframe';
    url = url+(params ? ("?"+params) : "");

    //hide all other apps, and show only the requested one
    $.each(activeIframe, function( index, value ) {
        var frameId="#"+value;
        if(value != iframeId){
            $(frameId).hide();
        }else{
            if(params && params!=""){
                $(frameId).attr("src", url);
            }
            $(frameId).toggle();
        }
    });

    //the app exist
    if(jQuery.inArray(iframeId,activeIframe)!== -1){
        return;
    }

    //the app does not exist, save the appId and create an iframe
    activeIframe.push(iframeId);

    $('<iframe>', {
        src: url,
        class:'iframeApp',
        id:  iframeId,
        frameborder: 0,
        scrolling: 'yes'
    }).appendTo('#appContainer');
}
