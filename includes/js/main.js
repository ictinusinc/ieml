(function(window, $) {
	'use strict';
	
	var api_offset = '/api/';
	
	function IEMLApp() {}
	
	IEMLApp.user = null;
	IEMLApp.userLibraries = null;
	IEMLApp.load_url = null;
	IEMLApp.lang = 'EN';
	IEMLApp.lexicon = '1';
	IEMLApp.lastGeneralSearch = null;

	IEMLApp.match_url_settings = function(url) {
		var opt_map = {
			'class': '(verb|noun|auxiliary)',
			'key': '(keys)',
			'layer': 'layer-([0-6])',
			'lexicon': 'library-([1-9][0-9]*)',
			'lang': '(en|fr)',
			'mode': '(search|view|users|login)'
		}, ret = {};

		for (var i in opt_map) {
			var mat = url.match(new RegExp('\/'+opt_map[i]+'\/?', 'i'));

			if (mat) {
				ret[i] = mat[1];
			}
		}

		return ret;
	};

	IEMLApp.set_search_from_settings = function(settings) {
		if (settings.layer) {
			$('#search-form #search-layer-select').val(settings.layer);
		}
		if (settings.class) {
			$('#search-form #search-class-select').val(settings.class);
		}
		if (settings.lexicon) {
			$('#search-form #search-spec-select').val(settings.lexicon);
		}
		if (settings.lang) {
			$('#search-form #search-lang-select').val(settings.lang);
		}
		if (settings.key && settings.key == 'keys') {
			$('#filter-results-keys').click();
		} else {
			$('#filter-results-button').click();
		}

		if (settings.search) {
			$('#search-form #search').val(settings.search);
		}
	};
	
	IEMLApp.init_from_url = function (url_obj) {
		var qry = url_obj.search, path_arr = path_split(url_obj.pathname),
			path_last = path_arr[path_arr.length-1], path = url_obj.pathname,
			star_index = path.indexOf('*'),
			settings = IEMLApp.match_url_settings(path.substr(0, star_index > -1 ? star_index : path.length));

		if (settings.lexicon) {
			IEMLApp.lexicon = settings.lexicon;

			$('#search-spec-select').val(IEMLApp.lexicon);
		}

		if (settings.lang) {
			IEMLApp.set_lang(settings.lang);
		}
		
		if (settings.mode == 'users') {
			IEMLApp.submit({'a': 'viewUsers', 'deleted' : 'no'});
		} else if (settings.mode == 'login') {
			switch_to_view('login');
		} else if (settings.mode == 'search') {
			settings.search = path_last.substr(1);
			IEMLApp.set_search_from_settings(settings);
			
			IEMLApp.submit({
				'a': 'searchDictionary',
				'lexicon': IEMLApp.lexicon,
				'lang': IEMLApp.lang,
				'layer': settings.layer,
				'class': settings.class,
				'keys': settings.key,
				'search': settings.search
			});
		} else if (settings.mode == 'view') {
			if (isNaN(parseInt(path_last, 10))) {
				IEMLApp.submit({ 'a': 'expression', 'lexicon': IEMLApp.lexicon, 'lang': IEMLApp.lang, 'exp': path_last });
			} else {
				IEMLApp.submit({ 'a': 'expression', 'lexicon': IEMLApp.lexicon, 'lang': IEMLApp.lang, 'id': path_last });
			}
		} else {
			switch_to_list();
		}
		
		IEMLApp.init_from_url_NO_STATE_CHANGE(url_obj);
	};
	
	IEMLApp.init_from_url_NO_STATE_CHANGE = function (url_obj) {
		var path_arr = path_split(url_obj.pathname);
		
		if (IEMLApp.user) {
			init_user_login(IEMLApp.user);
		} else {
			init_anon_user();
		}
		
		if (url_obj.hash !== '') {
			$('a[href="' + url_obj.hash + '"]').tab('show');
		}
	};
	
	IEMLApp.init_from_state = function(full_state) {
		var state = full_state.data, url_obj = url_to_location_obj(full_state.url);

		IEMLApp.switch_lang(IEMLApp.lang, full_state);
		
		if (state && state.req && state.resp) {
			var req = state.req, resp = state.resp;
			
			if (req.a == 'viewUsers') {
				IEMLApp.receiveUserList(resp);
			} else if (req.a == 'searchDictionary') {
				IEMLApp.receiveSearch(resp);
			} else if (req.a == 'expression') {
				IEMLApp.receiveExpression(resp);
			} else {
				return false;
			}
		} else {
			IEMLApp.receiveSearch([]);
		}
			
		IEMLApp.init_from_url_NO_STATE_CHANGE(url_obj);
		
		return true;
	};

	IEMLApp.set_lang = function(lang) {
		IEMLApp.lang = lang.toUpperCase();

		return IEMLApp.lang;
	};
	
	IEMLApp.switch_lang = function(new_lang, cur_state) {
		new_lang = new_lang.toUpperCase();

		if (cur_state && cur_state.req && cur_state.req.lang != new_lang) {
			cur_state.req.lang = new_lang;
			
			IEMLApp.submit(cur_state.req);
			
			IEMLApp.set_lang(new_lang);
			
			$('[data-lang-switch]').each(function(ind, el) {
				var jel = $(el), lang_els = jel.data('lang-switch').split(','), lang_attrs_str = jel.data('lang-switch-attr');
				
				if (lang_attrs_str && lang_attrs_str.length > 0) {
					var lang_attrs = lang_attrs_str.split(',');
					
					for (var i in lang_els) {
						if (lang_attrs_str[i] && lang_attrs_str[i].length > 0) {
							jel.prop(lang_attrs[i], window.UI_lang[IEMLApp.lang][lang_els[i]]);
						} else {
							jel.html(window.UI_lang[IEMLApp.lang][lang_els[i]]);
						}
					}
				} else {
					jel.html(window.UI_lang[IEMLApp.lang][lang_els[0]]);
				}
			});
			
			return true;
		} else {
			return false;
		}
	};
	
	IEMLApp.pushState = function() {
		window.History.ready = true;
		
		try {
			window.History.pushState.apply(null, arguments);
		} catch (e) { //the error is probably "...states with fragment-identifiers..." so ignore it
			console.log('[History.js]:', e);
		}
	};
	
	IEMLApp.replaceState = function() {
		window.History.ready = true;
		
		window.History.replaceState.apply(null, arguments);
	};

	IEMLApp.form_data_make_url_safe = function(obj) {
		if (obj.layer && obj.layer.substr(0, 5) != 'layer') {
			obj.layer = 'layer-'+obj.layer;
		}
		if (obj.search && obj.search.substr(0, 1) != '*') {
			obj.search = '*'+obj.search;
		} else {
			obj.search = '*';
		}

		return obj;
	};
	
	IEMLApp.submit = function (rvars, url, prev_state) {
		if (!url || url.length <= 0) {
			url = api_offset;
		}
		if (typeof prev_state == 'undefined') {
			prev_state = History.getState();
		}
		
		var state_call = obj_size(prev_state.data) === 0 ? IEMLApp.replaceState : IEMLApp.pushState;
		
		if (rvars) {
			if (rvars.a == 'getUserLibraries') {
				$.getJSON(url, rvars, function(responseData) {
					if (responseData.result != 'error') {
						IEMLApp.userLibraries = responseData;
					}
				});
			} else if (rvars.a == 'getAllLibraries') {
				$.getJSON(url, rvars, function(responseData) {
					var libHtml = '';

					for (var i=0; i<responseData.length; i++) {
						libHtml += '<option data-owned-by="' + responseData[i].fkUser +
							'" value="' + responseData[i].pkLibrary + '">' + responseData[i].strName + '</option>';
					}

					$('#search-spec-select').html(libHtml);
				});
			} else if (rvars.a == 'searchDictionary') {
				$.getJSON(url, rvars, function(responseData) {
					var rvars_url = IEMLApp.form_data_make_url_safe(rvars);

					state_call(IEMLApp.cons_state(rvars, responseData), '',
						cons_url([
							rvars_url.lang, rvars_url.lexicon, 'search',
							rvars_url.layer, rvars_url.class, rvars_url.keys, rvars_url.search
						])
					);
					
					IEMLApp.receiveSearch(responseData);
				});
			} else if (rvars.a == 'expression') {
				$.getJSON(url, rvars, function(responseData) {
					state_call(IEMLApp.cons_state(rvars, responseData), '',
						cons_url([rvars.lang, rvars.lexicon, 'view', (rvars.id ? rvars.id : rvars.exp)]
					));
					
					IEMLApp.receiveExpression(responseData);
				});
			} else if (rvars.a == 'login') {
				$.getJSON(url, rvars, function(responseData) {
					if (responseData.result == "error") {
						//TODO: change this to show some meaningful message to the user
						console.log("Unable to log in.");
					} else {
						init_user_login(responseData);

						IEMLApp.init_from_state(History.getState(), null);
					}
				});
			} else if (rvars.a == 'logout') {
				$.getJSON(url, rvars, init_anon_user);
			} else if (rvars.a == 'viewUsers') {
				$.getJSON(url, rvars, function(responseData) {
					state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([IEMLApp.lang, 'users']));
					
					IEMLApp.receiveUserList(responseData);
				});
			} else if (rvars.a == 'addUser') {
				$.getJSON(url, rvars, function(responseData) {
					if (responseData.pkUser) {
						$('#userlist tbody').append(formatUserRow(responseData));
					}
				});
			} else if (rvars.a == 'editDictionary' || rvars.a == 'newDictionary') {
				$.getJSON(url, rvars, function(responseData) {
					if ($('#desc-result-id').val() === '') {
						state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([rvars.lang, rvars.lexicon, 'view', responseData.expression]));
						
						$('#desc-result-id').val(responseData.id);
					}
					
					IEMLApp.receiveExpression(responseData);
				});
			} else if (rvars.a == 'deleteDictionary') {
				$.getJSON(url, rvars, function(responseData) {
					History.back(); //@TODO: find a more elegant solution
					
					$('[data-result-id="'+$('#desc-result-id').val()+'"]').remove();
					
					$('#iemlConfirmModal').modal('hide');
				});
			} else if (rvars.a == 'validateExpression') {
				$.getJSON(url, rvars, function(responseData) {
					if (responseData.result == 'success') {
						var parser_out = responseData.parser_output;

						if (parser_out.resultCode === 0) {
							$('.ieml-validation-result .result-success-icon, .ieml-validation-result .result-success').removeClass('hidden');
							$('.ieml-validation-result .result-error-icon, .ieml-validation-result .result-error').addClass('hidden');
						} else {
							$('.ieml-validation-result .result-error-icon, .ieml-validation-result .result-error').removeClass('hidden');
							$('.ieml-validation-result .result-success-icon, .ieml-validation-result .result-success').addClass('hidden');

							$('.ieml-validation-result .result-error').html(parser_out.error);
						}
					} else {
						$('.ieml-validation-result').addClass('hidden');
					}
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
			
			for (var j in respObj) {
				tstr += formatResultRow(respObj[j]);
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
	
	IEMLApp.popstateCallback = function(ev) {
		if (window.History.ready) {
			IEMLApp.init_from_state(window.History.getState());
		}
	};
	
	function cons_url(path, search, hash) {
		return '/' + array_map(
				array_filter(path,
					function(i, el) {
						return el;
					}),
				function(i,el) {
					return window.encodeURIComponent(el);
				}).join('/') +
			(typeof search != 'undefined' && search.length > 0 ? '?' + map_to_url(search) : '') +
			(typeof hash != 'undefined' && hash.length > 0 ? '#' + window.encodeURIComponent(hash) : '');
	}
	
	function reset_views() {
		$('.edit-buttons-wrap').addClass('hidden');
		$('#back-to-list-view').addClass('hidden');
		
		$('#filter-results-wrap').addClass('hidden');
		
		$('#record-view-container, .circuit-container').addClass('hidden');
		$('#user-view-container').addClass('hidden');
		$('#list-view-container').addClass('hidden');
		$('#login-view-container').addClass('hidden');
	}
	
	function switch_to_view(view) {
		reset_views();
		$('#'+view+'-view-container').removeClass('hidden');
	}
	
	function switch_to_record() {
		reset_views();
		
		if (IEMLApp.user) {
			$('.edit-buttons-wrap').removeClass('hidden');
		} else {
			$('.edit-buttons-wrap').addClass('hidden');
		}
		
		$('#back-to-list-view').removeClass('hidden');
		$('#record-view-container, .circuit-container').removeClass('hidden');
	}
	
	function switch_to_list() {
		reset_views();
		$('#filter-results-wrap').removeClass('hidden');
		$('#list-view-container').removeClass('hidden');
	}
	
	function init_user_login(userObj) {
		IEMLApp.user = userObj;
		IEMLApp.submit({'a': 'getUserLibraries'});
		
		$('.login-btn-wrap').addClass('hidden');
		$('.logout-btn-wrap').removeClass('hidden');
		$('#add-ieml-record-wrap').removeClass('hidden');
		
		if (IEMLApp.user.enumType == 'admin') {
			$('.ieml-view-users-wrap').removeClass('hidden');
		} else {
			$('.ieml-view-users-wrap').addClass('hidden');
		}
		
		$('.user-display-name').html(userObj.strDisplayName);
	}
	
	function init_anon_user() {
		$('.logout-btn-wrap').addClass('hidden');
		$('.login-btn-wrap').removeClass('hidden');
		$('#add-ieml-record-wrap').addClass('hidden');
		$('#ieml-view-users-wrap').addClass('hidden');
		$('.edit-buttons-wrap').addClass('hidden');
		
		IEMLApp.user = null;
	}
	
	function getVerbLayer(exp) {
		var gram_classes_verbs = ['O:', 'U:', 'A:', 'y.', 'o.', 'e.', 'u.', 'a.', 'i.', 'wo.', 'wa.', 'we.', 'wu.'];
		var gram_classes_nouns = ['M:', 'S:', 'B:', 'T:', 's.', 'b.', 't.', 'k.', 'm.', 'n.', 'd.', 'f.', 'l.', 'j.', 'h.', 'p.', 'g.', 'c.', 'x.'];
		var gram_classes_hybrid_nouns = ['I:', 'F:'];
		var gram = '';
		var layer = '';
	
		if (array_indexOf(gram_classes_verbs, exp.substr(0,2))>=0 || array_indexOf(gram_classes_verbs, exp.substr(0,3))>=0) gram = 'Verb';
		else if (array_indexOf(gram_classes_nouns, exp.substr(0,2))>=0) gram = 'Noun';
		else if (exp.substr(0,2) == 'E:') gram = 'AUXILIARY';
		else if (array_indexOf(gram_classes_hybrid_nouns, exp.substr(0,2))>=0 || array_lastIndexOf(exp, '+')>=0) gram = 'Hybrid';
	
		if (exp.substr(-1)==':') layer = 0;
		else if (exp.substr(-1)=='.') layer = 1;
		else if (exp.substr(-1)=='-') layer = 2;
		else if (exp.substr(-1)=="'") layer = 3;
		else if (exp.substr(-1)==',') layer = 4;
		else if (exp.substr(-1)=='_') layer = 5;
		else if (exp.substr(-1)==';') layer = 6;
	
		return {'layer':layer, 'gram':gram};
	}

	function format_single_relation(info) {
		var ret = '';

		if (info.id) {
			ret += '<a href="/ajax.php?id='+info.id + '&a=searchDictionary" data-exp="'+info.exp+'" data-id="'+info.id+'" class="editExp">';

			if (info.desc) {
				ret += info.desc + ' ('+info.exp[0]+')';
			} else {
				ret += info.exp[0];
			}
		} else {
			ret += '<a href="javascript:void(0);" class="createEmptyExp">' + info.exp[0];
		}

		ret += '</a>';
		return ret;
	}
	
	function format_relations(info) {
		var rel = info.relations, contained_html = '', containing_html = '', concurrent_html = '', complementary_html = '', etymology_html = '';
		
		if (rel.contained.length > 0) {
			for (var i=0; i<rel.contained.length; i++) {
				contained_html += '<li>'+format_single_relation(rel.contained[i])+'</li>';
				
				var concurrent_rel = rel.concurrent[rel.contained[i].exp[0]];
				if (concurrent_rel.length > 0) {
					concurrent_html += '<div class="concurring-relation col-md-6"><span class="concurring-relation-text"><strong>In relation to "' + format_single_relation(rel.contained[i]) + '"</strong></span><ul class="unstyled relation-list">';

					for (var j=0; j<concurrent_rel.length; j++) {
						concurrent_html += '<li>'+format_single_relation(concurrent_rel[j])+'</li>';
					}
					concurrent_html += '</ul></div>';
				}
			}
		}

		if (contained_html.length > 0) {
			contained_html = '<ul class="unstyled relation-list">' + contained_html + '</ul>';
		} else {
			contained_html = 'Nothing.';
		}
		
		if (rel.containing.length > 0) {
			containing_html += '<ul class="unstyled relation-list">';
			for (var k=0; k<rel.containing.length; k++) {
				containing_html += '<li>'+format_single_relation(rel.containing[k])+'</li>';
			}
			containing_html += '</ul>';
		} else {
			containing_html = 'Nothing.';
		}
		
		if (concurrent_html.length > 0) {
			concurrent_html = '<div class="row">' + concurrent_html + '</div>';
		} else {
			concurrent_html = 'Nothing.';
		}
		
		complementary_html = '<p>';
		if (rel.complementary && rel.complementary.exp) {
			complementary_html += format_single_relation(rel.complementary);
		} else {
			complementary_html += 'None.';
		}
		complementary_html += '</p>';
		
		return {'contained': contained_html, 'containing': containing_html, 'concurrent': concurrent_html, 'complementary': complementary_html};
	}
	
	function format_etymology(info) {
		var etym = info.etymology, ret = '<ul class="unstyled etymology">';
		
		for (var i=0; i<etym.length; i++) {
			if (etym[i].id && etym[i].id.length > 0 && etym[i].exp && etym[i].exp.length > 0 && etym[i].desc && etym[i].desc.length > 0) {
				ret += '<li><a href="/ajax.php?id='+etym[i].id+'&a=searchDictionary" data-exp="'+etym[i].exp + '" data-id="'+etym[i].id+'" class="editExp">'+etym[i].desc + ' (' + etym[i].exp + ')</a></li>';
			} else {
				ret += '<li><a href="javascript:void(0);" class="createEmptyExp">' + etym[i].exp + '</a></li>';
			}
		}
		
		ret += '</ul>';
		
		return ret;
	}
	
	function fillForm(info) {
		if ($('#ieml-desc-result-edit').hasClass('disabled')) {
			writeToRead();
		}
		
		$('#desc-result-id').val(info.id ? info.id : '');
		
		if (info.expression && info.expression.length > 0) {
			var details = getVerbLayer(info.expression);
			
			$('#ieml-result').html(info.expression);
		
			$('#ieml-result-details').html(details.gram+' Layer '+details.layer);
		} else {
			$('#ieml-result').empty();
			
			$('#ieml-result-details').html('NEW ENTRY');
		}
		
		if (info.example && info.example.length > 0) {
			$('#ieml-ex-result').html(info.example);
		} else {
			$('#ieml-ex-result').empty();
		}
		
		if (info.descriptor && info.descriptor.length > 0) {
			$('#ieml-desc-result').html(info.descriptor);
		} else {
			$('#ieml-desc-result').empty();
		}
		
		$('#iemlEnumCategoryModal').prop('checked', info.enumCategory == 'Y');
		
		window.__req_info = info;
		
		if (info.tables) {
			var str = '', render_callback = function(el) {
				var out = '';
				
				if (el.example) {
					out += '<div class="' + (el.enumEnabled == 'N' ? 'hidden ' : '') + 'cell_wrap' + '">' + '<a href="/ajax.php?id=' + el.id + '&a=searchDictionary" data-exp="' + el.expression + '" data-id="' + el.id + '" class="editExp">' + '<span class="cell_expression">' + el.expression + '</span><span class="cell_example">' + el.example + '</span></a>' + '</div>';
				} else {
					out += '<div><a href="javascript:void(0);" class="createEmptyExp">' + el.expression + '</a></div>';
				}
				
				return out;
			};
			
			for (var i=0; i<info.tables.length; i++) {
				for (var j=0; j<info.tables[i].length; j++) {
					info.tables[i][j].table.ver_header_depth = info.tables[i][j].edit_vertical_head_length;
					info.tables[i][j].table.hor_header_depth = info.tables[i][j].edit_horizontal_head_length;
					info.tables[i][j].table.length = info.tables[i][j].length;
					info.tables[i][j].table.height = info.tables[i][j].height;
					info.tables[i][j].table.top = {'expression': 'top', 'example': 'decriptor', 'id': undefined};
				}
				
				if (info.tables[i].length > 1) {
					str +='<table class="relation"><tbody>';
					
					for (var k=0; k<info.tables[i].length; k++) {
						if (k > 0) {
							str +='<tr><td class="table-concat-seperator" colspan="' + (parseInt(info.tables[i][k].table.length, 10) + parseInt(info.tables[i][k].table.hor_header_depth, 10)) + '"></td></tr>';
						}
						
						str += ieml_render_table_body(info.tables[i][k].table, render_callback);
					}
					
					str += '</tbody></table>';
				} else {
					str += ieml_render_table(info.tables[i][0].table, render_callback);
				}
				
				str += '<hr />';
			}
			
			$('#ieml-table-span').html(str);
		} else {
			$('#ieml-table-span').empty();
		}
		
		$('#iemlEnumShowTable').prop('checked', info.enumShowEmpties == 'Y').trigger('change');
		
		$('#iemlEnumComplConcOff').prop('checked', info.iemlEnumComplConcOff == 'Y').trigger('change');
		if (info.iemlEnumComplConcOff == 'Y') {
			$('#ieml-complementary-section').addClass('hidden');
		} else {
			$('#ieml-complementary-section').removeClass('hidden');
		}
		$('#iemlEnumSubstanceOff').prop('checked', info.iemlEnumSubstanceOff == 'Y').trigger('change');
		$('#iemlEnumAttributeOff').prop('checked', info.iemlEnumAttributeOff == 'Y').trigger('change');
		$('#iemlEnumModeOff').prop('checked', info.iemlEnumModeOff == 'Y').trigger('change');
		
		if (info.tables) {
			var relationHtml = {'contained': '', 'containing': '', 'concurrent': '', 'complementary': ''};
			
			for (var l=0; l<info.tables.length; l++) {
				for (var m=0; m<info.tables[l].length; m++) {
					var rel_info = format_relations(info.tables[l][m]);
					
					relationHtml.contained += rel_info.contained;
					relationHtml.containing += rel_info.containing;
					relationHtml.concurrent += rel_info.concurrent;
					relationHtml.complementary += rel_info.complementary;
				}
			}
			
			$('#ieml-contained-wrap').removeClass('hidden').html(relationHtml.contained);
			$('#ieml-containing-wrap').removeClass('hidden').html(relationHtml.containing);
			$('#ieml-concurrent-wrap').removeClass('hidden').html(relationHtml.concurrent);
			$('#ieml-complementary-wrap').removeClass('hidden').html(relationHtml.complementary);
		} else {
			$('#ieml-contained-wrap, #ieml-containing-wrap, #ieml-concurrent-wrap, #ieml-complementary-wrap').addClass('hidden');
		}
		
		if (info.etymology) {
			$('#ieml-etymology-wrap').removeClass('hidden').html(format_etymology(info));
		} else {
			$('#ieml-etymology-wrap').addClass('hidden');
		}
		
		if (info.debug) {
			console.log('Debug from server: ', info.debug);
		}
		
		switch_to_record();
	}
	
	function formatResultRow(obj) {
		return '<tr data-key="' + (obj.enumCategory == 'Y' ? 'true' : 'false') + '"' +
			'data-layer="'+(obj.intLayer >= 0 ? obj.intLayer : '-1') + '"' +
			'data-result-id="' + obj.id + '">' +
			'<td>' + obj.expression + '</td>' +
			'<td>' + (obj.example ? obj.example : '') + '</td>' +
			'<td>' +
				'<a href="/ajax.php?id=' + obj.id + '&a=searchDictionary"' +
					'data-exp="' + obj.expression + '"' +
					'data-id="' + obj.id + '"' +
					'class="btn btn-default editExp"><span class="glyphicon glyphicon-pencil"></span></a>' +
				(IEMLApp.user && IEMLApp.user.pkUser != obj.fkLibrary ?
					'<div class="btn-group">' +
						'<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">' +
							'<span class="glyphicon glyphicon-plus">&nbsp;</span><span class="caret"></span>' +
						'</button>' +
						'<ul class="dropdown-menu" role="menu">' +
							array_map(IEMLApp.userLibraries, function(i, lib) {
								return '<li><a href="javascript:void(0);" data-lib-id="' + lib.pkLibrary + '">' + lib.strName + '</a></li>';
							}) +
						'</ul>' +
					'</div>'
				: '') +
			'</td>' +
			'</tr>';
	}
	
	function formatUserRow(user) {
		return '<tr><td>'+user.strEmail+'</td><td>'+user.enumType+'</td><td>' + LightDate.date('Y-m-d H:i:s', LightDate.date_timezone_adjust(user.tsDateCreated*1000)) + '</td><td><!--a href="/ajax.php?a=editUser&pkUser='+user.pkUser+'" class="editUser btn btn-default">Edit</a--><a href="/ajax.php?a=delUser&pkUser=' + user.pkUser+'" class="delUser btn btn-default">Delete</a></td></tr>';
	}
	
	function showConfirmDialog(text, callback, etc) {
		if (typeof text !== 'undefined' && text !== null && text.length > 0) {
			$('#confirmCancelModalText').html(text);
		} else {
			$('#confirmCancelModalText').html('Are you sure?');
		}
	
		$('#confirmCancelModal').data('callback', function() {
			if (typeof callback !== 'undefined' && callback !== null) callback();
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
		$('.non-edit-buttons').removeClass('hidden');
		$('#ieml-desc-result-edit').addClass('disabled');
		textToInput($('#ieml-result'), 'input-large form-control');
		textToInput($('#ieml-ex-result'), 'input-xlarge form-control');
		textToInput($('#ieml-desc-result'), 'input-xlarge form-control');
		
		$('#ieml-result > .input-large').on('input', function() {
			var exp = $(this).val();

			if (exp.length > 0) {
				$('.ieml-validation-result').removeClass('hidden');

				IEMLApp.submit({
					'a': 'validateExpression',
					'expression': exp
				});
			} else {
				$('.ieml-validation-result').addClass('hidden');
			}
		});

		$('#iemlEnumCategoryModal').removeAttr('disabled');
		$('.edit-only').removeClass('hidden');
	}
	
	function writeToRead() {
		$('.non-edit-buttons').addClass('hidden');
		$('#ieml-desc-result-edit').removeClass('disabled');
		inputToText($('#ieml-result'));
		inputToText($('#ieml-ex-result'));
		inputToText($('#ieml-desc-result'));

		$('.ieml-validation-result').addClass('hidden');

		$('#iemlEnumCategoryModal').attr('disabled', 'disabled');
		$('.edit-only').addClass('hidden');
	}
	
	$(function() {
		$(window).on('popstate', IEMLApp.popstateCallback);
		
		$(document).on('submit', '#search-form', function() {
			var form_data = form_arr_to_map($(this).serializeArray());
			IEMLApp.submit(form_data);
			
			return false;
		}).on('change', '#search-form select', function() {
			$('#search-form').trigger('submit');
		}).on('change', '#search-spec-select', function() {
			var $this = $(this);
			var selected_option = $this.children('option[value="' + $this.val() + '"]');
			if (IEMLApp.user && selected_option.data('owned-by') == IEMLApp.user.pkUser) {
				$('.list-view-wrap').addClass('editor-in');
			} else {
				$('.list-view-wrap').removeClass('editor-in');
			}
		}).on('change', '#iemlEnumShowTable', function() {
			var info = IEMLApp.lastRetrievedData;
			
			if($('#iemlEnumShowTable').is(':checked')) {
				$('.nonExistentCell').removeClass('hidden');
				$('table.relation tr.empty_head_tr, table.relation td.empty_head_tr_td').removeClass('hidden');
				if (info.edit_vertical_head_length <= 0 && info.edit_horizontal_head_length <= 0) {
					$('table.relation td.empty_cell').addClass('hidden');
				} else {
					$('table.relation td.empty_cell').removeClass('hidden')
						.attr('rowspan', parseInt(info.edit_vertical_head_length, 10) + 1).attr('colspan', info.edit_horizontal_head_length);
				}
			} else {
				$('.nonExistentCell').addClass('hidden');
				$('table.relation tr.empty_head_tr, table.relation td.empty_head_tr_td').addClass('hidden');
				if (info.render_vertical_head_length <= 0 && info.render_horizontal_head_length <= 0) {
					$('table.relation td.empty_cell').addClass('hidden');
				} else {
					$('table.relation td.empty_cell').removeClass('hidden')
						.attr('rowspan', parseInt(info.render_vertical_head_length, 10) + 1).attr('colspan', info.render_horizontal_head_length);
				}
			}
		}).on('click', '.editExp', function() {
			var $this = $(this);
			
			IEMLApp.submit({
				'a': 'expression',
				'lang': IEMLApp.lang,
				'lexicon': IEMLApp.lexicon,
				'exp': $this.data('exp'),
				'id': $this.data('id')
			});
			
			return false;
		}).on('click', '#back-to-list-view', function() {
			switch_to_list();
			
			return false;
		}).on('change', '#search-lang-select', function() {
			IEMLApp.switch_lang($(this).val(), window.History.getState().data);
			
			return false;
		}).on('change', '#filter-results-wrap [name="filter-results"]', function() {
			var $this = $(this);

			$('#search-form [name="keys"]').val($this.val());

			if ($this.val() != 'keys')
			{
				var state_data = History.getState().data;
				if (state_data.req.keys == 'keys')
				{
					$('#search-form').trigger('submit');
					return;
				}
			}
			
			if ($this.val() == 'keys')
			{
				$('#listview tbody [data-key="false"]').addClass('hidden');
			}
			else
			{
				$('#listview tbody [data-key="false"]').removeClass('hidden');
			}
		}).on('click', '#add-ieml-record', function() {
			IEMLApp.lastRetrievedData = {'expression':'', 'example':'', 'enumCategory':'N', 'enumShowEmpties': 'N'};
			fillForm(IEMLApp.lastRetrievedData);
			
			readToWrite();
			
			$('#desc-result-id').val('');
			
			return false;
		}).on('click', '#ieml-desc-result-save', function() {
			writeToRead();
			
			var reqVars = {}, cur_id = $('#desc-result-id').val();
			if ($('#desc-result-id').val().length === 0) {
				reqVars.a = 'newDictionary';
			} else {
				reqVars.a = 'editDictionary';
			}
			if (cur_id.length > 0) {
				reqVars.id = parseInt($('#desc-result-id').val(), 10);
			}
			
			reqVars.pkTable2D = IEMLApp.lastRetrievedData.pkTable2D;
			reqVars.enumShowEmpties = $('#iemlEnumShowTable').is(':checked') ? 'Y' : 'N';
			
			reqVars.enumCategory = $('#iemlEnumCategoryModal').is(':checked') ? 'Y' : 'N';
			reqVars.oldEnumCategory = IEMLApp.lastRetrievedData.enumCategory;
			IEMLApp.lastRetrievedData.enumCategory = IEMLApp.lastRetrievedData.enumCategory == 'Y' ? 'N' : 'Y';
			reqVars.exp = $('#ieml-result').text();
			reqVars.oldExp = IEMLApp.lastRetrievedData.expression;
			
			reqVars.example = $('#ieml-ex-result').text();
			reqVars.descriptor = $('#ieml-desc-result').text();
			reqVars.lang = $('#search-lang-select').val();
			
			reqVars.iemlEnumComplConcOff = $('#iemlEnumComplConcOff').is(':checked') ? $('#iemlEnumComplConcOff').val() : 'N';
			
			reqVars.iemlEnumSubstanceOff = $('#iemlEnumSubstanceOff').is(':checked') ? $('#iemlEnumSubstanceOff').val() : 'N';
			reqVars.iemlEnumAttributeOff = $('#iemlEnumAttributeOff').is(':checked') ? $('#iemlEnumAttributeOff').val() : 'N';
			reqVars.iemlEnumModeOff = $('#iemlEnumModeOff').is(':checked') ? $('#iemlEnumModeOff').val() : 'N';
			
			IEMLApp.submit(reqVars);
			
			return false;
		}).on('click', '#ieml-desc-result-cancel', function() {
			writeToRead();
			fillForm(IEMLApp.lastRetrievedData);
			
		}).on('click', '#ieml-desc-result-edit', function() {
			if (!$('#ieml-desc-result-edit').hasClass('disabled')) {
				readToWrite();
			}
			
			return false;
		}).on('click', '#ieml-desc-result-delete', function() {
			if ($('#desc-result-id').val() !== '') {
				$('#iemlConfirmModal .modal-body').html('<div><span>Are you sure you want to delete "' + $('#ieml-result input').eq(0).val() + '"?<br /></span></div>');
				$('#iemlConfirmModal').modal('show');
			}
		
			return false;
		}).on('change', '.enable_check', function() {
			var thisID = $(this).data('ref-id'), thisval = $(this).is(':checked') ? 'Y' : 'N';
			if (thisval == 'N')
				$(this).siblings('.cell_wrap').addClass('hidden');
			else
				$(this).siblings('.cell_wrap').removeClass('hidden');
			$.post('/ajax.php?a=setTableEl&id=' + thisID + '&enumEnabled=' + thisval, function(response) {});
		}).on('click', '.createEmptyExp', function() {
			var $this = $(this);
			IEMLApp.lastRetrievedData.expression = $this.text();
			IEMLApp.lastRetrievedData.example_en = '';
			IEMLApp.lastRetrievedData.example_fr = '';
			IEMLApp.lastRetrievedData.enumCategory = 'N';
			IEMLApp.lastRetrievedData.enumShowEmpties = $('#iemlEnumShowTable').is(':checked') ? 'Y' : 'N';
			
			fillForm(IEMLApp.lastRetrievedData);
			
			readToWrite();
			
			$('#desc-result-id').val('');
			
			$('.relation-sel-cell').removeClass('relation-sel-cell');
			$this.parents('div').eq(0).addClass('relation-sel-cell'); //TODO highlight line properly
			
			return false;
		}).on('click', '#iemlConfirmModal #iemlConfirmYesModal', function() {
			IEMLApp.submit({'a':'deleteDictionary', 'id':$('#desc-result-id').val()});
			
			return false;
		}).on('click', '#ieml-view-users', function() {
			IEMLApp.submit({'a': 'viewUsers', 'deleted': 'no'});
			
			return false;
		}).on('click', '#addUser', function() {
			$('#iemlAddUserModal').modal('show');
			
			return false;
		}).on('click', '#iemlAddUserModalAdd', function() {
			var formData = form_arr_to_map($('#iemlUser').serializeArray());
			
			$('#iemlAddUserModal').modal('hide');
			
			IEMLApp.submit({ 'a' : formData.a, 'username' : formData.addUserModalUsername, 'pass' : formData.addUserModalPass, 'enumType' : formData.addUserModalType });
			
			return false;
		}).on('click', '.delUser', function() {
			var $this = $(this);
			
			showConfirmDialog('Are you sure?', function() {
				var jurl = $this.attr('href');
				
				$.post(jurl, function(resp) {
					$this.closest('tr').remove();
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
			
			return false;
		}).on('click', '.logout-btn', function() {
			IEMLApp.submit({'a': 'logout'});
			
			return false;
		}).on('submit', '#formLogin', function() {
			var formData = form_arr_to_map($(this).serializeArray());
			formData.a = 'login';
			IEMLApp.submit(formData);
			
			return false;
		}).on('shown', 'a[data-toggle="tab"]', function(e) {
			//var cur_state = History.getState(), state_call = obj_size(cur_state.data) == 0 ? IEMLApp.replaceState : IEMLApp.pushState;
			//state_call(cur_state, '', $(e.target).attr('href'));
			
			//$(window).trigger('hashchange');
		});


		IEMLApp.load_url = window.location;
		
		if (window.location.pathname.length == 1) {
			//IEMLApp.pushState(IEMLApp.cons_state({'a': 'searchDictionary'}, []), '', cons_url([IEMLApp.lang, IEMLApp.lexicon, 'all']));
			$('#filter-results-keys').click();
			$('#search-form').submit();
		}

		IEMLApp.user = _SESSION.user;
		if (IEMLApp.user)
		{
			init_user_login(IEMLApp.user);
		}
		else
		{
			init_anon_user();
		}
		IEMLApp.submit({'a': 'getAllLibraries'});
		
		IEMLApp.init_from_url(window.location);
	});
	
	$(window).on('hashchange', function(ev) {
		//TODO: keep track of/restore tab state on navigation, through hash info in url
	});
})(window, jQuery);