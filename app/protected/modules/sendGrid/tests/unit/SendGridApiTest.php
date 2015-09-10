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
     Yii::import('ext.sendgrid.lib.SendGrid');
     Yii::import('ext.sendgrid.lib.Smtpapi');
     Yii::import('ext.sendgrid.lib.Unirest');
    class SendGridApiTest extends ZurmoBaseTest
    {
        protected static $apiUsername;
        protected static $apiPassword;
        protected static $testEmailAddress;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            $super = SecurityTestHelper::createSuperAdmin();
            Yii::app()->user->userModel = $super;
            SendGrid::register_autoloader();
            Smtpapi::register_autoloader();
            if (SendGridTestHelper::isSetSendGridAccountTestConfiguration())
            {
                static::$apiUsername        = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiUsername'];
                static::$apiPassword        = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiPassword'];
                static::$testEmailAddress   = Yii::app()->params['emailTestAccounts']['testEmailAddress'];
            }
        }

        public function setUp()
        {
            parent::setUp();
            if (!SendGridTestHelper::isSetSendGridAccountTestConfiguration())
            {
                $this->markTestSkipped(Zurmo::t('SendGridModule', 'Email test settings are missing.'));
            }
        }

        /**
         * @covers ZurmoSendGridMailer::sendTestEmail
         * @covers ZurmoSendGridMailer::sendEmail
         */
        public function testSendEmail()
        {
            $sendGridEmailAccount         = new SendGridEmailAccount();
            $sendGridEmailAccount->apiUsername     = static::$apiUsername;
            $sendGridEmailAccount->apiPassword     = ZurmoPasswordSecurityUtil::encrypt(static::$apiPassword);
            $from = array(
                            'name'      => 'Test User',
                            'address'   => 'Super.test@test.zurmo.com'
                        );
            $emailMessage = EmailMessageHelper::processAndCreateTestEmailMessage($from, static::$testEmailAddress);
            $mailer       = new ZurmoSendGridMailer($emailMessage, $sendGridEmailAccount);
            $emailMessage = $mailer->sendTestEmail(true);
            $this->assertEquals('EmailMessage', get_class($emailMessage));
            $this->assertEquals(EmailFolder::TYPE_SENT, $emailMessage->folder->type);
        }
    }
?>