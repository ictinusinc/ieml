<?php

require_once('includes/config.php');
require_once(APPROOT.'/includes/LANGFILE.php');
require_once(APPROOT.'/includes/functions.php');

//smart_session($_REQUEST);
session_start();

api_log(api_message());

require_once(APPROOT.'/includes/header.php');

?>
        <div id="user-view-container" class="hide">
            <div class="row">
                <div class="span9"><h3><?php echo trans_phrase('users', $lang); ?></h3></div>
                <div class="span3"><a href="#" id="addUser" class="btn pull-right"><?php echo trans_phrase('add_user', $lang); ?></a></div>
            </div>
        	<table id="userlist" class="table table table-striped table-bordered"><thead><tr><th><?php echo trans_phrase('user_tab_user_col', $lang); ?></th><th><?php echo trans_phrase('user_tab_type_col', $lang); ?></th><th><?php echo trans_phrase('user_tab_created_col', $lang); ?></th><th>&nbsp;</th></tr></thead><tbody></tbody></table>
        </div>
        
        <div id="list-view-container" class="hide">
            <div class="row">
                <div class="span12"><h3><?php echo trans_phrase('results', $lang); ?></h3></div>
            </div>
        	<table id="listview" class="table table table-striped table-bordered">
        		<thead><tr><th><?php echo trans_phrase('list_tab_exp_col', $lang); ?></th><th><?php echo trans_phrase('list_tab_descriptor_col', $lang); ?></th><th>&nbsp;</th></tr></thead>
        		<tbody></tbody>
        	</table>
        </div>
        
        <div id="record-view-container" class="hide">
            <form id="iemlRecord" method="POST" action="/api/">
                <input type="hidden" name="id" id="desc-result-id" value="" />
                <div class="row">
	            	<div class="span12">
	            		<div class="pull-left"><span class="pull-left" id="ieml-result-details"></span><span class="pull-left" id="ieml-result"></span></div>
	            		<label class="checkbox pull-right" for="iemlEnumCategoryModal"><input type="checkbox" disabled="disabled" class="checkbox" name="iemlEnumCategoryModal" id="iemlEnumCategoryModal" value="Y" /> <?php echo trans_phrase('key', $lang); ?></label>
	            	</div>
                </div>
                <div class="row">
	            	<div class="span6" id="ieml-ex-wrap">
	            		<span class="example-top-tag"><?php echo trans_phrase('example', $lang); ?></span>
	            		<h3 id="ieml-ex-result"></h3>
	            	</div>
	            	<div class="span6" id="ieml-desc-wrap">
	            		<span class="desc-top-tag"><?php echo trans_phrase('descriptor', $lang); ?></span>
	            		<div id="ieml-desc-result"></div>
	            	</div>
                </div>
                <div class="row">
	            	<div class="span12">
						<span><?php echo trans_phrase('paradigmatic_curcuits', $lang); ?></span>
		                <div class="hide edit-only">
	            			<label class="checkbox" id="iemlEnumComplConcOffWrap" for="iemlEnumComplConcOff"><input type="checkbox" class="checkbox" name="iemlEnumComplConcOff" id="iemlEnumComplConcOff" value="Y" /> <?php echo trans_phrase('turn_off_comp_conc', $lang); ?></label>
	            			<div class="row">
		            			<div class="span12">
		            				<p><?php echo trans_phrase('disable', $lang); ?></p>
			            			<div class="span2">
			            				<label class="checkbox" id="iemlEnumSubstanceOffWrap" for="iemlEnumSubstanceOff"><input type="checkbox" class="checkbox" name="iemlEnumSubstanceOff" id="iemlEnumSubstanceOff" value="Y" /> <?php echo trans_phrase('substance', $lang); ?></label>
			            			</div>
			            			<div class="span2">
			            				<label class="checkbox" id="iemlEnumAttributeOffWrap" for="iemlEnumAttributeOff"><input type="checkbox" class="checkbox" name="iemlEnumAttributeOff" id="iemlEnumAttributeOff" value="Y" /> <?php echo trans_phrase('attribute', $lang); ?></label>
			            			</div>
			            			<div class="span2">
			            				<label class="checkbox" id="iemlEnumModeOffWrap" for="iemlEnumModeOff"><input type="checkbox" class="checkbox" name="iemlEnumModeOff" id="iemlEnumModeOff" value="Y" /> <?php echo trans_phrase('mode', $lang); ?></label>
			            			</div>
		            			</div>
	            			</div>
		                </div>
	                	<ul class="nav nav-tabs">
							<li class="active"><a href="#table" data-toggle="tab"><?php echo trans_phrase('appears_in_table', $lang); ?></a></li>
							<li><a href="#relations" data-toggle="tab"><?php echo trans_phrase('table_relations', $lang); ?></a></li>
							<li><a href="#graph" data-toggle="tab"><?php echo trans_phrase('table_graph', $lang); ?></a></li>
						</ul>
	            	</div>
	            	<div class="tab-content span12">
		                <div class="tab-pane active" id="table">
		                	<div id="ieml-table-info-wrap">
			                	<input type="hidden" id="iemlTableID" name="iemlTableID" />
				                <div class="hide edit-only">
			            			<label class="checkbox" id="iemlEnumShowTableWrap" for="iemlEnumShowTable"><input type="checkbox" class="checkbox" name="iemlEnumShowTable" id="iemlEnumShowTable" value="Y" /> <?php echo trans_phrase('show_empty_cells', $lang); ?></label>
				                </div>
				                <div id="ieml-table-span"></div>
		                	</div>
		                </div>
		                
						<div class="tab-pane" id="relations">
							<div id="ieml-relations-wrap">
								<h3 class="heading_underline"><?php echo trans_phrase('taxonomic', $lang); ?></h3>
								<div class="row">
									<div class="span6">
										<span class=""><strong><?php echo trans_phrase('contained_by', $lang); ?></strong></span>
										<div id="ieml-contained-wrap"></div>
									</div>
									<div class="span6">
										<span class=""><strong><?php echo trans_phrase('containing', $lang); ?></strong></span>
										<div id="ieml-containing-wrap"></div>
									</div>
								</div>
								<h3 class="heading_underline"><?php echo trans_phrase('concurring_concepts', $lang); ?></h3>
								<div id="ieml-concurrent-wrap"></div>
								<div id="ieml-complementary-section">
									<h3 class="heading_underline"><?php echo trans_phrase('complementary_concepts', $lang); ?></h3>
									<div id="ieml-complementary-wrap"></div>
								</div>
								<h3 class="heading_underline"><?php echo trans_phrase('etymology', $lang); ?></h3>
								<div id="ieml-etymology-wrap"></div>
							</div>
						</div>
						<div class="tab-pane" id="graph">...</div>
					</div>
                </div>
            </form>
        </div>
        
        <div id="login-view-container" class="hide">
			<form id="formLogin" method="POST" action="/api/">
				<input type="hidden" name="a" value="login" />
				<label for="loginEmail"><?php echo trans_phrase('user', $lang); ?>:</label>
				<input type="text" name="loginEmail" id="loginEmail" /><br />
				<label for="loginPassword"><?php echo trans_phrase('password', $lang); ?>:</label>
				<input type="password" name="loginPassword" id="loginPassword" /><br />
				<input class="btn" type="submit" name="submit" value="Login" />
			</form>
        </div>
        
        <div class="modal hide" id="iemlConfirmModal">
        	<div class="modal-header"><a class="close" data-dismiss="modal" id="iemlConfirmXModal">x</a><h3>Add Record</h3></div>
        	<div class="modal-body"></div>
        	<div class="modal-footer">
        	    <a id="iemlConfirmYesModal" class="btn" href="#"><?php echo trans_phrase('yes', $lang); ?></a><a id="iemlConfirmCancelModal" data-dismiss="modal" class="btn" href="#"><?php echo trans_phrase('cancel', $lang); ?></a>
        	</div>
        </div>
        
        <div class="modal hide" id="iemlAddUserModal">
        	<div class="modal-header"><a class="close" data-dismiss="modal" id="iemlConfirmXModal">x</a><h3><?php echo trans_phrase('add_user', $lang); ?></h3></div>
        	<div class="modal-body">
            	<form class="form-horizontal" id="iemlUser" method="POST" action="/api/">
                	<input type="hidden" name="a" value="addUser" />
                	
                	<div class="control-group">
                        <label class="control-label" for="addUserModalUsername"><?php echo trans_phrase('user', $lang); ?></label>
                    	<div class="controls">
                        	<input type="text" id="addUserModalUsername" name="addUserModalUsername" placeholder="username" />
                    	</div>
                	</div>
                	<div class="control-group">
                        <label class="control-label" for="addUserModalType"><?php echo trans_phrase('user_tab_type_col', $lang); ?></label>
                    	<div class="controls">
                        	    <select id="addUserModalType" name="addUserModalType">
                        	       <option value="user">User</option>
                        	       <option value="admin">Admin</option>
                        	    </select>
                    	</div>
                	</div>
                	<div class="control-group">
                        <label class="control-label" for="addUserModalUsername"><?php echo trans_phrase('password', $lang); ?></label>
                    	<div class="controls">
                            <input type="password" id="addUserModalPass" name="addUserModalPass" placeholder="password" />
                    	</div>
                	</div>
                	<div class="control-group">
                        <label class="control-label" for="addUserModalUsername"><?php echo trans_phrase('conf_pass', $lang); ?></label>
                    	<div class="controls">
                    	    <input type="password" id="addUserModalConfPass" name="addUserModalConfPass" placeholder="confirm password" />
                    	</div>
                	</div>
            	</form>
        	</div>
        	<div class="modal-footer">
        	    <a id="iemlAddUserModalAdd" class="btn btn-primary" href="#"><?php echo trans_phrase('add', $lang); ?></a><a data-dismiss="modal" class="btn" href="javascript:void(0);"><?php echo trans_phrase('cancel', $lang); ?></a>
        	</div>
        </div>
        
    	<div id="confirmCancelModal" class="modal hide" data-backdrop="static">
    		<div class="modal-body no-bot-margin"><a class="close" data-dismiss="modal" id="workPrintModalX">x</a><span id="confirmCancelModalText"></span></div>
    		<div class="modal-footer">
    			<a id="confirmCancelModalYes" class="btn btn-primary" href="#" data-dismiss="modal"><?php echo trans_phrase('yes', $lang); ?></a><a class="btn" data-dismiss="modal" href="#"><?php echo trans_phrase('no', $lang); ?></a>
    		</div>
    	</div><!--/confirmCancelModal-->
    </div>
</div>
<?php

require_once(APPROOT.'/includes/footer.php');

//set_session_data($_SESSION['auth_token'], $_SESSION);

Conn::closeStaticHandle();

?>
