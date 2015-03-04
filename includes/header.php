<?php
header('Content-Type: text/html; charset=utf-8;');

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>IEML Dictionary</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">
	<script type="text/javascript" src="//use.typekit.net/oga8trv.js"></script>
	<script type="text/javascript">try{Typekit.load();}catch(e){}</script>
	<!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	
	<!-- Stylesheets -->
	<!-- bootstrap-2.0.1.css -->
	<link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
	<!-- style.css -->
	<link rel="stylesheet" type="text/css" href="//<?php echo WEBAPPROOT.'/includes/css/style.css'; ?>" />
</head>
<body>
	<div id="ajax"></div>
	<div class="ieml-top-nav" role="navigation">
		<div class="container main-navigation">
			<div class="row">
				<div class="col-sm-12">
					<div class="pull-right">
						<div class="login-btn-wrap">
							<button type="button" class="btn btn-default login-btn" data-lang-switch="login"><?php echo trans_phrase('login', $lang); ?></button>
						</div>
						<div class="logout-btn-wrap hidden">
							<span class="color-white user-display-name"><?php echo isset($_SESSION['user']['strDisplayName']) ? $_SESSION['user']['strDisplayName'] : ''; ?></span>
							<button type="button" class="btn btn-default logout-btn" data-lang-switch="logout"><?php echo trans_phrase('logout', $lang); ?></button>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<form id="search-form" action="/api/" method="post" role="search">
					<input type="hidden" name="keys" value="" />
					<input type="hidden" name="a" value="searchDictionary" />
					<div class="col-sm-3">
						<div class="row">
							<div class="col-sm-6">
								<select id="search-library-select" name="library" class="form-control input-sm">
									<option value="1"></option>
								</select>
							</div>
							<div class="col-sm-6">
								<select id="search-lang-select" name="lang" class="form-control input-sm">
									<option <?php if (array_key_exists('lang', $_REQUEST) && $_REQUEST['lang'] == 'en') echo 'selected="selected"'; ?>value="EN">English</option>
									<option <?php if (array_key_exists('lang', $_REQUEST) && $_REQUEST['lang'] == 'fr') echo 'selected="selected"'; ?>value="FR">Français</option>
								</select>
							</div>
						</div>
					</div>
					<div class="col-sm-offset-3 col-sm-6">
						<div class="row">
							<div class="col-sm-3">
								<select id="search-layer-select" name="layer" class="form-control input-sm">
									<option value="">All Layers</option>
									<option value="0">0</option>
									<option value="1">1</option>
									<option value="2">2</option>
									<option value="3">3</option>
									<option value="4">4</option>
									<option value="5">5</option>
									<option value="6">6</option>
								</select>
							</div>
							<div class="col-sm-3">
								<select id="search-class-select" name="class" class="form-control input-sm">
									<option value="">All Classes</option>
									<option value="verb">Verb</option>
									<option value="noun">Noun</option>
									<option value="auxiliary">Auxiliary</option>
								</select>
							</div>
							<div class="col-sm-6">
								<input type="text" class="search-query form-control input-sm" id="search" name="search" data-lang-switch="search" data-lang-switch-attr="placeholder" placeholder="<?= trans_phrase('search', $lang) ?>" />
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
		<div class="subtop-bar">
			<div class="container">
				<div class="row">
					<div class="col-sm-12">
						
						<div class="pull-left">
							<div class="edit-buttons-wrap hidden">
								<button type="button" class="btn btn-default" id="ieml-desc-result-edit" data-lang-switch="edit"><?php echo trans_phrase('edit', $lang); ?></button>
								<span class="non-edit-buttons hidden"><button type="button" class="btn btn-default" id="ieml-desc-result-save" data-lang-switch="save"><?php echo trans_phrase('save', $lang); ?></button><button type="button" class="btn btn-default" id="ieml-desc-result-cancel"><?php echo trans_phrase('cancel', $lang); ?></button><button type="button" class="btn btn-default" id="ieml-desc-result-delete" data-lang-switch="delete"><?php echo trans_phrase('delete', $lang); ?></button></span>
							</div>
							
							<div class="btn-group" data-toggle="buttons" id="filter-results-wrap">

								<label class="btn btn-default">
									<input type="radio" id="filter-results-button" name="filter-results" autocomplete="off" value="" checked />
									<span data-lang-switch="filter_show_all">
										<?php echo trans_phrase('filter_show_all', $lang); ?>
									</span>
								</label>
								<label class="btn btn-default">
									<input type="radio" id="filter-results-keys" name="filter-results" autocomplete="off" value="keys" />
									<span data-lang-switch="filter_keys_only">
										<?php echo trans_phrase('filter_keys_only', $lang); ?>
									</span>
								</label>
							</div>
						</div>

						<div class="pull-right" id="add-ieml-record-wrap"><button type="button" class="btn btn-default" id="add-ieml-record">
							<span class="glyphicon glyphicon-plus"></span>
							<span data-lang-switch="add_record"><?= trans_phrase('add_record', $lang) ?></span>
						</button></div>
						<div class="pull-right" id="ieml-view-users-wrap"><button type="button" class="btn btn-default" id="ieml-view-users" data-lang-switch="view_users"><?php echo trans_phrase('view_users', $lang); ?></button></div>

					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="contentzone">
