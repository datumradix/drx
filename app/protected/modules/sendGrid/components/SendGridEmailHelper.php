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
     * Helper class consisting of functions related to sending emails using sendgrid.
     */
    class SendGridEmailHelper extends ZurmoBaseEmailHelper
    {
        /**
         * API username.
         * @var string
         */
        public $apiUsername;

        /**
         * API password.
         * @var string
         */
        public $apiPassword;

        /**
         * Name to use in the email sent
         * @var string
         */
        public $fromName;

        /**
         * Address to use in the email sent
         * @var string
         */
        public $fromAddress;

        /**
         * Event webhook url.
         * @var string
         */
        public $eventWebhookUrl;
        /**
         * Event webhook url.
         * @var string
         */
        public $eventWebhookFilePath;

        /**
         * Contains array of settings to load during initialization from the configuration table.
         * @see loadApiSettings
         * @var array
         */
        protected $settingsToLoad = array(
            'apiUsername',
            'apiPassword',
            'eventWebhookUrl',
            'eventWebhookFilePath'
        );

        /**
         * Fallback from address to use for sending out notifications.
         * @var string
         */
        public $defaultFromAddress;

        /**
         * Utilized when sending a test email nightly to check the status of the smtp server
         * @var string
         */
        public $defaultTestToAddress;

        /**
         * Called once per page load, will load up outbound settings from the database if available.
         * (non-PHPdoc)
         * @see CApplicationComponent::init()
         */
        public function init()
        {
            $this->loadApiSettings();
            $this->loadDefaultFromAndToAddresses();
        }

        /**
         * Used to load defaultFromAddress and defaultTestToAddress
         */
        public function loadDefaultFromAndToAddresses()
        {
            $this->defaultFromAddress   = static::resolveAndGetDefaultFromAddress();
            $this->defaultTestToAddress = static::resolveAndGetDefaultTestToAddress();
        }

        /**
         * Loads api settings.
         * @return void
         */
        protected function loadApiSettings()
        {
            foreach ($this->settingsToLoad as $keyName)
            {
                if ($keyName == 'apiPassword')
                {
                    $encryptedKeyValue = ZurmoConfigurationUtil::getByModuleName('SendGridModule', $keyName);
                    if ($encryptedKeyValue !== '' && $encryptedKeyValue !== null)
                    {
                        $keyValue = ZurmoPasswordSecurityUtil::decrypt($encryptedKeyValue);
                    }
                    else
                    {
                        $keyValue = null;
                    }
                }
                else
                {
                    $keyValue = ZurmoConfigurationUtil::getByModuleName('SendGridModule', $keyName);
                }
                if (null !== $keyValue)
                {
                    $this->$keyName = $keyValue;
                }
            }
        }

        /**
         * Set api settings into the database.
         */
        public function setApiSettings()
        {
            foreach ($this->settingsToLoad as $keyName)
            {
                if ($keyName == 'apiPassword')
                {
                    $password = ZurmoPasswordSecurityUtil::encrypt($this->$keyName);
                    ZurmoConfigurationUtil::setByModuleName('SendGridModule', $keyName, $password);
                }
                else
                {
                    ZurmoConfigurationUtil::setByModuleName('SendGridModule', $keyName, $this->$keyName);
                }
            }
        }

        /**
         * Resolve recipient address by type.
         * @param EmailMessage $emailMessage
         * @return array
         */
        public static function resolveRecipientAddressesByType($emailMessage)
        {
            $toAddresses    = array();
            $ccAddresses    = array();
            $bccAddresses   = array();
            foreach ($emailMessage->recipients as $recipient)
            {
                if($recipient->type == EmailMessageRecipient::TYPE_TO)
                {
                    $toAddresses[$recipient->toAddress] = $recipient->toName;
                }
                if($recipient->type == EmailMessageRecipient::TYPE_CC)
                {
                    $ccAddresses[$recipient->toAddress] = $recipient->toName;
                }
                if($recipient->type == EmailMessageRecipient::TYPE_BCC)
                {
                    $bccAddresses[$recipient->toAddress] = $recipient->toName;
                }
            }
            return array($toAddresses, $ccAddresses, $bccAddresses);
        }
    }
?>