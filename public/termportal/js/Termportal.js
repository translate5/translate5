
/***
 * Helper variables
 */
var termAttributeContainer=[],
    termEntryAttributeContainer=[],
    searchTermsResponse=[],
    requestFromSearchButton=false,
    KEY_TERM="term",
    KEY_TERM_ATTRIBUTES="termAttributes",
    KEY_TERM_ENTRY_ATTRIBUTES="termEntryAttributes";

/***
 * On dropdown select function handler
 */
var selectItem = function (event, ui) {
    
    //empty term options component
    $('#searchTermsSelect').empty();

    //if there are more than one results, show the term option component
    if(searchTermsResponse.length>1){
        $('#searchTermsSelect').show();
    }

    fillSearchTermSelect();
    //find the attributes for
    findTermsAndAttributes(ui.item.groupId);
    $('#attributeTable').empty();
    //$("#search").val(ui.item.value);
    return false;
}
 
$("#search").autocomplete({
    source: function(request, response) {
        searchTerm(request.term,function(data){
            response(data);
        })
    },
    select: selectItem,
    minLength: 3,
    change: function() {
        $("#myText").val("").css("display", 2);
    }
});

/***
 * Find all terms and terms attributes for the given term entry id (groupId)
 * @param termGroupid
 * @returns
 */
function findTermsAndAttributes(termGroupid){
    $.ajax({
        url: "http://translate5.local/editor/termcollection/search",
        dataType: "json",
        data: {
            'groupId':termGroupid
        },
        success: function(result){
            groupTermAttributeData(result.rows[KEY_TERM_ATTRIBUTES]);
            drawTermEntryAttributes(result.rows[KEY_TERM_ENTRY_ATTRIBUTES]);
        }
    })
}

/***
 * Search the term in the language and term collection
 * @param searchString
 * @param successCallback
 * @returns
 */
function searchTerm(searchString,successCallback){
    searchTermsResponse=[];
    $.ajax({
        url: "http://translate5.local/editor/termcollection/search",
        dataType: "json",
        data: {
            'term':searchString,
            'language':$("#languages").val(),
            'collectionIds':collectionIds
        },
        success: function(result){
            searchTermsResponse=result.rows[KEY_TERM];
            successCallback(result.rows[KEY_TERM]);
            fillSearchTermSelect();
        }
    });
}

/***
 * Fill up the term option component with the search results
 * @returns
 */
function fillSearchTermSelect(){

    if(!searchTermsResponse){
        $('#searchTermsSelect').hide();
        return;
    }

    if(requestFromSearchButton){
        requestFromSearchButton=false;

        $('#searchTermsSelect').empty();
        
        //if only one record, find the attributes and display them
        if(searchTermsResponse.length===1){
            findTermsAndAttributes(searchTermsResponse[0].groupId);
            return;
        }
        if(searchTermsResponse.length>1){
            $('#searchTermsSelect').show();
        }
        
    }

    if(!$('#searchTermsSelect').is(":visible")){
        return;
    }
    
    //fill the term component with the search results
    $.each(searchTermsResponse, function (i, item) {
        $('#searchTermsSelect').append($('<option>', { 
            value: item.groupId,
            text : item.desc
        }));
    });
}

/***
 * Fill the termAttributeContainer grouped by term.
 * 
 * @param data
 * @returns
 */
function groupTermAttributeData(data){
    if(!data || data.length<1){
        return;
    }
    console.log(data)
    termAttributeContainer=[];
    var oldKey="",
        newKey="",
        count=-1;
    
    for(var i=0;i<data.length;i++){
        newKey = data[i].termId;
        if(newKey !=oldKey){
            count++;
            termAttributeContainer[count]=[];
        }
        termAttributeContainer[count].push(data[i]);
        oldKey = data[i].termId;
    }
    console.log(termAttributeContainer)
    //draw the term groups
    drawTermGroups();
}

/***
 * Draw the tearm groups
 * @returns
 */
function drawTermGroups(){
    if(!termAttributeContainer || termAttributeContainer.length<1){
        return;
    }
    $('#termTable').empty();
    $('#termTable').append( '<tr><td colspan="3">'+termTableTitle+'</td></tr>' );
    var count=0;
    termAttributeContainer.forEach(function(attribute) {
        $('#termTable').append( '<tr onclick="drawAttributes('+count+');"><td>' + attribute[0].language + '</td><td>' + attribute[0].desc + '</td></tr>' );
        count++;
    });
}

/***
 * Draw the term entry groups
 * @param entryAttribute
 * @returns
 */
function drawTermEntryAttributes(entryAttribute){
    if(!entryAttribute || entryAttribute.length<1){
        return;
    }
    $('#termAttributeTable').empty();
    $('#termAttributeTable').append( '<tr><td colspan="3">'+termEntryAttributeTableTitle+'</td></tr>' );
    entryAttribute.forEach(function(attribute) {
        var type=attribute.attrType ? attribute.attrType : "";
        var attVal=attribute.value ? attribute.value : "";
        
        $('#termAttributeTable').append( '<tr><td>' + attribute.name + '</td><td>' + type + '</td><td>' + attVal + '</td></tr>' );
    });
}

/***
 * Draw term attributes by given term
 * 
 * @param termId
 * @returns
 */
function drawAttributes(termId){
    var attributes=termAttributeContainer[termId];
    $('#attributeTable').empty();

    $('#attributeTable').append( '<tr><td colspan="3">'+termAttributeTableTitle+'</td></tr>' );
    attributes.forEach(function(attribute) {
        var type=attribute.attrType ? attribute.attrType : "";
        var attVal=attribute.value ? attribute.value : "";
        
        //var labelTrans=attributeLabels.find( label => label.id === attribute.labelId );
        //$('#attributeTable').append( '<tr><td>' + labelTrans.labelText + '</td><td>' + attribute.name + '</td><td>' + type + '</td><td>' + attVal + '</td></tr>' );
        $('#attributeTable').append( '<tr><td>' + attribute.name + '</td><td>' + type + '</td><td>' + attVal + '</td></tr>' );
    });
}

$("#searchButton" ).button({
    icon:"ui-icon-search"
}).click(function(){
    requestFromSearchButton=true;
    $('#termAttributeTable').empty();
    $('#attributeTable').empty();
    $('#termTable').empty();
    
    $("#search").autocomplete( "search", $("#search").val() );
});

$('#search').keyup(function (e) {
    if (e.which == 13) {
      $("#search").autocomplete( "search", $("#search").val() );
      return false;
    }
    $('#searchTermsSelect').hide();
    $('#searchTermsSelect').empty();
    
    $('#termAttributeTable').empty();
    $('#attributeTable').empty();
    $('#termTable').empty();
});

$('#searchTermsSelect').on('change', function() {
    $('#attributeTable').empty();
    findTermsAndAttributes($(this).val());
});