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

    class UserTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
        }

        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        /**
         * You can only use setIsSystemUser to set the isSystemUser attribute
         * @expectedException NotSupportedException
         */
        public function testCannotSetIsSystemUserDirectlyOnModel()
        {
            $user = User::getByUsername('super');
            Yii::app()->user->userModel = $user;
            $user = new User();
            $user->isSystemUser = true;
        }

        public function testEmailUniquenessValidation()
        {
            $user = User::getByUsername('super');
            Yii::app()->user->userModel = $user;

            $user = new User();
            $user->username = 'usera';
            $user->lastName = 'UserA';
            $user->setPassword('myuser');
            $emailAddress = 'userA@example.com';
            $user->primaryEmail->emailAddress = $emailAddress;
            $saved = $user->save();
            $this->assertTrue($saved);

            $user2 = new User();
            $user2->username = 'userb';
            $user2->lastName = 'UserB';
            $user2->setPassword('myuser');
            $emailAddress = 'userA@example.com';
            $user2->primaryEmail->emailAddress = $emailAddress;
            $user2->secondaryEmail->emailAddress = $emailAddress;
            $saved = $user2->save();
            $this->assertFalse($saved);

            $validationErrors = $user2->getErrors();
            $this->assertTrue(count($validationErrors) > 0);

            // Todo: fix array keys below
            $this->assertTrue(isset($validationErrors['primaryEmail']));
            $this->assertTrue(isset($validationErrors['primaryEmail']['emailAddress']));
            $this->assertEquals('Email address already exists in system.', $validationErrors['primaryEmail']['emailAddress'][0]);
            $this->assertTrue(isset($validationErrors['secondaryEmail']));
            $this->assertTrue(isset($validationErrors['secondaryEmail']['emailAddress']));
            $this->assertEquals('Secondary email address cannot be the same as the primary email address.', $validationErrors['secondaryEmail']['emailAddress'][0]);

            $user2a = new User();
            $user2a->username = 'userb';
            $user2a->lastName = 'UserB';
            $user2a->setPassword('myuser');
            $emailAddress = 'userA@example.com';
            $user2a->secondaryEmail->emailAddress = $emailAddress;
            $saved = $user2a->save();
            $this->assertFalse($saved);

            $validationErrors = $user2a->getErrors();
            $this->assertTrue(count($validationErrors) > 0);

            // Todo: fix array keys below
            $this->assertTrue(isset($validationErrors['secondaryEmail']));
            $this->assertTrue(isset($validationErrors['secondaryEmail']['emailAddress']));
            $this->assertEquals('Email address already exists in system.', $validationErrors['secondaryEmail']['emailAddress'][0]);
            
            // Try to save user without email address
            $user3 = new User();
            $user3->username = 'userc';
            $user3->lastName = 'UserC';
            $user3->setPassword('myuser');
            $saved = $user3->save();
            $this->assertTrue($saved);
        }

        public function testSetTitleValuesAndRetrieveTitleValuesFromUser()
        {
            $titles = array('Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Swami');
            $customFieldData = CustomFieldData::getByName('Titles');
            $customFieldData->serializedData = serialize($titles);
            $this->assertTrue($customFieldData->save());
            $dropDownArray = unserialize($customFieldData->serializedData);
            $this->assertEquals($titles, $dropDownArray);
            $user = new User();
            $dropDownModel = $user->title;
            $dropDownArray = unserialize($dropDownModel->data->serializedData);
            $this->assertEquals($titles, $dropDownArray);
        }

        public function testSaveCurrentUser()
        {
            //some endless loop if you are trying to save yourself
            $user = User::getByUsername('super');
            Yii::app()->user->userModel = $user;
            $user->department = 'somethingNew';
            $this->assertTrue($user->save());
        }

        public function testCreateAndGetUserById()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $user = new User();
            $user->username           = 'bill';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'Bill';
            $user->lastName           = 'Billson';
            $user->setPassword('billy');
            $this->assertTrue($user->save());
            $id = $user->id;
            unset($user);
            $user = User::getById($id);
            $this->assertEquals('bill', $user->username);
        }

        /**
         * @depends testCreateAndGetUserById
         */
        public function testCreateUserWithRelatedUser()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $manager = new User();
            $manager->username           = 'bobi';
            $manager->title->value       = 'Mr.';
            $manager->firstName          = 'Bob';
            $manager->lastName           = 'Bobson';
            $manager->setPassword('bobii');
            $this->assertTrue($manager->save());

            $user = new User();
            $user->username     = 'dick';
            $user->title->value = 'Mr.';
            $user->firstName    = 'Dick';
            $user->lastName     = 'Dickson';
            $user->manager      = $manager;
            $user->setPassword('dickster');
            $this->assertTrue($user->save());
            $id = $user->id;
            $managerId = $user->manager->id;
            unset($user);
            $manager = User::getById($managerId);
            $this->assertEquals('bobi',  $manager->username);
            $user = User::getById($id);
            $this->assertEquals('dick', $user->username);
            $this->assertEquals('bobi',  $user->manager->username);
        }

        /**
         * @depends testCreateAndGetUserById
         * @expectedException NotFoundException
         */
        public function testCreateAndGetUserByIdThatDoesntExist()
        {
            $user = User::getById(123456);
        }

        /**
         * @depends testCreateAndGetUserById
         */
        public function testGetByUsername()
        {
            $user = User::getByUsername('bill');
            $this->assertEquals('bill', $user->username);
        }

        /**
         * @depends testGetByUsername
         */
        public function testGetLabel()
        {
            $user = User::getByUsername('bill');
            $this->assertEquals('User',  $user::getModelLabelByTypeAndLanguage('Singular'));
            $this->assertEquals('Users', $user::getModelLabelByTypeAndLanguage('Plural'));
        }

        /**
         * @depends testGetByUsername
         * @expectedException NotFoundException
         */
        public function testGetByUsernameForNonExistentUsername()
        {
            User::getByUsername('noodles');
        }

        /**
         * @depends testCreateAndGetUserById
         */
        public function testSearchByPartialName()
        {
            $user1= User::getByUsername('dick');
            $users = UserSearch::getUsersByPartialFullName('di', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user1->id,     $users[0]->id);
            $this->assertEquals('dick',         $users[0]->username);
            $this->assertEquals('Dick Dickson', $users[0]->getFullName());

            $user2 = User::getByUsername('bill');
            $users = UserSearch::getUsersByPartialFullName('bi', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user2->id,     $users[0]->id);
            $this->assertEquals('bill',         $users[0]->username);
            $this->assertEquals('Bill Billson', $users[0]->getFullName());

            $user3 = new User();
            $user3->username  = 'dison';
            $user3->title->value = 'Mr.';
            $user3->firstName    = 'Dison';
            $user3->lastName     = 'Smith';
            $user3->setPassword('dison');
            $this->assertTrue($user3->save());

            $user4 = new User();
            $user4->username  = 'graham';
            $user4->title->value = 'Mr.';
            $user4->firstName    = 'Graham';
            $user4->lastName   = 'Dillon';
            $user4->setPassword('graham');
            $this->assertTrue($user4->save());

            $users = UserSearch::getUsersByPartialFullName('di', 5);
            $this->assertEquals(3, count($users));
            $this->assertEquals($user1->id,      $users[0]->id);
            $this->assertEquals('dick',          $users[0]->username);
            $this->assertEquals('Dick Dickson',  $users[0]->getFullName());
            $this->assertEquals($user3->id,      $users[1]->id);
            $this->assertEquals('dison',         $users[1]->username);
            $this->assertEquals('Dison Smith',   $users[1]->getFullName());
            $this->assertEquals($user4->id,      $users[2]->id);
            $this->assertEquals('graham',        $users[2]->username);
            $this->assertEquals('Graham Dillon', $users[2]->getFullName());

            $users = UserSearch::getUsersByPartialFullName('g', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user4->id,      $users[0]->id);
            $this->assertEquals('graham',        $users[0]->username);
            $this->assertEquals('Graham Dillon', $users[0]->getFullName());

            $users = UserSearch::getUsersByPartialFullName('G', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user4->id,      $users[0]->id);
            $this->assertEquals('graham',        $users[0]->username);
            $this->assertEquals('Graham Dillon', $users[0]->getFullName());

            $users = UserSearch::getUsersByPartialFullName('Dil', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user4->id,      $users[0]->id);
            $this->assertEquals('graham',        $users[0]->username);
            $this->assertEquals('Graham Dillon', $users[0]->getFullName());
        }

        /**
         * @depends testSearchByPartialName
         */
        public function testSearchByPartialNameWithFirstNamePlusPartialLastName()
        {
            $user = User::getByUsername('dick');

            $users = UserSearch::getUsersByPartialFullName('dick', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user->id,      $users[0]->id);
            $this->assertEquals('dick',         $users[0]->username);
            $this->assertEquals('Dick Dickson', $users[0]->getFullName());

            $users = UserSearch::getUsersByPartialFullName('dick ', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user->id,      $users[0]->id);
            $this->assertEquals('dick',         $users[0]->username);
            $this->assertEquals('Dick Dickson', $users[0]->getFullName());

            $users = UserSearch::getUsersByPartialFullName('dick d', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user->id,      $users[0]->id);
            $this->assertEquals('dick',         $users[0]->username);
            $this->assertEquals('Dick Dickson', $users[0]->getFullName());

            $user = User::getByUsername('dick');
            $users = UserSearch::getUsersByPartialFullName('dick di', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user->id,      $users[0]->id);
            $this->assertEquals('dick',         $users[0]->username);
            $this->assertEquals('Dick Dickson', $users[0]->getFullName());

            $users = UserSearch::getUsersByPartialFullName('Dick di', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user->id,      $users[0]->id);
            $this->assertEquals('dick',         $users[0]->username);
            $this->assertEquals('Dick Dickson', $users[0]->getFullName());

            $users = UserSearch::getUsersByPartialFullName('dick Di', 5);
            $this->assertEquals(1, count($users));
            $this->assertEquals($user->id,      $users[0]->id);
            $this->assertEquals('dick',         $users[0]->username);
            $this->assertEquals('Dick Dickson', $users[0]->getFullName());
        }

        /**
         * @depends testCreateAndGetUserById
         */
        public function testCreateWithTitleThenClearTitleDirectly()
        {
            $user = new User();
            $user->username     = 'jason';
            $user->title->value = 'Mr.';
            $user->firstName    = 'Jason';
            $user->lastName     = 'Jasonson';
            $user->setPassword('jason');
            $this->assertTrue($user->save());
            $id = $user->id;
            unset($user);
            $user = User::getById($id);
            $this->assertEquals('jason', $user->username);
            $this->assertEquals('Mr.', strval($user->title));
            $user->title = null;
            $this->assertNotNull($user->title);
            $this->assertTrue($user->save());
        }

        /**
         * @depends testCreateWithTitleThenClearTitleDirectly
         */
        public function testCreateWithTitleThenClearTitleWithSetAttributesWithEmptyId()
        {
            $user = User::getByUsername('jason');
            $user->title->value = 'Mr.';
            $this->assertEquals('Mr.', strval($user->title));
            $this->assertTrue($user->save());

            $_FAKEPOST = array(
                'User' => array(
                    'title' => array(
                        'value' => '',
                    )
                )
            );

            $user->setAttributes($_FAKEPOST['User']);
            $this->assertEquals('(None)', strval($user->title));
            $this->assertTrue($user->save());
        }

        /**
         * @depends testCreateWithTitleThenClearTitleWithSetAttributesWithEmptyId
         */
        public function testCreateWithTitleThenClearTitleWithSetAttributesWithNullId()
        {
            $user = User::getByUsername('jason');
            $user->title->value = 'Mr.';
            $this->assertEquals('Mr.', strval($user->title));
            $this->assertTrue($user->save());

            $_FAKEPOST = array(
                'User' => array(
                    'title' => array(
                        'value' => '',
                    )
                )
            );

            $user->setAttributes($_FAKEPOST['User']);
            $this->assertEquals('(None)', strval($user->title));
            $this->assertTrue($user->save());
        }

        /**
         * @depends testCreateWithTitleThenClearTitleWithSetAttributesWithNullId
         */
        public function testCreateWithTitleThenClearTitleWithSetAttributesWithRealId()
        {
            $user = User::getByUsername('jason');
            $user->title->value = 'Mr.';
            $this->assertEquals('Mr.', strval($user->title));
            $this->assertTrue($user->save());

            $_FAKEPOST = array(
                'User' => array(
                    'title' => array(
                        'value' => 'Sir',
                    )
                )
            );

            $user->setAttributes($_FAKEPOST['User']);
            $this->assertEquals('Sir', strval($user->title));
            $this->assertTrue($user->save());
        }

        public function testSaveUserWithNoManager()
        {
            $user = UserTestHelper::createBasicUser('Steven');
            $_FAKEPOST = array(
                'User' => array(
                    'manager' => array(
                        'id' => '',
                    ),
                ),
            );

            $user->setAttributes($_FAKEPOST['User']);
            $user->validate();
            $this->assertEquals(array(), $user->getErrors());
        }

        /**
         * @depends testCreateWithTitleThenClearTitleWithSetAttributesWithRealId
         * @depends testSaveUserWithNoManager
         */
        public function testSaveExistingUserWithFakePost()
        {
            $user = User::getByUsername('jason');
            $_FAKEPOST = array(
                'User' => array(
                    'title' => array(
                        'value' => '',
                    ),
                    'firstName'   => 'Jason',
                    'lastName'    => 'Jasonson',
                    'username'    => 'jason',
                    'jobTitle'    => '',
                    'officePhone' => '',
                    'manager' => array(
                        'id' => '',
                    ),
                    'mobilePhone' => '',
                    'department'  => '',
                    'primaryEmail' => array(
                        'emailAddress' => '',
                        'optOut' => 0,
                        'isInvalid' => 0,
                    ),
                    'primaryAddress' => array(
                        'street1'    => '',
                        'street2'    => '',
                        'city'       => '',
                        'state'      => '',
                        'postalCode' => '',
                        'country'    => '',
                    )
                )
            );
            $user->setAttributes($_FAKEPOST['User']);
            $user->validate();
            $this->assertEquals(array(), $user->getErrors());
            $this->assertTrue($user->save());
        }

        /**
         * @depends testSaveExistingUserWithFakePost
         */
        public function testSaveExistingUserWithUsersIdAsManagerId()
        {
            $user = User::getByUsername('jason');
            $_FAKEPOST = array(
                'User' => array(
                    'title' => array(
                        'value' => '',
                    ),
                    'firstName'   => 'Jason',
                    'lastName'    => 'Jasonson',
                    'username'    => 'jason',
                    'jobTitle'    => '',
                    'officePhone' => '',
                    'manager' => array(
                        'id' => $user->id,
                    ),
                )
            );
            /*
            $user->setAttributes($_FAKEPOST['User']);
            $this->assertFalse($user->save());
            $errors = $user->getErrors();
            //todo: assert an error is present for manager, assert the error says can't
            //select self or something along those lines.
            */

            //probably should also check if you are picking a manager that is creating recursion,
            //not necessarily yourself, but someone in the chain of yourself already.
        }

        public function testUserMixingInPerson()
        {
            // See comments on User::getDefaultMetadata().

            $user = new User();
            $this->assertTrue($user->isAttribute('username'));
            $this->assertTrue($user->isAttribute('title'));
            $this->assertTrue($user->isAttribute('firstName'));
            $this->assertTrue($user->isAttribute('lastName'));
            $this->assertTrue($user->isAttribute('jobTitle'));

            $user->username     = 'oliver';
            $user->title->value = 'Mr.';
            $user->firstName    = 'Oliver';
            $user->lastName     = 'Oliverson';
            $user->jobTitle     = 'Recruiter';
            $this->assertEquals('oliver',           $user->username);
            $this->assertEquals('Oliver Oliverson', strval($user));
            $this->assertEquals('Recruiter',        $user->jobTitle);
            $user->setPassword('oliver');
            $this->assertTrue($user->save());

            $id = $user->id;
            $user->forget();
            unset($user);

            $user = User::getById($id);
            $this->assertEquals('oliver',           $user->username);
            $this->assertEquals('Oliver Oliverson', strval($user));
            $this->assertEquals('Recruiter',        $user->jobTitle);
        }

        public function testCreateNewUserFromPostNoBadValues()
        {
            $_FAKEPOST = array(
                'UserPasswordForm' => array(
                    'title' => array(
                        'value' => '',
                    ),
                    'firstName'   => 'Red',
                    'lastName'    => 'Jiambo',
                    'username'    => 'redjiambo',
                    'newPassword' => '123456',
                    'newPassword_repeat' => '123456',
                    'jobTitle'    => '',
                    'officePhone' => '',
                    'manager' => array(
                        'id' => '',
                    ),
                    'mobilePhone' => '',
                    'department'  => '',
                    'primaryEmail' => array(
                        'emailAddress' => '',
                        'optOut' => 0,
                        'isInvalid' => 0,
                    ),
                    'primaryAddress' => array(
                        'street1'    => '',
                        'street2'    => '',
                        'city'       => '',
                        'state'      => '',
                        'postalCode' => '',
                        'country'    => '',
                    )
                )
            );
            $user = new User();
            $user->setScenario('createUser');
            $userPasswordForm = new UserPasswordForm($user);
            $userPasswordForm->setScenario('createUser');
            $userPasswordForm->setAttributes($_FAKEPOST['UserPasswordForm']);
            $userPasswordForm->validate();
            $this->assertEquals(array(), $userPasswordForm->getErrors());
            $this->assertTrue($userPasswordForm->save());
            $user->forget();
            $user = User::getByUsername('redjiambo');
            $this->assertEquals('Red', $user->firstName);
            $this->assertEquals(null,  $user->officePhone);
            $this->assertEquals(null,  $user->jobTitle);
            $this->assertEquals(null,  $user->mobilePhone);
            $this->assertEquals(null,  $user->department);
        }

        /**
         * @depends testCreateAndGetUserById
         */
        public function testDeleteUserCascadesToDeleteEverythingItShould()
        {
            $group = new Group();
            $group->name = 'Os mais legais do Rio';
            $this->assertTrue($group->save());

            $user = new User();
            $user->username                   = 'carioca';
            $user->title->value               = 'Senhor';
            $user->firstName                  = 'José';
            $user->lastName                   = 'Olivereira';
            $user->jobTitle                   = 'Traficante';
            $user->primaryAddress->street1    = 'R. das Mulheres, 69';
            $user->primaryAddress->street2    = '';
            $user->primaryAddress->city       = 'Centro';
            $user->primaryAddress->state      = 'RJ';
            $user->primaryAddress->postalCode = '';
            $user->primaryAddress->country    = 'Brasil';
            $user->primaryEmail->emailAddress = 'jose@gmail.com';
            $user->primaryEmail->optOut       = 1;
            $user->primaryEmail->isInvalid    = 0;
            $user->manager                    = User::getByUsername('bill');
            $user->setPassword('Senhor');
            $user->groups->add($group);
            $user->save();
            $this->assertTrue($user->save());

            $titleId          = $user->title->id;
            $primaryAddressId = $user->primaryAddress->id;
            $primaryEmailId   = $user->primaryEmail  ->id;
            $groupId          = $group->id;

            $user->delete();
            unset($user);
            unset($group);

            Group::getById($groupId);
            User::getByUsername('bill');

            try
            {
                CustomField::getById($titleId);
                $this->fail("Title should have been deleted.");
            }
            catch (NotFoundException $e)
            {
            }

            try
            {
                Address::getById($primaryAddressId);
                $this->fail("Address should have been deleted.");
            }
            catch (NotFoundException $e)
            {
            }

            try
            {
                Email::getById($primaryEmailId);
                $this->fail("Email should have been deleted.");
            }
            catch (NotFoundException $e)
            {
            }
        }

        /**
         * @depends testCreateAndGetUserById
         */
        public function testCanRemoveRoleFromUser()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $parentRole = new Role();
            $parentRole->name = 'SomeParentRole';
            $saved = $parentRole->save();
            $this->assertTrue($parentRole->id > 0);
            $this->assertTrue($saved);
            $role = new Role();
            $role->name = 'SomeRole';
            $role->role = $parentRole;
            $saved = $role->save();
            $this->assertTrue($parentRole->id > 0);
            $this->assertEquals($parentRole->id, $role->role->id);
            $this->assertTrue($role->id > 0);
            $this->assertTrue($saved);
            $user = User::getByUsername('bill');
            $this->assertTrue($user->id > 0);
            $this->assertFalse($user->role->id > 0);
            $fakePost = array(
                'role' => array(
                    'id' => $role->id,
                )
            );
            $user->setAttributes($fakePost);
            $saved = $user->save();
            $this->assertTrue($saved);
            $user->forget();
            unset($user);
            $user  = User::getByUsername('bill');
            $this->assertTrue($user->id > 0);
            $this->assertTrue($role->id > 0);
            $this->assertEquals($role->id, $user->role->id);
            $fakePost = array(
                'role' => array(
                    'id' => '',
                )
            );
            $user->setAttributes($fakePost);
            $this->assertFalse($user->role->id > 0);
            $saved = $user->save();
            $this->assertTrue($saved);
            $user->forget();
            unset($user);
            $user  = User::getByUsername('bill');
            $this->assertTrue($user->id > 0);
            $this->assertFalse($user->role->id > 0);
        }

        /**
         * @depends testCreateAndGetUserById
         */
        public function testPasswordUserNamePolicyChangesValidationAndLogin()
        {
            $bill  = User::getByUsername('bill');
            $bill->setScenario('changePassword');
            $billPasswordForm = new UserPasswordForm($bill);
            $billPasswordForm->setScenario('changePassword');
            $this->assertEquals(null,       $bill->getEffectivePolicy('UsersModule', UsersModule::POLICY_ENFORCE_STRONG_PASSWORDS));
            $this->assertEquals(5,          $bill->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_PASSWORD_LENGTH));
            $this->assertEquals(3,          $bill->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_USERNAME_LENGTH));
            $_FAKEPOST = array(
                'UserPasswordForm' => array(
                    'username'           => 'ab',
                    'newPassword'        => 'ab',
                    'newPassword_repeat' => 'ab',
                )
            );
            $billPasswordForm->setAttributes($_FAKEPOST['UserPasswordForm']);
            $this->assertFalse($billPasswordForm->save());
            $errors = array(
                'newPassword' => array(
                    'The password is too short. Minimum length is 5.',
                ),
            );
            $this->assertEquals($errors, $billPasswordForm->getErrors());
            $_FAKEPOST = array(
                'UserPasswordForm' => array(
                    'username'           => 'abcdefg',
                    'newPassword'        => 'abcdefg',
                    'newPassword_repeat' => 'abcdefg',
                )
            );
            $billPasswordForm->setAttributes($_FAKEPOST['UserPasswordForm']);
            $this->assertEquals('abcdefg', $billPasswordForm->username);
            $this->assertEquals('abcdefg', $billPasswordForm->newPassword);
            $validated = $billPasswordForm->validate();
            $this->assertTrue($validated);
            $saved = $billPasswordForm->save();
            $this->assertTrue($saved);
            $bill->setPolicy('UsersModule', UsersModule::POLICY_ENFORCE_STRONG_PASSWORDS, Policy::YES);
            // If security is optimized the optimization will see the policy value in the database
            // and so wont use it in validating, so the non-strong password wont be validated as
            // invalid until the next save.
            $this->assertEquals(SECURITY_OPTIMIZED, $billPasswordForm->save());
            $_FAKEPOST = array(
                'UserPasswordForm' => array(
                    'newPassword'        => 'abcdefg',
                    'newPassword_repeat' => 'abcdefg',
                )
            );
            $billPasswordForm->setAttributes($_FAKEPOST['UserPasswordForm']);
            $this->assertFalse($billPasswordForm->save());

            $errors = array(
                'newPassword' => array(
                    'The password must have at least one uppercase letter',
                    'The password must have at least one number and one letter',
                ),
            );
            $this->assertEquals($errors, $billPasswordForm->getErrors());
            $_FAKEPOST = array(
                'UserPasswordForm' => array(
                    'newPassword'        => 'abcdefgN',
                    'newPassword_repeat' => 'abcdefgN',
                )
            );
            $billPasswordForm->setAttributes($_FAKEPOST['UserPasswordForm']);
            $this->assertFalse($billPasswordForm->save());
            $errors = array(
                'newPassword' => array(
                    'The password must have at least one number and one letter',
                ),
            );
            $this->assertEquals($errors, $billPasswordForm->getErrors());
            $_FAKEPOST = array(
                'UserPasswordForm' => array(
                    'newPassword'        => 'ABCDEFGH',
                    'newPassword_repeat' => 'ABCDEFGH',
                )
            );
            $billPasswordForm->setAttributes($_FAKEPOST['UserPasswordForm']);
            $this->assertFalse($billPasswordForm->save());
            $errors = array(
                'newPassword' => array(
                    'The password must have at least one lowercase letter',
                    'The password must have at least one number and one letter',
                ),
            );
            $this->assertEquals($errors, $billPasswordForm->getErrors());
            $_FAKEPOST = array(
                'UserPasswordForm' => array(
                    'newPassword'        => 'abcdefgN4',
                    'newPassword_repeat' => 'abcdefgN4',
                )
            );
            $billPasswordForm->setAttributes($_FAKEPOST['UserPasswordForm']);
            $this->assertTrue($billPasswordForm->save());
            $bill->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB);
            $this->assertTrue($billPasswordForm->save());
            $this->assertEquals(Right::ALLOW, $bill->getEffectiveRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB));
            //Now attempt to login as bill
            $bill->forget();
            $bill       = User::getByUsername('abcdefg');
            $this->assertEquals($bill, User::authenticate('abcdefg', 'abcdefgN4'));
            $identity = new UserIdentity('abcdefg', 'abcdefgN4');
            $authenticated = $identity->authenticate();
            $this->assertEquals(0, $identity->errorCode);
            $this->assertTrue($authenticated);

            //Now turn off login via web for bill
            Yii::app()->user->userModel = User::getByUsername('super');
            $bill  = User::getByUsername('abcdefg');
            $bill->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB, RIGHT::DENY);
            $this->assertTrue($bill->save());
            $identity = new UserIdentity('abcdefg', 'abcdefgN4');
            $this->assertFalse($identity->authenticate());
            $this->assertEquals(UserIdentity::ERROR_NO_RIGHT_WEB_LOGIN, $identity->errorCode);

            //Test creating a new user uses the everyone policy
            $everyone = Group::getByName(Group::EVERYONE_GROUP_NAME);
            $newUser = new User();
            $this->assertEquals(null, $everyone->getEffectivePolicy('UsersModule', UsersModule::POLICY_ENFORCE_STRONG_PASSWORDS));
            $this->assertEquals(5,    $everyone->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_PASSWORD_LENGTH));
            $this->assertEquals(3,    $everyone->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_USERNAME_LENGTH));
            $this->assertEquals(null, $newUser->getEffectivePolicy('UsersModule', UsersModule::POLICY_ENFORCE_STRONG_PASSWORDS));
            $this->assertEquals(5,    $newUser->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_PASSWORD_LENGTH));
            $this->assertEquals(3,    $newUser->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_USERNAME_LENGTH));
            $everyone->setPolicy('UsersModule', UsersModule::POLICY_ENFORCE_STRONG_PASSWORDS, Policy::YES);
            $everyone->setPolicy('UsersModule', UsersModule::POLICY_MINIMUM_PASSWORD_LENGTH, 3);
            $everyone->setPolicy('UsersModule', UsersModule::POLICY_MINIMUM_USERNAME_LENGTH, 15);
            $everyone->save();
            $this->assertEquals(Policy::YES, $newUser->getEffectivePolicy('UsersModule', UsersModule::POLICY_ENFORCE_STRONG_PASSWORDS));
            $this->assertEquals(3,           $newUser->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_PASSWORD_LENGTH));
            $this->assertEquals(15,          $newUser->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_USERNAME_LENGTH));

            //Make the permission as the default for next tests
            $everyone->setPolicy('UsersModule', UsersModule::POLICY_MINIMUM_PASSWORD_LENGTH, 5);
            $everyone->setPolicy('UsersModule', UsersModule::POLICY_MINIMUM_USERNAME_LENGTH, 3);
            $everyone->save();
        }

        /**
         * @depends testPasswordUserNamePolicyChangesValidationAndLogin
         */
        public function testUserNamePolicyValidatesCorrectlyOnDifferentScenarios()
        {
            $bill  = User::getByUsername('abcdefg');
            $bill->setScenario('editUser');
            $this->assertEquals(3,    $bill->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_USERNAME_LENGTH));
            $_FAKEPOST = array(
                'User' => array(
                    'username'           => 'ab',
                )
            );
            $bill->setAttributes($_FAKEPOST['User']);
            $this->assertFalse($bill->save());
            $errors = array(
                'username' => array(
                    'The username is too short. Minimum length is 3.',
                ),
            );
            $this->assertEquals($errors, $bill->getErrors());

            $bill  = User::getByUsername('abcdefg');
            $bill->setScenario('createUser');
            $this->assertEquals(3,    $bill->getEffectivePolicy('UsersModule', UsersModule::POLICY_MINIMUM_USERNAME_LENGTH));
            $_FAKEPOST = array(
                'User' => array(
                    'username'           => 'ab',
                )
            );
            $bill->setAttributes($_FAKEPOST['User']);
            $this->assertFalse($bill->save());
            $errors = array(
                'username' => array(
                    'The username is too short. Minimum length is 3.',
                ),
            );
            $this->assertEquals($errors, $bill->getErrors());
        }

        public function testValidatingUserAfterGettingAttributeValuesFromRelatedUsers()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $user = UserTestHelper::createBasicUser('notsuper');
            $this->assertTrue($user->save());
            $this->assertTrue($user->createdByUser ->isSame($super));
            $this->assertTrue($user->modifiedByUser->isSame($super));
            if (!$user->validate())
            {
                $this->assertEquals(array(), $user->getErrors());
            }
            // A regular user has a created by and
            // modified by user so accessing them is no problem.
            $test = $user->createdByUser->id;
            $this->assertTrue($user->validate());
            $this->assertEquals(array(), $user->getErrors());
        }

        public function testValidatingSuperAdministratorAfterGettingAttributeValuesFromRelatedUsers()
        {
            $super = User::getByUsername('super');
            $this->assertTrue($super->validate());
            $this->assertTrue($super->createdByUser->id  < 0);
            $this->assertTrue($super->modifiedByUser->isSame($super));
            $this->assertTrue($super->validate());
            $this->assertEquals(array(), $super->getErrors());
        }

        /**
         * @depends testCreateUserWithRelatedUser
         */
        public function testSavingExistingUserDoesntCreateRelatedBlankUsers()
        {
            $userCount = User::getCount();
            $dick = User::getByUsername('dick');
            $this->assertTrue($dick->save());
            $this->assertEquals($userCount, User::getCount());
        }

        public function testMixedInPersonInUser()
        {
            $user = new User();
            $user->username = 'dude';
            $user->lastName = 'Dude';
            $this->assertTrue($user->save());

            $this->assertTrue($user->isAttribute('id'));             // From RedBeanModel.
            $this->assertTrue($user->isAttribute('createdDateTime')); // From Item.
            $this->assertTrue($user->isAttribute('firstName'));      // From Person.
            $this->assertTrue($user->isAttribute('username'));       // From User.

            $this->assertTrue($user->isRelation ('createdByUser'));  // From Item.
            $this->assertTrue($user->isRelation ('rights'));         // From Permitable.
            $this->assertTrue($user->isRelation ('title'));          // From Person.
            $this->assertTrue($user->isRelation ('manager'));        // From User.

            unset($user);

            $user = User::getByUsername('dude');

            $this->assertTrue($user->isAttribute('id'));             // From RedBeanModel.
            $this->assertTrue($user->isAttribute('createdDateTime')); // From Item.
            $this->assertTrue($user->isAttribute('firstName'));      // From Person.
            $this->assertTrue($user->isAttribute('username'));       // From User.

            $this->assertTrue($user->isRelation ('createdByUser'));  // From Item.
            $this->assertTrue($user->isRelation ('rights'));         // From Permitable.
            $this->assertTrue($user->isRelation ('title'));          // From Person.
            $this->assertTrue($user->isRelation ('manager'));        // From User.

            RedBeanModelsCache::cacheModel($user);

            $modelIdentifier = $user->getModelIdentifier();
            unset($user);

            RedBeanModelsCache::forgetAll(true); // Forget it at the php level.
            RedBeansCache::forgetAll();

            if (MEMCACHE_ON)
            {
                $user = RedBeanModelsCache::getModel($modelIdentifier);

                $this->assertTrue($user->isAttribute('id'));             // From RedBeanModel.
                $this->assertTrue($user->isAttribute('createdDateTime')); // From Item.
                $this->assertTrue($user->isAttribute('firstName'));      // From Person.
                $this->assertTrue($user->isAttribute('username'));       // From User.

                $this->assertTrue($user->isRelation ('createdByUser'));  // From Item.
                $this->assertTrue($user->isRelation ('rights'));         // From Permitable.
                $this->assertTrue($user->isRelation ('title'));          // From Person.
                $this->assertTrue($user->isRelation ('manager'));        // From User.
            }
        }

        public function testGetModelClassNames()
        {
            $modelClassNames = UsersModule::getModelClassNames();
            $this->assertEquals(3, count($modelClassNames));
            $this->assertEquals('User', $modelClassNames[0]);
            $this->assertEquals('UserSearch', $modelClassNames[1]);
        }

        public function testLogAuditEventsListForCreatedAndModifedCreatingFirstUser()
        {
            Yii::app()->user->userModel = null;
            $user = new User();
            $user->username           = 'myuser';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'Userson';
            $user->setPassword('myuser');
            $saved = $user->save();
            $this->assertTrue($saved);
            $this->assertEquals(Yii::app()->user->userModel, $user);

            //Create a second user and confirm the first user is still the current user.
            $user2 = new User();
            $user2->username           = 'myuser2';
            $user2->title->value       = 'Mr.';
            $user2->firstName          = 'My';
            $user2->lastName           = 'Userson2';
            $user2->setPassword('myuser2');
            $this->assertTrue($user2->save());
            $this->assertEquals(Yii::app()->user->userModel, $user);
        }

        public function testAvatarForUser()
        {
            //Create a new user and confirm that gets the default avatar
            $user = new User();
            $user->username = 'avatar';
            $user->lastName = 'User';
            $this->assertTrue($user->save());
            $this->assertContains('width="250" height="250" src="//www.gravatar.com/avatar/?s=250&amp;r=g&amp;d=mm', // Not Coding Standard
                    $user->getAvatarImage());
            //When calling getAvatarImage it should return the same url to avoid querying gravatar twice
            $this->assertContains('width="50" height="50" src="//www.gravatar.com/avatar/?s=50&amp;r=g&amp;d=mm',   // Not Coding Standard
                    $user->getAvatarImage(50));
            unset($user);

            //Add avatar info to the user and confirm it gets saved
            $user = User::getByUsername('avatar');
            $avatar = array('avatarType' => 1);
            $user->serializeAndSetAvatarData($avatar);
            $this->assertEquals(serialize($avatar), $user->serializedAvatarData);
            $this->assertTrue($user->save());
            unset($user);
            $user = User::getByUsername('avatar');
            $this->assertContains('width="250" height="250" src="//www.gravatar.com/avatar/?s=250&amp;r=g&amp;d=mm', // Not Coding Standard
                    $user->getAvatarImage());
            $this->assertContains('width="50" height="50" src="//www.gravatar.com/avatar/?s=50&amp;r=g&amp;d=mm',   // Not Coding Standard
                    $user->getAvatarImage(50));
            unset($user);

            //Change avatar to primary email address
            $user = new User();
            $user->username = 'avatar2';
            $user->lastName = 'User';
            $emailAddress = 'avatar@zurmo.org';
            $user->primaryEmail->emailAddress = $emailAddress;
            $user->primaryEmail->optOut       = 1;
            $user->primaryEmail->isInvalid    = 0;
            $avatar = array('avatarType' => 2);
            $user->serializeAndSetAvatarData($avatar);
            $this->assertContains(serialize($avatar), $user->serializedAvatarData);
            $this->assertTrue($user->save());
            unset($user);
            $user = User::getByUsername('avatar2');
            $avatarUrl   = 'width="250" height="250" src="//www.gravatar.com/avatar/' .
                    md5(strtolower(trim($emailAddress))) .
                    '?s=250&amp;r=g&amp;d=identicon'; // Not Coding Standard
            $this->assertContains($avatarUrl, $user->getAvatarImage());
            $avatarUrl   = 'width="5" height="5" src="//www.gravatar.com/avatar/' .
                    md5(strtolower(trim($emailAddress))) .
                    '?s=5&amp;r=g&amp;d=identicon'; // Not Coding Standard
            $this->assertContains($avatarUrl, $user->getAvatarImage(5));
            unset($user);

            //Change avatar to custom avatar email address
            $user = new User();
            $user->username = 'avatar3';
            $user->lastName = 'User';
            $emailAddress = 'avatar-custom@zurmo.org';
            $avatar = array('avatarType' => 3, 'customAvatarEmailAddress' => $emailAddress);
            $user->serializeAndSetAvatarData($avatar);
            $this->assertEquals(serialize($avatar), $user->serializedAvatarData);
            $this->assertTrue($user->save());
            unset($user);
            $user = User::getByUsername('avatar3');
            $avatarUrl   = 'width="250" height="250" src="//www.gravatar.com/avatar/' .
                    md5(strtolower(trim($emailAddress))) .
                    "?s=250&amp;r=g&amp;d=identicon"; // Not Coding Standard
            $this->assertContains($avatarUrl, $user->getAvatarImage());
            $avatarUrl   = 'width="2500" height="2500" src="//www.gravatar.com/avatar/' .
                    md5(strtolower(trim($emailAddress))) .
                    "?s=2500&amp;r=g&amp;d=identicon"; // Not Coding Standard
            $this->assertContains($avatarUrl, $user->getAvatarImage(2500));
            unset($user);
        }

        /**
         * @expectedException NotSupportedException
         */
        public function testDeleteLastUserInSuperAdministratorsGroup()
        {
            Yii::app()->user->userModel = User::getByUsername('super');

            $superAdminGroup = Group::getByName(Group::SUPER_ADMINISTRATORS_GROUP_NAME);
            //At this point the super administrator is part of this group
            $this->assertEquals(1, $superAdminGroup->users->count());

            //Now try to delete super user, It should not work
            $this->assertFalse(Yii::app()->user->userModel->delete());
            $this->fail();
        }

        /**
         * test for checking isActive attribute
         */
        public function testIsActiveOnUserSave()
        {
            $user = new User();
            $user->username           = 'activeuser';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'activeuserson';
            $user->setPassword('myuser');
            $this->assertTrue($user->save());
            unset($user);

            $user = User::getByUsername('activeuser');
            $this->assertEquals(1, $user->isActive);
            unset($user);

            //Change the user's status to inactive and confirm the changes in rights and isActive attribute.
            $user = User::getByUsername('activeuser');
            $user->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB, RIGHT::DENY);
            $this->assertTrue($user->save());
            $this->assertEquals(0, $user->isActive);
            unset($user);

            //Now change the user's status back to active.
            $user = User::getByUsername('activeuser');
            $user->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB, RIGHT::ALLOW);
            $this->assertTrue($user->save());
            $this->assertEquals(1, $user->isActive);
            unset($user);
        }

        public function testUserLocaleSettings()
        {
            $user = new User();
            $user->username             = 'userforlocaletest';
            $user->title->value         = 'Mr.';
            $user->firstName            = 'Locale';
            $user->lastName             = 'User';
            $user->setPassword('localeuser');
            $this->assertTrue($user->save());

            $user = User::getByUsername('userForLocaleTest');
            $this->assertNull($user->locale);
            Yii::app()->user->userModel = $user;
            $this->assertEquals('12/1/13 12:00:00 AM',
                                Yii::app()->dateFormatter->formatDateTime('2013-12-01', 'short'));
            $user->locale               = 'en_gb';
            $this->assertTrue($user->save());

            $user = User::getByUsername('userForLocaleTest');
            $this->assertContains($user->locale, ZurmoLocale::getSelectableLocaleIds());
            Yii::app()->user->userModel = $user;
            $this->assertEquals('01/12/2013 00:00:00',
                                Yii::app()->dateFormatter->formatDateTime('2013-12-01', 'short'));
        }

        public function testLastLoginDateTimeAttribute()
        {
            $user = new User();
            $user->username           = 'lastloginuser';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'myFirstName';
            $user->lastName           = 'myLastName';
            $user->setPassword('lastlogin');
            $user->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB);
            $this->assertTrue($user->save());

            $user = User::getByUsername('lastloginuser');
            $this->assertNull($user->lastLoginDateTime);
            unset($user);

            $now = time();
            User::authenticate('lastloginuser', 'lastlogin');
            $user = User::getByUsername('lastloginuser');
            $this->assertLessThanOrEqual(5, $user->lastLoginDateTime - $now);
        }

        public function testTrimUsername()
        {
            $user = new User();
            $user->username           = ' trimusername ';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'trim';
            $user->lastName           = 'username';
            $user->setPassword('trimusername');
            $this->assertTrue($user->save());

            $user = User::getByUsername('trimusername');
            $this->assertEquals('trimusername', $user->username);
        }

        /**
         * test for checking hideFromSelecting attribute
         */
        public function testHideFromSelectingOnUserSave()
        {
            $user = new User();
            $user->username           = 'hidefromselectuser';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'hidefromselectuser';
            $user->hideFromSelecting  = true;
            $user->setPassword('myuser');
            $this->assertTrue($user->save());
            unset($user);

            $user = User::getByUsername('hidefromselectuser');
            $this->assertEquals(1, $user->hideFromSelecting);
            unset($user);

            $userSet = UserSearch::getUsersByPartialFullName('hide', 20);
            $this->assertEquals(0, count($userSet));

            $user = User::getByUsername('hidefromselectuser');
            $user->hideFromSelecting  = false;
            $this->assertTrue($user->save());
            unset($user);

            $user = User::getByUsername('hidefromselectuser');
            $this->assertEquals(0, $user->hideFromSelecting);
            unset($user);

            $userSet = UserSearch::getUsersByPartialFullName('hide', 20);

            $this->assertEquals(1, count($userSet));
        }

        /**
         * test for checking hideFromLeaderboard attribute
         */
        public function testHideFromLeaderboardOnUserSave()
        {
            $user = new User();
            $user->username           = 'leaderboard';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'leaderboard';
            $user->hideFromLeaderboard  = true;
            $user->setPassword('myuser');
            $this->assertTrue($user->save());
            unset($user);

            $user = User::getByUsername('leaderboard');
            Yii::app()->user->userModel = $user;

            $pointTypeAndValueData = array('some type' => 400);
            GamePointUtil::addPointsByPointData(Yii::app()->user->userModel, $pointTypeAndValueData);
            Yii::app()->gameHelper->processDeferredPoints();

            $user = User::getByUsername('leaderboard');
            $this->assertEquals(1, $user->hideFromLeaderboard);
            unset($user);

            $userSet = GamePointUtil::getUserLeaderboardData(GamePointUtil::LEADERBOARD_TYPE_OVERALL);
            $this->assertEquals(0, count($userSet));

            $user = User::getByUsername('leaderboard');
            $user->hideFromLeaderboard  = false;
            $this->assertTrue($user->save());
            unset($user);

            $user = User::getByUsername('leaderboard');
            $this->assertEquals(0, $user->hideFromLeaderboard);
            unset($user);

            $userSet = GamePointUtil::getUserLeaderboardData(GamePointUtil::LEADERBOARD_TYPE_OVERALL);

            $this->assertTrue(count($userSet) > 0);
        }

        /**
         * test for checking hideFromSelecting attribute
         */
        public function testIsRootUserOnUserSave()
        {
            $user = new User();
            $user->username           = 'rootuser';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'rootuser';
            $user->setPassword('myuser');
            $this->assertTrue($user->save());
            unset($user);

            $user = User::getByUsername('rootuser');
            $this->assertNull($user->isRootUser);
            unset($user);

            $superUser = User::getByUsername('leaderboard');
            Yii::app()->user->userModel = $superUser;

            $user = User::getByUsername('rootuser');
            $this->assertTrue(UserAccessUtil::resolveCanCurrentUserAccessRootUser($user));

            $user->setIsRootUser();
            $this->assertTrue($user->save());
            unset($user);
            $user = User::getByUsername('rootuser');
            $this->assertFalse(UserAccessUtil::resolveCanCurrentUserAccessRootUser($user, false));

            $user = new User();
            $user->username           = 'rootuser2';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'rootuser2';
            $user->setPassword('myuser');
            $this->assertTrue($user->save());
            unset($user);

            //Get root user count
            $this->assertEquals(1, User::getRootUserCount());

            //Take care that only root user could be there
            $user = User::getByUsername('rootuser2');
            try
            {
                $user->setIsRootUser();
            }
            catch (Exception $e)
            {
                $this->assertEquals('ExistingRootUserException', get_class($e));
            }
        }

        /**
         * test for checking hideFromSelecting attribute
         */
        public function testIsSystemUserAndActiveUserCountOnUserSave()
        {
            $user = new User();
            $user->username           = 'sysuser';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'sysuser';
            $user->setPassword('myuser');
            $this->assertTrue($user->save());
            unset($user);

            $user = User::getByUsername('sysuser');
            $this->assertNull($user->isSystemUser);
            unset($user);

            //Check active user count
            $activeUserCount = User::getActiveUserCount();
            $this->assertEquals(26, $activeUserCount);

            $user = User::getByUsername('sysuser');
            $this->assertTrue(UserAccessUtil::resolveAccessingASystemUser($user));

            $user->setIsSystemUser();
            $this->assertTrue($user->save());
            unset($user);
            $user = User::getByUsername('sysuser');
            $this->assertFalse(UserAccessUtil::resolveAccessingASystemUser($user, false));

            //As the user has been made a system user so count should reduce
            $activeUserCount = User::getActiveUserCount();
            $this->assertEquals(25, $activeUserCount);

            $user = User::getByUsername('rootuser');
            $user->setIsNotRootUser();
            $this->assertTrue($user->save());
            unset($user);

            //As the user removed from root user so count should increase
            $activeUserCount = User::getActiveUserCount();
            $this->assertEquals(26, $activeUserCount);
        }

        /**
         * test getUsersByEmailAddress
         */
        public function testGetUsersByEmailAddress()
        {
            $user = UserTestHelper::createBasicUserWithEmailAddress("emailhideuser");
            $user->hideFromSelecting  = true;
            $this->assertTrue($user->save());
            unset($user);
            $users = UserSearch::getUsersByEmailAddress("emailhideuser@zurmo.com", null, false);
            $this->assertEquals(true, (bool)$users[0]->hideFromSelecting);
            $this->assertEquals(1, count($users));

            $users = UserSearch::getUsersByEmailAddress("emailhideuser@zurmo.com", null, true);
            $this->assertEquals(0, count($users));
        }

        /**
         * test getUsersByPartialFullName
         */
        public function testGetUsersByPartialFullName()
        {
            $user = UserTestHelper::createBasicUserWithEmailAddress("partialhideuser");
            $user->hideFromSelecting  = true;
            $this->assertTrue($user->save());
            unset($user);
            $users = UserSearch::getUsersByPartialFullName("partial", 1);
            $this->assertEquals(0, count($users));

            $user = User::getByUsername('partialhideuser');
            $user->hideFromSelecting  = false;
            $this->assertTrue($user->save());
            unset($user);
            $users = UserSearch::getUsersByPartialFullName("partial", 1);
            $this->assertEquals(1, count($users));
        }

        /**
         * Test structure and clauses for NonSystemUsersStateMetadataAdapter
         */
        public function testNonSystemUsersStateMetadataAdapter()
        {
            $nonSystemUsersStateMetadataAdapter = new NonSystemUsersStateMetadataAdapter(array('clauses' => array(), 'structure' => ''));
            $metadata = $nonSystemUsersStateMetadataAdapter->getAdaptedDataProviderMetadata();
            $this->assertEquals('(1 or 2)', $metadata['structure']);

            $nonSystemUsersStateMetadataAdapter1 = new NonSystemUsersStateMetadataAdapter(array('clauses' => array(), 'structure' => 'x and y'));
            $metadata = $nonSystemUsersStateMetadataAdapter1->getAdaptedDataProviderMetadata();
            $this->assertEquals('(x and y) and (1 or 2)', $metadata['structure']);
        }

        public function testIsSuperAdministrator()
        {
            $userA = User::getByUsername('super');
            $userB = User::getByUsername('dick');
            $this->assertTrue($userA->isSuperAdministrator());
            $this->assertFalse($userB->isSuperAdministrator());
        }

        public function testInactiveUsers()
        {
            $activeUserCount = User::getActiveUserCount();
            $this->assertEquals(28, $activeUserCount);
            $this->assertCount(28, User::getActiveUsers());
            $user = new User();
            $user->username           = 'inactiveuser';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'inactiveuser';
            $user->setPassword('myuser');
            $user->setIsSystemUser();
            $this->assertTrue($user->save());
            $this->assertEquals(28, $activeUserCount);
            $this->assertCount(28, User::getActiveUsers());
        }

        public function testMakeActiveUsersQuerySearchAttributeData()
        {
            $searchAttributeData = User::makeActiveUsersQuerySearchAttributeData();
            $compareData = array(
                'clauses'   => array(
                                1 => array(
                                        "attributeName" => "isActive",
                                        "operatorType"  => "equals",
                                        "value"         => true
                                      ),
                                2 => array(
                                        "attributeName" => "isSystemUser",
                                        "operatorType"  => "equals",
                                        "value"         => 0
                                      ),
                                3 => array(
                                        "attributeName" => "isSystemUser",
                                        "operatorType"  => "isNull",
                                        "value"         => null
                                      ),
                                4 => array(
                                        "attributeName" => "isRootUser",
                                        "operatorType"  => "equals",
                                        "value"         => 0
                                      ),
                                5 => array(
                                        "attributeName" => "isRootUser",
                                        "operatorType"  => "isNull",
                                        "value"         => null
                                      )
                                ),
                'structure' => "1 and (2 or 3) and (4 or 5)"
            );
            $this->assertEquals($compareData, $searchAttributeData);
            $searchAttributeData = User::makeActiveUsersQuerySearchAttributeData(false);
            $compareData = array(
                'clauses'   => array(
                                1 => array(
                                        "attributeName" => "isActive",
                                        "operatorType"  => "equals",
                                        "value"         => true
                                      ),
                                2 => array(
                                        "attributeName" => "isSystemUser",
                                        "operatorType"  => "equals",
                                        "value"         => 0
                                      ),
                                3 => array(
                                        "attributeName" => "isSystemUser",
                                        "operatorType"  => "isNull",
                                        "value"         => null
                                      ),
                                4 => array(
                                        "attributeName" => "isRootUser",
                                        "operatorType"  => "equals",
                                        "value"         => 0
                                      ),
                                5 => array(
                                        "attributeName" => "isRootUser",
                                        "operatorType"  => "isNull",
                                        "value"         => null
                                      )
                                ),
                'structure' => "1 and (2 or 3) and (4 or 5)"
            );
            $this->assertEquals($compareData, $searchAttributeData);
            $searchAttributeData = User::makeActiveUsersQuerySearchAttributeData(true);
            $compareData = array(
                'clauses'   => array(
                                1 => array(
                                        "attributeName" => "isActive",
                                        "operatorType"  => "equals",
                                        "value"         => true
                                      ),
                                2 => array(
                                        "attributeName" => "isSystemUser",
                                        "operatorType"  => "equals",
                                        "value"         => 0
                                      ),
                                3 => array(
                                        "attributeName" => "isSystemUser",
                                        "operatorType"  => "isNull",
                                        "value"         => null
                                      ),
                                ),
                'structure' => "1 and (2 or 3)"
            );
            $this->assertEquals($compareData, $searchAttributeData);
        }

        public function testActiveUsers()
        {
            $activeUserCount = User::getActiveUserCount();
            $this->assertEquals(28, $activeUserCount);
            $this->assertCount(28, User::getActiveUsers());

            $activeUserCount = User::getActiveUserCount(false);
            $this->assertEquals(28, $activeUserCount);
            $this->assertCount(28, User::getActiveUsers(false));

            $activeUserCount = User::getActiveUserCount(true);
            $this->assertEquals(28, $activeUserCount);
            $this->assertCount(28, User::getActiveUsers(true));

            $user = User::getByUsername('rootuser');
            $this->assertTrue(UserAccessUtil::resolveCanCurrentUserAccessRootUser($user));
            $user->setIsRootUser();
            $this->assertTrue($user->save());
            unset($user);

            $activeUserCount = User::getActiveUserCount();
            $this->assertEquals(27, $activeUserCount);
            $this->assertCount(27, User::getActiveUsers());

            $activeUserCount = User::getActiveUserCount(false);
            $this->assertEquals(27, $activeUserCount);
            $this->assertCount(27, User::getActiveUsers(false));

            $activeUserCount = User::getActiveUserCount(true);
            $this->assertEquals(28, $activeUserCount);
            $this->assertCount(28, User::getActiveUsers(true));
        }

        public function testLogAuditEventsForIsActive()
        {
            $user = new User();
            $user->username           = 'testlogauditforisactive';
            $user->title->value       = 'Mr.';
            $user->firstName          = 'My';
            $user->lastName           = 'testlogauditforisactive';
            $user->setPassword('testlogauditforisactive');
            $this->assertTrue($user->save());
            unset($user);

            $user = User::getByUsername('testlogauditforisactive');
            $this->assertEquals(1, $user->isActive);
            unset($user);

            AuditEvent::deleteAll();

            //Change the user's status to inactive and confirm new audit event is created
            $user = User::getByUsername('testlogauditforisactive');
            $user->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB, RIGHT::DENY);
            $this->assertTrue($user->save());
            $this->assertEquals(0, $user->isActive);
            $auditEvents = AuditEvent::getAll();
            $this->assertCount(1, $auditEvents);
            $this->assertContains('Item Modified', strval($auditEvents[0]));
            unset($user);

            //Now change the user's status back to active and confirm new audit event is created
            $user = User::getByUsername('testlogauditforisactive');
            $user->setRight('UsersModule', UsersModule::RIGHT_LOGIN_VIA_WEB, RIGHT::ALLOW);
            $this->assertTrue($user->save());
            $this->assertEquals(1, $user->isActive);
            $auditEvents = AuditEvent::getAll();
            $this->assertCount(2, $auditEvents);
            $this->assertContains('Item Modified', strval($auditEvents[1]));
            unset($user);
        }

        public function testSetMetadata()
        {
            $metadata   = User::getMetadata();
            $this->assertArrayHasKey('Person', $metadata);
            $this->assertNotEmpty($metadata['Person']);
            $this->assertArrayHasKey('User', $metadata);
            $this->assertNotEmpty($metadata['User']);
            $personMetaData = $metadata['Person'];
            $userMetaData   = $metadata['User'];
            $this->assertArrayHasKey('members', $personMetaData);
            $this->assertCount(7, $personMetaData['members']);
            $this->assertArrayHasKey('members', $userMetaData);
            $this->assertCount(12, $userMetaData['members']);

            // unset a member from person, update metadata
            unset($personMetaData['members'][0]);
            User::setMetadata(array('Person' => $personMetaData));

            // ensure metadata update has propagated
            $metadata   = User::getMetadata();
            $this->assertArrayHasKey('Person', $metadata);
            $this->assertNotEmpty($metadata['Person']);
            $this->assertArrayHasKey('User', $metadata);
            $this->assertNotEmpty($metadata['User']);
            $personMetaData = $metadata['Person'];
            $userMetaData   = $metadata['User'];
            $this->assertArrayHasKey('members', $personMetaData);
            $this->assertCount(6, $personMetaData['members']);
            $this->assertArrayHasKey('members', $userMetaData);
            $this->assertCount(12, $userMetaData['members']);

            // unset a member from User, update metadata
            unset($userMetaData['members'][0]);
            User::setMetadata(array('User' => $userMetaData));

            // ensure metadata update has propagated
            $metadata   = User::getMetadata();
            $this->assertArrayHasKey('Person', $metadata);
            $this->assertNotEmpty($metadata['Person']);
            $this->assertArrayHasKey('User', $metadata);
            $this->assertNotEmpty($metadata['User']);
            $personMetaData = $metadata['Person'];
            $userMetaData   = $metadata['User'];
            $this->assertArrayHasKey('members', $personMetaData);
            $this->assertCount(6, $personMetaData['members']);
            $this->assertArrayHasKey('members', $userMetaData);
            $this->assertCount(11, $userMetaData['members']);

            // unset a member from User and Person, update metadata
            unset($userMetaData['members'][1]);
            unset($personMetaData['members'][1]);
            User::setMetadata(array('Person' => $personMetaData, 'User' => $userMetaData));

            // ensure metadata update has propagated
            $metadata   = User::getMetadata();
            $this->assertArrayHasKey('Person', $metadata);
            $this->assertNotEmpty($metadata['Person']);
            $this->assertArrayHasKey('User', $metadata);
            $this->assertNotEmpty($metadata['User']);
            $personMetaData = $metadata['Person'];
            $userMetaData   = $metadata['User'];
            $this->assertArrayHasKey('members', $personMetaData);
            $this->assertCount(5, $personMetaData['members']);
            $this->assertArrayHasKey('members', $userMetaData);
            $this->assertCount(10, $userMetaData['members']);
        }
    }
?>
