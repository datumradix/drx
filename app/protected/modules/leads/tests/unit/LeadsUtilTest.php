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

    class LeadsUtilTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        public function testAttributesToAccount()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $super = Yii::app()->user->userModel;
            $account = AccountTestHelper::createAccountByNameForOwner('Account1', $super);
            $contact = ContactTestHelper::createContactByNameForOwner('Contact1', $super);
            $contact->officePhone = '12345';
            $contact->officeFax = '12345';
            $saved = $contact->save();
            $this->assertTrue($saved);
            $account = LeadsUtil::attributesToAccount($contact, $account);
            $this->assertEquals($contact->officePhone, $account->officePhone);
            $this->assertEquals($contact->officeFax, $account->officeFax);
        }

        public function testAttributesToAccountWithNoPostData()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $super = Yii::app()->user->userModel;
            $account = AccountTestHelper::createAccountByNameForOwner('Account2', $super);
            $contact = ContactTestHelper::createContactByNameForOwner('Contact2', $super);
            $contact->officePhone = '12345';
            $contact->officeFax = '12345';
            $saved = $contact->save();
            $this->assertTrue($saved);
            $account = LeadsUtil::attributesToAccountWithNoPostData($contact, $account, array('officeFax' => '15165'));
            $this->assertEquals($contact->officePhone, $account->officePhone);
            $this->assertNotEquals($contact->officeFax, $account->officeFax);
        }

        public function testCreateAccountForLeadConversionFromAccountPostData()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $super = Yii::app()->user->userModel;
            $contact = ContactTestHelper::createContactByNameForOwner('Contact3', $super);
            $controllerUtil = new ZurmoControllerUtil();

            //Scenario #1 - Skip the account creation
            $accountPostData = array('AccountSkip' => true);
            $account = LeadsUtil::
                createAccountForLeadConversionFromAccountPostData($accountPostData, $contact, $controllerUtil);
            $this->assertNull($account);

            //Scenario #2 - Select an already existing account
            $account3 = AccountTestHelper::createAccountByNameForOwner('Account3', $super);
            $accountPostData = array('SelectAccount' => true, 'accountId' => $account3->id);
            $account = LeadsUtil::
                createAccountForLeadConversionFromAccountPostData($accountPostData, $contact, $controllerUtil);
            $this->assertEquals($account3->id, $account->id);

            //Scenario #3 - Create new account from POST data
            $accountPostData = array(
                'CreateAccount' => true,
                'name' => 'Account Created From Post',
                'employees' => '5',
                'website' => 'http://www.exa.com'
            );
            $account = LeadsUtil::
                createAccountForLeadConversionFromAccountPostData($accountPostData, $contact, $controllerUtil);
            $this->assertEquals('Account Created From Post', $account->name);
            $this->assertEquals(5, $account->employees);
            $this->assertEquals('http://www.exa.com', $account->website);
        }
    }
?>