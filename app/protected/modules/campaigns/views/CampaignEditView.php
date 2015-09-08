<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2015 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU Affero General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
     * details.
     *
     * You should have received a copy of the GNU Affero General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 27 North Wacker Drive
     * Suite 370 Chicago, IL 60606. or at email address contact@zurmo.com.
     *
     * The interactive user interfaces in original and modified versions
     * of this program must display Appropriate Legal Notices, as required under
     * Section 5 of the GNU Affero General Public License version 3.
     *
     * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
     * these Appropriate Legal Notices must retain the display of the Zurmo
     * logo and Zurmo copyright notice. If the display of the logo is not reasonably
     * feasible for technical reasons, the Appropriate Legal Notices must display the words
     * "Copyright Zurmo Inc. 2015. All rights reserved".
     ********************************************************************************/

    class CampaignEditView extends SecuredEditView
    {
        public static function getDefaultMetadata()
        {
            $metadata = array(
                'global' => array(
                    'toolbar' => array(
                        'elements' => array(
                            array('type'    => 'SaveButton', 'label' => 'eval:static::renderLabelForSaveButton()',
                                  'htmlOptions' => 'eval:static::resolveHtmlOptionsForSaveButton()'),
                            array('type'    => 'CancelLink'),
                            array('type'    => 'SendTestEmailLink'),
                            array('type'    => 'CampaignDeleteLink'),
                        ),
                    ),
                    'panels' => array(
                        array(
                            'rows' => array(
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'name', 'type' => 'Text'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                array(
                                    array(
                                        'elements' => array(
                                            array('attributeName' => 'marketingList', 'type' => 'MarketingList'),
                                        ),
                                    ),
                                )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'fromName', 'type' => 'Text'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'fromAddress', 'type' => 'Email'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'sendOnDateTime', 'type' => 'DateTime'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'subject', 'type' => 'Text'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'enableTracking',
                                                      'type'          => 'EnableTrackingCheckBox'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'supportsRichText',
                                                      'type'          => 'SupportsRichTextCheckBox'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'null', 'type' => 'EmailTemplate')
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'null', 'type' => 'Files'),
                                            ),
                                        ),
                                    )
                                ),
                            ),
                        ),
                    ),
                ),
            );
            return $metadata;
        }

        protected function renderContent()
        {
            $this->registerCopyInfoFromMarketingListScript();
            $this->registerRedactorHeightScript();
            $this->registerSendTestEmailScriptsForEditView();
            return parent::renderContent();
        }

        protected function renderAfterFormLayout($form)
        {
            $content = $this->renderHtmlAndTextContentElement($this->model, null, $form);
            return ZurmoHtml::tag('div', array('class' => 'email-template-combined-content left-column strong-right clearfix'), $content);
        }

        protected function renderHtmlAndTextContentElement($model, $attribute, $form)
        {
            $content = null;
            if (!$this->isCampaignEditable())
            {
                $element = new EmailTemplateHtmlAndTextContentElement($model, $attribute);
            }
            else
            {
                $content .= ZurmoHtml::tag('div', array('class' => 'left-column'), $this->renderMergeTagsContent());
                $element = new EmailTemplateHtmlAndTextContentElement($model, $attribute , $form);
                $element->plugins = array('fontfamily', 'fontsize', 'fontcolor');
            }
            if ($form !== null)
            {
                $this->resolveElementDuringFormLayoutRender($element);
            }
            $content .= ZurmoHtml::tag('div', array('class' => 'email-template-combined-content right-column'), $element->render());
            return $content;
        }

        protected function renderMergeTagsContent()
        {
            $title = ZurmoHtml::tag('h3', array(), Zurmo::t('Default', 'Merge Tags'));
            $view = new MergeTagsView('Campaign',
                            Element::resolveInputIdPrefixIntoString(array(get_class($this->model), 'textContent')),
                            Element::resolveInputIdPrefixIntoString(array(get_class($this->model), 'htmlContent')),
                            false);
            $content = $view->render();
            return $title . $content;
        }

        /**
         * Override to mark disable elements for campaign edit
         */
        protected function resolveElementInformationDuringFormLayoutRender(& $elementInformation)
        {
            if (!$this->isCampaignEditable() && $elementInformation['attributeName'] != 'name')
            {
                $elementInformation['disabled'] = true;
            }
        }

        protected function resolveElementDuringFormLayoutRender(& $element)
        {
            if ($this->alwaysShowErrorSummary())
            {
                $element->editableTemplate = str_replace('{error}', '', $element->editableTemplate);
            }
        }

        protected function alwaysShowErrorSummary()
        {
            return true;
        }

        protected function getNewModelTitleLabel()
        {
            return Zurmo::t('Default', 'Create AutorespondersModuleSingularLabel',
                                                                        LabelUtil::getTranslationParamsForAllModules());
        }

        protected function registerCopyInfoFromMarketingListScript()
        {
            $url           = Yii::app()->createUrl('marketingLists/default/getInfoToCopyToCampaign');
            // Begin Not Coding Standard
            Yii::app()->clientScript->registerScript('copyInfoFromMarketingListScript', "
                $('#Campaign_marketingList_id').live('change', function()
                    {
                       if ($('#Campaign_marketingList_id').val())
                          {
                            $.ajax(
                            {
                                url : '" . $url . "?id=' + $('#Campaign_marketingList_id').val(),
                                type : 'GET',
                                dataType: 'json',
                                success : function(data)
                                {
                                    $('#Campaign_fromName').val(data.fromName);
                                    $('#Campaign_fromAddress').val(data.fromAddress)
                                },
                                error : function()
                                {
                                    //todo: error call
                                }
                            }
                            );
                          }
                    }
                );
            ");
            // End Not Coding Standard
        }

        protected function registerRedactorHeightScript()
        {
            /*Yii::app()->clientScript->registerScript('redactorHeightScript', '
                        var contentHeight = $(".redactor_box iframe").contents().find("html").outerHeight();
                        $(".redactor_box iframe").height(contentHeight + 50);');*/
        }

        protected function isCampaignEditable()
        {
            return ($this->model->status == Campaign::STATUS_ACTIVE);
        }

        protected function renderLabelForSaveButton()
        {
            if ($this->model->isAttributeEditable('sendOnDateTime'))
            {
                return Zurmo::t("CampaignsModule", "Save and Schedule");
            }
            else
            {
                Zurmo::t('Core', 'Save');
            }
        }

        /**
         * Show save button only for active campaigns, otherwise Campaign saving would cause error because required
         * fields are disabled
         * @return array|null
         */
        protected function resolveHtmlOptionsForSaveButton()
        {
            if ($this->model->status == Campaign::STATUS_ACTIVE)
            {
                return null;
            }
            else
            {
                return array('style' => 'display: none');
            }
        }

        protected function renderRightSideFormLayoutForEdit($form)
        {
            assert('$form instanceof ZurmoActiveForm');
            $content = "<h3>".Zurmo::t('ZurmoModule', 'Rights and Permissions') . '</h3><div id="owner-box">';
            if($this->model->isAttributeEditable('owner'))
            {
                $element = new UserElement($this->getModel(), 'owner', $form);
            }
            else
            {
                $element = new UserElement($this->getModel(), 'owner', $form, array('disabled' => true));
            }
            $element->editableTemplate = '{label}{content}{error}';
            $content .= $element->render().'</div>';
            $element  = new CampaignDerivedExplicitReadWriteModelPermissionsElement($this->getModel(), 'null', $form);
            $element->editableTemplate = '{label}{content}{error}';
            $content .= $element->render();
            return $content;
        }

        protected function registerSendTestEmailScriptsForEditView()
        {
            $scriptName = $this->modelClassName. '-compile-send-test-email-data-for-campaign-edit-view';
            if (Yii::app()->clientScript->isScriptRegistered($scriptName))
            {
                return;
            }
            else
            {
                $functionName   = SendTestEmailModalEditView::COMPILE_SEND_TEST_EMAIL_DATA_JS_FUNCTION_NAME;
                // Begin Not Coding Standard
                Yii::app()->clientScript->registerScript($scriptName, "
                window.{$functionName} = function ()
                    {
                        var textContent, htmlContent;
                        if ($('#" . ZurmoHtml::activeId($this->model, 'textContent') . "').length > 0) {
                            textContent = $('#" . ZurmoHtml::activeId($this->model, 'textContent') . "').val();
                        }
                        else
                        {
                            textContent = $('.email-template-textContent').text();
                        }

                        if ($('#" . ZurmoHtml::activeId($this->model, 'htmlContent') . "').length > 0) {
                            htmlContent = $('#" . ZurmoHtml::activeId($this->model, 'htmlContent') . "').val();
                        }
                        else
                        {
                            htmlContent = $('.email-template-htmlContent iframe').contents().find('html').html()
                        }

                        var testData    = {
                            fromName            : $('#" . ZurmoHtml::activeId($this->model, 'fromName') . "').val(),
                            fromAddress         : $('#" . ZurmoHtml::activeId($this->model, 'fromAddress') . "').val(),
                            subject             : $('#" . ZurmoHtml::activeId($this->model, 'subject') . "').val(),
                            textContent         : textContent,
                            htmlContent         : htmlContent,
                            marketingListId     : $('#" . ZurmoHtml::activeId($this->model, '_marketingList_id') . "').val(),
                            enableTracking      : $('input:checkbox[name*=enableTracking]').prop('checked') ? 1 : 0,
                            supportsRichText    : $('input:checkbox[name*=supportsRichText]').prop('checked') ? 1 : 0,
                            attachmentIds	    : new Array()
                        };
                        $(':input[name*=filesIds]').each(function()
                        {
                            testData.attachmentIds.push($(this).val());
                        });
                        return testData;
                    }
                    ");
                // End Not Coding Standard
            }
        }
    }
?>