function getVerbLayer(exp) {
	var gram_classes_verbs = ['O:', 'U:', 'A:', 'y.', 'o.', 'e.', 'u.', 'a.', 'i.', 'wo.', 'wa.', 'we.', 'wu.'];
	var gram_classes_nouns = ['M:', 'S:', 'B:', 'T:', 's.', 'b.', 't.', 'k.', 'm.', 'n.', 'd.', 'f.', 'l.', 'j.', 'h.', 'p.', 'g.', 'c.', 'x.'];
	var gram_classes_hybrid_nouns = ['I:', 'F:'];
	var gram = '';
	var layer = '';

	if (gram_classes_verbs.indexOf(exp.substr(0,2))>=0 || gram_classes_verbs.indexOf(exp.substr(0,3))>=0) gram = 'Verb';
	if (gram_classes_nouns.indexOf(exp.substr(0,2))>=0) gram = 'Noun';
	if (exp.substr(0,2) == 'E:') gram = 'AUXILIARY';
	if (gram_classes_hybrid_nouns.indexOf(exp.substr(0,2))>=0 || exp.lastIndexOf('+')>=0) gram = 'Hybrid';

	if (exp.substr(-1)==':') layer = '0';
	else if (exp.substr(-1)=='.') layer = '1';
	else if (exp.substr(-1)=='-') layer = '2';
	else if (exp.substr(-1)=="'") layer = '3';

	return {'layer':layer, 'gram':gram};
}

function format_relations(info) {
	var contained_html = '', containing_html = '', concurrent_html = '', comp_concept_html = '', etymology_html = '';
	
    if (info['relations']['contained'].length > 0) {
    	contained_html += '<ul class="relation-list">';
	    for (var i=0; i<info['relations']['contained'].length; i++) {
	    	if (info['relations']['contained'][i]['desc'] != null) {
		    	contained_html += '<li><a href="/ajax.php?id='+info['relations']['contained'][i]['id']+'&a=searchDictionary" class="editExp">' + info['relations']['contained'][i]['desc'] + ' (' + ordinal_str(info['relations']['contained'][i]['exp'][1]) + ' degree)</a></li>';
		    	
		    	var concurrent_rel = info['relations']['concurrent'][info['relations']['contained'][i]['exp'][0]];
		    	if (concurrent_rel.length > 0) {
			    	concurrent_html += '<div class="concurring-relation span6"><span class="concurring-relation-text"><strong>In relation to "' + info['relations']['contained'][i]['desc'] + '"</strong></span><ul class="relation-list">';
			    	for (var j=0; j<concurrent_rel.length; j++) {
			    		if (concurrent_rel[j]['desc'] != null) {
			    			concurrent_html += '<li><a href="/ajax.php?id='+concurrent_rel[j]['id']+'&a=searchDictionary" class="editExp">' + concurrent_rel[j]['desc'] + '</a></li>';
			    		}
			    	}
			    	concurrent_html += '</ul></div>';
		    	}
	    	}
	    }
    	contained_html += '</ul>';
    	
    } else {
	    contained_html = 'Nothing.';
    }
    
    if (info['relations']['containing'].length > 0) {
	    containing_html += '<ul class="relation-list">';
	    for (var i=0; i<info['relations']['containing'].length; i++) {
	    	if (info['relations']['containing'][i]['desc']) {
	    		containing_html += '<li><a href="/ajax.php?id='+info['relations']['containing'][i]['id']+'&a=searchDictionary" class="editExp">'+info['relations']['containing'][i]['desc']+'</a></li>';
	    	}
	    }
	    containing_html += '</ul>';
    } else {
	    containing_html = 'Nothing.';
    }
    
    if (concurrent_html == '') {
    	concurrent_html = 'None.';
    } else {
    	concurrent_html = '<div class="row">' + concurrent_html + '</div>';
    }
    
    comp_concept_html = '<p>';
    if (info['relations']['comp_concept'] && info['relations']['comp_concept']['exp']) {
	    comp_concept_html += '<a href="/ajax.php?id='+info['relations']['comp_concept']['id']+'&a=searchDictionary" class="editExp">' + info['relations']['comp_concept']['desc'] + ' (' + info['relations']['comp_concept']['exp'][0] + ')</a>';
    } else {
	    comp_concept_html += 'None.';
    }
    comp_concept_html += '</p>';
    
    etymology_html = 'None.';
    
    return {'contained': contained_html, 'containing': containing_html, 'concurrent': concurrent_html, 'comp_concept': comp_concept_html, 'etymology': etymology_html};
}

function format_etymology(info) {
	var etym = info['etymology'], ret = '<ul>';
	
	for (var i=0; i<etym.length; i++) {
		ret += '<li><a href="/ajax.php?id='+etym[i]['id']+'&a=searchDictionary" class="editExp">'+etym[i]['desc'] + ' (' + etym[i]['exp'] + ')</a></li>';
	}
	
	ret += '</ul>';
	
	return ret;
}

