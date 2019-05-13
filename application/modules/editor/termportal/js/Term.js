const Term={
		
		$_searchTermsSelect:null,
		$_searchTermsHolder:null,
		$_resultTermsHolderUl:null,
		
		searchTermsResponse:[],
		termGroupsCache:[],
		termAttributeContainer:[],

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
	        this.$_searchTermsHolder=$('#searchTermsHolder');
            this.$_resultTermsHolderUl = $('#resultTermsHolder > ul');
		},
		
		initEvents:function(){
			var me=this;
			me.$_searchTermsSelect.on( "selectableselected", function( event, ui ) { // TODO: why does this take so long? maybe not global, but on each separately?
				me.findTermsAndAttributes($(ui.selected).attr('data-value'));
		    });
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
				lng=$('#languages').val();
			
			if(!lng){
				lng=$("input[name='languages']:checked").val();
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
				
				$("#finalResultContent").hide();
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
				me.$_searchTermsSelect.append(
						$('<li>').attr('data-value', item.groupId).attr('class', 'ui-widget-content search-terms-result').append(
								$('<div>').attr('class', 'ui-widget').append(item.desc)
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
		        me.groupTermAttributeData(me.termGroupsCache[termGroupid].rows[me.KEY_TERM_ATTRIBUTES]);
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
		            me.groupTermAttributeData(result.rows[me.KEY_TERM_ATTRIBUTES]);
		            
		            //reset term entry reload flag
		            me.reloadTermEntry=false;
		        }
		    })
		},
		
		/***
		 * Fill the termAttributeContainer grouped by term.
		 * 
		 * @param data
		 * @returns
		 */
		groupTermAttributeData:function(data){
		    if(!data || data.length<1){
		        return;
		    }
		    var me=this;
		    
		    me.termAttributeContainer=[];
		    var oldKey="",
		        newKey="",
		        count=-1;
		    
		    for(var i=0;i<data.length;i++){
		        newKey = data[i].termId;
		        if(newKey !=oldKey){
		            count++;
		            me.termAttributeContainer[count]=[];
		        }        
		        me.termAttributeContainer[count].push(data[i]);
		        oldKey = data[i].termId
		    }
		    
		    //merge the childs
		    for(var i=0;i<me.termAttributeContainer.length;i++){
		    	me.termAttributeContainer[i]=groupChildData(me.termAttributeContainer[i]);
		    }
		    
		    //draw the term groups
		    me.drawTermGroups();
		},
		
		/***
		 * Draw the tearm groups
		 * @returns
		 */
		drawTermGroups:function(){
			var me=this,
				$_termTable=$('#termTable');
			
		    if(!me.termAttributeContainer || me.termAttributeContainer.length<1){
		        return;
		    }
		    
		    $_termTable.empty();
		    $("#resultTermsHolder").show();
		    
		    var count=0,
		        rfcLanguage,
		        termAttributesHtmlContainer=[];
		    
		    
		    for(var i=0;i<me.termAttributeContainer.length;i++){
		    	var attribute=me.termAttributeContainer[i],
		    		rfcLanguage = getLanguageFlag(attribute[0].language),
		    		statusIcon=me.checkTermStatusIcon(attribute);//check if the term contains attribute with status icon

		    	//draw term header
		    	termAttributesHtmlContainer.push('<h3 data-term-value="'+attribute[0].desc+'" class="term-data">');
		    	
		    	//add empty space between
		    	termAttributesHtmlContainer.push(' ');
		    	
		    	//add language flag
		    	termAttributesHtmlContainer.push(rfcLanguage);
		    	
		    	//add empty space between
		    	termAttributesHtmlContainer.push(' ');
		    	
		    	//get term render data
		    	termAttributesHtmlContainer.push(me.getTermRenderData(attribute[0]));

		    	if(statusIcon){
		    		termAttributesHtmlContainer.push(statusIcon);
		    	}
		    	
		    	termAttributesHtmlContainer.push('</h3>');
		    	
		    	//draw term attriubtes
		    	termAttributesHtmlContainer.push('<div>');
		    	termAttributesHtmlContainer.push(me.drawTermAttributes(count));
		    	termAttributesHtmlContainer.push('</div>');
		    	
		    	//termAttributesHtmlContainer.push('<h3 data-term-value="'+attribute[0].desc+'">'+ rfcLanguage + ' ' + attribute[0].desc +' '+(statusIcon ? statusIcon : '') + '</h3><div>' + me.drawTermAttributes(count) + '</div>');
		        count++;
		    }
		    
		    if(termAttributesHtmlContainer.length>0){
		    	$_termTable.append(termAttributesHtmlContainer.join(''));
		    }
		    
		    if ($_termTable.hasClass('ui-accordion')) {
		    	$_termTable.accordion('refresh');
		    } else {
		    	$_termTable.accordion({
		            active: false,
		            collapsible: true,
		            heightStyle: "content"
		        });
		    }
		    
		    //find the selected item form the search result and expand it
		    $.each($("#searchTermsSelect li"), function (i, item) {
		        if($(item).hasClass('ui-state-active')){
		        	$_termTable.accordion({
		                active:false
		            });
		            
		            $.each($("#termTable h3"), function (i, termitem) {
		                if(termitem.dataset.termValue === item.textContent){
		                	$_termTable.accordion({
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
		 * @param termId
		 * @returns {String}
		 */
		drawTermAttributes:function(termId){
		    var me=this,
		    	attributes=me.termAttributeContainer[termId],
		        html = [],
		        tmpLang=attributes[0].language;
		    
		    if(Attribute.languageDefinitionContent[tmpLang]){
		    	html.push(Attribute.languageDefinitionContent[tmpLang]);
		    }
		    
		    for(var i=0;i<attributes.length;i++){
		    	html.push(Attribute.handleAttributeDrawData(attributes[i]));
		    }
		    
		    return html.join('');
		},
        
		/**
		 * Append the buttons for proposals in the DOM.
		 * Address elements as specific as possible (= avoid long jQuery-selects).
         * @param elements
		 */
        drawProposalButtons: function (elements){
            var me = this,
                userProposalRights=true, //TODO: get me from the backend;
                userProposalDecideRights=false, //TODO: get me from the backend;
                htmlProposalAddIcon,
                htmlProposalEditIcon,
                htmlProposalDeleteIcon,
                $_selector,$_selectorAdd,
                $titleAdd, $titleDelete, $titleEdit, $titleAddition;
            if(userProposalRights){
                // TODO: Tooltips: use translations
                htmlProposalAddIcon = '<span class="proposeTermBtn addTerm ui-icon ui-icon-squaresmall-plus"></span>';
                htmlProposalDeleteIcon = '<span class="proposeTermBtn deleteTerm ui-icon ui-icon-trash-b"></span>';
                htmlProposalEditIcon = '<span class="proposeTermBtn editTerm ui-icon ui-icon-pencil"></span>';
                switch(elements) {
                    case "term-entries":
                        $_selector = false;
                        $_selectorAdd = me.$_searchTermsHolder;
                        $titleAdd = 'Add Term-Entry';
                      break;
                    case "term-entry":
                        $_selector = me.$_resultTermsHolderUl;
                        $_selectorAdd = false;
                        $titleDelete = 'Delete Term-Entry';
                        $titleEdit = 'Edit Term-Entry';
                        break;
                    case "term-entry-attributes":
                        $_selector = $('#termAttributeTable .attribute-data');
                        $_selectorAdd = $('#termAttributeTable');
                        $titleAdd = 'Add Term-Entry-Attribute';
                        $titleDelete = 'Delete Term-Entry-Attribute';
                        $titleEdit = 'Edit Term-Entry-Attribute';
                      break;
                    case "terms":
                        $_selector = $('#termTable .term-data');
                        $_selectorAdd = $('#termTable');
                        $titleAdd = 'Add Term';
                        $titleDelete = 'Delete Term';
                        $titleEdit = 'Edit Term';
                        break;
                    case "terms-attribute":
                        $_selector = $('#termTable .attribute-data');
                        $_selectorAdd = $('#termTable .term-data').next('div');
                        $titleAdd = 'Add Term-Attribute';
                        $titleDelete = 'Delete Term-Attribute';
                        $titleEdit = 'Edit Term-Attribute';
                      break;
                }
                $titleAddition = (userProposalDecideRights) ? '' : ' (Proposal only)';
                if ($_selector && $_selector.children('.proposeTermBtn').length === 0) {
                    $_selector.append(htmlProposalDeleteIcon+htmlProposalEditIcon);
                    $_selector.children('.editTerm').prop('title', $titleEdit + $titleAddition);
                    $_selector.children('.deleteTerm').prop('title', $titleDelete + $titleAddition);
                }
                return; // Add-Buttons are not implemented currently
                if ($_selectorAdd && $_selectorAdd.children('.proposeTermBtn').length === 0) {
                    $_selectorAdd.append(htmlProposalAddIcon);
                    $_selectorAdd.find('.addTerm').prop('title', $titleAdd + $titleAddition);
                }
                
            }
        },
		
		/***
		 * Check the term status icon in the term attributes.
		 * Return the image html if such an attribute is found
		 * @param attribute
		 * @returns
		 */
		checkTermStatusIcon:function(attribute){
		    var retVal="", 
		        status = 'unknown', 
		        map = Editor.data.termStatusMap,
		        labels = Editor.data.termStatusLabel,
		        label;
		    $.each($(attribute), function (i, attr) {
		        var statusIcon=Attribute.getAttributeValue(attr),
		        	cmpStr='<img src="';
		        if(statusIcon && statusIcon.slice(0, cmpStr.length) == cmpStr){
		            retVal+=statusIcon;
		        }
		    });
		    if(map[attribute[0].termStatus]) {
		        status = map[attribute[0].termStatus];
		    }
		    //FIXME encoding of the string!
		    label = labels[status]+' ('+attribute[0].termStatus+')';
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
			
			if(!userHasTermProposalRights){
				return termData.desc!=undefined ? termData.desc : termData.term;
			}
			
			//the proposal is allready defined, render the proposal
			if(termData.proposal && termData.proposal!=''){
				htmlCollection.push('<del>'+termData.desc!=undefined ? termData.desc : termData.term+'</del>');
				htmlCollection.push('<ins>'+termData.proposal+'</ins>');
				return htmlCollection.join(' ');
			}
			//the user has proposal rights -> init term proposal span
			return Attribute.getProposalDefaultHtml('term',termData.termId,termData.desc!=undefined ? termData.desc : termData.term);
		}
};

Term.init();
