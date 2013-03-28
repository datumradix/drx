<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2012 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     * details.
     *
     * You should have received a copy of the GNU General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 113 McHenry Road Suite 207,
     * Buffalo Grove, IL 60089, USA. or at email address contact@zurmo.com.
     ********************************************************************************/

    /**
     * View for displaying a row of email alert information for a component
     */
    class EmailAlertRowForWorkflowComponentView extends View
    {
        const ADD_RECIPIENT_TYPE_NAME               = 'addRecipientType';

        const ADD_RECIPIENT_CLASS_NAME              = 'add-recipient-link';

        const RECIPIENTS_ROW_COUNTER_CLASS_NAME     = 'email-alert-recipients-row-counter';

        const RECIPIENTS_CONTAINER_CLASS_NAME       = 'recipients-container';

        const EMAIL_ALERT_RECIPIENTS_ROW_CLASS_NAME = 'email-alert-recipient-rows';

        protected $model;

        protected $rowNumber;

        protected $inputPrefixData;

        protected $form;

        protected $workflowWizardFormClassName;

        protected $emailAlertsRowCounterInputId;

        public static function getFormId()
        {
            return WizardView::getFormId();
        }

        protected static function resolveRecipientTypeDataAndLabels()
        {
            $data = array('' => Zurmo::t('WorkflowsModule', 'Add Recipient'));
            return array_merge($data, WorkflowEmailAlertRecipientForm::getTypeValuesAndLabels());
        }

        public function __construct(EmailAlertForWorkflowForm $model, $rowNumber, $inputPrefixData,
                                    WizardActiveForm $form, $workflowWizardFormClassName, $emailAlertsRowCounterInputId)
        {
            assert('is_int($rowNumber)');
            assert('is_array($inputPrefixData)');
            assert('is_string($workflowWizardFormClassName)');
            assert('is_string($emailAlertsRowCounterInputId)');
            $this->model                        = $model;
            $this->rowNumber                    = $rowNumber;
            $this->inputPrefixData              = $inputPrefixData;
            $this->form                         = $form;
            $this->workflowWizardFormClassName  = $workflowWizardFormClassName;
            $this->emailAlertsRowCounterInputId = $emailAlertsRowCounterInputId;
        }

        public function render()
        {
            $this->registerScripts();
            return $this->renderContent();
        }

        /**
         * @return string
         */
        protected function renderContent()
        {
            $content  = '<div>';
            $content .= $this->renderEmailAlertRowNumberLabel();
            $content .= ZurmoHtml::tag('div', array('class' => 'dynamic-row-label'), 'todo some label saying this is an alert?');
            $content .= '</div>';
            $content .= ZurmoHtml::link('—', '#', array('class' => 'remove-dynamic-row-link'));
            $content .= '<div>';
            $content .= $this->renderEmailAlertContent();
            $content .= '</div>';
            //todo: call correctly as email-alert?, fix theme? need to maybe refcator
            $content  =  ZurmoHtml::tag('div', array('class' => 'dynamic-row'), $content);
            return ZurmoHtml::tag('li', array(), $content);
        }

        /**
         * @return string
         */
        protected function renderEmailAlertRowNumberLabel()
        {
            return ZurmoHtml::tag('span', array('class' => 'dynamic-row-number-label'),
                ($this->rowNumber + 1) . '.');
        }

        protected function renderEmailAlertContent()
        {
            //todo: still call it attributesContainer??
            $params            = array('inputPrefix' => $this->inputPrefixData);
            $content           = '<div class="attributesContainer">';
            //todo: move EmailTemplatesForWorkflowStaticDropDownElement to emailTemplates module when ready.
            $element           = new EmailTemplatesForWorkflowStaticDropDownElement($this->model, 'emailTemplateId',
                                 $this->form, $params);
            $innerContent      = '<table><colgroup><col class="col-0"><col class="col-1">' .
                                 '</colgroup><tr>' . $element->render() . '</tr>';
            $element           = new EmailAlertSendAfterDurationStaticDropDownElement(
                                 $this->model, 'sendAfterDurationSeconds', $this->form, $params);
            $innerContent     .= '<tr>' . $element->render() . '</tr>';
            $element           = new EmailAlertSendFromTypeStaticDropDownElement(
                                 $this->model, 'sendFromType', $this->form, $params);
            $innerContent     .= '<tr>' . $element->render() . '</tr>';
            $element           = new TextElement(
                                 $this->model, 'sendFromName', $this->form, $params);
            $innerContent     .= '<tr>' . $element->render() . '</tr>';
            $element           = new TextElement(
                                 $this->model, 'sendFromAddress', $this->form, $params);
            $innerContent     .= '<tr>' . $element->render() . '</tr>';
            $innerContent     .= '</table>';
            $content          .= ZurmoHtml::tag('div', array('class' => 'panel'), $innerContent);
            $content          .= '</div>';
            $content          .= $this->renderRecipientsContent();
            return $content;
        }

        protected function renderRecipientsContent()
        {
            $content  = '<div class="' . self::RECIPIENTS_CONTAINER_CLASS_NAME . '">';
            $content .= $this->renderRecipientsContentAndWrapper();
            $content .= $this->renderRecipientSelectorContentAndWrapper();
            $content .= $this->renderHiddenRecipientsInputForValidationContent();
            $content .= '</div>';
            return $content;
        }

        /**
         * @return string
         */
        protected function renderHiddenRecipientsInputForValidationContent()
        {
            $hiddenInputId       = Element::resolveInputIdPrefixIntoString(array_merge($this->inputPrefixData, array('recipientsValidation')));
            $hiddenInputName     = Element::resolveInputNamePrefixIntoString(array_merge($this->inputPrefixData, array('recipientsValidation')));
            $idInputHtmlOptions  = array('id' => $hiddenInputId);
            $id                  = Element::resolveInputIdPrefixIntoString(array_merge($this->inputPrefixData, array('recipientsValidation')));

            $content             = ZurmoHtml::hiddenField($hiddenInputName, null,
                                   $idInputHtmlOptions);
            $content            .= $this->form->error($this->model, 'recipientsValidation',
                                   array('inputID' => $hiddenInputId), true, true, $id);
            return $content;
        }

        protected function renderRecipientSelectorContentAndWrapper()
        {
            $content     = ZurmoHtml::tag('h2', array(), Zurmo::t('WorkflowsModule', 'Recipients'));
            $htmlOptions = array('id' => $this->resolveAddRecipientId(), 'class' => self::ADD_RECIPIENT_CLASS_NAME);
            $content     = ZurmoHtml::dropDownList(self::ADD_RECIPIENT_TYPE_NAME, null,
                           self::resolveRecipientTypeDataAndLabels(), $htmlOptions);
            return         ZurmoHtml::tag('div', array('class' => 'email-alert-recipient-type-selector-container'), $content);
        }

        protected function resolveAddRecipientId()
        {
            self::ADD_RECIPIENT_TYPE_NAME . '_' . $this->rowNumber;
        }

        protected function renderRecipientsContentAndWrapper()
        {
            $rowCount                    = 0;
            $items                       = $this->getRecipientItemsContent($rowCount);
            $itemsContent                = $this->getNonSortableListContent($items);
            $idInputHtmlOptions          = array('id'    => $this->getRecipientsRowCounterInputId($this->resolveRecipientsPrefix()),
                                                 'class' => self::RECIPIENTS_ROW_COUNTER_CLASS_NAME);
            $hiddenInputName             = $this->resolveRecipientsPrefix() . 'RowCounter';
            $recipientsContent           = ZurmoHtml::tag('div',
                                           array('class' => self::EMAIL_ALERT_RECIPIENTS_ROW_CLASS_NAME), $itemsContent);
            $content                     = ZurmoHtml::hiddenField($hiddenInputName, $rowCount, $idInputHtmlOptions);
            $content                    .= ZurmoHtml::tag('div', array(), $content . $recipientsContent);
            return $content;
        }

        protected function resolveRecipientsPrefix()
        {
            return EmailAlertForWorkflowForm::TYPE_EMAIL_ALERT_RECIPIENTS . $this->rowNumber;
        }

        /**
         * @param int $rowCount
         * @return array|string
         */
        protected function getRecipientItemsContent(& $rowCount)
        {
            return $this->renderRecipients($rowCount, $this->model->getEmailAlertRecipients());
        }

        protected function renderRecipients(& $rowCount, $recipients)
        {
            assert('is_int($rowCount)');
            assert('is_array($recipients)');
            $items = array();
            foreach($recipients as $recipient)
            {
                $inputPrefixData  = array_merge($this->inputPrefixData, array(
                                    EmailAlertForWorkflowForm::TYPE_EMAIL_ALERT_RECIPIENTS, (int)$rowCount));
                $adapter          = new WorkflowEmailAlertRecipientToElementAdapter($recipient, $this->form,
                                    $recipient->type, $inputPrefixData);
                $view             = new EmailAlertRecipientRowForWorkflowComponentView($adapter, $rowCount, $inputPrefixData);
                $view->addWrapper = false;
                $items[]          = array('content' => $view->render());
                $rowCount ++;
            }
            return $items;
        }

        //todo: getNonSortableListContent and getSortableListContent need to be moved so we can call them statically ? then also for reporting too.
        /**
         * @param array $items
         * @return string
         */
        protected function getNonSortableListContent(Array $items)
        {
            $content = null;
            foreach($items as $item)
            {
                $content .= ZurmoHtml::tag('li', array('class' => 'dynamic-sub-row'), $item['content']);
            }
            return ZurmoHtml::tag('ul', array(), $content);
        }

        /**
         * @param string $componentType
         * @return string
         */
        protected function getRecipientsRowCounterInputId($prefix)
        {
            assert('is_string($prefix)');
            return $prefix . 'RowCounter';
        }

        protected function registerScripts()
        {
            $this->registerSendFromTypeChangeScript();
            $this->registerAddRecipientScript();
        }

        protected function registerSendFromTypeChangeScript()
        {
            $inputPrefixData          = $this->inputPrefixData;
            $sendFromTypeSelectId     = EmailAlertSendFromTypeStaticDropDownElement::
                                        resolveInputIdPrefixIntoString(array_merge($inputPrefixData, array('sendFromType')));
            $sendFromNameId           = TextElement::resolveInputIdPrefixIntoString(
                                        array_merge($inputPrefixData, array('sendFromName')));
            $sendFromAddressId        = TextElement::resolveInputIdPrefixIntoString(
                                        array_merge($inputPrefixData, array('sendFromAddress')));
            Yii::app()->clientScript->registerScript('emailAlertSendFromTypeHelper' . $sendFromTypeSelectId, "
                if($('#" . $sendFromTypeSelectId . "').val() == '" . EmailAlertForWorkflowForm::SEND_FROM_TYPE_DEFAULT . "')
                {
                    $('#" . $sendFromNameId . "').parentsUntil('tr').parent().hide();
                    $('#" . $sendFromAddressId . "').parentsUntil('tr').parent().hide();
                }
                $('#" . $sendFromTypeSelectId . "').change( function()
                    {
                        if($(this).val() == '" . EmailAlertForWorkflowForm::SEND_FROM_TYPE_CUSTOM . "')
                        {
                    $('#" . $sendFromNameId . "').parentsUntil('tr').parent().show();
                    $('#" . $sendFromAddressId . "').parentsUntil('tr').parent().show();
                        }
                        else
                        {
                            $('#" . $sendFromNameId . "').val('');
                            $('#" . $sendFromNameId . "').parentsUntil('tr').parent().hide();
                            $('#" . $sendFromAddressId . "').val('');
                            $('#" . $sendFromAddressId . "').parentsUntil('tr').parent().hide();
                        }
                    }
                );
            ");
        }

        protected function registerAddRecipientScript()
        {
            $moduleClassNameId = $this->workflowWizardFormClassName . '[moduleClassName]';
            $url               = Yii::app()->createUrl('workflows/default/addEmailAlertRecipient',
                                 array_merge($_GET, array('type' => $this->model->getWorkflowType())));
            // Begin Not Coding Standard
            $ajaxSubmitScript  = ZurmoHtml::ajax(array(
                'type'    => 'GET',
                'data'    => 'js:\'recipientType=\' + $(this).val() + ' .
                             '\'&moduleClassName=\' + $("input:radio[name=\"' . $moduleClassNameId . '\"]:checked").val() + ' .
                             '\'&rowNumber=\' + ($("#' . $this->emailAlertsRowCounterInputId . '").val() - 1) + ' .
                             '\'&recipientRowNumber=\' +
                             $(this).parentsUntil(".' . self::RECIPIENTS_CONTAINER_CLASS_NAME . '").parent().find(".' . self::RECIPIENTS_ROW_COUNTER_CLASS_NAME . '").val()',
                'url'     =>  $url,
                'success' => 'js:function(data){
                    existingRowNumber = parseInt(triggeredObject.parentsUntil(".' . self::RECIPIENTS_CONTAINER_CLASS_NAME . '").parent().
                    find(".' . self::RECIPIENTS_ROW_COUNTER_CLASS_NAME . '").val());
                    triggeredObject.parentsUntil(".' . self::RECIPIENTS_CONTAINER_CLASS_NAME . '").parent().
                    find(".' . self::RECIPIENTS_ROW_COUNTER_CLASS_NAME . '")
                    .val(existingRowNumber + 1);
                    triggeredObject.parentsUntil(".' . self::RECIPIENTS_CONTAINER_CLASS_NAME . '").parent()
                    .find(".' . self::EMAIL_ALERT_RECIPIENTS_ROW_CLASS_NAME . '").find("ul").append(data);
                    rebuildWorkflowEmailAlertRecipientRowNumbers(triggeredObject.
                    parentsUntil(".' . self::RECIPIENTS_CONTAINER_CLASS_NAME . '").parent()
                    .find(".' . self::EMAIL_ALERT_RECIPIENTS_ROW_CLASS_NAME . '"));
                    triggeredObject.val("");
                }',
            ));
            $script = "$('." . self::ADD_RECIPIENT_CLASS_NAME . "').unbind('change');
                       $('." . self::ADD_RECIPIENT_CLASS_NAME . "').bind('change', function()
                        {
                            if ($(this).val() != '')
                            {
                                var triggeredObject = $(this);
                                $ajaxSubmitScript
                            }
                        });";
            // End Not Coding Standard
            Yii::app()->clientScript->registerScript('workflowAddEmailAlertRecipientScript', $script);
        }
    }
?>