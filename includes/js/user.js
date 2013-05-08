function textToInput(sel, classes) {
    var text = sel.text();
    sel.html('<input type="text" class="'+classes+'" />');
    sel.children('input').val(text);
}

function inputToText(sel) {
    sel.html(sel.children('input').val());
}

function readToWrite() {
    $('.non-edit-buttons').show();
    $('#ieml-desc-result-edit').addClass('disabled');
	textToInput($('#ieml-result'), 'input-large');
	textToInput($('#ieml-ex-result'), 'input-xlarge');
	$('#iemlEnumCategoryModal').removeAttr('disabled');
	$('.edit-only').show();
}

function writeToRead() {
    $('.non-edit-buttons').hide();
    $('#ieml-desc-result-edit').removeClass('disabled');
	inputToText($('#ieml-result'));
	inputToText($('#ieml-ex-result'));
	$('#iemlEnumCategoryModal').attr('disabled', 'disabled');
	$('.edit-only').hide();
}

$(function() {
	'use strict';
	
	$(document).on('click', '#add-ieml-record', function() {
	    window.__lastRetrievedData = {'expression':'', 'descriptor':'', 'enumCategory':'N', 'enumShowEmpties': 'N'};
	    fillForm(window.__lastRetrievedData);
	    
	    readToWrite();
	    
        $('#desc-result-id').val('');
        
        return false;
	}).on('click', '#ieml-desc-result-save', function() {
	    writeToRead();
	    
	    var reqVars = {}, cur_id = $('#desc-result-id').val();
        if ($('#desc-result-id').val() == '') {
            reqVars['a'] = 'newDictionary';
        } else {
            reqVars['a'] = 'editDictionary';
        }
        if (cur_id.length > 0) {
            reqVars['id'] = parseInt($('#desc-result-id').val(), 10);
        }
        
        reqVars['pkTable2D'] = window.__lastRetrievedData['pkTable2D'];
        reqVars['enumShowEmpties'] = $('#iemlEnumShowTable').is(':checked') ? 'Y' : 'N';
        
        reqVars['enumCategory'] = $('#iemlEnumCategoryModal').is(':checked') ? 'Y' : 'N';
        reqVars['oldEnumCategory'] = window.__lastRetrievedData['enumCategory'];
        window.__lastRetrievedData['enumCategory'] = window.__lastRetrievedData['enumCategory'] == 'Y' ? 'N' : 'Y';
        reqVars['exp'] = $('#ieml-result').text();
        reqVars['oldExp'] = window.__lastRetrievedData['expression'];
        
        reqVars['descriptor'] = $('#ieml-ex-result').text();
		reqVars['lang'] = $('#search-lang-select').val();
		
		reqVars['iemlEnumComplConcOff'] = $('#iemlEnumComplConcOff').is(':checked') ? $('#iemlEnumComplConcOff').val() : 'N';
		
		reqVars['iemlEnumSubstanceOff'] = $('#iemlEnumSubstanceOff').is(':checked') ? $('#iemlEnumSubstanceOff').val() : 'N';
		reqVars['iemlEnumAttributeOff'] = $('#iemlEnumAttributeOff').is(':checked') ? $('#iemlEnumAttributeOff').val() : 'N';
		reqVars['iemlEnumModeOff'] = $('#iemlEnumModeOff').is(':checked') ? $('#iemlEnumModeOff').val() : 'N';
		
		$.post('/ajax.php', reqVars, function(response) {
		    var respO = $.parseJSON(response);
            if ($('#desc-result-id').val() == '') {
                $('#desc-result-id').val(respO['id']);
            }
            
    		handleAPIResponse(respO);
		});
		return false;
	}).on('click', '#ieml-desc-result-cancel', function() {
	    writeToRead();
		fillForm(window.__lastRetrievedData);
	    
	}).on('click', '#ieml-desc-result-edit', function() {
		if (!$('#ieml-desc-result-edit').hasClass('disabled'))
	    	readToWrite();
	    
	}).on('click', '#ieml-desc-result-delete', function() {
	    if ($('#desc-result-id').val() != '') {
    		$('#iemlConfirmModal .modal-body').html('<div><span>Are you sure you want to delete "' + $('#ieml-result input').eq(0).val() + '"?<br /></span></div>');
    		$('#iemlConfirmModal').modal('show');
		}
	
		return false;
	}).on('change', '.enable_check', function() {
        var thisID = $(this).data('ref-id'), thisval = $(this).is(':checked') ? 'Y' : 'N';
        if (thisval == 'N')
            $(this).siblings('.cell_wrap').hide();
        else
            $(this).siblings('.cell_wrap').show();
		$.post('/ajax.php?a=setTableEl&id=' + thisID + '&enumEnabled=' + thisval, function(response) {});
    }).on('click', '.createEmptyExp', function() {
    	var jthis = $(this);
	    window.__lastRetrievedData['expression'] = jthis.text();
	    window.__lastRetrievedData['descriptor_en'] = '';
	    window.__lastRetrievedData['descriptor_fr'] = '';
	    window.__lastRetrievedData['enumCategory'] = 'N';
	    window.__lastRetrievedData['enumShowEmpties'] = $('#iemlEnumShowTable').is(':checked') ? 'Y' : 'N';
	    
	    fillForm(window.__lastRetrievedData);
	    
	    readToWrite();
	    
        $('#desc-result-id').val('');
	    
	    $('.relation-sel-cell').removeClass('relation-sel-cell');
	    jthis.parents('div').eq(0).addClass('relation-sel-cell'); //TODO highlight line properly
	    
	    return false;
    }).on('click', '#iemlConfirmModal #iemlConfirmYesModal', function() {
		$.post('/ajax.php', {'a':'deleteDictionary', 'id':$('#desc-result-id').val()}, function(responseText) {
			switch_to_list();
			$('[data-result-id="'+$('#desc-result-id').val()+'"]').remove();

			$('#iemlConfirmModal').modal('hide');
		});
		return false;
    });
});