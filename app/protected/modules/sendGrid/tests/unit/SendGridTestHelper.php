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

    class SendGridTestHelper
    {
        public static function isSetSendGridAccountTestConfiguration()
        {
            $isSetSendGridEmailAccountsTestConfiguration = false;

            if (isset(Yii::app()->params['emailTestAccounts']))
            {
                $sendGridGlobalSettings     = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings'];
                $sendGridUserSettings       = Yii::app()->params['emailTestAccounts']['sendGridUserSettings'];
                if ( $sendGridGlobalSettings['host'] != '' &&
                     $sendGridUserSettings['host'] != '' && $sendGridUserSettings['apiUsername'] != '' &&
                     $sendGridUserSettings['apiPassword'] != '' && $sendGridGlobalSettings['apiUsername'] != '' &&
                     $sendGridGlobalSettings['apiPassword'] != '' &&
                     Yii::app()->params['emailTestAccounts']['testEmailAddress'] != ''
                )
                {
                    $isSetSendGridEmailAccountsTestConfiguration = true;
                }
            }
            return $isSetSendGridEmailAccountsTestConfiguration;
        }

        public static function createDraftSendGridSystemEmail($subject, User $owner)
        {
            $emailMessage              = new EmailMessage();
            $emailMessage->owner       = $owner;
            $emailMessage->subject     = $subject;

            //Set sender, and recipient, and content
            $emailContent              = new EmailMessageContent();
            $emailContent->textContent = 'My First Message';
            $emailContent->htmlContent = 'Some fake HTML content';
            $emailMessage->content     = $emailContent;

            //Sending from the system, does not have a 'person'.
            $sender                    = new EmailMessageSender();
            $sender->fromAddress       = 'system@somewhere.com';
            $sender->fromName          = 'Zurmo System';
            $emailMessage->sender      = $sender;

            //Recipient is billy.
            $recipient                 = new EmailMessageRecipient();
            $recipient->toAddress      = Yii::app()->params['emailTestAccounts']['testEmailAddress'];
            $recipient->toName         = 'Billy James';
            $recipient->type           = EmailMessageRecipient::TYPE_TO;
            $emailMessage->recipients->add($recipient);

            $box                  = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
            $emailMessage->folder = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_DRAFT);

            //Save, at this point the email should be in the draft folder
            $saved = $emailMessage->save();
            if (!$saved)
            {
                throw new NotSupportedException();
            }
            return $emailMessage;
        }

        public static function createSendGridEmailAccount(User $user)
        {
            $emailAccount                    = new SendGridEmailAccount();
            $emailAccount->user              = $user;
            $emailAccount->name              = EmailAccount::DEFAULT_NAME;
            $emailAccount->fromName          = $user->getFullName();
            $emailAccount->fromAddress       = 'user@zurmo.com';
            $emailAccount->apiUsername        = Yii::app()->params['emailTestAccounts']['sendGridUserSettings']['apiUsername'];
            $emailAccount->apiPassword        = Yii::app()->params['emailTestAccounts']['sendGridUserSettings']['apiPassword'];
            $emailAccount->eventWebhookUrl    = 'http://yahoo.com';
            $emailAccount->eventWebhookFilePath    = 'http://yahoo.com/a.php';
            $emailAccount->save();
            return $emailAccount;
        }
    }
?>