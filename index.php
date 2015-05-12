<?php

require_once('includes/config.php');
require_once(APPROOT.'/includes/LANGFILE.php');
require_once(APPROOT.'/includes/functions.php');

//smart_session($_REQUEST);
session_start();

api_log(api_message());

require_once(APPROOT.'/includes/header.php');

?>
	<div class="container user-padding">
		<div id="user-view-container" class="hidden">
			<div class="row">
				<div class="col-md-9">
					<h3 data-lang-switch="users"><?php echo trans_phrase('users', $lang); ?></h3>
				</div>

				<div class="col-md-3">
					<button type="button" id="addUser" class="btn btn-default pull-right">
						<span class="glyphicon glyphicon-plus"></span>
						<span data-lang-switch="add_user"><?= trans_phrase('add_user', $lang) ?></span>
					</button>
				</div>
			</div>

			<table id="userlist" class="table table table-striped table-bordered">
				<thead>
					<tr>
						<th data-lang-switch="user_tab_user_col"><?php echo trans_phrase('user_tab_user_col', $lang); ?></th>
						<th data-lang-switch="user_tab_type_col"><?php echo trans_phrase('user_tab_type_col', $lang); ?></th>
						<th data-lang-switch="user_tab_created_col"><?php echo trans_phrase('user_tab_created_col', $lang); ?></th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>

		<div id="list-view-container" class="hidden">
			<div class="list-view-wrap">
					<table id="listview" class="table table-striped table-bordered table-condensed">
						<thead>
							<tr>
								<th data-lang-switch="list_tab_exp_col"><?= trans_phrase('list_tab_exp_col', $lang); ?></th>

								<th data-lang-switch="list_tab_example_col"><?= trans_phrase('list_tab_example_col', $lang); ?></th>

								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
			</div>
		</div>

		<div id="record-view-container" class="hidden">
			<form id="iemlRecord" method="post" action="/api/">
				<input type="hidden" name="id" id="desc-result-id" value="">

				<div class="row">
					<div class="col-md-2">
						<span class="pull-left" id="ieml-result-details"></span>
					</div>
					<div class="col-md-3">
						<span class="pull-left" id="ieml-result"></span>
					</div>
					<div class="col-md-3">
						<span class="pull-left ieml-validation-result hidden">
							<div class="result-success-icon hidden"><span class="glyphicon glyphicon-ok"></span></div>
							<div class="result-error-icon hidden"><span class="glyphicon glyphicon-remove">&nbsp;</span></div>
						</span>
					</div>
					<div class="col-md-3 pull-right">
						<label class="checkbox pull-right" for="iemlEnumCategoryModal">
							<input type="checkbox" disabled="disabled" class="checkbox" name="iemlEnumCategoryModal" id="iemlEnumCategoryModal" value="Y"><?php echo trans_phrase('key', $lang); ?>
						</label>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<span class="pull-left ieml-validation-result hidden">
							<span class="result-success"></span>
							<span class="result-error"></span>
						</span>
					</div>
				</div>

				<div class="row">
					<div class="col-md-6" id="ieml-ex-wrap">
						<span class="example-top-tag" data-lang-switch="example"><?php echo trans_phrase('example', $lang); ?></span>

						<h3 id="ieml-ex-result"></h3>
					</div>

					<div class="col-md-6" id="ieml-desc-wrap">
						<span class="desc-top-tag" data-lang-switch="descriptor"><?php echo trans_phrase('descriptor', $lang); ?></span>

						<h3 id="ieml-desc-result"></h3>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<span data-lang-switch="paradigmatic_curcuits"><?php echo trans_phrase('paradigmatic_curcuits', $lang); ?></span> <div class="hidden edit-only">
						<label class="checkbox" id="iemlEnumComplConcOffWrap" for="iemlEnumComplConcOff"><input type="checkbox" class="checkbox" name="iemlEnumComplConcOff" id="iemlEnumComplConcOff" value="Y" /><span data-lang-switch="turn_off_comp_conc"><?php echo trans_phrase('turn_off_comp_conc', $lang); ?></span></label>
						
						<div class="row">
							<div class="col-md-4">
								<p data-lang-switch="disable"><?php echo trans_phrase('disable', $lang); ?></p>
							</div>
						</div>
						
						<div class="row">
							<div class="col-md-12">
								<div class="col-md-2">
									<label class="checkbox" id="iemlEnumSubstanceOffWrap" for="iemlEnumSubstanceOff"><input type="checkbox" class="checkbox" name="iemlEnumSubstanceOff" id="iemlEnumSubstanceOff" value="Y" /><span data-lang-switch="substance"><?php echo trans_phrase('substance', $lang); ?></span></label>
								</div>
								<div class="col-md-2">
									<label class="checkbox" id="iemlEnumAttributeOffWrap" for="iemlEnumAttributeOff"><input type="checkbox" class="checkbox" name="iemlEnumAttributeOff" id="iemlEnumAttributeOff" value="Y" /><span data-lang-switch="attribute"><?php echo trans_phrase('attribute', $lang); ?></span></label>
								</div>
								<div class="col-md-2">
									<label class="checkbox" id="iemlEnumModeOffWrap" for="iemlEnumModeOff"><input type="checkbox" class="checkbox" name="iemlEnumModeOff" id="iemlEnumModeOff" value="Y" /><span data-lang-switch="mode"><?php echo trans_phrase('mode', $lang); ?></span></label>
								</div>
							</div>
						</div>
						
						<div class="row">
							<div class="col-md-4">
								<div class="hidden edit-only">
									<label class="checkbox" id="iemlEnumShowTableWrap" for="iemlEnumShowTable"><input type="checkbox" class="checkbox" name="iemlEnumShowTable" id="iemlEnumShowTable" value="Y"><span data-lang-switch="users"><?php echo trans_phrase('show_empty_cells', $lang); ?></span></label>
								</div>
							</div>
						</div>
					</div>

						<ul class="nav nav-tabs">
							<li class="active"><a href="#table" data-toggle="tab" data-lang-switch="appears_in_table"><?php echo trans_phrase('appears_in_table', $lang); ?></a></li>

							<li><a href="#relations" data-toggle="tab" data-lang-switch="table_relations"><?php echo trans_phrase('table_relations', $lang); ?></a></li>

							<li><a href="#graph" data-toggle="tab" data-lang-switch="table_graph"><?php echo trans_phrase('table_graph', $lang); ?></a></li>
						</ul>
					</div>
				</div>
			</form>
		</div>
	
		<div id="login-view-container" class="hidden">
			<div class="row">
				<div class="col-md-4">
					<form id="formLogin" role="form" class="form-horizontal" method="POST" action="/api/">
						<input type="hidden" name="a" value="login" />
						<div class="form-group">
							<label for="loginEmail" class="col-sm-4 control-label" data-lang-switch="user"><?php echo trans_phrase('user', $lang); ?>:</label>
							<div class="col-sm-8">
								<input type="email" name="loginEmail" id="loginEmail" class="form-control" />
							</div>
						</div>
						<div class="form-group">
							<label for="loginPassword" class="col-sm-4 control-label" data-lang-switch="password"><?php echo trans_phrase('password', $lang); ?>:</label>
							<div class="col-sm-8">
								<input type="password" name="loginPassword" id="loginPassword" class="form-control" />
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-4 col-sm-8">
								<button type="submit" class="btn btn-default" name="submit" data-lang-switch="login">
									<?php echo trans_phrase('login', $lang); ?>
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<div class="container-liquid circuit-container">
		<div class="row-liquid tablerow">
			<div class="tab-content">
				<div class="tab-pane active" id="table">
					<div id="ieml-table-info-wrap">
						<input type="hidden" id="iemlTableID" name="iemlTableID">

						<div id="ieml-table-span"></div>
					</div>
				</div>

				<div class="tab-pane" id="relations">
					<div class="container">
						<div id="ieml-relations-wrap">

							<h3 class="heading_underline" data-lang-switch="etymology"><?php echo trans_phrase('etymology', $lang); ?></h3>

							<div id="ieml-etymology-wrap"></div>

							<div id="ieml-complementary-section">
								<h3 class="heading_underline" data-lang-switch="complementary_concepts"><?php echo trans_phrase('complementary_concepts', $lang); ?></h3>

								<div id="ieml-complementary-wrap"></div>
							</div>

							<div id="ieml-diagonal-section">
								<h3 class="heading_underline" data-lang-switch="diagonal"><?php echo trans_phrase('diagonal', $lang); ?></h3>

								<div id="ieml-diagonal-wrap"></div>
							</div>

							<h3 class="heading_underline"><span data-lang-switch="taxonomic"><?php echo trans_phrase('taxonomic', $lang); ?></span></h3>

							<div class="row">
								<div class="col-md-6">
									<span class=""><strong data-lang-switch="contained_by"><?php echo trans_phrase('contained_by', $lang); ?></strong></span>

									<div id="ieml-contained-wrap"></div>
								</div>

								<div class="col-md-6">
									<span class=""><strong data-lang-switch="containing"><?php echo trans_phrase('containing', $lang); ?></strong></span>

									<div id="ieml-containing-wrap"></div>
								</div>
							</div>

							<h3 class="heading_underline" data-lang-switch="concurring_concepts"><?php echo trans_phrase('concurring_concepts', $lang); ?></h3>

							<div id="ieml-concurrent-wrap"></div>

						</div>
					</div>
				</div>

				<div class="tab-pane" id="graph">
					<div class="container">
						&hellip;
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="editor-drawer hidden">
		<div class="row">
			<div class="col-md-12">
				<span class="pull-left ieml-validation-result hidden">
					<div class="result-error-icon hidden"><span class="glyphicon glyphicon-remove">&nbsp;</span></div>
				</span>
			</div>
			<div class="col-md-12">
				<span class="pull-left ieml-validation-result hidden">
					<span class="result-error"></span>
				</span>
			</div>

			<div class="col-md-12">
				<div class="editor">
					<div class="editor-head">
						<div class="row">
							<div class="col-md-4">
								<span class="content-type"><span data-lang-switch="proposition_phrase"><?= trans_phrase('proposition_phrase', $lang); ?></span><span class="hidden" data-lang-switch="text_usl"><?= trans_phrase('text_usl', $lang); ?></span></span>
							</div>
							<div class="col-md-6 draggable-list">
								<div class="draggable" data-script-val="+"><span class="glyphicon glyphicon-plus"></span></div>
								<div class="draggable" data-script-val="*"><span class="glyphicon glyphicon-remove"></span></div>
								<div class="draggable" data-script-val="("><strong>(</strong></div>
								<div class="draggable" data-script-val=")"><strong>)</strong></div>
								<div class="draggable" data-script-val="["><strong>[</strong></div>
								<div class="draggable" data-script-val="]"><strong>]</strong></div>
								<div class="draggable" data-script-val="/"><strong>/</strong></div>
							</div>
						</div>
					</div>
					<div class="editor-proper"></div>
					<div class="editor-garbage hidden"><span class="glyphicon glyphicon-trash"></span></div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="editor-example">
					<div class="row">
						<div class="col-md-1">
							<a href="" class="btn btn-link editor-short"></a>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control editor-example-input" />
						</div>
						<div class="col-md-2">
							<input type="hidden" name="rel-id" />
							<button type="button" class="btn btn-default editor-clear" data-lang-switch="clear"><?= trans_phrase('clear', $lang); ?></button>
							<button type="button" class="btn btn-default editor-save" data-lang-switch="save"><?= trans_phrase('save', $lang); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="iemlAddUserModal" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				
				<div class="modal-header">
					<a class="close" data-dismiss="modal">&times;</a>

					<h3 data-lang-switch="add_user"><?php echo trans_phrase('add_user', $lang); ?></h3>
				</div>

				<div class="modal-body">
					<form class="form-horizontal" id="iemlUser" method="post" action="/api/">
						<input type="hidden" name="a" value="addUser">

						<div class="control-group">
							<label class="control-label" for="addUserModalDisplayName" data-lang-switch="name"><?php echo trans_phrase('name', $lang); ?></label>

							<div class="controls">
								<input type="text" class="form-control" id="addUserModalDisplayName" name="addUserModalDisplayName" />
							</div>
						</div>

						<div class="control-group">
							<label class="control-label" for="addUserModalUsername" data-lang-switch="user"><?php echo trans_phrase('user', $lang); ?></label>

							<div class="controls">
								<input type="text" class="form-control" id="addUserModalUsername" name="addUserModalUsername" />
							</div>
						</div>

						<div class="control-group">
							<label class="control-label" for="addUserModalType" data-lang-switch="user_tab_type_col"><?php echo trans_phrase('user_tab_type_col', $lang); ?></label>

							<div class="controls">
								<select id="addUserModalType" name="addUserModalType">
									<option value="user">User</option>
									<option value="admin">Admin</option>
								</select>
							</div>
						</div>

						<div class="control-group">
							<label class="control-label" for="addUserModalPass" data-lang-switch="password"><?php echo trans_phrase('password', $lang); ?></label>

							<div class="controls">
								<input type="password" class="form-control" id="addUserModalPass" name="addUserModalPass" />
							</div>
						</div>

						<div class="control-group">
							<label class="control-label" for="addUserModalConfPass" data-lang-switch="conf_pass"><?php echo trans_phrase('conf_pass', $lang); ?></label>

							<div class="controls">
								<input type="password" class="form-control" id="addUserModalConfPass" name="addUserModalConfPass" />
							</div>
						</div>
					</form>
				</div>

				<div class="modal-footer">
					<button type="button" id="iemlAddUserModalAdd" class="btn btn-primary" data-lang-switch="add">
						<?php echo trans_phrase('add', $lang); ?>
					</button>
					<button type="button" data-dismiss="modal" class="btn btn-default" data-lang-switch="cancel">
						<?php echo trans_phrase('cancel', $lang); ?>
					</button>
				</div>

			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal.fade -->

	<div id="confirmCancelModal" class="modal" aria-hidden="true" data-backdrop="static">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-body no-bot-margin">
					<a class="close" data-dismiss="modal" id="workPrintModalX">x</a><span id="confirmCancelModalText"></span>
				</div>

				<div class="modal-footer">
					<button type="button" id="confirmCancelModalYes" class="btn btn-primary" data-dismiss="modal" data-lang-switch="yes"><?php echo trans_phrase('yes', $lang); ?></button><button type="button" class="btn btn-default" data-dismiss="modal" data-lang-switch="no"><?php echo trans_phrase('no', $lang); ?></button>
				</div>
			</div>
		</div>
	</div><!-- /#confirmCancelModal -->

	<div class="globalLoading">
		<div class="backdrop"></div>
	</div><!-- /.globalLoading -->
	<?php

	require_once(APPROOT.'/includes/footer.php');

	//set_session_data($_SESSION['auth_token'], $_SESSION);

	Conn::closeStaticHandle();

	?>
