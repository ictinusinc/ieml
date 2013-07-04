<!DOCTYPE html>
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
        <!--style><?php //echo file_get_contents(APPROOT.'/includes/css/bootstrap-2.0.1.min.css'); ?></style-->
        <link rel="stylesheet" type="text/css" href="<?php echo WEBAPPROOT.'/includes/css/bootstrap-2.0.1.min.css'; ?>">
        <!-- style.css -->
        <!--style><?php //echo file_get_contents(APPROOT.'/includes/css/style.css'); ?></style-->
        <link rel="stylesheet" type="text/css" href="<?php echo WEBAPPROOT.'/includes/css/style.css'; ?>">
    </head>
    <body>
    	<div id="ajax"></div>
        <div class="navbar navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                	<div class="row">
	                    <div class="pull-right">
	                    	<div class="login-btn-wrap">
	                            <a class="btn login-btn" href="/login/" data-lang-switch="login"><?php echo trans_phrase('login', $lang); ?></a>
	                    	</div>
	                    	<div class="logout-btn-wrap hide">
	                        	<span class="color-white user-display-name"><?php echo $_SESSION['strDisplayName']; ?></span>
	                            <a class="btn logout-btn" href="?a=logout" data-lang-switch="logout"><?php echo trans_phrase('logout', $lang); ?></a>
	                    	</div>
	                    </div>
                	</div>
                    <div class="row">
	                    <form id="search-form" class="span12 navbar-search" action="/api/" method="post">
	                    	<a class="brand logo" href="/">&nbsp;</a>
	                    	<input type="hidden" name="a" value="searchDictionary" />
	                    	<div class="pull-left">
	                    		<select id="search-spec-select" name="lexicon" class="span2"><option value="BasicLexicon">Basic Lexicon</option></select>
	                    		<select id="search-lang-select" name="lang" class="span2">
	                    			<option <?php if ($_REQUEST['lang'] == 'en') echo 'selected="selected"'; ?>value="en">English</option>
	                    			<option <?php if ($_REQUEST['lang'] == 'fr') echo 'selected="selected"'; ?>value="fr">Français</option>
	                    		</select>
	                    	</div>
	                    	<div class="pull-right"><input type="text" class="search-query span2" id="search" name="search" data-lang-switch="search" data-lang-switch-attr="placeholder" placeholder="<?php echo trans_phrase('search', $lang); ?>" /></div>
	                    </form>
                    </div>
                </div>
            </div>
            <div class="subtop-bar">
	            <div class="container">
	            	<div class="row">
	            		<div class="span12">
						    <div class="pull-left" id="add-ieml-record-wrap"><a href="javascript:void(0);" class="btn" id="add-ieml-record"><span class="icon-plus">&nbsp;</span><span data-lang-switch="add_record"><?php echo trans_phrase('add_record', $lang); ?></span></a></div>
						    <div class="pull-left" id="ieml-view-users-wrap"><a href="javascript:void(0);" class="btn" id="ieml-view-users" data-lang-switch="view_users"><?php echo trans_phrase('view_users', $lang); ?></a></div>
						    <!--div class="pull-left"><div id="back-to-list-view" class="btn hide"><?php echo trans_phrase('back_to_res', $lang); ?></div></div-->
						    
						    <div class="pull-right">
								<div class="edit-buttons-wrap hide">
									<span class="non-edit-buttons hide"><a class="btn" id="ieml-desc-result-save" data-lang-switch="save"><?php echo trans_phrase('save', $lang); ?></a><a class="btn" id="ieml-desc-result-cancel"><?php echo trans_phrase('cancel', $lang); ?></a><a class="btn" id="ieml-desc-result-delete" data-lang-switch="delete"><?php echo trans_phrase('delete', $lang); ?></a></span>
									<a class="btn" id="ieml-desc-result-edit" data-lang-switch="edit"><?php echo trans_phrase('edit', $lang); ?></a>
								</div>
								
								<div class="btn-group" id="filter-results-wrap">
									<button class="btn" id="filter-results-button" data-lang-switch="filter_show_all"><?php echo trans_phrase('filter_show_all', $lang); ?></button>
									<button class="btn" id="filter-results-keys" data-lang-switch="filter_keys_only"><?php echo trans_phrase('filter_keys_only', $lang); ?></button>
								</div>
						    </div>
						</div>
				    </div>
			    </div>
            </div>
        </div>
    
    <div class="contentzone">
