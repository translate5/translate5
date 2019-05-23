const Term={
		
        $_searchTermsSelect:null,
		$_termTable:null,
		
		// for proposal-buttons:
		$_TermEntriesList_Header: null,
		$_TermEntryHeader: null,
		$_TermEntryAttributesHolder: null,
		$_TermsHolder: null,
        
		searchTermsResponse:[],
		termGroupsCache:[],

		disableLimit:false,
		reloadTermEntry:false,//shoul the term entry be reloaded or fatched from the cache
		
		KEY_TERM:"term",
		KEY_TERM_ATTRIBUTES:"termAttributes",
		
		init:function(){
			this.cacheDom();
			this.initEvents();
		},
		
		cacheDom:function(){
			this.$_searchTermsSelect=$('#searchTermsSelect');
            this.$_termTable=$('#termTable');
            
            this.$_TermEntriesList_Header = $('#resultTermEntriesHolder > ul');
            this.$_TermEntryHeader = $('#resultTermsHolder > ul');
            this.$_TermEntryAttributesHolder = $('#termAttributeTable');
            this.$_TermsHolder = $('#termTable');
		},
		
		initEvents:function(){
			var me=this;
			me.$_searchTermsSelect.on( "selectableselected", function( event, ui ) { // FIXME: why is this triggered twice sometimes (with attr('data-value') = "undefined" in the second)
				me.findTermsAndAttributes($(ui.selected).attr('data-value'));
		    });
			
            // TermEntries
            me.$_TermEntriesList_Header.on('click', ".proposal-add",{scope:me},me.onAddTermEntryClick);
            me.$_TermEntryHeader.on('click', ".proposal-delete",{scope:me},me.onDeleteTermEntryClick);
            me.$_TermEntryHeader.on('click', ".proposal-edit",{scope:me},me.onEditTermEntryClick);
            me.$_TermEntryAttributesHolder.on('click', ".proposal-add",{scope:me},me.onAddTermEntryAttributeClick); // step3
            me.$_TermEntryAttributesHolder.on('click', ".attribute-data .proposal-delete",{scope:me},me.onDeleteTermEntryAttributeClick);
            me.$_TermEntryAttributesHolder.on('click', ".attribute-data .proposal-edit",{scope:me},me.onEditTermEntryAttributeClick);
            
            // Terms
            me.$_TermsHolder.on('click', "> .proposal-add",{scope:me},me.onAddTermClick);
            me.$_termTable.on('click', ".term-data .proposal-delete",{scope:me},me.onDeleteTermClick);
            me.$_termTable.on('click', ".term-data .proposal-edit",{scope:me},me.onEditTermClick);
            me.$_termTable.on('click', ".term-attributes .proposal-add",{scope:me},me.onAddTermAttributeClick); // step3
            me.$_termTable.on('click', ".attribute-data .proposal-delete",{scope:me},me.onDeleteTermAttributeClick);
            me.$_termTable.on('click', ".attribute-data .proposal-edit",{scope:me},me.onEditTermAttributeClick);
		},
		
		/***
		 * Search the term in the language and term collection
		 * @param searchString
		 * @param disableLimit: disable the search limit on the backend
		 * @param successCallback
		 * @returns
		 */
		searchTerm:function(searchString,successCallback){
			var me=this,
				lng=$('#language').val(),
				collectionIds = me.getFilteredCollections(),
                processStats = me.getFilteredProcessStats();
			
			if(!lng){
				lng=$("input[name='language']:checked").val();
			}
			console.log("searchTerm() for: " + searchString);
			console.log("searchTerm() for language: " + lng);
			me.searchTermsResponse=[];  
			$.ajax({
				url: Editor.data.termportal.restPath+"termcollection/search",
				dataType: "json",
				type: "POST",
				data: {
					'term':searchString,
					'language':lng,
					'collectionIds':collectionIds,
                    'processStats':processStats,
					'disableLimit':me.disableLimit
				},
				success: function(result){
					me.searchTermsResponse=result.rows[me.KEY_TERM];
					if(successCallback){
						successCallback(result.rows[me.KEY_TERM]);
					}
					me.fillSearchTermSelect();
				}
			});
		},
        
        /**
         * Return all the Term-Collections that are set in the tag field.
         * @returns {Array}
         */
        getFilteredCollections: function() {
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
        },
        
        /**
         * Return all the clients that are set in the tag field.
         * @returns {Array}
         */
        getFilteredClients: function() {
            var filteredClients = [];
            $( '#searchFilterTags input.filter.client' ).each(function( index, el ) {
                filteredClients.push(el.value);
            });
            return filteredClients;
        },
        
        /**
         * Return all the prcoessStats that are set in the tag field.
         * @returns {Array}
         */
        getFilteredProcessStats: function() {
            var filteredProcessStats = [];
            $( '#searchFilterTags input.filter.processStatus' ).each(function( index, el ) {
                filteredProcessStats.push(el.value);
            });
            return filteredProcessStats;
        },
		
		/***
		 * Fill up the term option component with the search results
		 * @returns
		 */
		fillSearchTermSelect:function(){
			var me=this;
			
			if(!me.searchTermsResponse){
				
				$('#error-no-results').show();
				
				console.log("fillSearchTermSelect: nichts gefunden");
				
				if(me.disableLimit){
					me.disableLimit=false;
				}
				
				$("#finalResultContent").show();
                me.drawProposalButtons('term-entries');
				$("#resultTermsHolder").hide();
				return;
			}
			
			$('#error-no-results').hide();
			me.$_searchTermsSelect.empty();
			
			console.log("fillSearchTermSelect: " + me.searchTermsResponse.length + " Treffer");
			
			if(me.disableLimit){
				console.log("fillSearchTermSelect: me.disableLimit");
				
				me.disableLimit=false;
				
				//if only one record, find the attributes and display them
				if(me.searchTermsResponse.length===1){
					console.log("fillSearchTermSelect: only one record => find the attributes and display them");
					me.findTermsAndAttributes(me.searchTermsResponse[0].groupId);
				}
				
				if(me.searchTermsResponse.length>0){
					showFinalResultContent();
				}
				
                me.drawProposalButtons('term-entries');
				
			}
			
			if(!me.$_searchTermsSelect.is(":visible")){
				return;
			}
			
			if(me.searchTermsResponse.length > 1){
				$("#resultTermsHolder").hide();
			}
			
			//fill the term component with the search results
			for(var i=0;i<me.searchTermsResponse.length;i++){
				var item=me.searchTermsResponse[i];
				me.$_searchTermsSelect.append( // FIXME; this takes too long
						$('<li>').attr('data-value', item.groupId).attr('class', 'ui-widget-content search-terms-result').append(
								$('<div>').attr('class', 'ui-widget').append(item.label)
						));
			}
			
			if (me.$_searchTermsSelect.hasClass('ui-selectable')) {
				me.$_searchTermsSelect.selectable("destroy");
			}
			
			me.$_searchTermsSelect.selectable();
			
			var searchTermsSelectLi=me.$_searchTermsSelect.find('li');
			
			searchTermsSelectLi.mouseenter(function() {
				$(this).addClass('ui-state-hover');
			});
			searchTermsSelectLi.mouseleave(function() {
				$(this).removeClass('ui-state-hover');
			});
			me.$_searchTermsSelect.on( "selectableselecting", function( event, ui ) {
				$(ui.selecting).addClass('ui-state-active');
			});
			me.$_searchTermsSelect.on( "selectableunselecting", function( event, ui ) {
				$(ui.unselecting).removeClass('ui-state-active');
			});
			me.$_searchTermsSelect.on( "selectableselected", function( event, ui ) {
				$(ui.selected).addClass('ui-state-active');
			});
			
			if(me.searchTermsResponse.length==1){
				me.$_searchTermsSelect.find("li:first-child").addClass('ui-state-active').addClass('ui-selected');
			}
			
			// "reset" search form
			$("#search").autocomplete( "search", $("#search").val('') );
		},
		
		/***
		 * Find all terms and terms attributes for the given term entry id (groupId)
		 * @param termGroupid
		 * @returns
		 */
		findTermsAndAttributes:function(termGroupid){
			var me=this;
		    console.log("findTermsAndAttributes() for: " + termGroupid);
		    Attribute.languageDefinitionContent=[];
		    
		    //check the cache
		    if(!me.reloadTermEntry && me.termGroupsCache[termGroupid]){
		    	TermEntry.drawTermEntryAttributes(me.termGroupsCache[termGroupid].rows[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);
		        me.drawTermGroups(me.termGroupsCache[termGroupid].rows[me.KEY_TERM_ATTRIBUTES]);
		        return;
		    }
		    
		    $.ajax({
		        url: Editor.data.termportal.restPath+"termcollection/searchattribute",
		        dataType: "json",
		        type: "POST",
		        data: {
		            'groupId':termGroupid
		        },
		        success: function(result){
		            //store the results to the cache
		            me.termGroupsCache[termGroupid]=result;
		            
		            TermEntry.drawTermEntryAttributes(result.rows[TermEntry.KEY_TERM_ENTRY_ATTRIBUTES]);

		            me.drawTermGroups(result.rows[me.KEY_TERM_ATTRIBUTES]);
		            //reset term entry reload flag
		            me.reloadTermEntry=false;
		        }
		    })
		},
		
		/***
		 * Draw the tearm groups
		 * @returns
		 */
		drawTermGroups:function(termsData){
			var me=this;
			
		    if(!termsData || termsData.length<1){
		        return;
		    }
		    
		    me.$_termTable.empty();
		    $("#resultTermsHolder").show();
		    
		    var termAttributesHtmlContainer=[],
                $_filteredCollections = $('#searchFilterTags input.filter.collection'),
                $_filteredClients = $('#searchFilterTags input.filter.client'),
                showInfosForSelection = ($_filteredCollections.length > 1 || $_filteredClients.length > 1);
		    
		    $.each(termsData, function (i, term) {
		    	var termRflLang=term.attributes[0]!=undefined ? term.attributes[0].language : '',
		    		rfcLanguage = getLanguageFlag(termRflLang),
		    		statusIcon=me.checkTermStatusIcon(term), //check if the term contains attribute with status icon
                    infosForSelection = '',
                    filteredCientsNames = [],
                    filteredCollectionsNames = [],
                    clientId,
                    clientName;
                
		    	//draw term header
		    	termAttributesHtmlContainer.push('<h3 class="term-data" data-term-value="'+term.term+'" data-term-id="'+term.termId+'">');
		    	
		    	
		    	//add empty space between
		    	termAttributesHtmlContainer.push(' ');
		    	
		    	//add language flag
		    	termAttributesHtmlContainer.push(rfcLanguage);
		    	
		    	//add empty space between
		    	termAttributesHtmlContainer.push(' ');
		    	
		    	//get term render data
		    	termAttributesHtmlContainer.push(me.getTermRenderData(term));
	
		    	if(statusIcon){
		    		termAttributesHtmlContainer.push(statusIcon);
		    	}
                
                //add empty space between
                termAttributesHtmlContainer.push(' ');
                
                //add client- and termCollection-names like this: [CUSTOMERNAME; termCollectionNAME]
                //(display only those that are selected for filtering)
                if (showInfosForSelection) {
                    infosForSelection = '';
                    clientsForCollection = collectionsClients[term.collectionId];
                    for (i = 0; i < clientsForCollection.length; i++) {
                        clientId = clientsForCollection[i];
                        clientName = clientsNames[clientId];
                        if ($("#searchFilterTags").tagit("assignedTags").indexOf(clientName) != -1) {
                            filteredCientsNames.push(clientName);
                        }
                    }
                    infosForSelection = filteredCientsNames.join(', ');
                    collectionName = collectionsNames[term.collectionId];
                    if ($("#searchFilterTags").tagit("assignedTags").indexOf(collectionName) != -1) {
                        infosForSelection += '; '+collectionName;
                    }
                    termAttributesHtmlContainer.push('<span class="selection-infos">['+infosForSelection+']</span>');
                }
		    	
		    	termAttributesHtmlContainer.push('</h3>');
		    	
		    	//draw term attriubtes
		    	termAttributesHtmlContainer.push('<div data-term-id="'+term.termId+'" class="term-attributes">');
		    	termAttributesHtmlContainer.push(me.drawTermAttributes(term.attributes,termRflLang));
		    	termAttributesHtmlContainer.push('</div>');
		    });
		    if(termAttributesHtmlContainer.length>0){
		    	me.$_termTable.append(termAttributesHtmlContainer.join(''));
		    }
		    
		    if (me.$_termTable.hasClass('ui-accordion')) {
		    	me.$_termTable.accordion('refresh');
		    } else {
		    	me.$_termTable.accordion({
		            active: false,
		            //collapsible: true,
		            heightStyle: "content"
		        });
		    }
		    
		    //find the selected item form the search result and expand it
		    $.each($("#searchTermsSelect li"), function (i, item) {
		        if($(item).hasClass('ui-state-active')){
		        	me.$_termTable.accordion({
		                active:false
		            });
		            
		            $.each($("#termTable h3"), function (i, termitem) {
		                if(termitem.dataset.termValue === item.textContent){
		                	me.$_termTable.accordion({
		                        active:i
		                    });
		                }
		            });
		            
		        }
		    });

            me.drawProposalButtons('terms-attribute');
            me.drawProposalButtons('terms');
		    
		    setSizesInFinalResultContent();
		},
		
		/***
		 * Render term attributes by given term
		 * 
		 * @param attributes
		 * @param termLang
		 * @returns {String}
		 */
		drawTermAttributes:function(attributes,termLang){
		    var me=this,
		        html = [],
		        commentAttribute=[];
		    
		    if(Attribute.languageDefinitionContent[termLang]){
		    	html.push(Attribute.languageDefinitionContent[termLang]);
		    }
		    
		    for(var i=0;i<attributes.length;i++){
		    	//comment attribute should always appear as first
		    	if(attributes[i].name=='note'){
		    		commentAttribute.push(Attribute.handleAttributeDrawData(attributes[i]));
		    		continue;
		    	}
		    	html.push(Attribute.handleAttributeDrawData(attributes[i]));
		    }
		    html=commentAttribute.concat(html);
		    
		    return html.join('');
		},
        
		/**
		 * Append the buttons for proposals in the DOM.
		 * Address elements as specific as possible (= avoid long jQuery-selects).
         * @param elements
		 */
        drawProposalButtons: function (elements){
            var me = this,
                htmlProposalAddIcon,
                htmlProposalEditIcon,
                htmlProposalDeleteIcon,
                $_selector,$_selectorAdd,
                $titleAdd, $titleDelete, $titleEdit,
                $_TermsHeaders, $_TermEntryAttributes, $_TermsAttributesHolder, $_TermsAttributesHeaders;
            htmlProposalAddIcon = '<span class="proposal-btn proposal-add ui-icon ui-icon-squaresmall-plus"></span>';
            htmlProposalDeleteIcon = '<span class="proposal-btn proposal-delete ui-icon ui-icon-trash-b"></span>';
            htmlProposalEditIcon = '<span class="proposal-btn proposal-edit ui-icon ui-icon-pencil"></span>';
            switch(elements) {
                case "term-entries":
                    $_selector = false;
                    $_selectorAdd = me.$_TermEntriesList_Header;
                    $titleAdd = translations['addTermEntry'];
                    htmlProposalAddIcon = '<span class="proposal-btn proposal-add ui-icon ui-icon-plus"></span>';
                  break;
                case "term-entry":
                    $_selector = me.$_TermEntryHeader;
                    $_selectorAdd = false;
                    $titleDelete = translations['deleteTermEntry'];
                    $titleEdit = translations['editTermEntry'];
                    break;
                case "term-entry-attributes":
                    $_TermEntryAttributes = $('#termAttributeTable .attribute-data'); // cannot use cacheCom(), rendered too late
                    $_selector = $_TermEntryAttributes;
                    $_selectorAdd = me.$_TermEntryAttributesHolder; // false
                    $titleAdd = translations['addTermEntryAttribute'];
                    $titleDelete = translations['deleteTermEntryAttribute'];
                    $titleEdit = translations['editTermEntryAttribute'];
                  break;
                case "terms":
                    $_TermsHeaders = $('#termTable .term-data'); // cannot use cacheCom(), rendered too late
                    $_selector = $_TermsHeaders;
                    $_selectorAdd = me.$_TermsHolder; // false;
                    $titleAdd = translations['addTerm'];
                    $titleDelete = translations['deleteTerm'];
                    $titleEdit = translations['editTerm'];
                    break;
                case "terms-attribute":
                    $_TermsAttributesHolder = $('#termTable .term-data').next('div'); // cannot use cacheCom(), rendered too late
                    $_TermsAttributesHeaders = $('#termTable .attribute-data'); // cannot use cacheCom(), rendered too late
                    $_selector = $_TermsAttributesHeaders;
                    $_selectorAdd = $_TermsAttributesHolder; // false;
                    $titleAdd = translations['addTermAttribute'];
                    $titleDelete = translations['deleteTermAttribute'];
                    $titleEdit = translations['editTermAttribute'];
                  break;
            }
            if ($_selector && $_selector.children('.proposal-btn').length === 0) {
                $_selector.append(htmlProposalDeleteIcon+htmlProposalEditIcon);
                $_selector.children('.proposal-edit').prop('title', $titleEdit);
                $_selector.children('.proposal-delete').prop('title', $titleDelete);
            }
            if ($_selectorAdd && $_selectorAdd.children('.proposal-btn').length === 0) {
                $_selectorAdd.append(htmlProposalAddIcon);
                $_selectorAdd.find('.proposal-add').prop('title', $titleAdd);
            }
        },
		
		/***
		 * Check the term status icon in the term attributes.
		 * Return the image html if such an attribute is found
		 * @param term
		 * @returns
		 */
		checkTermStatusIcon:function(term){
		    var retVal="", 
		    	attributes=term.attributes,
		        status = 'unknown', 
		        map = Editor.data.termStatusMap,
		        labels = Editor.data.termStatusLabel,
		        label;
		    $.each(attributes, function (i, attr) {
		        var statusIcon=Attribute.getAttributeValue(attr),
		        	cmpStr='<img src="';
		        if(statusIcon && statusIcon.slice(0, cmpStr.length) == cmpStr){
		            retVal+=statusIcon;
		        }
		    });
		    if(map[term.termStatus]) {
		        status = map[term.termStatus];
		    }
		    //FIXME encoding of the string!
		    label = labels[status]+' ('+term.termStatus+')';
		    retVal += ' <img src="' + moduleFolder + 'images/termStatus/'+status+'.png" alt="'+label+'" title="'+label+'">';
		    return retVal;
		},
		
		
		/***
		 * Get the term render data. If the user has proposal tights, the term proposal render data will be set.
		 */
		getTermRenderData:function(termData){
			var me=this,
				htmlCollection=[],
				userHasTermProposalRights=true;//TODO: get me from backend
			
			if(!userHasTermProposalRights){// || !attributeData.proposable){
				return termData.term;
			}
			
			//the proposal is not set, init the component editor
			if(!termData.proposal){
				//the user has proposal rights -> init term proposal span
				return Attribute.getProposalDefaultHtml('term',termData.termId,termData.term,termData);
			}
			
			//the proposal is allready defined, render the proposal
			htmlCollection.push('<del>'+termData.term+'</del>');
			htmlCollection.push('<ins>'+termData.proposal.term+'</ins>');
			return htmlCollection.join(' ');
		},
        
        /**
         * Returns term data for creating a new term.
         */
        renderNewTermData: function(newTermAttributes) {
            var newTermData = {};
            // TODO: what data to use?
            newTermData = {0: {
                'attributes': newTermAttributes,
                'collectionId': null,
                'definition': "",
                'desc': "",
                'groupId': null,
                'label': "",
                'languageId': null, 
                'proposal': null,
                'term': "",
                'termId': null,
                'termStatus': null,
                'value': null
            }};
            return newTermData;
        },
        
        /***
         * On add term-entry icon click handler:
         * Render "result" with empty (dummy) data and open it for editing.
         */
        onAddTermEntryClick: function(eventData){
            var me = eventData.data.scope,
                newTermEntryAttributes = Attribute.renderNewTermEntryAttributes(),
                newTermAttributes = Attribute.renderNewTermAttributes(),
                newTermData = me.renderNewTermData(newTermAttributes);
            console.log('onAddTermEntryClick');
            TermEntry.drawTermEntryAttributes(newTermEntryAttributes);
            me.drawTermGroups(newTermData);
            me.$_termTable.find('.proposal-edit')[0].click();
            // TODO: choose which collectionId the new term-entry shall belong to
        },
        
        /***
         * On delete term-entry icon click handler
         */
        onDeleteTermEntryClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onDeleteTermEntryClick');
            // TODO
        },
        
        /***
         * On edit term-entry icon click handler
         */
        onEditTermEntryClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onEditTermEntryClick');
            // TODO
        },

        /***
         * On add term-entry-attribute icon click handler
         */
        onAddTermEntryAttributeClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onAddTermEntryAttributeClick');
            // TODO
        },
        
        /***
         * On delete term-entry-attribute icon click handler
         */
        onDeleteTermEntryAttributeClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onDeleteTermEntryAttributeClick');
            // TODO
        },
        
        /***
         * On edit term-entry-attribute icon click handler
         */
        onEditTermEntryAttributeClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onEditTermEntryAttributeClick');
            // TODO
        },
        
        /***
         * On add term icon click handler
         */
        onAddTermClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onAddTermClick');
            // TODO
        },
		
		/***
		 * On remove proposal click handler
		 */
		onDeleteTermClick:function(eventData){
			var me = eventData.data.scope,
                $element=$(this),
				$parent=$element.parents('h3[data-term-id]');
			
			if(parent.length==0){
				return;
			}
			
			//ajax call to the remove proposal action
			var me=eventData.data.scope,
				url=Editor.data.termportal.restPath+'term/{ID}/removeproposal/operation'.replace("{ID}",$parent.data('term-id'));
			$.ajax({
		        url: url,
		        dataType: "json",	
		        type: "POST",
		        success: function(result){
		        	//the term proposal is removed, render the initial term proposable content
		        	var renderData=me.getTermRenderData(result.rows),
		        		ins=$parent.find('ins');
		        		
		        	ins.replaceWith(renderData);
		        	$parent.find('del').empty();
		        	
		        }
		    });
		},
        
        /***
         * On edit term icon click handler
         */
        onEditTermClick:function(eventData){
            var me = eventData.data.scope,
                element=$(this),
                parent=element.parent(),
                search=parent.find("span[data-editable]");
            console.log('onEditTermClick');
            ComponentEditor.addComponentEditor(search);
        },

        /***
         * On add term-attribute icon click handler
         */
        onAddTermAttributeClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onAddTermAttributeClick');
            // TODO
        },
        
        /***
         * On delete term-attribute icon click handler
         */
        onDeleteTermAttributeClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onDeleteTermAttributeClick');
            // TODO
        },
        
        /***
         * On edit term-attribute icon click handler
         */
        onEditTermAttributeClick: function(eventData){
            var me = eventData.data.scope;
            console.log('onEditTermAttributeClick');
            // TODO
        },
};

Term.init();
