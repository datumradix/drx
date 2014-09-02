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
     Yii::import('ext.sendgrid.lib.SendGrid');
     Yii::import('ext.sendgrid.lib.Smtpapi');
     Yii::import('ext.sendgrid.lib.Unirest');
    class SendGridEmailHelperTest extends ZurmoBaseTest
    {
        protected static $apiUsername;
        protected static $apiPassword;
        protected static $testEmailAddress;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            UserTestHelper::createBasicUser('billy');
            UserTestHelper::createBasicUser('jane');
            $someoneSuper = UserTestHelper::createBasicUser('someoneSuper');

            $group = Group::getByName('Super Administrators');
            $group->users->add($someoneSuper);
            $saved = $group->save();
            assert($saved); // Not Coding Standard
            $box = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
            SendGrid::register_autoloader();
            Smtpapi::register_autoloader();
            if (SendGridTestHelper::isSetSendGridAccountTestConfiguration())
            {
                //$steve = UserTestHelper::createBasicUser('steve');
                //EmailMessageTestHelper::createEmailAccount($steve);
                static::$apiUsername        = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiUsername'];
                static::$apiPassword        = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiPassword'];
                static::$testEmailAddress   = Yii::app()->params['emailTestAccounts']['testEmailAddress'];
            }
            // Delete item from jobQueue, that is created when new user is created
            Yii::app()->jobQueue->deleteAll();
        }

        public function testSend()
        {
            $emailHelper    = new SendGridEmailHelper();
            $emailHelper->apiUsername = static::$apiUsername;
            $emailHelper->apiPassword = static::$apiPassword;
            $super                      = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $emailMessage = SendGridTestHelper::createDraftSendGridSystemEmail('a test email', $super);
            $this->assertEquals(0, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(0, Yii::app()->emailHelper->getSentCount());
            $this->assertEquals(0, count(Yii::app()->jobQueue->getAll()));
            $emailHelper->send($emailMessage);
            $this->assertEquals(1, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(0, Yii::app()->emailHelper->getSentCount());
            $queuedJobs = Yii::app()->jobQueue->getAll();
            $this->assertEquals(1, count($queuedJobs));
            $this->assertEquals('ProcessOutboundEmail', $queuedJobs[0][0]['jobType']);
        }

        /**
         * @depends testSend
         */
        public function testSendQueued()
        {
            $emailHelper    = new SendGridEmailHelper();
            $emailHelper->apiUsername = static::$apiUsername;
            $emailHelper->apiPassword = static::$apiPassword;
            $super                      = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            //add a message in the outbox_error folder.
            $emailMessage         = SendGridTestHelper::createDraftSendGridSystemEmail('a test email 2', $super);
            $box                  = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
            $emailMessage->folder = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_OUTBOX_ERROR);
            $emailMessage->save();

            $this->assertEquals(2, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(0, Yii::app()->emailHelper->getSentCount());
            $emailHelper->sendQueued();
            $this->assertEquals(0, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(2, Yii::app()->emailHelper->getSentCount());

            //add a message in the outbox folder.
            $emailMessage         = SendGridTestHelper::createDraftSendGridSystemEmail('a test email 3', $super);
            $box                  = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
            $emailMessage->folder = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_OUTBOX);
            $emailMessage->save();
            //add a message in the outbox_error folder.
            $emailMessage         = SendGridTestHelper::createDraftSendGridSystemEmail('a test email 4', $super);
            $box                  = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
            $emailMessage->folder = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_OUTBOX_ERROR);
            $emailMessage->save();
            //add a message in the outbox_error folder.
            $emailMessage         = SendGridTestHelper::createDraftSendGridSystemEmail('a test email 5', $super);
            $box                  = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
            $emailMessage->folder = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_OUTBOX_ERROR);
            $emailMessage->save();

            $this->assertEquals(3, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(2, Yii::app()->emailHelper->getSentCount());
            $emailHelper->sendQueued(1);
            $this->assertEquals(2, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(3, Yii::app()->emailHelper->getSentCount());
            $emailHelper->sendQueued(2);
            $this->assertEquals(0, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(5, Yii::app()->emailHelper->getSentCount());
        }

        /**
         * @depends testSendQueued
         */
        public function testSendImmediately()
        {
            $emailHelper    = new SendGridEmailHelper();
            $emailHelper->apiUsername = static::$apiUsername;
            $emailHelper->apiPassword = static::$apiPassword;
            $super                      = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $emailMessage = SendGridTestHelper::createDraftSendGridSystemEmail('a test email 2', $super);
            $this->assertEquals(0, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(5, Yii::app()->emailHelper->getSentCount());
            $emailHelper->sendImmediately($emailMessage);
            $this->assertEquals(0, Yii::app()->emailHelper->getQueuedCount());
            $this->assertEquals(6, Yii::app()->emailHelper->getSentCount());
        }

        public function testResolveRecipientAddressesByType()
        {
            $super                      = User::getByUsername('super');
            $emailMessage              = new EmailMessage();
            $emailMessage->owner       = $super;
            $emailMessage->subject     = "Hello";

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
            $recipient->toAddress      = 'abc@yahoo.com';
            $recipient->toName         = 'Billy James';
            $recipient->type           = EmailMessageRecipient::TYPE_TO;
            $emailMessage->recipients->add($recipient);
            //CC
            $recipient                 = new EmailMessageRecipient();
            $recipient->toAddress      = 'def@yahoo.com';
            $recipient->toName         = 'Billy James CC';
            $recipient->type           = EmailMessageRecipient::TYPE_CC;
            $emailMessage->recipients->add($recipient);
            //BCC
            $recipient                 = new EmailMessageRecipient();
            $recipient->toAddress      = 'ghi@yahoo.com';
            $recipient->toName         = 'Billy James BCC';
            $recipient->type           = EmailMessageRecipient::TYPE_BCC;
            $emailMessage->recipients->add($recipient);
            list($toAddresses, $ccAddresses, $bccAddresses) = SendGridEmailHelper::resolveRecipientAddressesByType($emailMessage);
            $this->assertArrayHasKey('abc@yahoo.com', $toAddresses);
            $this->assertArrayHasKey('def@yahoo.com', $ccAddresses);
            $this->assertArrayHasKey('ghi@yahoo.com', $bccAddresses);
            $this->assertEquals('Billy James', $toAddresses['abc@yahoo.com']);
            $this->assertEquals('Billy James CC', $ccAddresses['def@yahoo.com']);
            $this->assertEquals('Billy James BCC', $bccAddresses['ghi@yahoo.com']);
        }
    }
?>