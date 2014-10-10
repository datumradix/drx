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
     * Component for working with outbound and inbound email transport
     */
    class EmailHelper extends ZurmoBaseEmailHelper
    {
        const OUTBOUND_TYPE_SMTP = 'smtp';

        /**
         * Currently supports smtp.
         * @var string
         */
        public $outboundType = self::OUTBOUND_TYPE_SMTP;

        /**
         * Outbound mail server host name. Example mail.someplace.com
         * @var string
         */
        public $outboundHost;

        /**
         * Outbound mail server port number. Default to 25, but it can be set to something different.
         * @var integer
         */
        public $outboundPort = 25;

        /**
         * Outbound mail server username. Not always required, depends on the setup.
         * @var string
         */
        public $outboundUsername;

        /**
         * Outbound mail server password. Not always required, depends on the setup.
         * @var string
         */
        public $outboundPassword;

        /**
         * Outbound mail server security. Options: null, 'ssl', 'tls'
         * @var string
         */
        public $outboundSecurity;

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
         * Name of the html converter to use for outgoing html emails
         * null to disable
         * @var string
         */
        public $htmlConverter   = null;

        /**
         * Contains array of settings to load during initialization from the configuration table.
         * @see loadOutboundSettings
         * @var array
         */
        protected static $settingsToLoad = array(
            'outboundType',
            'outboundHost',
            'outboundPort',
            'outboundUsername',
            'outboundPassword',
            'outboundSecurity'
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
            $this->loadOutboundSettings();
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

        protected function loadOutboundSettings()
        {
            $settings = self::getOutboundSettings();
            foreach ($settings as $keyName => $keyValue)
            {
                $this->$keyName = $keyValue;
            }
        }

        /**
         * Load user's outbound settings from user's email account or the system settings
         * @param User   $user
         * @param string $name  EmailAccount name or null for default name
         */
        public function loadOutboundSettingsFromUserEmailAccount(User $user, $name = null)
        {
            $userEmailAccount = EmailAccount::getByUserAndName($user, $name);
            if ($userEmailAccount->useCustomOutboundSettings)
            {
                $settingsToLoad = array_merge($this->settingsToLoad, array('fromName', 'fromAddress'));
                foreach ($settingsToLoad as $keyName)
                {
                    if ($keyName == 'outboundPassword')
                    {
                        $keyValue = ZurmoPasswordSecurityUtil::decrypt($userEmailAccount->$keyName);
                        $this->$keyName = $keyValue;
                    }
                    else
                    {
                        $this->$keyName = $userEmailAccount->$keyName;
                    }
                }
            }
            else
            {
                $this->loadOutboundSettings();
                $this->fromName = strval($user);
                $this->fromAddress = $this->resolveFromAddressByUser($user);
            }
        }

        /**
         * Set outbound settings into the database.
         */
        public function setOutboundSettings()
        {
            foreach ($this->settingsToLoad as $keyName)
            {
                if ($keyName == 'outboundPassword')
                {
                    $password = ZurmoPasswordSecurityUtil::encrypt($this->$keyName);
                    ZurmoConfigurationUtil::setByModuleName('EmailMessagesModule', $keyName, $password);
                }
                else
                {
                    ZurmoConfigurationUtil::setByModuleName('EmailMessagesModule', $keyName, $this->$keyName);
                }
            }
        }

        /**
         * Use this method to send immediately, instead of putting an email in a queue to be processed by a scheduled
         * job.
         * @param EmailMessage $emailMessage
         * @throws NotSupportedException - if the emailMessage does not properly save.
         * @throws FailedToSaveModelException
         * @return null
         */
        public function sendImmediately(EmailMessage $emailMessage)
        {
            if ($emailMessage->folder->type == EmailFolder::TYPE_SENT)
            {
                throw new NotSupportedException();
            }
            $mailerFactory  = new ZurmoMailerFactory($emailMessage);
            $mailer         = $mailerFactory->resolveMailer();
            $mailer->sendEmail();
        }

        /**
         * Prepare message content.
         * @param EmailMessage $emailMessage
         * @return string
         */
        public static function prepareMessageContent(EmailMessage $emailMessage)
        {
            $messageContent  = null;
            if (!($emailMessage->hasErrors() || $emailMessage->hasSendError()))
            {
                $messageContent .= Zurmo::t('EmailMessagesModule', 'Message successfully sent') . "\n";
            }
            else
            {
                $messageContent .= Zurmo::t('EmailMessagesModule', 'Message failed to send') . "\n";
                if ($emailMessage->hasSendError())
                {
                    $messageContent .= $emailMessage->error     . "\n";
                }
                else
                {
                    //todo: refactor to use ZurmoHtml::errorSummary after this supports that method
                    //todo: supports nested messages better.
                    $errors = $emailMessage->getErrors();
                    foreach ($errors as $attributeNameWithErrors)
                    {
                        foreach ($attributeNameWithErrors as $attributeError)
                        {
                            if (is_array($attributeError))
                            {
                                foreach ($attributeError as $nestedAttributeError)
                                {
                                    $messageContent .= reset($nestedAttributeError) . "\n";
                                }
                            }
                            else
                            {
                                $messageContent .= reset($attributeError) . "\n";
                            }
                        }
                    }
                }
            }
            return $messageContent;
        }

        /**
         * Get outbound settings.
         * @return array
         */
        public static function getOutboundSettings()
        {
            $settings = array();
            foreach (static::$settingsToLoad as $keyName)
            {
                if ($keyName == 'outboundPassword')
                {
                    $encryptedKeyValue = ZurmoConfigurationUtil::getByModuleName('EmailMessagesModule', $keyName);
                    if ($encryptedKeyValue !== '' && $encryptedKeyValue !== null)
                    {
                        $keyValue = ZurmoPasswordSecurityUtil::decrypt($encryptedKeyValue);
                    }
                    else
                    {
                        $keyValue = null;
                    }
                }
                elseif ($keyName == 'outboundType')
                {
                    $keyValue = ZurmoConfigurationUtil::getByModuleName('EmailMessagesModule', 'outboundType');
                    if($keyValue == null)
                    {
                        $keyValue = self::OUTBOUND_TYPE_SMTP;
                    }
                }
                else
                {
                    $keyValue = ZurmoConfigurationUtil::getByModuleName('EmailMessagesModule', $keyName);
                }
                if (null !== $keyValue)
                {
                    $settings[$keyName] = $keyValue;
                }
                else
                {
                    $settings[$keyName] = null;
                }
            }
            return $settings;
        }
    }
?>