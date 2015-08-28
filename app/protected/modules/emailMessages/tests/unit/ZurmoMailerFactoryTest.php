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

    class ZurmoMailerFactoryTest extends ZurmoBaseTest
    {
        public static $emailHelperSendEmailThroughTransport;

        protected $user = null;
        protected static $userpsg;
        protected static $usercstmsmtp;
        protected static $basicuser;
        protected static $bothSGandCstmUser;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            self::$emailHelperSendEmailThroughTransport = Yii::app()->emailHelper->sendEmailThroughTransport;
            static::$userpsg = UserTestHelper::createBasicUser('userpsg');
            static::$usercstmsmtp = UserTestHelper::createBasicUser('usercstmsmtp');
            static::$basicuser = UserTestHelper::createBasicUser('basicuser');
            static::$bothSGandCstmUser = UserTestHelper::createBasicUser('bothSGandCstmUser');
            $someoneSuper = UserTestHelper::createBasicUser('someoneSuper');

            $group = Group::getByName('Super Administrators');
            $group->users->add($someoneSuper);
            $saved = $group->save();
            assert($saved); // Not Coding Standard

            $box = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);

            if (EmailMessageTestHelper::isSetEmailAccountsTestConfiguration())
            {
                EmailMessageTestHelper::createEmailAccountForMailerFactory(static::$usercstmsmtp);
                EmailMessageTestHelper::createEmailAccountForMailerFactory(static::$bothSGandCstmUser);
            }
            SendGrid::register_autoloader();
            Smtpapi::register_autoloader();
            if (SendGridTestHelper::isSetSendGridAccountTestConfiguration())
            {
                SendGridTestHelper::createSendGridEmailAccount(static::$userpsg);
                SendGridTestHelper::createSendGridEmailAccount(static::$bothSGandCstmUser);
                Yii::app()->sendGridEmailHelper->apiUsername = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiUsername'];
                Yii::app()->sendGridEmailHelper->apiPassword = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiPassword'];
                Yii::app()->sendGridEmailHelper->setApiSettings();
                Yii::app()->sendGridEmailHelper->init();
            }
            // Delete item from jobQueue, that is created when new user is created
            Yii::app()->jobQueue->deleteAll();
        }

        public function setUp()
        {
            parent::setUp();
            if (!(EmailMessageTestHelper::isSetEmailAccountsTestConfiguration() && SendGridTestHelper::isSetSendGridAccountTestConfiguration()))
            {
                $this->markTestSkipped('Please fix the test email settings');
            }
            $this->user                 = User::getByUsername('super');
            Yii::app()->user->userModel = $this->user;
        }

        public function testResolveMailerWithSendGridEnabled()
        {
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', true);
            ZurmoConfigurationUtil::setByModuleName('SendGridModule', 'enableSendgrid', true);
            $emailMessage = EmailMessageHelper::processAndCreateTestEmailMessage(array('name' => 'Test User', 'address' => 'test@yahoo.com'), 'abc@yahoo.com');
            $emailMessage->owner = static::$userpsg;
            assert($emailMessage->save()); // Not Coding Standard
            //user sendgrid
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSendGridMailer);
            $this->assertNotNull($mailer->getEmailAccount());
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', false);
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSendGridMailer);
            $this->assertNotNull($mailer->getEmailAccount());

            //user custom
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', true);
            $emailMessage->owner = static::$usercstmsmtp;
            assert($emailMessage->save()); // Not Coding Standard
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSwiftMailer);
            $this->assertNotNull($mailer->getEmailAccount());
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', false);
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSwiftMailer);
            $this->assertNotNull($mailer->getEmailAccount());

            //Global sendgrid
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', true);
            $emailMessage->owner = static::$basicuser;
            assert($emailMessage->save()); // Not Coding Standard
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSendGridMailer);
            $this->assertNull($mailer->getEmailAccount());
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', false);
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSendGridMailer);
            $this->assertNull($mailer->getEmailAccount());

            //Global smtp
            Yii::app()->sendGridEmailHelper->apiUsername = '';
            Yii::app()->sendGridEmailHelper->apiPassword = '';
            Yii::app()->sendGridEmailHelper->setApiSettings();
            Yii::app()->sendGridEmailHelper->init();
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSwiftMailer);
            $this->assertNull($mailer->getEmailAccount());
        }

        public function testResolveMailerWithSendGridDisabled()
        {
            Yii::app()->sendGridEmailHelper->apiUsername = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiUsername'];
            Yii::app()->sendGridEmailHelper->apiPassword = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiPassword'];
            Yii::app()->sendGridEmailHelper->setApiSettings();
            Yii::app()->sendGridEmailHelper->init();
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', true);
            ZurmoConfigurationUtil::setByModuleName('SendGridModule', 'enableSendgrid', false);
            $emailMessage = EmailMessageHelper::processAndCreateTestEmailMessage(array('name' => 'Test User WO sendgrid', 'address' => 'testwosendgrid@yahoo.com'), 'abc@yahoo.com');
            //user custom
            $emailMessage->owner = static::$usercstmsmtp;
            assert($emailMessage->save()); // Not Coding Standard
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSwiftMailer);
            $this->assertNotNull($mailer->getEmailAccount());
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', false);
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSwiftMailer);
            $this->assertNotNull($mailer->getEmailAccount());

            //Global custom
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', true);
            $emailMessage->owner = static::$basicuser;
            assert($emailMessage->save()); // Not Coding Standard
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSwiftMailer);
            $this->assertNull($mailer->getEmailAccount());
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', false);
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSwiftMailer);
            $this->assertNull($mailer->getEmailAccount());
        }

        public function testResolveMailerWithItemsData()
        {
            $contact = ContactTestHelper::createContactByNameForOwner('mailercontact', $this->user);
            $campaignItem                          = new CampaignItem();
            $campaignItem->processed               = 0;
            $campaignItem->contact                 = $contact;
            $this->assertTrue($campaignItem->unrestrictedSave());

            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', true);
            ZurmoConfigurationUtil::setByModuleName('SendGridModule', 'enableSendgrid', true);
            $emailMessage = EmailMessageHelper::processAndCreateTestEmailMessage(array('name' => 'Test User', 'address' => 'test@yahoo.com'), 'abc@yahoo.com');
            $emailMessage->owner = static::$bothSGandCstmUser;
            $this->assertTrue($emailMessage->save());
            $campaignItem->emailMessage = $emailMessage;
            $this->assertTrue($campaignItem->unrestrictedSave());
            //user sendgrid, should return personal sg instance
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSendGridMailer);
            $this->assertNotNull($mailer->getEmailAccount());
            //Uncheck autoresponder/camapign settings for owner, should return global sg instance
            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', false);
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSendGridMailer);
            $this->assertNull($mailer->getEmailAccount());

            ZurmoConfigurationUtil::setByModuleName('MarketingModule', 'UseAutoresponderOrCampaignOwnerMailSettings', true);
            $emailMessage->owner = static::$usercstmsmtp;
            assert($emailMessage->save()); // Not Coding Standard
            $mailerFactory = new ZurmoMailerFactory($emailMessage);
            $mailer        = $mailerFactory->resolveMailer();
            $this->assertTrue($mailer instanceof ZurmoSwiftMailer);
            $this->assertNotNull($mailer->getEmailAccount());
            $this->assertEquals('smtp', $mailer->getEmailMessage()->mailerType);
            $this->assertEquals('personal', $mailer->getEmailMessage()->mailerSettings);
        }
    }
?>