function fillForm(info) {
    if ($('#ieml-desc-result-edit').hasClass('disabled')) {
        writeToRead();
    }
    
	$('#ieml-result').html(info['expression']);
	$('#ieml-ex-result').html(info['descriptor']);
    $('#desc-result-id').val(info['id']?info['id']:'');
    $('#iemlEnumCategoryModal').prop('checked', info['enumCategory'] == 'Y');

	if (info['expression'].length > 0) {
    	var details = getVerbLayer(info['expression']);
    
    	$('#ieml-result-details').html(details['gram']+' Layer '+details['layer']);
	} else {
    	$('#ieml-result-details').html('NEW ENTRY');
	}
	
	if (info['table']) {
		$('#ieml-table-span').html(info['table']);
		$('#iemlTableID').html(info['pkTable2D']);
	} else {
	   $('#ieml-table-span').empty();
	   $('#iemlTableID').empty();
    }
    
    $('#iemlEnumShowTable').prop('checked', info['enumShowEmpties'] == 'Y').trigger('change');
    
    $('#iemlEnumComplConcOff').prop('checked', info['iemlEnumComplConcOff'] == 'Y').trigger('change');
    if (info['iemlEnumComplConcOff'] == 'Y') {
    	$('#ieml-complementary-section').hide();
    } else {
    	$('#ieml-complementary-section').show();
    }
    $('#iemlEnumSubstanceOff').prop('checked', info['iemlEnumSubstanceOff'] == 'Y').trigger('change');
    $('#iemlEnumAttributeOff').prop('checked', info['iemlEnumAttributeOff'] == 'Y').trigger('change');
    $('#iemlEnumModeOff').prop('checked', info['iemlEnumModeOff'] == 'Y').trigger('change');
    
    if (info['relations']) {
    	var rel_info = format_relations(info);
	    
	    $('#ieml-contained-wrap').show().html(rel_info['contained']);
	    $('#ieml-containing-wrap').show().html(rel_info['containing']);
	    $('#ieml-concurrent-wrap').show().html(rel_info['concurrent']);
	    $('#ieml-complementary-wrap').show().html(rel_info['comp_concept']);
    } else {
	    $('#ieml-contained-wrap, #ieml-containing-wrap, #ieml-concurrent-wrap, #ieml-complementary-wrap').hide();
    }
    
    if (info['etymology']) {
	    $('#ieml-etymology-wrap').show().html(format_etymology(info));
    } else {
	    $('#ieml-etymology-wrap').hide();
    }
	
	if (info['debug']) {
	   console.log(info['debug']);
	}
	
	switch_to_record();
}

function switch_to_record() {
	$('.edit-buttons-wrap').show();
	$('#back-to-list-view').show();
	
	$('#filter-results-wrap').hide();
	
	$('#user-view-container').hide();
	$('#list-view-container').hide();
	$('#record-view-container').show();
}

function switch_to_list() {
	$('.edit-buttons-wrap').hide();
	$('#back-to-list-view').hide();
	
	$('#filter-results-wrap').show();
	
	$('#record-view-container').hide();
	$('#user-view-container').hide();
	$('#list-view-container').show();
}

function formatResultRow(obj) {
	return '<tr data-key="' + (obj['enumCategory'] == 'Y' ? 'true' : 'false') + '" data-result-id="' + obj['id'] + '"><td>' + obj['expression'] + '</td><td>' + obj['descriptor'] + '</td><td><a href="/ajax.php?id=' + obj['id'] + '&a=searchDictionary" class="btn editExp"><span class="icon-pencil"></span></a></td></tr>';
}

function handleAPIResponse(responseData) {
	window.__lastRetrievedData = responseData;
	fillForm(responseData);
}

$(function() {
	'use strict';

	$('#search-form').ajaxForm({
		data: { 'a':'searchDictionary', 'lang':$('#search-lang-select').val() },
		success: function(responseText) {
			var respObj = $.parseJSON(responseText), tstr = '';
			for (var i in respObj)
				tstr += formatResultRow(respObj[i]);
				
			$('#listview tbody').html(tstr);
	
			$('#ieml-desc-result-non-edit-buttons').hide();
			switch_to_list();
		}
	});
	
	$(document).on('change', '#iemlEnumShowTable', function() {
		var info = window.__lastRetrievedData;
		
        if($('#iemlEnumShowTable').is(':checked')) {
			$('.nonExistentCell').show();
			$('table.relation tr.empty_head_tr, table.relation td.empty_head_tr_td').show();
			if (info['edit_vertical_head_length'] <= 0 && info['edit_horizontal_head_length'] <= 0) {
				$('table.relation td.empty_cell').hide();
			} else {
				$('table.relation td.empty_cell').show().attr('rowspan', parseInt(info['edit_vertical_head_length'], 10) + 1).attr('colspan', info['edit_horizontal_head_length']);
			}
        } else {
			$('.nonExistentCell').hide();
			$('table.relation tr.empty_head_tr, table.relation td.empty_head_tr_td').hide();
			if (info['render_vertical_head_length'] <= 0 && info['render_horizontal_head_length'] <= 0) {
				$('table.relation td.empty_cell').hide();
			} else {
				$('table.relation td.empty_cell').show().attr('rowspan', parseInt(info['render_vertical_head_length'], 10) + 1).attr('colspan', info['render_horizontal_head_length']);
			}
        }
	}).on('click', '.editExp', function() {
		var href = this.href;
        
		$.post(href, { 'lang' : $('#search-lang-select').val() },
		    function(responseText) {
    			handleAPIResponse($.parseJSON(responseText));
		    }
		);
		
		return false;
	}).on('click', '#back-to-list-view', function() {
		switch_to_list();
	}).on('change', '#search-lang-select', function() {
		var reqvars = get_URL_params(window.location.search);
		reqvars['lang'] = $('#search-lang-select').val();
		
		window.location.href = window.location.origin + window.location.pathname + '?' + map_to_url(reqvars);
	}).on('click', '#filter-results-button', function() {
		$('#listview tbody [data-key="false"]').show();
	}).on('click', '#filter-results-keys', function() {
		$('#listview tbody [data-key="false"]').hide();
	});
});