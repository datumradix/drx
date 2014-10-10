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

    class SendGridEmailAccountTest extends ZurmoBaseTest
    {
        protected static $apiUsername;
        protected static $apiPassword;
        protected static $testEmailAddress;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            UserTestHelper::createBasicUser('billy');
            if (SendGridTestHelper::isSetSendGridAccountTestConfiguration())
            {
                //$steve = UserTestHelper::createBasicUser('steve');
                //EmailMessageTestHelper::createEmailAccount($steve);
                static::$apiUsername        = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiUsername'];
                static::$apiPassword        = Yii::app()->params['emailTestAccounts']['sendGridGlobalSettings']['apiPassword'];
                static::$testEmailAddress   = Yii::app()->params['emailTestAccounts']['testEmailAddress'];
            }
        }

        public function testResolveAndGetByUserAndName()
        {
            //Test a user that not have a Primary Email Address
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $emailAccount = SendGridEmailAccount::resolveAndGetByUserAndName($super);

            $this->assertEquals('Default', $emailAccount->name);
            $this->assertEquals($super, $emailAccount->user);
            $this->assertEquals($super->getFullName(), $emailAccount->fromName);
            $this->assertEquals($super->primaryEmail->emailAddress, $emailAccount->fromAddress);
            $emailAccountId = $emailAccount->id;
            $emailAccount = SendGridEmailAccount::resolveAndGetByUserAndName($super);
            $this->assertNotEquals($emailAccountId, $emailAccount->id);
            $emailAccount->apiUsername = static::$apiUsername;
            $emailAccount->apiPassword = static::$apiPassword;
            $emailAccount->save();
            $this->assertEquals($emailAccount->getError('fromAddress'), 'From Address cannot be blank.');
            $emailAccount->fromAddress = 'super@zurmo.org';
            $emailAccount->save();
            $emailAccountId = $emailAccount->id;
            $emailAccount = SendGridEmailAccount::resolveAndGetByUserAndName($super);
            $this->assertEquals($emailAccountId, $emailAccount->id);
            $this->assertEquals('Default', $emailAccount->name);
            $this->assertEquals($super, $emailAccount->user);
            $this->assertEquals($super->getFullName(), $emailAccount->fromName);
            $this->assertEquals('super@zurmo.org', $emailAccount->fromAddress);
            $this->assertEquals(static::$apiUsername, $emailAccount->apiUsername);
            $this->assertEquals(static::$apiPassword, ZurmoPasswordSecurityUtil::decrypt($emailAccount->apiPassword));
        }

        /**
         * @depends testResolveAndGetByUserAndName
         */
        public function testGetByUserAndName()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $emailAccount = SendGridEmailAccount::getByUserAndName($super);
            $this->assertEquals('Default', $emailAccount->name);
            $this->assertEquals($super, $emailAccount->user);
            $this->assertEquals($super->getFullName(), $emailAccount->fromName);
            $this->assertEquals('super@zurmo.org', $emailAccount->fromAddress);
        }
    }
?>