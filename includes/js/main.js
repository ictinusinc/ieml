(function(window, $) {
	'use strict';
	
	var api_offset = 'http://ieml.ictinusdesign.com/api/';
	
	function IEMLApp() {
		
	}
	
	IEMLApp.user = null;
	IEMLApp.load_url = null;
	IEMLApp.lang = 'en';
	IEMLApp.lexicon = 'BasicLexicon';
	
	IEMLApp.init_from_url = function (url_obj) {
		var path = url_obj.pathname, qry = url_obj.search, qry_obj = get_URL_params(qry), path_arr = path_split(path);
		
		var lang = path_arr[0];
		if (lang !== IEMLApp.lang) {
			IEMLApp.switch_lang(lang);
			IEMLApp.lang = lang;
		}
		$('#search-lang-select').val(lang);
		
		if (path_arr[1] == 'users') {
	        IEMLApp.submit({'a': 'viewUsers'});
		} else if (path_arr[1] == 'login') {
			switch_to_view('login');
		} else if (path_arr.length > 2) {
			var lexicon = path_arr[1];
			
			IEMLApp.lexicon = lexicon;
			
			$('#search-spec-select').val(lexicon);
			
			if (path_arr[2] == 'search') {
				$('#search').val(path_arr[3]);
				
				IEMLApp.submit({'a': 'searchDictionary', 'lexicon': lexicon, 'lang': lang, 'search': path_arr[3]});
			} else {
				if (isNaN(parseInt(path_arr[2]))) {
					IEMLApp.submit({ 'a': 'expression', 'lexicon': lexicon, 'lang': lang, 'exp': path_arr[2] });
				} else {
					IEMLApp.submit({ 'a': 'expression', 'lexicon': lexicon, 'lang': lang, 'id': path_arr[2] });
				}
			}
		} else {
			switch_to_list();
		}
		
		if (_SESSION['user']) {
			init_user_login(_SESSION['user']);
		} else {
			init_anon_user();
		}
	};
	
	IEMLApp.init_from_state = function(full_state) {
		var state = full_state.data, url_obj = url_to_location_obj(full_state.url);
		
		if (state && state['req'] && state['resp']) {
			var req = state['req'], resp = state['resp'];
			
			if (req['a'] == 'viewUsers') {
				IEMLApp.receiveUserList(resp);
			} else if (req['a'] == 'searchDictionary') {
				IEMLApp.receiveSearch(resp);
			} else if (req['a'] == 'expression') {
				IEMLApp.receiveExpression(resp);
			} else {
				return false;
			}
			
			if (url_obj.hash) {
				$('a[href="'+url_obj.hash+'"]').tab('show');
			}
		} else {
			IEMLApp.receiveSearch([]);
		}
		
		return true;
	};
	
	IEMLApp.switch_lang = function(new_lang) {
		var cur_path = path_split(window.location.pathname), cur_state = History.getState().data;
		cur_path[0] = new_lang;
		if (cur_state && cur_state['req']) {
			cur_state['req']['lang'] = new_lang;
		}
		
		IEMLApp.pushState(cur_state, '', cons_url(cur_path, window.location.search, window.location.hash));
		
		IEMLApp.lang = new_lang.toUpperCase();
		
		for (var i in window.UI_lang[IEMLApp.lang]) {
			$('[data-lang-switch="'+i+'"]').html(window.UI_lang[IEMLApp.lang][i]);
		}
		
		return true;
	};
	
	IEMLApp.pushState = function() {
		window.History.ready = true;
		
		window.History.pushState.apply(null, arguments);
	};
	
	IEMLApp.replaceState = function() {
		window.History.ready = true;
		
		window.History.replaceState.apply(null, arguments);
	};
	
	IEMLApp.submit = function (rvars, url, prev_state) {
		if (typeof url == 'undefined') {
			url = api_offset;
		}
		if (typeof prev_state == 'undefined') {
			prev_state = History.getState();
		}
		
		var state_call = obj_size(prev_state.data) == 0 ? IEMLApp.replaceState : IEMLApp.pushState;
		
		if (rvars) {
			if (rvars['a'] == 'searchDictionary') {
				$.getJSON(url, rvars, function(responseData) {
					state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([rvars['lang'], rvars['lexicon'], 'search', rvars['search']]));
					
					IEMLApp.receiveSearch(responseData);
				});
			} else if (rvars['a'] == 'expression') {
				$.getJSON(url, rvars, function(responseData) {
					state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([rvars['lang'], rvars['lexicon'], (rvars['id'] ? rvars['id'] : rvars['exp'])]));
					
			    	IEMLApp.receiveExpression(responseData);
			    });
			} else if (rvars['a'] == 'login') {
				$.getJSON(url, rvars, function(responseData) {
					History.back(); //TODO: find a more elegant solution
					init_user_login(responseData);
					
					IEMLApp.init_from_state(History.getState());
				});
			} else if (rvars['a'] == 'logout') {
				$.getJSON(url, rvars, init_anon_user);
			} else if (rvars['a'] == 'viewUsers') {
				$.getJSON(url, rvars, function(responseData) {
					state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url(IEMLApp.lang, 'users'));
					
					IEMLApp.receiveUserList(responseData);
				});
			} else if (rvars['a'] == 'addUser') {
				$.getJSON(url, rvars, function(responseData) {
					if (responseData['pkUser']) {
						$('#userlist tbody').append(formatUserRow(responseData));
					}
				});
			} else if (rvars['a'] == 'editDictionary' || rvars['a'] == 'newDictionary') {
				$.getJSON(url, rvars, function(responseData) {
		            if ($('#desc-result-id').val() == '') {
						state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([rvars['lang'], rvars['lexicon'], rvars['exp']]));
		                $('#desc-result-id').val(responseData['id']);
		            }
		            
		    		IEMLApp.receiveExpression(responseData);
				});
			} else if (rvars['a'] == 'deleteDictionary') {
				$.getJSON(url, rvars, function(responseData) {
		            History.back(); //TODO: find a more elegant solution
		            
					$('[data-result-id="'+$('#desc-result-id').val()+'"]').remove();
					
					$('#iemlConfirmModal').modal('hide');
				});
			} else {
				return false;
			}
			
			return true;
		}
		
		return false;
	};
	
	IEMLApp.receiveSearch = function (respObj) {
		
		if (respObj && respObj.length > 0) {
			var tstr = '';
			
			for (var i in respObj) {
				tstr += formatResultRow(respObj[i]);
			}
			
			$('#listview tbody').html(tstr);
		} else {
			$('#listview tbody').empty();
		}
			
		
		switch_to_list();
	};
	
	IEMLApp.receiveExpression = function (responseData) {
		IEMLApp.lastRetrievedData = responseData;
		fillForm(responseData);
	};
	
	IEMLApp.receiveUserList = function (responseData) {
		var hstr = '';
		
		for (var i=0; i<responseData.length; i++) {
			hstr += formatUserRow(responseData[i]);
		}
		
		$('#userlist tbody').html(hstr);
		
		switch_to_view('user');
	};
	
	IEMLApp.cons_state = function (req, resp) {
		return {'req': req, 'resp': resp};
	};
	
	function cons_url(path, search, hash) {
		return '/' + array_map(path, function(i,el) { return window.encodeURIComponent(el); }).join('/')
			+ (typeof search != 'undefined' && search.length > 0 ? '?' + map_to_url(search) : '')
			+ (typeof hash != 'undefined' && hash.length > 0 ? '#' + window.encodeURIComponent(hash) : '');
	}
	
	function reset_views() {
		$('.edit-buttons-wrap').hide();
		$('#back-to-list-view').hide();
		
		$('#filter-results-wrap').hide();
		
		$('#record-view-container').hide();
		$('#user-view-container').hide();
		$('#list-view-container').hide();
		$('#login-view-container').hide();
	}
	
	function switch_to_view(view) {
		reset_views();
		$('#'+view+'-view-container').show();
	}
	
	function switch_to_record() {
		reset_views();
		
		if (IEMLApp.user) {
			$('.edit-buttons-wrap').show();
		}
		
		$('#back-to-list-view').show();
		
		$('#record-view-container').show();
	}
	
	function switch_to_list() {
		reset_views();
		$('#filter-results-wrap').show();
		$('#list-view-container').show();
	}
	
	function init_user_login(userObj) {
		$('.login-btn-wrap').hide();
		$('.logout-btn-wrap').show();
		$('#add-ieml-record-wrap').show();
		$('#ieml-view-users-wrap').show();
		
		$('.user-display-name').html(userObj['strDisplayName']);
	
		IEMLApp.user = userObj;
	}
	
	function init_anon_user() {
		$('.logout-btn-wrap').hide();
		$('.login-btn-wrap').show();
		$('#add-ieml-record-wrap').hide();
		$('#ieml-view-users-wrap').hide();
		
		IEMLApp.user = null;
	}
	
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
			    	contained_html += '<li><a href="/ajax.php?id='+info['relations']['contained'][i]['id']
				    	+ '&a=searchDictionary" data-exp="'+info['relations']['contained'][i]['exp'][0]+'" data-id="'
				    	+ info['relations']['contained'][i]['id']+'" class="editExp">' + info['relations']['contained'][i]['desc'] 
				    	+ ' (' + ordinal_str(info['relations']['contained'][i]['exp'][1]) + ' degree)</a></li>';
			    	
			    	var concurrent_rel = info['relations']['concurrent'][info['relations']['contained'][i]['exp'][0]];
			    	if (concurrent_rel.length > 0) {
				    	concurrent_html += '<div class="concurring-relation span6"><span class="concurring-relation-text"><strong>In relation to "'
				    		+ info['relations']['contained'][i]['desc'] + '"</strong></span><ul class="relation-list">';
				    	for (var j=0; j<concurrent_rel.length; j++) {
				    		if (concurrent_rel[j]['desc'] != null) {
				    			concurrent_html += '<li><a href="/ajax.php?id='+concurrent_rel[j]['id']+'&a=searchDictionary" data-exp="'
				    				+ concurrent_rel[j]['exp'][0]+'" data-id="'+concurrent_rel[j]['id']+'" class="editExp">' + concurrent_rel[j]['desc'] + '</a></li>';
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
		    		containing_html += '<li><a href="/ajax.php?id='+info['relations']['containing'][i]['id']
			    		+ '&a=searchDictionary" data-exp="'+info['relations']['containing'][i]['exp'][0]+'" data-id="'
			    		+ info['relations']['containing'][i]['id'] + '" class="editExp">'+info['relations']['containing'][i]['desc']+'</a></li>';
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
		    comp_concept_html += '<a href="/ajax.php?id='+info['relations']['comp_concept']['id']+'&a=searchDictionary" data-exp="'
		    	+ info['relations']['comp_concept']['exp'][0]+'" data-id="'+info['relations']['comp_concept']['id']+'" class="editExp">'
		    	+ info['relations']['comp_concept']['desc'] + ' (' + info['relations']['comp_concept']['exp'][0] + ')</a>';
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
			ret += '<li><a href="/ajax.php?id='+etym[i]['id']+'&a=searchDictionary" data-exp="'+etym[i]['exp']
				+ '" data-id="'+etym[i]['id']+'" class="editExp">'+etym[i]['desc'] + ' (' + etym[i]['exp'] + ')</a></li>';
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
	
	function formatResultRow(obj) {
		return '<tr data-key="' + (obj['enumCategory'] == 'Y' ? 'true' : 'false') + '" data-result-id="' + obj['id'] + '"><td>' 
			+ obj['expression'] + '</td><td>' + obj['descriptor'] + '</td><td><a href="/ajax.php?id=' + obj['id']
			+ '&a=searchDictionary" data-exp="'+obj['expression']+'" data-id="' + obj['id']
			+ '" class="btn editExp"><span class="icon-pencil"></span></a></td></tr>';
	}
	
	function formatUserRow(user) {
	    return '<tr><td>'+user['strEmail']+'</td><td>'+user['enumType']+'</td><td>'
	    	+ LightDate.date('Y-m-d H:i:s', LightDate.date_timezone_adjust(user['tsDateCreated']*1000))
	    	+ '</td><td><!--a href="/ajax.php?a=editUser&pkUser='+user['pkUser']+'" class="editUser btn">Edit</a--><a href="/ajax.php?a=delUser&pkUser='
	    	+user['pkUser']+'" class="delUser btn">Delete</a></td></tr>';
	}
	
	function showConfirmDialog(text, callback, etc) {
		if (typeof text !== 'undefined' && text != null && text.length > 0) $('#confirmCancelModalText').html(text);
		else $('#confirmCancelModalText').html('Are you sure?');
	
		$('#confirmCancelModal').data('callback', function() {
			if (typeof callback !== 'undefined' && callback != null) callback();
		});
	
		$('#confirmCancelModal').modal('show');
	}
	
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
		History.Adapter.bind(window, 'popstate', function(ev) {
			if (window.History.ready) {
				var id = ev.state, state_obj = History.getStateById(id);
				
				if (state_obj) {
					IEMLApp.init_from_state(state_obj);
				}
			}
		});
		
		$(document).on('submit', '#search-form', function() {
			var form_data = form_arr_to_map($(this).serializeArray());
			IEMLApp.submit(form_data);
			
			return false;
		}).on('change', '#iemlEnumShowTable', function() {
			var info = IEMLApp.lastRetrievedData;
			
	        if($('#iemlEnumShowTable').is(':checked')) {
				$('.nonExistentCell').show();
				$('table.relation tr.empty_head_tr, table.relation td.empty_head_tr_td').show();
				if (info['edit_vertical_head_length'] <= 0 && info['edit_horizontal_head_length'] <= 0) {
					$('table.relation td.empty_cell').hide();
				} else {
					$('table.relation td.empty_cell').show()
						.attr('rowspan', parseInt(info['edit_vertical_head_length'], 10) + 1).attr('colspan', info['edit_horizontal_head_length']);
				}
	        } else {
				$('.nonExistentCell').hide();
				$('table.relation tr.empty_head_tr, table.relation td.empty_head_tr_td').hide();
				if (info['render_vertical_head_length'] <= 0 && info['render_horizontal_head_length'] <= 0) {
					$('table.relation td.empty_cell').hide();
				} else {
					$('table.relation td.empty_cell').show()
						.attr('rowspan', parseInt(info['render_vertical_head_length'], 10) + 1).attr('colspan', info['render_horizontal_head_length']);
				}
	        }
		}).on('click', '.editExp', function() {
			var jthis = $(this), sub_data = { 'a': 'expression', 'lang': IEMLApp.lang, 'lexicon': IEMLApp.lexicon }, exp = jthis.data('exp'), id = jthis.data('id');
			
			if (exp) {
				sub_data['exp'] = exp;
			} else {
				sub_data['id'] = id;
			}
			
			IEMLApp.submit(sub_data);
			
			return false;
		}).on('click', '#back-to-list-view', function() {
			switch_to_list();
		}).on('change', '#search-lang-select', function() {
			IEMLApp.switch_lang($(this).val());
		}).on('click', '#filter-results-button', function() {
			$('#listview tbody [data-key="false"]').show();
		}).on('click', '#filter-results-keys', function() {
			$('#listview tbody [data-key="false"]').hide();
		}).on('click', '#add-ieml-record', function() {
		    IEMLApp.lastRetrievedData = {'expression':'', 'descriptor':'', 'enumCategory':'N', 'enumShowEmpties': 'N'};
		    fillForm(IEMLApp.lastRetrievedData);
		    
		    readToWrite();
		    
	        $('#desc-result-id').val('');
	        
	        return false;
		}).on('click', '#ieml-desc-result-save', function() {
		    writeToRead();
		    
		    var reqVars = {}, cur_id = $('#desc-result-id').val();
	        if ($('#desc-result-id').val().length == 0) {
	            reqVars['a'] = 'newDictionary';
	        } else {
	            reqVars['a'] = 'editDictionary';
	        }
	        if (cur_id.length > 0) {
	            reqVars['id'] = parseInt($('#desc-result-id').val(), 10);
	        }
	        
	        reqVars['pkTable2D'] = IEMLApp.lastRetrievedData['pkTable2D'];
	        reqVars['enumShowEmpties'] = $('#iemlEnumShowTable').is(':checked') ? 'Y' : 'N';
	        
	        reqVars['enumCategory'] = $('#iemlEnumCategoryModal').is(':checked') ? 'Y' : 'N';
	        reqVars['oldEnumCategory'] = IEMLApp.lastRetrievedData['enumCategory'];
	        IEMLApp.lastRetrievedData['enumCategory'] = IEMLApp.lastRetrievedData['enumCategory'] == 'Y' ? 'N' : 'Y';
	        reqVars['exp'] = $('#ieml-result').text();
	        reqVars['oldExp'] = IEMLApp.lastRetrievedData['expression'];
	        
	        reqVars['descriptor'] = $('#ieml-ex-result').text();
			reqVars['lang'] = $('#search-lang-select').val();
			
			reqVars['iemlEnumComplConcOff'] = $('#iemlEnumComplConcOff').is(':checked') ? $('#iemlEnumComplConcOff').val() : 'N';
			
			reqVars['iemlEnumSubstanceOff'] = $('#iemlEnumSubstanceOff').is(':checked') ? $('#iemlEnumSubstanceOff').val() : 'N';
			reqVars['iemlEnumAttributeOff'] = $('#iemlEnumAttributeOff').is(':checked') ? $('#iemlEnumAttributeOff').val() : 'N';
			reqVars['iemlEnumModeOff'] = $('#iemlEnumModeOff').is(':checked') ? $('#iemlEnumModeOff').val() : 'N';
			
			IEMLApp.submit(reqVars);
			
			return false;
		}).on('click', '#ieml-desc-result-cancel', function() {
		    writeToRead();
			fillForm(IEMLApp.lastRetrievedData);
		    
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
		    IEMLApp.lastRetrievedData['expression'] = jthis.text();
		    IEMLApp.lastRetrievedData['descriptor_en'] = '';
		    IEMLApp.lastRetrievedData['descriptor_fr'] = '';
		    IEMLApp.lastRetrievedData['enumCategory'] = 'N';
		    IEMLApp.lastRetrievedData['enumShowEmpties'] = $('#iemlEnumShowTable').is(':checked') ? 'Y' : 'N';
		    
		    fillForm(IEMLApp.lastRetrievedData);
		    
		    readToWrite();
		    
	        $('#desc-result-id').val('');
		    
		    $('.relation-sel-cell').removeClass('relation-sel-cell');
		    jthis.parents('div').eq(0).addClass('relation-sel-cell'); //TODO highlight line properly
		    
		    return false;
	    }).on('click', '#iemlConfirmModal #iemlConfirmYesModal', function() {
			IEMLApp.submit({'a':'deleteDictionary', 'id':$('#desc-result-id').val()});
			
			return false;
	    }).on('click', '#ieml-view-users', function() {
	        IEMLApp.submit({'a': 'viewUsers'});
	        
	        return false;
	    }).on('click', '#addUser', function() {
	        $('#iemlAddUserModal').modal('show');
	        
	        return false;
	    }).on('click', '#iemlAddUserModalAdd', function() {
	        var formData = form_arr_to_map($('#iemlUser').serializeArray());
	        
	        $('#iemlAddUserModal').modal('hide');
	        
	        IEMLApp.submit({ 'a' : formData['a'], 'username' : formData['addUserModalUsername'], 'pass' : formData['addUserModalPass'], 'enumType' : formData['addUserModalType'] });
	        
	        return false;
	    }).on('click', '.delUser', function() {
	        var jthis = $(this);
	        
	        showConfirmDialog('Are you sure?', function() {
	            var jurl = jthis.attr('href');
	            
	            $.post(jurl, function(resp) {
	                jthis.closest('tr').remove();
	            });
	        });
	        
	        return false;
	    }).on('click', '.editUser', function() {
	        
	        return false;
	    }).on('click', '#confirmCancelModalYes', function() {
			$('#confirmCancelModal').data('callback')();
			
			return false;
		}).on('click', '.login-btn', function() {
			switch_to_view('login');
			state_call(null, '', '/' + cons_url(['login']));
			
			return false;
		}).on('click', '.logout-btn', function() {
			IEMLApp.submit({'a': 'logout'});
			
			return false;
		}).on('submit', '#formLogin', function() {
	        var formData = form_arr_to_map($(this).serializeArray());
	        formData['a'] = 'login';
			IEMLApp.submit(formData);
			
			return false;
		}).on('shown', 'a[data-toggle="tab"]', function(e) {
			//var cur_state = History.getState(), state_call = obj_size(cur_state.data) == 0 ? IEMLApp.replaceState : IEMLApp.pushState;
			//state_call(cur_state, '', $(e.target).attr('href'));
			
			//$(window).trigger('hashchange');
		});
		
		IEMLApp.load_url = window.location;
		
		if (window.location.pathname.length == 1) {
			IEMLApp.replaceState(null, '', cons_url([IEMLApp.lang, IEMLApp.lexicon]));
		}
		
		IEMLApp.init_from_url(window.location);
		
	    if (location.hash !== '') {
	    	$('a[href="' + location.hash + '"]').tab('show');
	    }
	});
	
	$(window).on('hashchange', function(ev) {
		//console.log(ev.fragment);
	});
})(window, jQuery);