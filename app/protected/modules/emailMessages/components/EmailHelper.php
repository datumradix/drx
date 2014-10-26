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
            foreach (static::$settingsToLoad as $keyName)
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

        /**
         * Process message as failure.
         * @param EmailMessage $emailMessage
         * @param bool $useSQL
         */
        public function processMessageAsFailure(EmailMessage $emailMessage, $useSQL = false)
        {
            $folder = EmailFolder::getByBoxAndType($emailMessage->folder->emailBox, EmailFolder::TYPE_OUTBOX_FAILURE);
            static::updateFolderForEmailMessage($emailMessage, $useSQL, $folder);
        }

        /**
         * Call this method to process all email Messages in the queue. This is typically called by a scheduled job
         * or cron.  This will process all emails in a TYPE_OUTBOX folder or TYPE_OUTBOX_ERROR folder. If the message
         * has already been sent 3 times then it will be moved to a failure folder.
         * @param bool|null $count
         * @return bool number of queued messages to be sent
         */
        public function sendQueued($count = null)
        {
            return EmailMessageUtil::sendQueued($this, $count);
        }

        /**
         * Send an email message. This will queue up the email to be sent by the queue sending process. If you want to
         * send immediately, consider using @sendImmediately
         * @param EmailMessage $emailMessage
         * @param bool $useSQL
         * @param bool $validate
         * @return bool|void
         * @throws FailedToSaveModelException
         * @throws NotFoundException
         * @throws NotSupportedException
         */
        public function send(EmailMessage & $emailMessage, $useSQL = false, $validate = true)
        {
            static::isValidFolderType($emailMessage);
            $folder     = EmailFolder::getByBoxAndType($emailMessage->folder->emailBox, EmailFolder::TYPE_OUTBOX);
            $saved      = static::updateFolderForEmailMessage($emailMessage, $useSQL, $folder, $validate);
            if ($saved)
            {
                Yii::app()->jobQueue->add('ProcessOutboundEmail');
            }
            return $saved;
        }

        /**
         * @return integer count of how many emails are queued to go.  This means they are in either the TYPE_OUTBOX
         * folder or the TYPE_OUTBOX_ERROR folder.
         */
        public static function getQueuedCount()
        {
            return count(EmailMessage::getAllByFolderType(EmailFolder::TYPE_OUTBOX)) +
                   count(EmailMessage::getAllByFolderType(EmailFolder::TYPE_OUTBOX_ERROR));
        }

        /**
         * Verify if folder type of an emailMessage is valid or not.
         * @param EmailMessage $emailMessage
         * @throws NotSupportedException
         */
        public static function isValidFolderType(EmailMessage $emailMessage)
        {
            if ($emailMessage->folder->type == EmailFolder::TYPE_OUTBOX ||
                $emailMessage->folder->type == EmailFolder::TYPE_SENT ||
                $emailMessage->folder->type == EmailFolder::TYPE_OUTBOX_ERROR ||
                $emailMessage->folder->type == EmailFolder::TYPE_OUTBOX_FAILURE)
            {
                throw new NotSupportedException();
            }
        }

        /**
         * Update an email message's folder and save it
         * @param EmailMessage $emailMessage
         * @param $useSQL
         * @param EmailFolder $folder
         * @param bool $validate
         * @return bool|void
         * @throws FailedToSaveModelException
         */
        public static function updateFolderForEmailMessage(EmailMessage & $emailMessage, $useSQL,
                                                              EmailFolder $folder, $validate = true)
        {
            // we don't have syntax to support saving related records and other attributes for emailMessage, yet.
            $saved  = false;
            if ($useSQL && $emailMessage->id > 0)
            {
                $saved = static::updateFolderForEmailMessageWithSQL($emailMessage, $folder);
            }
            else
            {
                $saved = static::updateFolderForEmailMessageWithORM($emailMessage, $folder, $validate);
            }
            if (!$saved)
            {
                throw new FailedToSaveModelException();
            }
            return $saved;
        }

        /**
         * Update an email message's folder and save it using SQL
         * @param EmailMessage $emailMessage
         * @param EmailFolder $folder
         * @throws NotSupportedException
         */
        protected static function updateFolderForEmailMessageWithSQL(EmailMessage & $emailMessage, EmailFolder $folder)
        {
            // TODO: @Shoaibi/@Jason: Critical0: This fails CampaignItemsUtilTest.php:243
            $folderForeignKeyName   = RedBeanModel::getForeignKeyName('EmailMessage', 'folder');
            $tableName              = EmailMessage::getTableName();
            $sql                    = "UPDATE " . DatabaseCompatibilityUtil::quoteString($tableName);
            $sql                    .= " SET " . DatabaseCompatibilityUtil::quoteString($folderForeignKeyName);
            $sql                    .= " = " . $folder->id;
            $sql                    .= " WHERE " . DatabaseCompatibilityUtil::quoteString('id') . " = ". $emailMessage->id;
            $effectedRows           = ZurmoRedBean::exec($sql);
            if ($effectedRows == 1)
            {
                $emailMessageId = $emailMessage->id;
                $emailMessage->forgetAll();
                $emailMessage = EmailMessage::getById($emailMessageId);
                return true;
            }
            return false;
        }

        /**
         * Update an email message's folder and save it using ORM
         * @param EmailMessage $emailMessage
         * @param EmailFolder $folder
         * @param bool $validate
         */
        protected static function updateFolderForEmailMessageWithORM(EmailMessage & $emailMessage,
                                                                        EmailFolder $folder, $validate = true)
        {
            $emailMessage->folder = $folder;
            $saved = $emailMessage->save($validate);
            return $saved;
        }
    }
?>