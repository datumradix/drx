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
     Yii::import('ext.sendgrid.lib.*');
     Yii::import('ext.sendgrid.lib.SendGrid.*');
     Yii::import('ext.sendgrid.lib.Smtpapi.*');

    /**
     * Mailer class for Zurmo specific sendgrid functionality.
     */
    class ZurmoSendGridMailer extends Mailer
    {
        protected $emailHelper;

        protected $fromUser;

        protected $toAddresses;

        protected $ccAddresses;

        protected $bccAddresses;

        protected $fromUserEmailData;

        protected $emailAccount;

        protected $emailMessage;

        /**
         * Class constructor.
         * @param EmailMessage $emailMessage
         * @param mixed $emailAccount
         */
        public function __construct(EmailMessage $emailMessage, $emailAccount)
        {
            SendGrid::register_autoloader();
            Smtpapi::register_autoloader();
            $from = array('address' => $emailMessage->sender->fromAddress, 'name' => $emailMessage->sender->fromName);
            $this->fromUserEmailData = $from;
            $this->emailMessage      = $emailMessage;
            $this->resolveRecipientAddressesByType();
            $this->emailAccount = $emailAccount;
        }

        /**
         * Send a test email.
         * @param bool $isUser
         * @return EmailMessage
         */
        public function sendTestEmail($isUser = true)
        {
            $this->emailMessage->mailerType = 'sendgrid';
            if($isUser)
            {
                $this->emailMessage->mailerSettings = 'personal';
            }
            if ($this->emailMessage->validate())
            {
                $this->emailMessage->save();
                self::resolveRecipientAddressesByType();
                $this->sendEmail();
            }
            return $this->emailMessage;
        }

        /**
         * Send email.
         * @param EmailMessage $emailMessage
         */
        public function sendEmail()
        {
            $emailMessage   = $this->emailMessage;
            if($this->emailAccount == null)
            {
                $apiUser        = Yii::app()->sendGridEmailHelper->apiUsername;
                $apiPassword    = Yii::app()->sendGridEmailHelper->apiPassword;
            }
            else
            {
                $apiUser        = $this->emailAccount->apiUsername;
                $apiPassword    = ZurmoPasswordSecurityUtil::decrypt($this->emailAccount->apiPassword);
            }
            $itemData   = EmailMessageUtil::getCampaignOrAutoresponderDataByEmailMessage($this->emailMessage);
            $sendgrid   = new SendGrid($apiUser, $apiPassword, array("turn_off_ssl_verification" => true));
            $email      = new SendGrid\Email();
            $email->setFrom($this->fromUserEmailData['address'])->
                   setFromName($this->fromUserEmailData['name'])->
                   setSubject($emailMessage->subject)->
                   setText($emailMessage->content->textContent)->
                   setHtml($emailMessage->content->htmlContent)->
                   addHeader('X-Sent-Using', 'SendGrid-API')->
                   addHeader('X-Transport', 'web');
            //Check if campaign and if yes, associate to email.
            if($itemData != null)
            {
                list($itemId, $itemClass, $personId) = $itemData;
                $email->addUniqueArg("itemId", $itemId);
                $email->addUniqueArg("itemClass", $itemClass);
                $email->addUniqueArg("personId", $personId);
            }
            foreach($this->toAddresses as $emailAddress => $name)
            {
                $email->addTo($emailAddress, $name);
            }
            foreach($this->ccAddresses as $emailAddress => $name)
            {
                $email->addCc($emailAddress);
            }
            foreach($this->bccAddresses as $emailAddress => $name)
            {
                $email->addBcc($emailAddress);
            }
            //Attachments
            $attachmentsData = array();
            $tempAttachmentPath = Yii::app()->getRuntimePath() . DIRECTORY_SEPARATOR . 'emailAttachments';
            if(!file_exists($tempAttachmentPath))
            {
                mkdir($tempAttachmentPath);
            }
            if (!empty($emailMessage->files))
            {
                foreach ($emailMessage->files as $file)
                {
                    $fileName   = tempnam($tempAttachmentPath, 'zurmo_');
                    $fp         = fopen($fileName, 'wb');
                    fwrite($fp, $file->fileContent->content);
                    fclose($fp);
                    $email->addAttachment($fileName, $file->name);
                    $attachmentsData[] = $fileName;
                }
            }
            $emailMessage->sendAttempts = $emailMessage->sendAttempts + 1;
            $response = $sendgrid->send($email);
            if($response->message == 'success')
            {
                //Here we need to check if
                $emailMessage->error        = null;
                $emailMessage->folder       = EmailFolder::getByBoxAndType($emailMessage->folder->emailBox, EmailFolder::TYPE_SENT);
                $emailMessage->sentDateTime = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            }
            //In case message is not delivered but there is no api related error than also flow would not enter here.
            elseif($response->message == 'error')
            {
                $content = Zurmo::t('EmailMessagesModule', 'Response from Server') . "\n";
                foreach($response->errors as $error)
                {
                    $content .= $error;
                }
                $emailMessageSendError = new EmailMessageSendError();
                $data                  = array();
                $data['message']                       = $content;
                $emailMessageSendError->serializedData = serialize($data);
                $emailMessage->folder                  = EmailFolder::getByBoxAndType($emailMessage->folder->emailBox,
                                                                                      EmailFolder::TYPE_OUTBOX_ERROR);
                $emailMessage->error                   = $emailMessageSendError;
            }
            if(count($attachmentsData) > 0)
            {
                foreach($attachmentsData as $path)
                {
                    unlink($path);
                }
            }
            $saved = $emailMessage->save();
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
        public function resolveRecipientAddressesByType()
        {
            $emailMessage   = $this->emailMessage;
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
            $this->toAddresses = $toAddresses;
            $this->ccAddresses = $ccAddresses;
            $this->bccAddresses = $bccAddresses;
        }

        /**
         * Sets from user.
         * @param User $userToSendMessagesFrom
         */
        public function setFromUser($userToSendMessagesFrom)
        {
            if(is_array($userToSendMessagesFrom))
            {
                $this->fromUserEmailData = $userToSendMessagesFrom;
            }
            elseif(is_object($userToSendMessagesFrom) && $userToSendMessagesFrom instanceof User)
            {
                $this->fromUser    = $userToSendMessagesFrom;
            }
        }

        public function setToAddresses($toAddresses)
        {
            $this->toAddresses = $toAddresses;
        }

        public function setCcAddresses($ccAddresses)
        {
            $this->ccAddresses = $ccAddresses;
        }

        public function setBccAddresses($bccAddresses)
        {
            $this->bccAddresses = $bccAddresses;
        }

        public function getEmailAccount()
        {
            return $this->emailAccount;
        }

        public function getEmailMessage()
        {
            return $this->emailMessage;
        }
    }
?>