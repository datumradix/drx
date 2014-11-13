<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2014 Zurmo Inc.
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
     * "Copyright Zurmo Inc. 2014. All rights reserved".
     ********************************************************************************/

    /**
     * View to show a create email interface that appears in a modal window.
     */
    class SendTestEmailModalEditView extends EditView
    {
        const COMPILE_SEND_TEST_EMAIL_DATA_JS_FUNCTION_NAME = 'compileSendTestEmailData';

        /**
         * Since this edit view shows in a modal, we do not want the wrapper div to display as it is unneeded.
         * @var boolean
         */
        protected $wrapContentInWrapperDiv = false;

        public function __construct($controllerId, $moduleId, $model, $title = null)
        {
            assert('$title == null || is_string($title)');
            $this->assertModelIsValid($model);
            $this->controllerId   = $controllerId;
            $this->moduleId       = $moduleId;
            $this->model          = $model;
            $this->modelClassName = get_class($model);
            $this->modelId        = null;
            $this->title          = $title;
        }

        public static function getDefaultMetadata()
        {
            $metadata = array(
                'global' => array(
                    'toolbar' => array(
                        'elements' => array(
                            array('type'  => 'SaveButton', 'label' => Zurmo::t('EmailMessagesModule', 'Send')),
                        ),
                    ),
                    'panelsDisplayType' => FormLayout::PANELS_DISPLAY_TYPE_ALL,
                    'panels' => array(
                        array(
                            'rows' => array(
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'null',
                                                      'type' => 'SelectContactOrInputEmailComposite')
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

        protected function resolveElementDuringFormLayoutRender(& $element)
        {
            $element->editableTemplate = '<td>{content}</td>';
        }

        protected function doesLabelHaveOwnCell()
        {
            return false;
        }

        protected static function getFormId()
        {
            return 'send-test-form';
        }

        protected function getFormLayoutUniqueId()
        {
            return 'send-test-form-layout';
        }

        protected function resolveActiveFormAjaxValidationOptions()
        {
            return array(
                'enableAjaxValidation' => true,
                'clientOptions' => array(
                    'validationUrl'     => $this->resolveSendTestEmailAjaxValidationRoute(),
                    'beforeValidate'    => 'js:$(this).beforeValidateAction',
                    'afterValidate'     => 'js:$(this).afterValidateAjaxAction',
                    'validateOnSubmit'  => true,
                    'validateOnChange'  => false,
                    'afterValidateAjax' => $this->renderSendTestEmailPostAjax(),
                )
            );
        }

        protected function renderSendTestEmailPostAjax()
        {
            $this->registerFormDataExtractionScripts();
            $modelClass         = get_class($this->model);
            $formId             = static::getFormId();
            $allInputSelector   = "\$('#{$formId} :input')";
            return ZurmoHtml::ajax(array(
                'type'          => 'POST',
                'beforeSend'    => 'js:function() {
                                        ' . $allInputSelector . '.prop("disabled", true);
                                    }',
                'data'          => 'js:(function() {
                                    var formData            = resolveSendTestModalFormData();
                                    var sendTestEmailData   = window.' . static::COMPILE_SEND_TEST_EMAIL_DATA_JS_FUNCTION_NAME .'();
                                    var requestData         = {"' . $modelClass . '": formData,
                                                                "sendTestEmailData": sendTestEmailData,
                                                                "YII_CSRF_TOKEN": "' . addslashes(Yii::app()->request->csrfToken) .
                                                                '"};
                                    return requestData;
                                    })()',
                'complete'      => 'js:function() {
                                        ' . $allInputSelector . '.prop("disabled", false);
                                    }',
                'url'           => $this->resolveSendTestEmailRoute(),
                'update'        => '#modalContainer',
            ));
        }

        protected function registerFormDataExtractionScripts()
        {
            $formId                     = static::getFormId();
            $radioWrapperId             = SelectContactOrInputEmailCompositeElement::SELECT_PRIMARY_OR_SECONDARY_RADIO_WRAPPER_ID;
            $primaryInputWrapperId      = SelectContactOrInputEmailCompositeElement::PRIMARY_INPUT_WRAPPER_ID;
            $secondaryInputWrapperId    = SelectContactOrInputEmailCompositeElement::SECONDARY_INPUT_WRAPPER_ID;

            $radioSelector              = "\$('form#{$formId} #{$radioWrapperId} input:radio:checked')";
            $primaryInputSelector       = "\$('form#{$formId} #{$primaryInputWrapperId} input:text')";
            $secondaryInputSelector     = "\$('form#{$formId} #{$secondaryInputWrapperId} input:text')";
            $scriptName                 = 'resolveSendTestModalFormData';

            if (Yii::app()->clientScript->isScriptRegistered($scriptName))
            {
                return;
            }
            else
            {
                // Begin Not Coding Standard
                Yii::app()->clientScript->registerScript($scriptName, "
                {$scriptName} = function ()
                    {
                        var data                = {};
                        var selectedRadioValue  = " . $radioSelector . ".val();
                        if (selectedRadioValue == 0)
                        {
                            selectedContactValue    = " . $primaryInputSelector . ".val();
                            var regExp              = /\(([^)]+)\)/;
                            var matches             = selectedContactValue.match(regExp);
                            data.contactId          = matches[1];
                        }
                        else
                        {
                            data.emailAddress       = " . $secondaryInputSelector . ".val();
                        }
                        return data;
                    }
                    ", CClientScript::POS_HEAD);
                // End Not Coding Standard
            }
        }

        protected function resolveSendTestEmailAction()
        {
            return 'sendTestEmail';
        }

        protected function resolveSendTestEmailAjaxValidationAction()
        {
            return 'validateSendTestEmail';
        }

        protected function resolveSendTestEmailRoute()
        {
            return $this->resolveRelativeUrlForAction($this->resolveSendTestEmailAction());
        }

        protected function resolveSendTestEmailAjaxValidationRoute()
        {
            return $this->resolveRelativeUrlForAction($this->resolveSendTestEmailAjaxValidationAction());
        }

        protected function resolveRelativeUrlForAction($action)
        {
            return Yii::app()->createUrl($this->moduleId . '/' . $this->controllerId . '/' . $action);
        }

        protected function alwaysShowErrorSummary()
        {
            return true;
        }

        public static function registerSendTestEmailScriptsForDetailsView($modelId, $modelClassName)
        {
            $scriptName = $modelClassName . '-compile-send-test-email-data-for-details-view';
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
                        return {
                            id     : '{$modelId}',
                            class  : '{$modelClassName}'
                        };
                    }
                    ");
                // End Not Coding Standard
            }
        }
    }
?>