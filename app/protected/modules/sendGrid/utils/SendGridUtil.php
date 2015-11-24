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

    class SendGridUtil
    {
        /**
         * Send Test Message
         * @param SendGridWebApiConfigurationForm $configurationForm
         * @param string $fromNameToSendMessagesFrom
         * @param string $fromAddressToSendMessagesFrom
         */
        public static function sendTestMessage($configurationForm,
                                               $fromNameToSendMessagesFrom = null,
                                               $fromAddressToSendMessagesFrom = null)
        {
            if ($configurationForm->aTestToAddress != null)
            {
                $sendGridEmailAccount         = new SendGridEmailAccount();
                $sendGridEmailAccount->apiUsername     = $configurationForm->username;
                $sendGridEmailAccount->apiPassword     = ZurmoPasswordSecurityUtil::encrypt($configurationForm->password);
                $isUser = false;
                if ($fromNameToSendMessagesFrom != null && $fromAddressToSendMessagesFrom != null)
                {
                    $isUser                 = true;
                    $from = array(
                        'name'      => $fromNameToSendMessagesFrom,
                        'address'   => $fromAddressToSendMessagesFrom
                    );
                }
                else
                {
                    $user                   = BaseControlUserConfigUtil::getUserToRunAs();
                    $userToSendMessagesFrom = User::getById((int)$user->id);
                    $from = array(
                        'name'      => Yii::app()->emailHelper->resolveFromNameForSystemUser($userToSendMessagesFrom),
                        'address'   => Yii::app()->emailHelper->resolveFromAddressByUser($userToSendMessagesFrom)
                    );
                }
                $emailMessage = EmailMessageHelper::processAndCreateTestEmailMessage($from, $configurationForm->aTestToAddress);
                $mailer       = new ZurmoSendGridMailer($emailMessage, $sendGridEmailAccount);
                $emailMessage = $mailer->sendTestEmail($isUser);
                $messageContent  = EmailHelper::prepareMessageContent($emailMessage);
            }
            else
            {
                $messageContent = Zurmo::t('EmailMessagesModule', 'A test email address must be entered before you can send a test email.') . "\n";
            }
            return $messageContent;
        }

        /**
         * Register event webhook url script.
         * @param string $id
         * @param string $baseUrl
         */
        public static function registerEventWebhookUrlScript($id, $baseUrl)
        {
            $script = "$('#{$id}').keyup(function()
                                         {
                                            var name = $(this).val();
                                            var url  = '{$baseUrl}?username=' + name;
                                            $('#eventWebhookUrl').html(url);
                                         });
                                        ";
            Yii::app()->clientScript->registerScript('eventWebhookUrlScript', $script);
        }

        /**
         * Render webhook url on form.
         * @param CModel $model
         * @param string $attribute
         * @param string $width
         * @return string
         */
        public static function renderEventWebHookUrlOnForm($model, $attribute, $width = '')
        {
            $url   = static::resolveUrl($model, $attribute);
            $url   = ZurmoHtml::tag('div', array('id' => 'eventWebhookUrl', 'style' => 'padding-top:5px;'), $url);
            $label = ZurmoHtml::label(Zurmo::t('SendGridModule', 'Event Webhook Url'), 'eventWebhookUrl');
            if (!empty($width))
            {
                $content = '<table class="form-fields"><tr><th width="' . $width . '">' . $label . '</th>'
                                                                        . '<td colspan="1">' . $url . '</td></tr></table>';
            }
            else
            {
                $content = '<table class="form-fields"><tr><th>' . $label . '</th></tr>'
                            . '<tr><td style="padding-left:0px;padding-bottom:0px;">' . $url . '</td></tr></table>';
            }
            return ZurmoHtml::tag('div', array('class' => 'panel'), $content);
        }

        /**
         * @param CModel $model
         * @param string $attribute
         * @return string
         */
        public static function resolveUrl($model, $attribute)
        {
            return Yii::app()->createAbsoluteUrl('sendGrid/external/writeLog', array('username' => $model->$attribute));
        }
    }
?>