const Attribute={
	languageDefinitionContent:[],
	
	init:function(){
	},
	
	/***
	 * Find child's for the attribute, and build the data in needed structure
	 *  
	 * @param attribute
	 * @returns html
	 */
	handleAttributeDrawData:function(attribute){
	    
	    if(!attribute.attributeId){
	        return noExistingAttributes; // see /application/modules/editor/views/scripts/termportal/index.phtml
	    }
	    
	    var me=this,
	    	html='',
            proposable = attribute.proposable ? ' proposable' : '';
	    	headerTagOpen='<h4 class="ui-widget-header ui-corner-all attribute-data' + proposable + '">',
	    	headerTagClose='</h4>';
	    
	    switch(attribute.name) {
	        case "transac":
	            
	            
	            var header=me.handleAttributeHeaderText(attribute,true);
	            
	            html += headerTagOpen + header + headerTagClose;
	            
	            if(attribute.children && attribute.children.length>0){
	                var childData=[];
	                attribute.children.forEach(function(child) {
	                    //get the header text
	                    childDataText=me.handleAttributeHeaderText(child,true);
	                    
	                    var attVal=me.getAttributeValue(child);
	                    
	                    //the data tag is displayed as first in this group
	                    if(child.name ==="date"){
	                        childData.unshift('<p>' + childDataText + ' ' + attVal+'</p>')
	                        return true;
	                    }
	                    childData.push('<p>' + childDataText + ' ' + attVal+'</p>')
	                });
	                html+=childData.join('');
	            }
	            break;
	        case "descrip":
	            
	            var attVal=me.getAttributeValue(attribute);
	
	            var flagContent="";
	            //add the flag for the definition in the term entry attributes
	            if(attribute.attrType=="definition" && attribute.language){
	                flagContent=" "+getLanguageFlag(attribute.language);
	            }
	
	            var headerText="";
	            
	            if(flagContent && flagContent!=""){
	                headerText =me.handleAttributeHeaderText(attribute,false)+flagContent;
	            }else{
	                headerText =me.handleAttributeHeaderText(attribute)+":";
	            }
	            
	            html=headerTagOpen + headerText + headerTagClose + '<p>' + attVal + '</p>';
	            
	            //if it is definition on language level, get store the data in variable so it is displayed also on term language level
	            if(attribute.attrType=="definition" && attribute.language){
	                me.languageDefinitionContent[attribute.language]="";
	                if(attribute.children && attribute.children.length>0){
	                    attribute.children.forEach(function(child) {
	                        html+=me.handleAttributeDrawData(child);
	                    });
	                }
	                
	                //remove the flag from the html which will be displayed in the term
	                me.languageDefinitionContent[attribute.language]=html.replace(flagContent,'');
	            }
	            
	            break;
	        default:
	            
	            var attVal=me.getAttributeValue(attribute);
	            
	            var headerText = me.handleAttributeHeaderText(attribute,true);
	            
	            html=headerTagOpen + headerText + headerTagClose + '<p>' + attVal + '</p>';
	            break;
	    }
	    return html;
	},
	
	/***
	 * Build the attribute text, based on if headerText (db translation for the attribute) is provided
	 * @param attribute: entry or term attribute
	 * @param addColon: add colon on the end of the header text
	 * @returns
	 */
	handleAttributeHeaderText:function(attribute,addColon){
	    var me=this,
	    	noHeaderName=attribute.name + (attribute.attrType ? (" "+attribute.attrType) : ""),
	    	headerText=attribute.headerText ? attribute.headerText :  noHeaderName;//if no headerText use attribute name + if exist attribute type
	    
	    return headerText+ (addColon ? ":" : "");
	},
	
	/***
	 * Get the value from the attribute. Replace the line break with br tag.
	 * @param attribute
	 * @returns
	 */
	getAttributeValue:function(attribute){
	    var me=this,
	    	attVal=attribute.attrValue ? attribute.attrValue : "";
	    
	    //if it is a date attribute, handle the date format
	    if(attribute.name=="date"){
	        var dateFormat='dd.mm.yy';
	        if(SESSION_LOCALE=="en"){
	            dateFormat='mm/dd/yy';
	        }
	        attVal=$.datepicker.formatDate(dateFormat, new Date(attVal*1000));
	    }
	    if (attribute.attrType == "processStatus" && attVal == "finalized") {
	    	attVal='<img src="' + moduleFolder + 'images/tick.png" alt="finalized" title="finalized">';
	    }else if(attribute.attrType == "processStatus" && attVal == "provisionallyProcessed"){
	    	attVal="-";
	    } else {
	    	attVal=attVal.replace(/$/mg,'<br>');
	    }
	    
	    return me.getAttributeRenderData(attribute,attVal);
	},
	
	getAttributeRenderData:function(attributeData,attValue){
		var me=this,
			htmlCollection=[],
			userHasAttributeProposalRights=true;//TODO: get me from backend
		
		if(!userHasAttributeProposalRights || !attributeData.proposable){
			return attValue;
		}
		
		//the proposal is allready defined, render the proposal
		if(attributeData.proposal && attributeData.proposal!=''){
			htmlCollection.push('<del>'+attributeData.attValue+'</del>');
			htmlCollection.push('<ins>'+attributeData.proposal.term+'</ins>');
			return htmlCollection.join(' ');
		}
		
		//the user has proposal rights -> init attribute proposal span
		return me.getProposalDefaultHtml(attributeData.attributeOriginType,attributeData.attributeId,attValue,attributeData);
	},

	/***
	 * Get the proposalable component default html. 
	 * This will init the component as editable and set the type,id and value so thay can be used by the ComponentEditor
	 */
	getProposalDefaultHtml:function(type,id,value,attributeData){
		var data='data-editable';
		if(attributeData && attributeData.name=='note'){
			data='data-editable-comment';
		}
		return '<span '+data+' data-type="'+type+'" data-id="'+id+'">'+value+'</span>';
	}
};

Attribute.init();
