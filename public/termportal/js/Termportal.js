
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

    //if there are results, show them
    if(searchTermsResponse.length>0){
        $('#searchTermsSelect').show();
        $('#finalResultContent').show();
    }

    fillSearchTermSelect();
    //find the attributes for
    findTermsAndAttributes(ui.item.groupId);
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
        
        if(requestFromSearchButton){
            requestFromSearchButton=false;
        }
        
        $("#finalResultContent").hide();
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
        if(searchTermsResponse.length>0){
            $("#finalResultContent").show();
        }
        
    }

    if(!$('#searchTermsSelect').is(":visible")){
        return;
    }
    
    //fill the term component with the search results
    $.each(searchTermsResponse, function (i, item) {
        $('#searchTermsSelect').append(
                $('<li>').attr('data-value', item.groupId).attr('class', 'ui-widget-content').append(
                        $('<div>').attr('class', 'ui-widget').append(item.desc)
            ));
    });
    $("#searchTermsSelect").selectable();
    $("#searchTermsSelect li.ui-selectee").on( "mouseover", function( event ) {
        $(this).addClass('ui-state-hover');
    });
    $("#searchTermsSelect li.ui-selectee").on( "mouseout", function( event ) {
        $(this).removeClass('ui-state-hover');
    });
    $("#searchTermsSelect").on( "selectableselecting", function( event, ui ) {
        $(ui.selecting).addClass('ui-state-active');
    });
    $("#searchTermsSelect").on( "selectableunselecting", function( event, ui ) {
        $(ui.unselecting).removeClass('ui-state-active');
    });
    $("#searchTermsSelect").on( "selectableselected", function( event, ui ) {
        $(ui.selected).addClass('ui-state-active');
        findTermsAndAttributes($(ui.selected).attr('data-value'));
    });
    if(searchTermsResponse.length==1){
        $("#searchTermsSelect li:first-child").addClass('ui-state-active').addClass('ui-selected');
    }
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
    var count=0;
    termAttributeContainer.forEach(function(attribute) {
        $('#termTable').append( '<h3>'+attribute[0].language + ' ' + attribute[0].desc + '</h3><div>' + this.renderAttributes(count) + '</div>' );
        count++;
    });
    if ($('#termTable').hasClass('ui-accordion')) {
        $('#termTable').accordion('refresh');
    } else {
        $("#termTable").accordion({
            active: false,
            collapsible: true,
            heightStyle: "content"
        });
    }
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
    entryAttribute.forEach(function(attribute) {
        var type=attribute.attrType ? attribute.attrType : "";
        var attVal=attribute.value ? attribute.value : "";
        
        $('#termAttributeTable').append( '<tr><td>' + attribute.name + '</td><td>' + type + '</td><td>' + attVal + '</td></tr>' );
    });
}

/***
 * Render term attributes by given term
 * 
 * @param termId
 * @returns {String}
 */
function renderAttributes(termId){
    var attributes=termAttributeContainer[termId]
        html = '<table class="tabble-inner-style">';
    attributes.forEach(function(attribute) {
        var type=attribute.attrType ? attribute.attrType : "";
        var attVal=attribute.value ? attribute.value : "";
        
        //var labelTrans=attributeLabels.find( label => label.id === attribute.labelId );
        //$('#attributeTable').append( '<tr><td>' + labelTrans.labelText + '</td><td>' + attribute.name + '</td><td>' + type + '</td><td>' + attVal + '</td></tr>' );
        html += '<tr><td>' + attribute.name + '</td><td>' + type + '</td><td>' + attVal + '</td></tr>';
    });
    html += '</table>';
    return html;
}

$("#searchButton" ).button({
    icon:"ui-icon-search"
}).click(function(){
    requestFromSearchButton=true;
    $('#termAttributeTable').empty();
    $('#termTable').empty();
    
    $("#search").autocomplete( "search", $("#search").val() );
});

$('#search').keyup(function (e) {
    if (e.which == 13) {
      $("#search").autocomplete( "search", $("#search").val() );
      return false;
    }
    $('#finalResultContent').hide();
    $('#searchTermsSelect').empty();
    
    $('#termAttributeTable').empty();
    $('#termTable').empty();
});

