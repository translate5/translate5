/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * @class Editor.plugins.TermTagger.view.TermPortlet
 */
Ext.define('Editor.plugins.TermTagger.view.TermPortlet', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.termPortalTermPortlet',
    autoScroll: true,
    border: 0,
    minHeight: 60, //needed so that loader is fully shown on segments without terms
    itemId: 'metaTermPanel',
    cls: 'metaTermPanel',
    loader: {
        url: Editor.data.restpath + 'segment/terms',
        loadMask: true,
        renderer: 'data'
    },

    tpl:new Ext.XTemplate(
        '<tpl if="noTerms">',
            '<p style="margin-bottom:5px;">{locales.noTermsMessage}</p>',
        '<tpl else>',
                '<dl>',
                    '<div class="term-box">',
                        '<tpl foreach="termGroups">',
                            '{[this.rendereValues(values,xindex,parent)]}',
                        '</tpl>',
                    '</div>',
                '</dl>',
        '</tpl>'
        ,{
            /***
             * Render term values as source and target groups.
             * @param terms
             * @param xindex
             * @param parent
             * @returns {string}
             */
            rendereValues: function (terms,xindex,parent){
                var source = [],
                    target = [],
                    classes = [],
                    term = {},
                    termValue = '',
                    termHtml = '',
                    isSourceRtl = false,
                    isTargetRtl = false,
                    tpl = '<div class="term-group ' + (xindex % 2 === 0 ? "even" : "odd") + '">\n' +
                        '    <div class={2} >\n' +
                        '    {0}    ' +
                        '    </div>\n' +
                        '    <div class={3} >\n' +
                        '    {1}    ' +
                        '    </div>\n' +
                        '</div>';


                for(var i=0;i<terms.length; i++){

                    term = terms[i];
                    classes = [];


                    classes.push('term',term.status);

                    if(term.used){
                        classes.push('used');
                    }

                    if(term.transFound){
                        classes.push('transfound');
                    }

                    // if linkPortal is enabled, on term click the term will be opened in the term portal in new
                    // browser tab
                    if(parent.linkPortal){
                        //for the syntax of the search array see TermPortal.view.termportal.TermportalController::encode64
                        var search = [
                            term.term,
                            term.languageId,
                            '', //empty client-id filter
                            term.collectionId,
                        ];
                        var url = parent.applicationRundir  + '/editor/termportal#termportal/search/' + btoa(JSON.stringify(search));
                        termValue = '<a href="' + url + '" target="termportalandinstanttranslate">' + term.term + '</a>';
                    }else {
                        termValue = term.term;
                    }

                    // get the term status image
                    termHtml = this.getTermStatusImage(term.status,parent);

                    // generate the term attributes tooltip and add the term classes
                    termHtml += '<span data-qtip="' // tooltip start
                        +this.renderAttributes(term.id,term.termEntryId,term.language,parent)
                        +'" ' + // tooltip end
                        'class="'+classes.join(' ')+'">' + termValue + '</span>' ;

                    // put the term in the correct group (source or target)
                    if(term.isSource){

                        isSourceRtl = term.rtl || isSourceRtl;

                        source.push(termHtml);
                    }else {
                        isTargetRtl = term.rtl || isTargetRtl;

                        target.push(termHtml);
                    }
                }

                tpl = tpl.replace('{0}',source.join('<br/>'));
                tpl = tpl.replace('{1}',target.join('<br/>'));

                classes = ['"source-terms']
                // check if the term is rtl
                if(isSourceRtl){
                    classes.push('direction-rtl"','dir="rtl"');
                }else {
                    classes.push('"');
                }
                tpl = tpl.replace('{2}',classes.join(' '));

                classes = ['"target-terms']
                if(isTargetRtl){
                    classes.push('direction-rtl"','dir="rtl"');
                }else {
                    classes.push('"');
                }
                tpl = tpl.replace('{3}',classes.join(' '));
                return tpl;
            },

            /***
             * Get all term, termEntry and language level attributes for the given term.
             * This will generate nice tooltip html layout to display the attributes.
             * @param termId
             * @param termEntryId
             * @param language
             * @param parent
             * @returns {string}
             */
            renderAttributes: function (termId,termEntryId,language,parent){

                var renderHtml = '<div>',
                    attributes = parent.attributeGroups,
                    html = [],
                    flag = null;

                attributes['entry'].forEach(function (attribute){
                    if(attribute['termEntryId'] === termEntryId){
                        html.push('<li>' + attribute['nameTranslated'] + ' :<i> ' + attribute['value'] + '</i></li>');
                    }
                });


                if(html.length > 0){
                    renderHtml += '<h3>' +parent.locales.entryAttrs +'</h3>';
                    renderHtml += '<ul>' + html.join('') + '</ul>';
                }

                html = [];

                flag = parent.flags[language] ? parent.flags[language] : null;

                if(!flag || Ext.isEmpty(flag.iso3166Part1alpha2)){
                    renderHtml += '<h3>' + language + '</h3>';
                }else {
                    renderHtml += '<img src=\'/modules/editor/images/flags/' + flag.iso3166Part1alpha2 + '.png\'>';
                }

                attributes['language'].forEach(function (attribute){
                    if(attribute['language'] === language){
                        html.push('<li>' + attribute['nameTranslated'] + ' : <i>' + attribute['value'] + '</i></li>');
                    }
                });

                if(html.length > 0){
                    renderHtml += '<h3>' +parent.locales.languageAttrs + '</h3>';
                    renderHtml += '<ul>' + html.join('') + '</ul>';
                }

                html = [];

                attributes['term'].forEach(function (attribute){
                    if(attribute['termId'] === termId){
                        html.push('<li>' + attribute['nameTranslated'] + ' : <i>' + attribute['value'] + '</i></li>');
                    }
                });


                if(html.length > 0){
                    renderHtml += '<h3>' + parent.locales.termAttrs + '</h3>';
                    renderHtml += '<ul>' + html.join('') + '</ul>';
                }

                renderHtml += '</div>';

                return renderHtml;

            },

            /***
             * Get the term status image
             * @param status
             * @param parent
             * @returns {string}
             */
            getTermStatusImage: function (status, parent){

                var guiStatus = '',
                    src = '',
                    title = '';

                if(Ext.isEmpty(parent.termStatMap[status]) || Ext.isEmpty(parent.termStatus[parent.termStatMap[status]])) {
                    guiStatus = 'unknown';
                }
                else {
                    guiStatus = parent.termStatMap[status];
                }

                src = parent.publicModulePath + '/images/termStatus/' + guiStatus + '.png';
                title = parent.termStatus[guiStatus] + ' (' + status + ')';

                return '<img src="'+src+'" alt="'+title+'" title="'+title+'"/>';
            }
        }),
});