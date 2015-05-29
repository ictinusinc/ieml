(function($) {
	'use strict';
	
	$.fn.extend({
		'resetCondition': function() {
			var pthis = this.get(0);
			pthis.deleteCount = 0;
		},
		'conditionalDelete': function(delete_limit, on_delete) {
			var pthis = this.get(0);
			if (typeof pthis.deleteCount == 'undefined') {
				pthis.deleteCount = 0;
			}

			if (this.parent().hasClass('editor-proper')) {
				++pthis.deleteCount;

				if (pthis.deleteCount >= delete_limit) {
					this.remove();
					on_delete();
				}
			}
		},

		'bhide': function() {
			return this.addClass('hidden');
		},

		'bshow': function() {
			return this.removeClass('hidden');
		},

		'btoggle': function() {
			if (this.hasClass('hidden')) {
				return this.bshow();
			} else {
				return this.bhide();
			}
		}
	});
})(jQuery);

(function(window, $) {
	'use strict';
	
	var api_offset = '/api/';
	
	var IEMLApp = {};
	
	IEMLApp.ongoingRequestCount = 0;
	IEMLApp.user = null;
	IEMLApp.userLibraries = null;
	IEMLApp.library = '1';
	IEMLApp.lang = 'EN';
	IEMLApp.sortableGroup = 'table';
	IEMLApp.draggedObject = null;
	IEMLApp.sortableOptions = {
		'onStart': function(evt) {
			$('.editor-garbage').bshow();
			IEMLApp.draggedObject = evt.item;
			$(IEMLApp.draggedObject).resetCondition();
		},
		'onEnd': function(evt) {
			$('.editor-garbage').bhide();
			$(IEMLApp.draggedObject).conditionalDelete(2, function() {
				IEMLApp.onSort($('.editor-proper'));
			});
		}
	};

	IEMLApp.onSort = function($this) {
		var $children = $this.children();
		var only_script = $children.toArray().reduce(function(acc, el) {
				return acc && !!$(el).data('is-script');
			}, true);
		var $content_type = $('.editor-drawer .content-type');
		$content_type.children().bhide();

		if (only_script) {
			$this.addClass('only-script');
			$content_type.find('[data-lang-switch="text_usl"]').bshow();
		} else {
			$this.removeClass('only-script');
			$content_type.find('[data-lang-switch="proposition_phrase"]').bshow();
		}
	};

	IEMLApp.construct_editor_array = function(elements) {
		var only_script = elements.get().reduce(function(acc, el) {
				return acc && !!$(el).data('is-script');
			}, true);

		return elements.get()
			.reduce(function(acc, el, i) {
				acc.push({
					'expression': $(el).data('script-val'),
					'id': $(el).data('script-id')
				});

				return acc;
			}, []);
	};

	IEMLApp.match_url_settings = function(url) {
		var opt_map = {
			'class': '(verb|noun|auxiliary)',
			'key': '(keys)',
			'layer': 'layer-([0-6])',
			'library': 'library-([1-9][0-9]*)',
			'lang': '(EN|FR)',
			'mode': '(search|view|users|login|rel-view)'
		};
		var ret = {};

		for (var i in opt_map) {
			var mat = url.match(new RegExp('\/' + opt_map[i] + '\/?', 'i'));

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
		if (settings.library) {
			$('#search-form #search-library-select').val(settings.library);
		}
		if (settings.lang) {
			$('#search-form #search-lang-select').val(settings.lang.toUpperCase());
		}

		var $filter;
		if (settings.key && settings.key == 'keys') {
			$filter = $('#filter-results-keys');
		} else {
			$filter = $('#filter-results-button');
		}
		$filter.get().checked = true;
		$filter.parent().addClass('active');

		if (settings.search) {
			$('#search-form #search').val(settings.search);
		}
	};
	
	IEMLApp.init_from_url = function (url_obj) {
		var qry = url_obj.search, path_arr = path_split(url_obj.pathname);
		var path_last = path_arr[path_arr.length-1], path = url_obj.pathname;
		var star_index = path.indexOf('*');
		var settings = IEMLApp.match_url_settings(path.substr(0, star_index > -1 ? star_index : path.length));

		if (settings.library) {
			IEMLApp.library = settings.library;

			$('#search-library-select').val(IEMLApp.library);
		}

		if (settings.lang) {
			IEMLApp.set_lang(settings.lang);

			IEMLApp.switch_lang(IEMLApp.lang);
		}
		else
		{
			IEMLApp.submit({ 'a': 'getAllLibraries', 'lang': IEMLApp.lang });
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
				'library': IEMLApp.library,
				'lang': IEMLApp.lang,
				'layer': settings.layer,
				'class': settings.class,
				'keys': settings.key,
				'search': settings.search
			});
		} else if (settings.mode == 'view') {
			if (isNaN(parseInt(path_last, 10))) {
				IEMLApp.submit({ 'a': 'expression', 'library': IEMLApp.library, 'lang': IEMLApp.lang, 'exp': path_last });
			} else {
				IEMLApp.submit({
					'a': 'expression',
					'library': IEMLApp.library,
					'lang': IEMLApp.lang,
					'id': path_last
				});
			}
		} else if (settings.mode == 'rel-view') {
			IEMLApp.submit({
				'a': 'relationalExpression',
				'library': IEMLApp.library,
				'lang': IEMLApp.lang,
				'id': path_last
			});
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
				IEMLApp.receiveSearch(resp, req);
			} else if (req.a == 'expression') {
				IEMLApp.receiveExpression(resp);
			} else if (req.a == 'relationalExpression') {
				IEMLApp.recieveVisualExpression(resp);
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

		if (!cur_state || !cur_state.req || cur_state.req.lang != new_lang) {
			IEMLApp.submit({ 'a': 'getAllLibraries', 'lang': new_lang });

			if (IEMLApp.user) {
				IEMLApp.submit({ 'a': 'getUserLibraries', 'lang': new_lang });
			}
		}

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
		var escaped = $.extend({}, obj);

		if (escaped.layer && escaped.layer.substr(0, 5) != 'layer') {
			escaped.layer = 'layer-' + escaped.layer;
		}
		if (escaped.search && escaped.search.substr(0, 1) != '*') {
			escaped.search = '*' + escaped.search;
		} else {
			escaped.search = '*';
		}

		return escaped;
	};

	IEMLApp.fetch = function(rvars, callback) {
		IEMLApp.ongoingRequestCount++;

		showLoadingIndicator();
		$.post(api_offset, rvars, null, 'json')
			.done(function() {
				callback.apply(null, arguments);

				IEMLApp.ongoingRequestCount--;

				if (IEMLApp.ongoingRequestCount === 0) {
					hideLoadingIndicator();
				}
			});
	};
	
	IEMLApp.submit = function (rvars)
	{
		var prev_state = History.getState();
		
		var state_call = obj_size(prev_state.data) === 0 ? IEMLApp.replaceState : IEMLApp.pushState;
		
		if (rvars)
		{
			if (rvars.a == 'duplicate')
			{
				IEMLApp.fetch(rvars, function(responseData)
				{
					if (responseData.result != 'error' && responseData.fkLibrary[0] == IEMLApp.library)
					{
						$('[data-result-id="' + rvars.id + '"]').after(formatResultRow(responseData));
					}
				});
			} else if (rvars.a == 'getUserLibraries') {
				IEMLApp.fetch(rvars, function(responseData) {
					if (responseData.result != 'error') {
						IEMLApp.userLibraries = responseData;
					}
				});
			} else if (rvars.a == 'getAllLibraries') {
				IEMLApp.fetch(rvars, function(responseData) {
					var libHtml = '';

					for (var i=0; i<responseData.length; i++) {
						libHtml += '<option data-owned-by="' + responseData[i].fkUser +
							'" value="' + responseData[i].pkLibrary + '">' + responseData[i].strName + '</option>';
					}

					$('#search-library-select').html(libHtml);
					$('#search-library-select').val(IEMLApp.library);
				});
			} else if (rvars.a == 'searchDictionary') {
				IEMLApp.fetch(rvars, function(responseData) {
					var rvars_url = IEMLApp.form_data_make_url_safe(rvars);

					state_call(IEMLApp.cons_state(rvars, responseData), '',
						cons_url([
							rvars_url.lang, 'library-' + rvars_url.library, 'search',
							rvars_url.layer, rvars_url.class, rvars_url.keys, rvars_url.search
						])
					);
					
					IEMLApp.receiveSearch(responseData, rvars);
				});
			} else if (rvars.a == 'expression') {
				IEMLApp.fetch(rvars, function(responseData) {
					state_call(IEMLApp.cons_state(rvars, responseData), '',
						cons_url([rvars.lang, 'library-' + rvars.library, 'view', (rvars.id ? rvars.id : rvars.exp)]
					));
					
					IEMLApp.receiveExpression(responseData);
				});
			} else if (rvars.a == 'login') {
				IEMLApp.fetch(rvars, function(responseData) {
					if (responseData.result == "error") {
						//TODO: change this to show some meaningful message to the user
						console.log("Unable to log in.");
					} else {
						init_user_login(responseData);

						IEMLApp.init_from_state(History.getState(), null);
					}
				});
			} else if (rvars.a == 'logout') {
				IEMLApp.fetch(rvars, function() {
					init_anon_user();

					IEMLApp.init_from_state(History.getState(), null);
				});
			} else if (rvars.a == 'viewUsers') {
				IEMLApp.fetch(rvars, function(responseData) {
					state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([IEMLApp.lang, 'users']));
					
					IEMLApp.receiveUserList(responseData);
				});
			} else if (rvars.a == 'addUser') {
				IEMLApp.fetch(rvars, function(responseData) {
					if (responseData.pkUser) {
						$('#userlist tbody').append(formatUserRow(responseData));
					}
				});
			} else if (rvars.a == 'delUser') {
				IEMLApp.fetch(rvars, function(responseData) {
					if (responseData.result != 'error') {
						$('#userlist tbody tr[data-id="' + rvars.pkUser + '"]').remove();
					}
				});
			} else if (rvars.a == 'editDictionary' || rvars.a == 'newDictionary') {
				IEMLApp.fetch(rvars, function(responseData) {
					if ($('#desc-result-id').val() === '') {
						state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([rvars.lang, 'library-' + rvars.library, 'view', responseData.expression]));
						
						$('#desc-result-id').val(responseData.id);
					}
					
					IEMLApp.receiveExpression(responseData);
				});
			} else if (rvars.a == 'editVisualExpression') {
				IEMLApp.fetch(rvars, function(responseData) {
					if (responseData.result == 'error') {
						$('.ieml-validation-result, .result-error-icon').bshow();
						$('.result-error').html(responseData.error);
						return;
					} else {
						$('.ieml-validation-result, .result-error-icon').bhide();
					}

					var $existing_row = $('[data-result-id="' + responseData.id + '"][data-expression-type="relational"]');
					if ($existing_row.length > 0) {
						$existing_row.get(0).outerHTML = formatResultRow(responseData);
					}

					IEMLApp.recieveVisualExpression(responseData);
				});
			} else if (rvars.a == 'newVisualExpression') {
				IEMLApp.fetch(rvars, function(responseData) {
					if (responseData.result == 'error') {
						$('.ieml-validation-result, .result-error-icon').bshow();
						$('.result-error').html(responseData.error);
						return;
					} else {
						$('.ieml-validation-result, .result-error-icon').bhide();
					}

					//trigger list reload
					$('#search-form').trigger('submit');
					
					state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([rvars.lang, 'library-' + rvars.library, 'rel-view', responseData.id]));
					
					IEMLApp.recieveVisualExpression(responseData);
				});
			}
			else if (rvars.a == 'relationalExpression')
			{
				IEMLApp.fetch(rvars, function(responseData)
				{
					IEMLApp.library = responseData.fkLibrary[0];

					$('#search-form #search-library-select').val(IEMLApp.library);

					state_call(IEMLApp.cons_state(rvars, responseData), '', cons_url([rvars.lang, 'library-' + IEMLApp.library, 'rel-view', responseData.id]));
					
					IEMLApp.recieveVisualExpression(responseData);
				});
			}
			else if (rvars.a == 'deleteDictionary')
			{
				IEMLApp.fetch(rvars, function(responseData)
				{
					History.back();
					
					$('[data-result-id="' + rvars.id + '"]').remove();
				});
			}
			else if (rvars.a == 'deleteVisualExpression')
			{
				rvars.a = 'deleteDictionary';

				IEMLApp.fetch(rvars, function(responseData)
				{
					$('[data-result-id="' + rvars.id + '"]').remove();
				});
			}
			else if (rvars.a == 'deleteExpressionFromLibrary')
			{
				IEMLApp.fetch(rvars, function(responseData)
				{
					$('[data-result-id="' + rvars.id + '"]').remove();
				});
			} else if (rvars.a == 'validateExpression') {
				if (rvars.expression.length === 0) {
					$('.ieml-validation-result').bhide();
				} else {
					IEMLApp.fetch(rvars, function(responseData) {
						if (responseData.result == 'success') {
							var parser_out = responseData.parser_output;

							$('.ieml-validation-result').bshow();

							if (parser_out.resultCode === 0) {
								$('.ieml-validation-result .result-success-icon, .ieml-validation-result .result-success').bshow();
								$('.ieml-validation-result .result-error-icon, .ieml-validation-result .result-error').bhide();
							} else {
								$('.ieml-validation-result .result-error-icon, .ieml-validation-result .result-error').bshow();
								$('.ieml-validation-result .result-success-icon, .ieml-validation-result .result-success').bhide();

								$('.ieml-validation-result .result-error').html(parser_out.error);
							}
						} else {
							$('.ieml-validation-result').bhide();
						}
					});
				}
			} else if (rvars.a == 'addExpressionToLibrary') {
				IEMLApp.fetch(rvars, function(responseData) {
					//remove link that added this expression to library
					if (responseData.result != 'error') {
						var $link = $('.addExpressionToLibrary[data-exp-id="' + rvars.id + '"][data-lib-id="' + rvars.library + '"]');
						var $list = $link.parents('ul').eq();

						$link.parent('li').remove();

						if ($list.children().length === 0) {
							$list.parents('.btn-group').eq(0).remove();
						}
					}
				});
			} else {
				return false;
			}
			
			return true;
		}
		
		return false;
	};
	
	IEMLApp.receiveSearch = function (respObj, reqObj) {
		if (respObj && respObj.length > 0) {
			var tstr = '';
			
			for (var j in respObj) {
				tstr += formatResultRow(respObj[j]);
			}

			$('#listview tbody').html(tstr);

			Array.prototype.forEach.call(document.querySelectorAll('#listview tr td:nth-child(2)'), function(el) {
				Sortable.create(el, $.extend({
					'group': { 
						'name': IEMLApp.sortableGroup,
						'pull': 'clone',
						'put': false
					}
				}, IEMLApp.sortableOptions));
			});
		} else {
			$('#listview tbody').empty();
		}
		
		switch_to_list();
	};
	
	IEMLApp.receiveExpression = function (responseData) {
		IEMLApp.lastRetrievedData = responseData;
		
		fillForm(responseData);
	};

	IEMLApp.recieveVisualExpression = function(responseData) {
		fillEditor(responseData);

		switch_to_list();
		show_editor();
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
		$('.ieml-validation-result').bhide();

		$('.edit-buttons-wrap').bhide();
		
		$('#filter-results-wrap').bhide();
		$('#ieml-view-index').bhide();
		
		$('#record-view-container, .circuit-container').bhide();
		$('#user-view-container').bhide();
		$('#list-view-container').bhide();
		$('#login-view-container').bhide();

		$('#add-ieml-record').bshow();
	}
	
	function switch_to_view(view) {
		reset_views();

		$('#' + view + '-view-container').bshow();
	}
	
	function switch_to_record() {
		reset_views();
		
		if (IEMLApp.user) {
			$('.edit-buttons-wrap').bshow();
		} else {
			$('.edit-buttons-wrap').bhide();
		}
		
		$('#record-view-container, .circuit-container').bshow();
		$('#ieml-view-index').bshow();
	}
	
	function hide_editor() {
		$('.contentzone').removeClass('editor-in');
		$('.editor-drawer').bhide();
	}

	function show_editor() {
		$('.contentzone').addClass('editor-in');
		$('.editor-drawer').bshow();
	}

	function switch_to_list() {
		reset_views();

		if (IEMLApp.library == 1)
		{
			$('#filter-results-wrap').bshow();
		}

		$('#list-view-container').bshow();
	}
	
	function init_user_login(userObj) {
		IEMLApp.user = userObj;
		IEMLApp.submit({ 'a': 'getUserLibraries', 'lang': IEMLApp.lang });
		
		$('.login-btn-wrap').bhide();
		$('.logout-btn-wrap').bshow();
		
		if (IEMLApp.user.enumType == 'admin') {
			$('.ieml-view-users').bshow();
		} else {
			$('.ieml-view-users').bhide();
		}
		
		$('.user-display-name').html(userObj.strDisplayName);
	}
	
	function init_anon_user() {
		$('.login-btn-wrap').bshow();
		$('.logout-btn-wrap').bhide();
		$('#add-ieml-record').bhide();
		$('#ieml-view-users').bhide();
		$('.edit-buttons-wrap').bhide();
		
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

	function format_single_relation(info)
	{
		var ret = '';

		if (info.id)
		{
			ret += '<a href="/ajax.php?id=' + info.id + '&a=searchDictionary" data-exp="' + info.exp + '" data-id="' + info.id + '" class="editExp">';

			if (info.desc)
			{
				ret += info.desc + ' (' + info.exp[0] + ')';
			} else
			{
				ret += info.exp[0];
			}
		}
		else if (info.exp[0])
		{
			ret += '<a href="javascript:void(0);" class="createEmptyExp">' + info.exp[0];
		}
		else
		{
			return '';
		}

		ret += '</a>';
		return ret;
	}
	
	function format_relations(tables)
	{
		var contained_html = '';
		var containing_html = '';
		var concurrent_html = '';
		var complementary_html = '';
		var diagonal_html = '';
		var etymology_html = '';

		for (var m = 0; m < tables.length; m++)
		{
			var related_tables = tables[m];

			for (var n = 0; n < related_tables.length; n++)
			{
				var leftover_tables = related_tables[n];

				for (var o = 0; o < leftover_tables.length; o++)
				{
					var concerete_table = leftover_tables[o];
					var rel = concerete_table.relations;
		
					if (rel)
					{
						complementary_html = format_single_relation(rel.complementary);

						if (rel.contained && rel.contained.length > 0)
						{
							for (var i = 0; i < rel.contained.length; i++)
							{
								if (rel.contained[i].desc)
								{
									contained_html += '<li>' + format_single_relation(rel.contained[i]) + '</li>';
								}
								
								var concurrent_rel = rel.concurrent[rel.contained[i].exp[0]];
								if (concurrent_rel.length > 0) {
									concurrent_html += '<div class="concurring-relation col-md-6"><span class="concurring-relation-text"><strong>In relation to "' + format_single_relation(rel.contained[i]) + '"</strong></span><ul class="unstyled relation-list">';

									for (var j = 0; j < concurrent_rel.length; j++)
									{
										if (concurrent_rel[j].desc)
										{
											concurrent_html += '<li>' + format_single_relation(concurrent_rel[j]) + '</li>';
										}
									}
									concurrent_html += '</ul></div>';
								}
							}
						}
						
						if (rel.containing && rel.containing.length > 0)
						{
							for (var k = 0; k < rel.containing.length; k++)
							{
								if (rel.containing[k].desc)
								{
									containing_html += '<li>' + format_single_relation(rel.containing[k]) + '</li>';
								}
							}
						}
						
						if (rel.diagonal && rel.diagonal.length > 0)
						{
							for (var l = 0; l < rel.diagonal.length; l++)
							{
								if (rel.diagonal[l].desc)
								{
									diagonal_html += '<li>' + format_single_relation(rel.diagonal[l]) + '</li>';
								}
							}
						}
					}
				}
			}
		}
						
		if (complementary_html.length === 0)
		{
			complementary_html = 'None.';
		}
		complementary_html = '<p>' + complementary_html + '</p>';

		if (diagonal_html.length > 0)
		{
			diagonal_html = '<ul class="unstyled relation-list">' + diagonal_html + '</ul>';
		}
		else
		{
			diagonal_html = '<p>None.</p>';
		}

		if (contained_html.length > 0)
		{
			contained_html = '<ul class="unstyled relation-list">' + contained_html + '</ul>';
		}
		else
		{
			contained_html = '<p>None.</p>';
		}

		if (containing_html.length > 0)
		{
			containing_html = '<ul class="unstyled relation-list">' + containing_html + '</ul>';
		}
		else
		{
			containing_html = '<p>None.</p>';
		}
						
		if (concurrent_html.length > 0)
		{
			concurrent_html = '<div class="row">' + concurrent_html + '</div>';
		}
		else
		{
			concurrent_html = '<p>None.</p>';
		}
		
		return {
			'contained': contained_html,
			'containing': containing_html,
			'concurrent': concurrent_html,
			'complementary': complementary_html,
			'diagonal': diagonal_html
		};
	}
	
	function format_etymology(info)
	{
		var etym = info.etymology;
		var ret = '';
		
		for (var i = 0; i < etym.length; i++)
		{
			if (etym[i].id && etym[i].id.length > 0 &&
				etym[i].exp && etym[i].exp.length > 0 &&
				etym[i].desc && etym[i].desc.length > 0)
			{
				ret += '<li><a href="/ajax.php?id=' + etym[i].id + '&a=searchDictionary" data-exp="' + etym[i].exp + '" data-id="' + etym[i].id + '" class="editExp">' + etym[i].desc + ' (' + etym[i].exp + ')</a></li>';
			}
			else
			{
				ret += '<li><a href="javascript:void(0);" class="createEmptyExp">' + etym[i].exp + '</a></li>';
			}
		}
		
		return ret ? '<ul class="unstyled etymology">' + ret + '</ul>' : '';
	}

	function clearEditor()
	{
		$('.editor-proper').empty();
		$('.editor-example-input').val('');
		$('.editor-drawer [name="rel-id"]').val(null);

		$('.editor-short').attr('href', '').html('');
	}

	function fillEditor(info)
	{
		clearEditor();

		var plus_ = $('[data-script-val="+"]').eq(0);
		var times_ = $('[data-script-val="*"]').eq(0);
		var lparen_ = $('[data-script-val="("]').eq(0);
		var rparen_ = $('[data-script-val=")"]').eq(0);
		var lbracket_ = $('[data-script-val="["]').eq(0);
		var rbracket_ = $('[data-script-val="]"]').eq(0);

		var children = info.children;
		var $editor = $('.editor-proper');
		// var $link = $('.editor-short');

		// var short_url_domain = 'ieml.org';

		// $link.attr('href', '//' + short_url_domain + '/' + info.shortUrl);
		// $link.html(short_url_domain + '/' + info.shortUrl);

		$('.editor-drawer [name="rel-id"]').val(info.id);
		$('.editor-example-input').val(info.example);

		if (children)
		{
			var i, $inplace_el, child;
			for (i = 0; i < children.length; i++)
			{
				if (children[i].expression === '*')
				{
					$inplace_el = times_.clone();
				}
				else if (children[i].expression === '+')
				{
					$inplace_el = plus_.clone();
				}
				else
				{
					$inplace_el = $(formatDraggableScript(children[i]));
				}

				$editor.append($inplace_el);
			}
		}

		IEMLApp.onSort($('.editor-proper'));
	}
	
	function fillForm(info) {
		if ($('#ieml-desc-result-edit').hasClass('disabled')) {
			writeToRead();
		}
		
		$('#desc-result-id').val(info.id ? info.id : '');
		
		if (info.expression && info.expression.length > 0) {
			var details = getVerbLayer(info.expression);
			
			$('#ieml-result').html(info.expression);
		
			$('#ieml-result-details').html(details.gram + ' Layer ' + details.layer);
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
		
		if (info.tables)
		{
			var tables = info.tables;
			var str = '';
			var render_callback = function(el)
			{
				var out = '';
				
				if (el.id)
				{
					out +=
						'<div class="' + (el.enumEnabled == 'N' ? 'hidden ' : '') + 'cell_wrap' + '">' +
							'<a href="/ajax.php?id=' + el.id + '&a=searchDictionary" data-exp="' + el.expression + '" data-id="' + el.id + '" class="editExp">' +
								'<span class="cell_expression">' + el.expression + '</span>' +
								(null !== el.example ? '<span class="cell_example">' +
									formatDraggableScript(el) +
								'</span>' : '') +
							'</a>' +
						'</div>';
				}
				else
				{
					out += '<div><a href="javascript:void(0);" class="createEmptyExp">' + el.expression + '</a></div>';
				}
				
				return out;
			};

			var rel_info = format_relations(tables);
			
			$('#ieml-contained-wrap').bshow().html(rel_info.contained);
			$('#ieml-containing-wrap').bshow().html(rel_info.containing);
			$('#ieml-concurrent-wrap').bshow().html(rel_info.concurrent);
			$('#ieml-complementary-wrap').bshow().html(rel_info.complementary);
			$('#ieml-diagonal-wrap').bshow().html(rel_info.diagonal);
			
			for (var i = 0; i < tables.length; i++)
			{
				var related_tables = tables[i];

				for (var n = 0; n < related_tables.length; n++)
				{
					var leftover_tables = related_tables[n];

					for (var j = 0; j < leftover_tables.length; j++)
					{
						leftover_tables[j].table.ver_header_depth = leftover_tables[j].edit_vertical_head_length;
						leftover_tables[j].table.hor_header_depth = leftover_tables[j].edit_horizontal_head_length;
						leftover_tables[j].table.length = leftover_tables[j].length;
						leftover_tables[j].table.height = leftover_tables[j].height;
						leftover_tables[j].table.top = leftover_tables.top;
					}
					
					if (leftover_tables.length > 1)
					{
						str +='<table class="relation"><tbody>';
						
						for (var k = 0; k < leftover_tables.length; k++)
						{
							var concat_tables = leftover_tables[k];

							if (k > 0)
							{
								str +='<tr><td class="table-concat-seperator" colspan="' + (parseInt(concat_tables.table.length, 10) + parseInt(concat_tables.table.hor_header_depth, 10)) + '"></td></tr>';
							}
							
							str += ieml_render_table_body(concat_tables.table, render_callback);
						}
						
						str += '</tbody></table>';
					}
					else
					{
						str += ieml_render_table(leftover_tables[0].table, render_callback);
					}
					
					str += '<hr />';
				}
			}
			
			$('#ieml-table-span').html(str);

			Array.prototype.forEach.call(document.querySelectorAll('#ieml-table-span .cell_example'), function(el)
			{
				Sortable.create(el, $.extend({
					'group': {
						'name': IEMLApp.sortableGroup,
						'pull': 'clone',
						'put': false
					}
				}, IEMLApp.sortableOptions));
			});
		}
		else
		{
			$('#ieml-table-span').empty();
			$('#ieml-contained-wrap, #ieml-containing-wrap, #ieml-concurrent-wrap, #ieml-complementary-wrap').bhide();
		}
		
		$('#iemlEnumShowTable').prop('checked', info.enumShowEmpties == 'Y').trigger('change');
		
		$('#iemlEnumComplConcOff').prop('checked', info.iemlEnumComplConcOff == 'Y').trigger('change');
		if (info.iemlEnumComplConcOff == 'Y')
		{
			$('#ieml-complementary-section').bhide();
		}
		else
		{
			$('#ieml-complementary-section').bshow();
		}
		$('#iemlEnumSubstanceOff').prop('checked', info.iemlEnumSubstanceOff == 'Y').trigger('change');
		$('#iemlEnumAttributeOff').prop('checked', info.iemlEnumAttributeOff == 'Y').trigger('change');
		$('#iemlEnumModeOff').prop('checked', info.iemlEnumModeOff == 'Y').trigger('change');
		
		var etymology_html = format_etymology(info);
		$('#ieml-etymology-wrap').bshow().html(etymology_html || '<p>None.</p>');
		
		if (info.debug)
		{
			console.log('Debug from server: ', info.debug);
		}
		
		switch_to_record();
	}
	
	function formatResultRow(obj) {
		var relative_libraries = [];

		var current_lib_in_user_libs = IEMLApp.userLibraries ? IEMLApp.userLibraries.filter(function(el) { 
			return el.pkLibrary == IEMLApp.library;
		}).length > 0 : false;

		if (IEMLApp.user && IEMLApp.userLibraries) {
			for (var i = 0; i < IEMLApp.userLibraries.length; i++) {
				if (obj.fkLibrary.indexOf(IEMLApp.userLibraries[i].pkLibrary) < 0) {
					relative_libraries.push(IEMLApp.userLibraries[i]);
				}
			}
		}

		return '<tr data-key="' + (obj.enumCategory == 'Y' ? 'true' : 'false') + '" ' +
			'data-layer="' + (obj.intLayer >= 0 ? obj.intLayer : '-1') + '" ' +
			'data-result-id="' + obj.id + '" ' +
			'data-expression-type="' + obj.enumExpressionType + '">' +
			'<td>' + obj.expression + '</td>' +
			'<td>' + formatDraggableScript(obj) + '</td>' +
			'<td>' + (IEMLApp.user ?
				'<button type="button"' +
					'data-exp="' + obj.expression + '"' +
					'data-id="' + obj.id + '"' +
					'class="btn btn-default ' + (obj.enumExpressionType == 'relational' ? 'editRelExp' : 'editExp') + '"><span class="glyphicon glyphicon-pencil"></span></button>' +
				(obj.enumExpressionType == 'relational' ? '<button type="button"' +
					'data-id="' + obj.id + '"' +
					'class="btn btn-default duplicateExp"><span class="glyphicon glyphicon-duplicate"></span></button>' +
				'<button type="button"' +
					'data-id="' + obj.id + '"' +
					'class="btn btn-default delRelExp"><span class="glyphicon glyphicon-trash"></span></button>'
				: '') +
				(obj.enumExpressionType == 'basic' && IEMLApp.library !== 1 && current_lib_in_user_libs ?
					'<button type="button"' + 'data-id="' + obj.id + '"' +
					'class="btn btn-default removeExpFromList"><span class="glyphicon glyphicon-remove"></span></button>'
				: '') : '') +
			'</td>' +
		'</tr>';
	}

	function formatDraggableScript(obj) {
		return '<div class="draggable" ' +
			(obj.id ? 'data-script-id="' + obj.id + '" ' : '') +
			'data-script-val="' + obj.expression + '" data-is-script="true">' +
				(obj.example === null || typeof obj.example == "undefined" ?
					obj.expression :
					obj.example
				) +
			'</div>';
	}
	
	function formatUserRow(user) {
		return '<tr data-id="' + user.pkUser + '" data-name="' + user.strDisplayName + '">' +
			'<td>' + user.strEmail + '</td>' +
			'<td>' + user.enumType + '</td>' +
			'<td>' + LightDate.date('Y-m-d H:i:s', LightDate.date_timezone_adjust(user.tsDateCreated * 1000)) + '</td>' +
			'<td>' + 
				//<!--a href="/ajax.php?a=editUser&pkUser=' + user.pkUser + '" class="editUser btn btn-default">Edit</a-->' +
				'<button type="button" href="javascript:void(0)" class="delUser btn btn-default">Delete</button>' +
			'</td>' +
		'</tr>';
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
		var $input = $('<input type="text" class="' + classes + '" />');
		$input.val(text);
		sel.html($input);
	}

	function textToTextArea(sel, classes) {
		var text = sel.text();
		var $textarea = $('<textarea type="text" class="' + classes + '" ></textarea>');
		$textarea.val(text);
		sel.html($textarea);
	}
	
	function inputToText(sel) {
		sel.html(sel.children('input').val());
	}
	
	function readToWrite() {
		$('.non-edit-buttons').bshow();
		$('#ieml-desc-result-edit').bhide();
		textToInput($('#ieml-result'), 'input-large form-control');
		textToInput($('#ieml-ex-result'), 'input-xxlarge form-control');
		textToInput($('#ieml-desc-result'), 'input-xxlarge form-control');
		
		$('#ieml-result > .input-large').on('input', function() {
			var exp = $(this).val();

			if (exp.length > 0) {
				$('.ieml-validation-result').bshow();

				IEMLApp.submit({
					'a': 'validateExpression',
					'expression': exp
				});
			} else {
				$('.ieml-validation-result').bhide();
			}
		});

		$('#iemlEnumCategoryModal').removeAttr('disabled');
		$('.edit-only').bshow();
	}
	
	function writeToRead() {
		$('.non-edit-buttons').bhide();
		$('#ieml-desc-result-edit').bshow();
		inputToText($('#ieml-result'));
		inputToText($('#ieml-ex-result'));
		inputToText($('#ieml-desc-result'));

		$('.ieml-validation-result').bhide();

		$('#iemlEnumCategoryModal').attr('disabled', 'disabled');
		$('.edit-only').bhide();
	}

	function showLoadingIndicator()
	{
		$('.globalLoading').bshow();
	}

	function hideLoadingIndicator()
	{
		$('.globalLoading').bhide();
	}
	
	$(function()
	{
		$(window).on('popstate', IEMLApp.popstateCallback);
		
		$(document).on('submit', '#search-form', function()
		{
			var form_data = form_arr_to_map($(this).serializeArray());

			if ($('[name="filter-results"][value="keys"]').is(':checked'))
			{
				form_data.keys = 'keys';
			}

			IEMLApp.library = form_data.library || IEMLApp.library;

			IEMLApp.submit(form_data);
			
			return false;
		}).on('change', '#search-layer-select, #search-class-select, #search-library-select', function()
		{
			$('#search-form').trigger('submit');
		}).on('change', '#iemlEnumShowTable', function()
		{
			var info = IEMLApp.lastRetrievedData;
			
			if($('#iemlEnumShowTable').is(':checked'))
			{
				$('.nonExistentCell').bshow();
				$('table.relation tr.empty_head_tr, table.relation td.empty_head_tr_td').bshow();
				if (info.edit_vertical_head_length <= 0 && info.edit_horizontal_head_length <= 0)
				{
					$('table.relation td.empty_cell').bhide();
				}
				else
				{
					$('table.relation td.empty_cell').bshow()
						.attr('rowspan', parseInt(info.edit_vertical_head_length, 10) + 1).attr('colspan', info.edit_horizontal_head_length);
				}
			}
			else
			{
				$('.nonExistentCell').bhide();
				$('table.relation tr.empty_head_tr, table.relation td.empty_head_tr_td').bhide();

				if (info.render_vertical_head_length <= 0 && info.render_horizontal_head_length <= 0)
				{
					$('table.relation td.empty_cell').bhide();
				}
				else
				{
					$('table.relation td.empty_cell').bshow()
						.attr('rowspan', parseInt(info.render_vertical_head_length, 10) + 1).attr('colspan', info.render_horizontal_head_length);
				}
			}
		}).on('click', '.duplicateExp', function() {
			IEMLApp.submit({
				'a': 'duplicate',
				'id': $(this).data('id'),
				'lang': IEMLApp.lang,
				'library': IEMLApp.userLibraries[0].pkLibrary
			});

			return false;
		}).on('click', '.editExp', function() {
			var $this = $(this);
			
			IEMLApp.submit({
				'a': 'expression',
				'lang': IEMLApp.lang,
				'library': IEMLApp.library,
				'exp': $this.data('exp'),
				'id': $this.data('id')
			});
			
			return false;
		}).on('click', '.editRelExp', function() {
			var $this = $(this);
			
			IEMLApp.submit({
				'a': 'relationalExpression',
				'id': $this.data('id'),
				'lang': IEMLApp.lang
			});
			
			return false;
		}).on('change', '#search-lang-select', function() {
			writeToRead();
			IEMLApp.switch_lang($(this).val(), window.History.getState().data);
			
			return false;
		}).on('change', '#filter-results-wrap [name="filter-results"]', function()
		{
			$('#search-form').trigger('submit');
		}).on('click', '#add-ieml-record', function()
		{
			IEMLApp.lastRetrievedData = { 'expression': '', 'example': '', 'enumCategory': 'N', 'enumShowEmpties': 'N', 'etymology': '' };
			fillForm(IEMLApp.lastRetrievedData);
			
			readToWrite();
			
			$('#desc-result-id').val('');

			$('#add-ieml-record').bhide();
			
			return false;
		}).on('click', '#add-ieml-usl', function() {
			show_editor();

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
			reqVars.library = $('#search-library-select').val();
			
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
				showConfirmDialog('Are you sure you want to delete ' + $('#ieml-result input').eq(0).val() + '?', function() {
					IEMLApp.submit({'a':'deleteDictionary', 'id':$('#desc-result-id').val()});
				});
			}
		
			return false;
		}).on('change', '.enable_check', function() {
			var thisID = $(this).data('ref-id'), thisval = $(this).is(':checked') ? 'Y' : 'N';
			if (thisval == 'N')
				$(this).siblings('.cell_wrap').bhide();
			else
				$(this).siblings('.cell_wrap').bshow();
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
			$('#ieml-ex-result input').val('');
			
			$('.relation-sel-cell').removeClass('relation-sel-cell');
			$this.parents('div').eq(0).addClass('relation-sel-cell'); //TODO highlight line properly
			
			return false;
		}).on('click', '#ieml-view-users', function() {
			IEMLApp.submit({'a': 'viewUsers', 'deleted': 'no'});
			
			return false;
		}).on('click', '#ieml-view-index', function() {
			$('#filter-results-keys').click();
			$('#search-form').submit();

			return false;
		}).on('click', '#addUser', function() {
			$('#iemlAddUserModal').modal('show');
			
			return false;
		}).on('click', '#iemlAddUserModalAdd', function() {
			var formData = form_arr_to_map($('#iemlUser').serializeArray());
			
			$('#iemlAddUserModal').modal('hide');
			
			IEMLApp.submit({
				'a' : formData.a,
				'username' : formData.addUserModalUsername,
				'displayname' : formData.addUserModalDisplayName,
				'pass' : formData.addUserModalPass,
				'enumType' : formData.addUserModalType
			});
			
			return false;
		}).on('click', '.delUser', function() {
			var $this = $(this);
			var $row = $this.parents('tr').eq(0);

			showConfirmDialog('Are you wish to delete ' + $row.data('name') + '?',
				function() {
					IEMLApp.submit({ 'a': 'delUser', 'pkUser': $row.data('id') });
				}
			);
			
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
		}).on('click', '.addExpressionToLibrary', function() {
			IEMLApp.submit({
				'a': 'addExpressionToLibrary',
				'library': $(this).data('lib-id'),
				'id': $(this).data('exp-id')
			});
		}).on('dragenter', '.editor-garbage', function() {
			$(this).addClass('dragenter');
		}).on('dragleave', '.editor-garbage', function() {
			$(this).removeClass('dragenter');
		}).on('drop', '.editor-garbage', function(evt) {
			$(this).removeClass('dragenter');
			$(IEMLApp.draggedObject).conditionalDelete(2, function() {
				IEMLApp.onSort($('.editor-proper'));
			});
		}).on('click', '.editor-save', function() {
			var $editor = $('.editor-proper');
			var existing_id = $('.editor-drawer input[name="rel-id"]').val();
			var reqVars = {
				'editor_array': IEMLApp.construct_editor_array($editor.children()),
				'lang': $('#search-lang-select').val(),
				'library': IEMLApp.userLibraries[0].pkLibrary,
				'example': $('.editor-example-input').val()
			};

			if (existing_id) {
				reqVars.a = 'editVisualExpression';
				reqVars.id = existing_id;
			} else {
				reqVars.a = 'newVisualExpression';
			}

			IEMLApp.submit(reqVars);
		}).on('click', '.editor-clear', function() {
			hide_editor();

			clearEditor();
		}).on('click', '.delRelExp', function() {
			var $this = $(this);

			showConfirmDialog('Are you sure?', function() {
				IEMLApp.submit({ 'a': 'deleteVisualExpression', 'id': $this.data('id') });
			});
		}).on('click', '.removeExpFromList', function() {
			var $this = $(this);

			showConfirmDialog('Are you sure?', function() {
				IEMLApp.submit({
					'a': 'deleteExpressionFromLibrary',
					'library': IEMLApp.library,
					'id': $this.data('id')
				});
			});
		});

		//One-time setup
		var spinner = new Spinner({
			length: 20, // The length of each line
			width: 10, // The line thickness
			radius: 30, // The radius of the inner circle
			color: '#FFF',
		}).spin($('.globalLoading').get(0));

		if (window.location.pathname.length == 1) {
			$('#filter-results-keys').click();
			$('#search-form').submit();
		}

		Sortable.create(document.querySelector('.editor-proper'), $.extend({
			'group': IEMLApp.sortableGroup,
			'onSort': function(evt) {
				IEMLApp.onSort($(evt.target));
			}
		}, IEMLApp.sortableOptions));

		Array.prototype.forEach.call(document.querySelectorAll('.editor-head .draggable-list'), function(el) {
			Sortable.create(el, $.extend({
				'group': { 
					'name': IEMLApp.sortableGroup,
					'pull': 'clone',
					'put': false
				}
			}, IEMLApp.sortableOptions));
		});

		IEMLApp.user = _SESSION.user;
		if (IEMLApp.user)
		{
			init_user_login(IEMLApp.user);
		}
		else
		{
			init_anon_user();
		}
		
		IEMLApp.init_from_url(window.location);
	});
})(window, jQuery);
