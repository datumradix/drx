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
    * Plugin Walkthrough of sendgrid.
    */
    class SendGridConfigurationSuperUserWalkthroughTest extends ZurmoWalkthroughBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $aUser = UserTestHelper::createBasicUser('aUser');
            $aUser->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB);
            $saved = $aUser->save();
            if (!$saved)
            {
                throw new NotSupportedException();
            }
        }

        public function testSuperUserSendGridDefaultControllerActions()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');

            //Load configuration list for Maps.
            $this->runControllerWithNoExceptionsAndGetContent('sendGrid/default/configurationView');

            //Save the configuration details.
            $this->setPostArray(array('SendGridConfigurationForm' => array('enableSendgrid' => true),
                                      'save' => 'Save'));
            $content = $this->runControllerWithRedirectExceptionAndGetContent('sendGrid/default/configurationView');
            $this->assertEquals('Sendgrid configuration saved successfully.', Yii::app()->user->getFlash('notification'));

            //Check whether key is set.
            $enableSendGrid = (bool)ZurmoConfigurationUtil::getByModuleName('SendGridModule', 'enableSendgrid');
            $this->assertEquals(true, $enableSendGrid);
        }

        public function testSuperUserModifySendGridEmailConfiguration()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');

            //Change email settings
            $this->resetGetArray();
            $this->resetPostArray();
            $this->setPostArray(array('SendGridWebApiConfigurationForm' => array(
                                    'username'                          => 'myuser',
                                    'password'                          => 'apassword',
                                    'eventWebhookUrl'                   => 'http://yahoo.com')));
            $this->runControllerWithRedirectExceptionAndGetContent('sendGrid/default/configurationEditOutbound');
            $this->assertEquals('Sendgrid configuration saved successfully.', Yii::app()->user->getFlash('notification'));

            //Confirm the setting did in fact change correctly
            $this->assertEquals('myuser',         Yii::app()->sendGridEmailHelper->apiUsername);
            $this->assertEquals('apassword',      Yii::app()->sendGridEmailHelper->apiPassword);
            $this->assertEquals('http://yahoo.com',          Yii::app()->sendGridEmailHelper->eventWebhookUrl);
        }

        public function testSuperUserChangeOtherUserSendGridEmailSignature()
        {
            $super = $this->logoutCurrentUserLoginNewUserAndGetByUsername('super');
            $aUser = User::getByUsername('auser');
            $this->assertEquals(0, $aUser->emailSignatures->count());
            $this->assertEquals($aUser, $aUser->getEmailSignature()->user);

            //Change aUser email signature
            $this->setGetArray(array('id' => $aUser->id));
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent('users/default/sendGridConfiguration');
            $this->assertNotContains('abc email signature', $content);
            $this->setPostArray(array('UserSendGridConfigurationForm' => array(
                                    'fromName'                          => 'abc',
                                    'fromAddress'                       => 'abc@zurmo.org',
                                    'apiUsername'                       => 'abc',
                                    'apiPassword'                       => 'password',
                                    'eventWebhookUrl'                   => 'http://yahoo.com',
                                    'emailSignatureHtmlContent'         => 'abc email signature')));
            $this->runControllerWithRedirectExceptionAndGetContent('users/default/sendGridConfiguration');
            $this->assertEquals('User SendGrid API configuration saved successfully.',
                                Yii::app()->user->getFlash('notification'));
            $aUser = User::getByUsername('auser');
            $this->assertEquals(1, $aUser->emailSignatures->count());
            $this->assertEquals('abc email signature', $aUser->emailSignatures[0]->htmlContent);
            $this->resetPostArray();
            $content = $this->runControllerWithNoExceptionsAndGetContent('users/default/sendGridConfiguration');
            $this->assertContains('abc email signature', $content);
            $this->assertContains('abc@zurmo.org', $content);
            $this->assertContains('http://yahoo.com', $content);
            $this->assertContains('password', $content);
        }
    }
?>
