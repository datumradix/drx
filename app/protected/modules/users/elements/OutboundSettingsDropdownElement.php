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

    class OutboundSettingsDropdownElement extends StaticDropDownFormElement
    {
        protected function renderControlEditable()
        {
            $dropdown      = parent::renderControlEditable();
            $sendTestEmail = new SendATestEmailToElement($this->model, 'aTestToAddress', $this->form);
            $sendTestEmail->editableTemplate = '{label}{content}{error}';
            $content       = ZurmoHtml::tag('div', array('class' => 'beforeToolTip'), $dropdown);
            $content      .= $this->renderPersonalSmtpSettings();
            $content      .= $this->renderPersonalSendGridSettings();
            $this->renderScripts();
            return $content;
        }

        public function renderEditableTextField($model, $form, $attribute, $isPassword = false)
        {
            $id          = $this->getEditableInputId($attribute);
            $htmlOptions = array(
                'name'  => $this->getEditableInputName($attribute),
                'id'    => $id,
            );
            $label       = $form->labelEx  ($model, $attribute, array('for'   => $id));
            if (!$isPassword)
            {
                $textField = $form->textField($model, $attribute, $htmlOptions);
            }
            else
            {
                $textField = $form->passwordField($model, $attribute, $htmlOptions);
            }
            $error       = $form->error    ($model, $attribute);
            return '<div>' . $label . $textField . $error . '</div>';
        }

        protected static function renderToolTipContent()
        {
            $title       = Zurmo::t('UsersModule', 'Select the option to send out email');
            $content     = '<span id="custom-outbound-settings-tooltip" class="tooltip"  title="' . $title . '">?</span>';
            $qtip = new ZurmoTip(array('options' => array('position' => array('my' => 'bottom right', 'at' => 'top left'))));
            $qtip->addQTip("#custom-outbound-settings-tooltip");
            return $content;
        }

        protected function renderLabel()
        {
            $label = Zurmo::t('UsersModule', 'Select Outbound Settings');
            if ($this->form === null)
            {
                return $this->getFormattedAttributeLabel();
            }
            $content  = ZurmoHtml::label($label, $this->getEditableInputId());
            $content .= self::renderToolTipContent();
            return $content;
        }

        protected function renderScripts()
        {
            $dropdownId = $this->getIdForSelectInput();
            // Begin Not Coding Standard
            Yii::app()->clientScript->registerScript('userEmailConfigurationOutbound', "
                    $('#{$dropdownId}').change(function(){
                        var value = $(this).val();
                        if(value == 1)
                        {
                            $('#smtp-settings').hide();
                            $('#sendgrid-settings').hide();
                        }
                        if(value == 2)
                        {
                            $('#smtp-settings').show();
                            $('#sendgrid-settings').hide();
                        }
                        if(value == 3)
                        {
                            $('#smtp-settings').hide();
                            $('#sendgrid-settings').show();
                        }
                    });
                ");
            // End Not Coding Standard
        }

        protected function getDropDownArray()
        {
            return array(
                EmailMessageUtil::OUTBOUND_GLOBAL_SETTINGS         => Zurmo::t('EmailMessagesModule', 'Global'),
                EmailMessageUtil::OUTBOUND_PERSONAL_SMTP_SETTINGS  => Zurmo::t('EmailMessagesModule', 'Personal SMTP'),
                EmailMessageUtil::OUTBOUND_PERSONAL_SENDGRID_SETTINGS  => Zurmo::t('EmailMessagesModule', 'Personal SendGrid'),
            );
        }

        /**
         * Renders personal smtp settings fields.
         * @param string $style
         * @return string
         */
        protected function renderPersonalSmtpSettings()
        {
            $sendTestEmail = new SendATestEmailToElement($this->model, 'aTestToAddress', $this->form);
            $sendTestEmail->editableTemplate = '{label}{content}{error}';
            $settings      = $this->renderEditableTextField($this->model, $this->form, 'outboundHost');
            $settings     .= $this->renderEditableTextField($this->model, $this->form, 'outboundPort');
            $settings     .= $this->renderEditableTextField($this->model, $this->form, 'outboundUsername');
            $settings     .= $this->renderEditableTextField($this->model, $this->form, 'outboundPassword', true);
            $settings     .= $this->renderEditableTextField($this->model, $this->form, 'outboundSecurity');
            $settings     .= $sendTestEmail->renderEditable();
            $selectedValue = $this->model->{$this->attribute};
            if($selectedValue == EmailMessageUtil::OUTBOUND_PERSONAL_SMTP_SETTINGS)
            {
                $style = 'display:block;';
            }
            else
            {
                $style = 'display:none;';
            }
            return ZurmoHtml::tag('div', array('class' => 'outbound-settings', 'id' => 'smtp-settings', 'style' => $style),
                                         $settings);
        }

        /**
         * Renders personal smtp settings fields.
         * @param string $style
         * @return string
         */
        protected function renderPersonalSendGridSettings()
        {
            $model  = $this->model->userSendGridConfigurationForm;
            $sendTestEmail = new SendATestEmailToElement($model, 'aTestToAddress', $this->form);
            $sendTestEmail->editableTemplate = '{label}{content}{error}';
            $settings      = $this->renderEventWebhookUrl($model);
            $settings     .= $this->renderSendGridEditableTextField($model, $this->form, 'apiUsername');
            $settings     .= $this->renderSendGridEditableTextField($model, $this->form, 'apiPassword', true);
            $settings     .= $sendTestEmail->renderEditable();
            $selectedValue = $this->model->{$this->attribute};
            if($selectedValue == EmailMessageUtil::OUTBOUND_PERSONAL_SENDGRID_SETTINGS)
            {
                $style = 'display:block;';
            }
            else
            {
                $style = 'display:none;';
            }
            return ZurmoHtml::tag('div', array('class' => 'outbound-settings', 'id' => 'sendgrid-settings', 'style' => $style),
                                         $settings);
        }

        /**
         * Render sendgrid editable text field
         * @param CModel $model
         * @param CFormModel $form
         * @param string $attribute
         * @param boolean $isPassword
         * @return string
         */
        public function renderSendGridEditableTextField($model, $form, $attribute, $isPassword = false)
        {
            $id         = ZurmoHtml::activeId($model, $attribute);
            $name       = ZurmoHtml::activeName($model, $attribute);
            $htmlOptions = array(
                'name'  => $name,
                'id'    => $id,
            );
            $label       = $form->labelEx($model, $attribute, array('for'   => $id));
            if (!$isPassword)
            {
                $textField = $form->textField($model, $attribute, $htmlOptions);
            }
            else
            {
                $textField = $form->passwordField($model, $attribute, $htmlOptions);
            }
            $error       = $form->error    ($model, $attribute);
            return '<div>' . $label . $textField . $error . '</div>';
        }

        /**
         * Render event webhook url.
         * @param CModel $model
         * @return string
         */
        protected function renderEventWebhookUrl($model)
        {
            $baseUrl    = Yii::app()->createAbsoluteUrl('sendGrid/external/writeLog');
            $text       = SendGridUtil::renderEventWebHookUrlOnForm($model, 'apiUsername');
            SendGridUtil::registerEventWebhookUrlScript('UserSendGridConfigurationForm_apiUsername', $baseUrl);
            return $text;
        }
    }
?>