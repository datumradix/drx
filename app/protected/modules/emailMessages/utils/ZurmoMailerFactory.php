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
     * Resolve mailer required to send email.
     */
    class ZurmoMailerFactory
    {
        protected $emailMessage;

        protected $emailAccount;

        protected $sendGridEmailAccount;

        protected $sendGridPluginEnabled;

        /**
         * Class constructor.
         * @param EmailMessage $emailMessage
         */
        public function __construct(EmailMessage $emailMessage)
        {
            $this->emailMessage = $emailMessage;
            //Sendgrid enabled
            $this->sendGridPluginEnabled = (bool)ZurmoConfigurationUtil::getByModuleName('SendGridModule', 'enableSendgrid');
        }

        /**
         * Resolve mailer by email message.
         * @return \ZurmoSwiftMailer|\ZurmoSendGridMailer
         */
        public function resolveMailer()
        {
            $apiUser             = Yii::app()->sendGridEmailHelper->apiUsername;
            $apiPassword         = Yii::app()->sendGridEmailHelper->apiPassword;
            $user                = $this->emailMessage->owner;
            if($user != null)
            {
                $this->sendGridEmailAccount = SendGridEmailAccount::resolveAndGetByUserAndName($user, null, false);
                $this->emailAccount         = EmailAccount::resolveAndGetByUserAndName($user, null, false);
            }
            if($this->sendGridPluginEnabled && $user != null)
            {
                //Should user settings be used
                if($this->shouldSendGridUserSettingsBeUsed())
                {
                    return new ZurmoSendGridMailer($this->emailMessage, $this->sendGridEmailAccount);
                }
                else
                {
                    //Check for personal settings
                    if($this->shouldCustomUserSettingsBeUsed())
                    {
                        return new ZurmoSwiftMailer($this->emailMessage, $this->emailAccount);
                    }
                    else
                    {
                        if($apiUser != null && $apiPassword != null)
                        {
                            $this->updateMailerDetailsForEmailMessage('sendgrid', 'global');
                            return new ZurmoSendGridMailer($this->emailMessage, null);
                        }
                        else
                        {
                            $this->updateMailerDetailsForEmailMessage('smtp', 'global');
                            return new ZurmoSwiftMailer($this->emailMessage, null);
                        }
                    }
                }
            }
            elseif($user != null && $this->shouldCustomUserSettingsBeUsed() === true)
            {
                return new ZurmoSwiftMailer($this->emailMessage, $this->emailAccount);
            }
            elseif($this->sendGridPluginEnabled && $apiUser != null && $apiPassword != null)
            {
                $this->updateMailerDetailsForEmailMessage('sendgrid', 'global');
                return new ZurmoSendGridMailer($this->emailMessage, null);
            }
            else
            {
                $this->updateMailerDetailsForEmailMessage('smtp', 'global');
                return new ZurmoSwiftMailer($this->emailMessage, null);
            }
        }

        /**
         * Should sendgrid user settings be used to send email.
         * @return boolean
         */
        protected function shouldSendGridUserSettingsBeUsed()
        {
            if($this->sendGridEmailAccount == null)
            {
                return false;
            }
            $itemData       = EmailMessageUtil::getCampaignOrAutoresponderDataByEmailMessage($this->emailMessage);
            if($itemData != null)
            {
                $useAutoresponderOrCampaignOwnerMailSettings = (bool)ZurmoConfigurationUtil::getByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings');
                if($this->sendGridEmailAccount->apiUsername != ''
                    && $this->sendGridEmailAccount->apiPassword != ''
                        && $useAutoresponderOrCampaignOwnerMailSettings === true)
                {
                    $this->updateMailerDetailsForEmailMessage('sendgrid', 'personal');
                    return true;
                }
                return false;
            }
            elseif($this->sendGridEmailAccount->apiUsername != ''
                        && $this->sendGridEmailAccount->apiPassword != '')
            {
                $this->updateMailerDetailsForEmailMessage('sendgrid', 'personal');
                return true;
            }
            return false;
        }

        /**
         * Should sendgrid user settings be used to send email.
         * @return boolean
         */
        protected function shouldCustomUserSettingsBeUsed()
        {
            if($this->emailAccount == null)
            {
                return false;
            }
            $itemData       = EmailMessageUtil::getCampaignOrAutoresponderDataByEmailMessage($this->emailMessage);
            if($itemData != null)
            {
                $useAutoresponderOrCampaignOwnerMailSettings = (bool)ZurmoConfigurationUtil::getByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings');
                if((bool)$this->emailAccount->useCustomOutboundSettings === true && $this->emailAccount->outboundHost != ''
                        && $this->emailAccount->outboundUsername != '' && $this->emailAccount->outboundPassword != ''
                            && $useAutoresponderOrCampaignOwnerMailSettings === true)
                {
                    $this->updateMailerDetailsForEmailMessage('smtp', 'personal');
                    return true;
                }
                return false;
            }
            elseif((bool)$this->emailAccount->useCustomOutboundSettings === true && $this->emailAccount->outboundHost != ''
                        && $this->emailAccount->outboundUsername != '' && $this->emailAccount->outboundPassword != '')
            {
                $this->updateMailerDetailsForEmailMessage('smtp', 'personal');
                return true;
            }
            return false;
        }

        /**
         * Updates mailer details for email message
         * @param string $mailerType
         * @param string $mailerSettings
         * @return void
         */
        protected function updateMailerDetailsForEmailMessage($mailerType, $mailerSettings)
        {
            $emailMessage                   = $this->emailMessage;
            $emailMessage->mailerType       = $mailerType;
            $emailMessage->mailerSettings   = $mailerSettings;
            $emailMessage->save();
            $this->emailMessage = $emailMessage;
        }
    }