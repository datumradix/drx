<div id="compose-email" class="clearfix">

	<!--
		<input type="text" placeholder="To" />
		<input type="text" placeholder="CC" />
		<input type="text" placeholder="BCC" />
		<input type="text" placeholder="Subject" />
		<input type="text" placeholder="Template" />

		<textarea></textarea>

		<a href="#" class="z-button"><span class="z-label">Send</span></a>
	-->

	<div id="CreateEmailMessageModalEditView" class="EditView DetailsView ModelView ConfigurableMetadataView MetadataView">
		<div class="wide form">
			<form onsubmit="js:return $(this).attachLoadingOnSubmit(&quot;edit-form&quot;)"
				class="unstyle-panel"
				id="edit-form" action="/Zurmo/app/index.php/emailMessages/default/createEmailMessage?toAddress=Laura.Allen%40Primatech.com&amp;relatedId=1&amp;relatedModelClassName=Contact&amp;redirectUrl=%2FZurmo%2Fapp%2Findex.php%2Fcontacts%2Fdefault%2Fdetails%3Fid%3D1&amp;_=1400770491244" method="post">
				<div style="display:none">
					<input type="hidden" value="39c85569e9f83e75eec0a11d848f4e8153646d97" name="YII_CSRF_TOKEN"></div>
				<div class="attributesContainer">
					<div class="left-column full-width">
						<div class="panel">
							<h1>Compose Email</h1>
							<table class="form-fields">
								<colgroup>
									<col class="col-0">
								</colgroup>
								<tbody>
								<tr>
									<td>
										<div class="recipient">
											<label for="CreateEmailMessageForm_recipientsData_to">
												To <a onclick="js:$('#cc-bcc-fields').show();$('#cc-bcc-fields-link').hide(); return false;"
													id="cc-bcc-fields-link" class="simple-link more-panels-link" href="#">Add CC / BCC</a>
											</label>
											<div>
												<ul class="token-input-list">
													<li class="token-input-token">
														<p>Laura Allen (Laura.Allen@Primatech.com)</p>
														<span class="token-input-delete-token">×</span></li>
													<li class="token-input-input-token">
														<input type="text" autocomplete="off" id="token-input-CreateEmailMessageForm_recipientsData_to" style="outline: none;">
														<tester style="position: absolute; top: -9999px; left: -9999px; width: auto; font-size: 12px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-weight: 400; letter-spacing: 0px; white-space: nowrap;"></tester>
													</li>
												</ul>
												<input id="CreateEmailMessageForm_recipientsData_to" type="text" name="CreateEmailMessageForm[recipientsData][to]" style="display: none;">

												<div class="errorMessage" id="CreateEmailMessageForm_recipientsData_to_em_" style="display:none"></div>
											</div>
										</div>
										<div id="cc-bcc-fields" style="display: none;">
											<div class="recipient">
												<label for="CreateEmailMessageForm_recipientsData_cc">Cc</label>

												<div>
													<ul class="token-input-list">
														<li class="token-input-input-token">
															<input type="text" autocomplete="off" id="token-input-CreateEmailMessageForm_recipientsData_cc" style="outline: none;">
															<tester style="position: absolute; top: -9999px; left: -9999px; width: auto; font-size: 12px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-weight: 400; letter-spacing: 0px; white-space: nowrap;"></tester>
														</li>
													</ul>
													<input id="CreateEmailMessageForm_recipientsData_cc" type="text" name="CreateEmailMessageForm[recipientsData][cc]" style="display: none;">

													<div class="errorMessage" id="CreateEmailMessageForm_recipientsData_cc_em_" style="display:none"></div>
												</div>
											</div>
											<div class="recipient">
												<label for="CreateEmailMessageForm_recipientsData_bcc">Bcc</label>

												<div>
													<ul class="token-input-list">
														<li class="token-input-input-token">
															<input type="text" autocomplete="off" id="token-input-CreateEmailMessageForm_recipientsData_bcc" style="outline: none;">
															<tester style="position: absolute; top: -9999px; left: -9999px; width: auto; font-size: 12px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-weight: 400; letter-spacing: 0px; white-space: nowrap;"></tester>
														</li>
													</ul>
													<input id="CreateEmailMessageForm_recipientsData_bcc" type="text" name="CreateEmailMessageForm[recipientsData][bcc]" style="display: none;">

													<div class="errorMessage" id="CreateEmailMessageForm_recipientsData_bcc_em_" style="display:none"></div>
												</div>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<div class="overlay-label-field">
											<label for="CreateEmailMessageForm_subject">Subject</label><input id="CreateEmailMessageForm_subject" name="CreateEmailMessageForm[subject]" type="text" maxlength="255">

											<div class="errorMessage" id="CreateEmailMessageForm_subject_em_" style="display:none"></div>
										</div>
									</td>
								</tr>

								<tr>
									<td>
										<div class="overlay-label-field">
											<div class="has-model-select">
												<label for="CreateEmailMessageForm_contactEmailTemplateNames_name">Select a template</label><input name="" id="CreateEmailMessageForm_contactEmailTemplateNames_id" value="" type="hidden"><input onblur="clearIdFromAutoCompleteField($(this).val(), 'CreateEmailMessageForm_contactEmailTemplateNames_id');" id="CreateEmailMessageForm_contactEmailTemplateNames_name" type="text" value="" name="" class="ui-autocomplete-input" autocomplete="off"><span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
												<a id="CreateEmailMessageForm_contactEmailTemplateNames_SelectLink" href="#">
													<span class="model-select-icon"></span><span class="z-spinner"></span>
												</a>
											</div>
											<ul class="ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all" id="ui-id-8" tabindex="0" style="z-index: 1003; display: none;"></ul>
										</div>
									</td>
								</tr>
								<tr>
									<td class="email-body-editors">
										<div class="email-template-content">



												<div class="tabs-nav clearfix">
													<a class="active-tab" href="#tab1">
														<label for="CreateEmailMessageForm_content_textContent"><i class="icon-plaintext"></i>Text Content</label>
													</a>
													<a href="#tab2">
														<label for="CreateEmailMessageForm_content_htmlContent"><i class="icon-html"></i>Html Content</label>
													</a>
												</div>






											<div id="tab1" class="active-tab tab email-template-textContent">
												<textarea id="CreateEmailMessageForm_content_textContent" name="CreateEmailMessageForm[content][textContent]"></textarea>
											</div>
											<div id="tab2" class=" tab email-template-htmlContent">
												<div class="redactor_box" style="z-index: 100;">
													<ul class="redactor_toolbar" id="redactor_toolbar_0">
														<li>
															<a href="javascript:;" title="HTML" tabindex="-1" class="re-icon re-html"></a>
														</li>
														<div class="redactor_dropdown redactor_dropdown_box_formatting" style="display: none;">
															<a href="#" class=" redactor_dropdown_p" tabindex="-1">Normal text</a>
															<a href="#" class="redactor_format_blockquote redactor_dropdown_blockquote" tabindex="-1">Quote</a>
															<a href="#" class="redactor_format_pre redactor_dropdown_pre" tabindex="-1">Code</a>
															<a href="#" class="redactor_format_h1 redactor_dropdown_h1" tabindex="-1">Header 1</a>
															<a href="#" class="redactor_format_h2 redactor_dropdown_h2" tabindex="-1">Header 2</a>
															<a href="#" class="redactor_format_h3 redactor_dropdown_h3" tabindex="-1">Header 3</a>
															<a href="#" class="redactor_format_h4 redactor_dropdown_h4" tabindex="-1">Header 4</a>
															<a href="#" class="redactor_format_h5 redactor_dropdown_h5" tabindex="-1">Header 5</a>
														</div>
														<li>
															<a href="javascript:;" title="Formatting" tabindex="-1" class="re-icon re-formatting"></a>
														</li>
														<li>
															<a href="javascript:;" title="Bold" tabindex="-1" class="re-icon re-bold"></a>
														</li>
														<li>
															<a href="javascript:;" title="Italic" tabindex="-1" class="re-icon re-italic"></a>
														</li>
														<li>
															<a href="javascript:;" title="Deleted" tabindex="-1" class="re-icon re-deleted"></a>
														</li>
														<li>
															<a href="javascript:;" title="• Unordered List" tabindex="-1" class="re-icon re-unorderedlist"></a>
														</li>
														<li>
															<a href="javascript:;" title="1. Ordered List" tabindex="-1" class="re-icon re-orderedlist"></a>
														</li>
														<li>
															<a href="javascript:;" title="< Outdent" tabindex="-1" class="re-icon re-outdent"></a>
														</li>
														<li>
															<a href="javascript:;" title="> Indent" tabindex="-1" class="re-icon re-indent"></a>
														</li>
														<div class="redactor_dropdown redactor_dropdown_box_table" style="display: none;">
															<a href="#" class=" redactor_dropdown_insert_table" tabindex="-1">Insert Table</a>
															<a class="redactor_separator_drop" tabindex="-1"></a>
															<a href="#" class=" redactor_dropdown_insert_row_above" tabindex="-1">Add Row Above</a>
															<a href="#" class=" redactor_dropdown_insert_row_below" tabindex="-1">Add Row Below</a>
															<a href="#" class=" redactor_dropdown_insert_column_left" tabindex="-1">Add Column Left</a>
															<a href="#" class=" redactor_dropdown_insert_column_right" tabindex="-1">Add Column Right</a>
															<a class="redactor_separator_drop" tabindex="-1"></a>
															<a href="#" class=" redactor_dropdown_add_head" tabindex="-1">Add Head</a>
															<a href="#" class=" redactor_dropdown_delete_head" tabindex="-1">Delete Head</a>
															<a class="redactor_separator_drop" tabindex="-1"></a>
															<a href="#" class=" redactor_dropdown_delete_column" tabindex="-1">Delete Column</a>
															<a href="#" class=" redactor_dropdown_delete_row" tabindex="-1">Delete Row</a>
															<a href="#" class=" redactor_dropdown_delete_table" tabindex="-1">Delete Table</a>
														</div>
														<li>
															<a href="javascript:;" title="Table" tabindex="-1" class="re-icon re-table"></a>
														</li>
														<div class="redactor_dropdown redactor_dropdown_box_link" style="display: none;">
															<a href="#" class=" redactor_dropdown_link" tabindex="-1">Insert link</a>
															<a href="#" class=" redactor_dropdown_unlink" tabindex="-1">Unlink</a>
														</div>
														<li>
															<a href="javascript:;" title="Link" tabindex="-1" class="re-icon re-link"></a>
														</li>
														<li>
															<a href="javascript:;" title="Align text to the left" tabindex="-1" class="re-icon re-alignleft"></a>
														</li>
														<li>
															<a href="javascript:;" title="Center text" tabindex="-1" class="re-icon re-aligncenter"></a>
														</li>
														<li>
															<a href="javascript:;" title="Align text to the right" tabindex="-1" class="re-icon re-alignright"></a>
														</li>
														<li>
															<a href="javascript:;" title="Insert Horizontal Rule" tabindex="-1" class="re-icon re-horizontalrule"></a>
														</li>
														<li>
															<a href="javascript:;" title="Insert Image" tabindex="-1" class="re-icon re-image"></a>
														</li>
													</ul>
													<iframe style="width: 100%; min-height: 100px; height: 68px;" frameborder="0"></iframe>
													<textarea id="CreateEmailMessageForm_content_htmlContent" name="CreateEmailMessageForm[content][htmlContent]" dir="ltr" style="display: none;"></textarea>
												</div>
											</div>
										</div>
										<div class="errorMessage" id="CreateEmailMessageForm_content_em_" style="display:none"></div>
									</td>
								</tr>
								<tr>
									<td class="email-attachments-upload">
										<div id="dropzoneCreateEmailMessageForm"></div>
										<div id="fileUploadCreateEmailMessageForm" class="ui-widget">
											<div class="fileupload-buttonbar clearfix">
												<div class="addfileinput-button"><span>Y</span>
													<strong class="add-label">Add Files</strong>
													<input id="CreateEmailMessageForm_files" type="file" name="CreateEmailMessageForm_files">
												</div>
												<span class="max-upload-size">Max upload size: 1MB</span></div>
											<div class="fileupload-content">
												<table class="files">
													<tbody></tbody>
												</table>
											</div>
										</div>
									</td>
								</tr>

								<tr>
									<td class="email-send-button">
										<a id="saveyt1" name="save" class="attachLoading z-button" onclick="jQuery.yii.submitForm(this, '', {'save':'save'}); return false;" href="#">
											<span class="z-label">Send</span>
										</a>
										<a class="simple-link delete-email-link">
											<span class="z-label"><i class="icon-delete"></i> Cancel</span></a>
									</td>
								</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<!--
				<div class="float-bar">
					<div class="view-toolbar-container clearfix dock">
						<div class="form-toolbar">
							<a id="saveyt1" name="save" class="attachLoading z-button" onclick="jQuery.yii.submitForm(this, '', {'save':'save'}); return false;" href="#">
								<span class="z-spinner"></span><span class="z-icon"></span><span class="z-label">Send</span>
							</a>
						</div>
					</div>
				</div>
				-->
			</form>
			<div id="modalContainer-edit-form"></div>
		</div>
	</div>


</div>