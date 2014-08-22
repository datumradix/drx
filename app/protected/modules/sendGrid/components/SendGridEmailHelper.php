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
    class SendGridEmailHelper extends CApplicationComponent
    {
        const WEBAPI = 'webapi';

        /**
         * WebAPI username.
         * @var string
         */
        public $apiUsername;

        /**
         * WebAPI password.
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
         * Contains array of settings to load during initialization from the configuration table.
         * @see loadApiSettings
         * @var array
         */
        protected $settingsToLoad = array(
            'apiUsername',
            'apiPassword'
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
            $this->defaultFromAddress   = Yii::app()->emailHelper->resolveAndGetDefaultFromAddress();
            $this->defaultTestToAddress = Yii::app()->emailHelper->resolveAndGetDefaultTestToAddress();
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
         * Load user's outbound settings from user's email account or the system settings
         * @param User   $user
         * @param string $name  EmailAccount name or null for default name
         */
        public function loadApiSettingsFromUserEmailAccount(User $user, $name = null)
        {
            $this->loadApiSettings();
            $this->fromName = strval($user);
            $this->fromAddress = Yii::app()->emailHelper->resolveFromAddressByUser($user);
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

        public static function sendMailUsingGlobalConfiguration(EmailMessage $emailMessage)
        {
            //Check for global sendgrid configuration
            $username = ZurmoConfigurationUtil::getByModuleName('SendGridModule', 'apiUsername');
            $password = ZurmoConfigurationUtil::getByModuleName('SendGridModule', 'apiPassword');
            if($username != '' && $password != '')
            {
                list($toAddresses, $ccAddresses, $bccAddresses) = self::resolveRecipientAddressesByType($emailMessage);
                //Send email using sendgrid global
                $emailHelper  = new SendGridEmailHelper();
                $mailer       = new ZurmoSendGridMailer($emailHelper, $emailMessage->sender, $toAddresses, $ccAddresses, $bccAddresses);
                $emailMessage = $mailer->send();
            }
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
                Yii::app()->jobQueue->add('ProcessSendGridOutboundEmail');
            }
            return $saved;
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
            EmailMessageUtil::processAndSendQueuedMessages($this, $count);
            return true;
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
            $from         = array('address' => $emailMessage->sender->fromAddress, 'name' => $emailMessage->sender->fromName);
            list($toAddresses, $ccAddresses, $bccAddresses) = self::resolveRecipientAddressesByType($emailMessage);
            $mailer       = new ZurmoSendGridMailer($this, $from, $toAddresses, $ccAddresses, $bccAddresses);
            $mailer->sendEmail($emailMessage);
            $saved        = $emailMessage->save();
            if (!$saved)
            {
                throw new FailedToSaveModelException();
            }
        }

        /**
         * Resolve recipient address by type.
         * @param EmailMessage $emailMessage
         * @return array
         */
        public static function resolveRecipientAddressesByType(EmailMessage $emailMessage)
        {
            $recipientTypeData = array();
            foreach ($emailMessage->recipients as $recipient)
            {
                $recipientTypeData[$recipient->type][] = array($recipient->toAddress => $recipient->toName);
            }
            $toAddresses    = ArrayUtil::getArrayValue($recipientTypeData, EmailMessageRecipient::TYPE_TO, array());
            $ccAddresses    = ArrayUtil::getArrayValue($recipientTypeData, EmailMessageRecipient::TYPE_CC, array());
            $bccAddresses   = ArrayUtil::getArrayValue($recipientTypeData, EmailMessageRecipient::TYPE_BCC, array());
            return array($toAddresses, $ccAddresses, $bccAddresses);
        }
    }
?>