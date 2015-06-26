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

    class EmailArchivingUtilTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            AllPermissionsOptimizationUtil::rebuild();
            $box = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
        }

        public function testIsMessageForwarded()
        {
            Yii::app()->imap->imapUsername = 'dropbox@emample.com';

            $imapMessage = new ImapMessage();
            $imapMessage->subject        = "Test subject";
            $imapMessage->to[0]['email'] = 'dropbox@emample.com';
            $imapMessage->fromEmail      = 'steve@example.com';
            $this->assertTrue(EmailArchivingUtil::isMessageForwarded($imapMessage));

            $imapMessage->to[0]['email'] = 'dropbox@emample.com';
            $imapMessage->to[1]['email'] = 'john@emample.com';
            $this->assertTrue(EmailArchivingUtil::isMessageForwarded($imapMessage));

            $imapMessage->to[0]['email'] = 'john@emample.com';
            $imapMessage->to[1]['email'] = 'dropbox@emample.com';
            $this->assertTrue(EmailArchivingUtil::isMessageForwarded($imapMessage));

            $imapMessage = new ImapMessage();
            $imapMessage->subject        = "Test subject";
            $imapMessage->to[0]['email'] = 'john@emample.com';
            $imapMessage->cc[0]['email'] = 'dropbox@emample.com';
            $this->assertFalse(EmailArchivingUtil::isMessageForwarded($imapMessage));

            $imapMessage = new ImapMessage();
            $imapMessage->subject        = "Test subject";
            $imapMessage->to[0]['email'] = 'john@emample.com';
            $imapMessage->cc[0]['email'] = 'peter@emample.com';
            $imapMessage->cc[1]['email'] = 'dropbox@emample.com';
            $this->assertFalse(EmailArchivingUtil::isMessageForwarded($imapMessage));

            // Bcc is not visible when email message is sent to dropbox as Bcc
            $imapMessage = new ImapMessage();
            $imapMessage->subject        = "Test subject";
            $imapMessage->to[0]['email'] = 'john@emample.com';
            $this->assertFalse(EmailArchivingUtil::isMessageForwarded($imapMessage));
        }

        public function testResolveEmailSenderFromForwardedEmailMessage()
        {
            $imapMessage = new ImapMessage();
            $imapMessage->subject = "Test subject";
            $imapMessage->fromEmail = "test@example.com";

            // Outlook, Yahoo, Outlook express format
            // Begin Not Coding Standard
            $imapMessage->textBody = "
From: John Smith [mailto:john@example.com]
Sent: 02 March 2012 AM 01:23
To: 'Steve Tytler' <steve@example.com>, Peter Smith <peter@example.com>
Cc: 'John Wein' <john@example.com>, Peter Smith <peter@example.com>
Subject: Hello Steve";
            // End Not Coding Standard

            $imapMessage->subject = "FW: Test subject";
            $sender = EmailArchivingUtil::resolveEmailSenderFromForwardedEmailMessage($imapMessage);
            $this->assertEquals('john@example.com', $sender['email']);
            $this->assertEquals('John Smith', $sender['name']);

            //Google, Thunderbird format
            // Begin Not Coding Standard
            $imapMessage->textBody = "
From: John Smith <john@example.com>
Date: Thu, Apr 19, 2012 at 5:22 PM
Subject: Hello Steve
To: 'Steve Tytler' <steve@example.com>, Peter Smith <peter@example.com>
Cc: 'John Wein' <john@example.com>, Peter Smith <peter@example.com>";
            // End Not Coding Standard

            $sender = EmailArchivingUtil::resolveEmailSenderFromForwardedEmailMessage($imapMessage);
            $this->assertEquals('john@example.com', $sender['email']);
            $this->assertEquals('John Smith', $sender['name']);

            $imapMessage->textBody = "
Date: Thu, Apr 19, 2012 at 5:22 PM
Subject: Hello Steve
To: 'Steve'";

            $sender = EmailArchivingUtil::resolveEmailSenderFromForwardedEmailMessage($imapMessage);
            $this->assertFalse($sender);
        }

        /**
        * @depends testResolveEmailSenderFromForwardedEmailMessage
        */
        public function testResolveEmailSenderFromEmailMessage()
        {
            Yii::app()->imap->imapUsername = 'dropbox@emample.com';

            $imapMessage = new ImapMessage();
            $imapMessage->subject = "Test subject";
            $imapMessage->fromEmail = "test@example.com";
            $imapMessage->cc[0]['email'] = 'dropbox@emample.com';
            $from = EmailArchivingUtil::resolveEmailSenderFromEmailMessage($imapMessage);
            $this->assertEquals($imapMessage->fromEmail, $from['email']);

            $imapMessage = new ImapMessage();
            $imapMessage->fromEmail = "test@example.com";
            $imapMessage->to[0]['email'] = 'dropbox@emample.com';

            // Outlook, Yahoo, Outlook express format
            $imapMessage->textBody = "
From: John Smith [mailto:john@example.com]
Sent: 02 March 2012 AM 01:23
To: 'Steve Tytler' <steve@example.com>, Peter Smith <peter@example.com>
Cc: Peter Smith <peter@example.com>
Subject: Hello Steve";

            $imapMessage->subject = "FW: Test subject";
            $from = EmailArchivingUtil::resolveEmailSenderFromEmailMessage($imapMessage);
            $this->assertEquals('john@example.com', $from['email']);
            $this->assertEquals('John Smith', $from['name']);

            //Google, Thunderbird format
            $imapMessage->textBody = "
From: John Smith <john@example.com>
Date: Thu, Apr 19, 2012 at 5:22 PM
Subject: Hello Steve
To: 'Steve Tytler' <steve@example.com>, Peter Smith <peter@example.com>
Cc: 'John Wein' <john@example.com>, Peter Smith <peter@example.com>";
            $from = EmailArchivingUtil::resolveEmailSenderFromEmailMessage($imapMessage);
            $this->assertEquals('john@example.com', $from['email']);
            $this->assertEquals('John Smith', $from['name']);

            $imapMessage = new ImapMessage();
            $imapMessage->subject = "Fwd: Test subject";
            $imapMessage->to[0]['email'] = 'dropbox@emample.com';
            // Begin Not Coding Standard
            $imapMessage->htmlBody = "

            -------- Original Message --------
            Subject:    Test
            Date:   Mon, 28 May 2012 15:43:39 +0200
            From:   John Smith <john@example.com>
            To: 'Steve'
            ";
            // End Not Coding Standard
            $from = EmailArchivingUtil::resolveEmailSenderFromEmailMessage($imapMessage);
            $this->assertEquals('john@example.com', $from['email']);
            $this->assertEquals('John Smith', $from['name']);

            $imapMessage = new ImapMessage();
            $imapMessage->to[0]['email'] = 'dropbox@emample.com';
            $imapMessage->subject = "Fwd: Test subject";
            // Begin Not Coding Standard
            $imapMessage->textBody = "
-------- Original Message --------
Subject:     Test
Date:   Mon, 28 May 2012 15:43:39 +0200
From:   John Smith <john@example.com>
To: 'Steve Tytler' <steve@example.com>, Peter Smith <peter@example.com>
Cc: 'John Wein' <john@example.com>, Peter Smith <peter@example.com>
";
            // End Not Coding Standard
            $from = EmailArchivingUtil::resolveEmailSenderFromEmailMessage($imapMessage);
            $this->assertEquals('john@example.com', $from['email']);
            $this->assertEquals('John Smith', $from['name']);
        }

        /**
        * @depends testIsMessageForwarded
        */
        public function testResolveEmailRecipientsFromEmailMessage()
        {
            Yii::app()->imap->imapUsername = 'dropbox@emample.com';
            $imapMessage = new ImapMessage();
            $imapMessage->subject = "Test subject";
            $imapMessage->fromEmail = "test@example.com";
            $imapMessage->to = array(
                array('email' => 'info@example.com')
            );

            $recipients = EmailArchivingUtil::resolveEmailRecipientsFromEmailMessage($imapMessage);
            $this->assertEquals($imapMessage->to, $recipients);

            // Check with multiple recipients.
            $imapMessage->to = array(
                array('email' => 'info2@example.com'),
                array('email' => 'info@example.com')
            );
            $recipients = EmailArchivingUtil::resolveEmailRecipientsFromEmailMessage($imapMessage);
            $this->assertEquals($imapMessage->to, $recipients);

            // Check without to recipients.
            $imapMessage->to = array();
            $imapMessage->cc = array(
                array('email' => 'info@example.com')
            );
            $recipients = EmailArchivingUtil::resolveEmailRecipientsFromEmailMessage($imapMessage);
            $this->assertEquals($imapMessage->cc, $recipients);

            //Test when email and name are the same
            $imapMessage->to = array(
                array('name'  => 'info@example.com',
                      'email' => 'info@example.com')
            );
            $imapMessage->cc = array();
            $recipients = EmailArchivingUtil::resolveEmailRecipientsFromEmailMessage($imapMessage);
            $this->assertEquals($imapMessage->to, $recipients);

            $imapMessage->subject = "FW: Test subject";
            $imapMessage->to[0]['email'] = 'dropbox@emample.com';
            $recipients = EmailArchivingUtil::resolveEmailRecipientsFromEmailMessage($imapMessage);
            $compareData = array(
                               array(
                                   'email' => $imapMessage->fromEmail,
                                   'name'  => '',
                                   'type'  => EmailMessageRecipient::TYPE_TO
                               )
                           );
            $this->assertEquals($compareData, $recipients);
        }

        /**
        * @depends testIsMessageForwarded
        */
        public function testResolveOwnerOfEmailMessage()
        {
            $user = UserTestHelper::createBasicUser('billy');
            $email = new Email();
            $email->emailAddress = 'billy@example.com';
            $user->primaryEmail = $email;
            $this->assertTrue($user->save());

            // User send message to dropbox, via additional to field
            // This shouldn't be done in practice, but we need to cover it.
            $imapMessage = new ImapMessage();
            $imapMessage->subject = "Test subject";
            $imapMessage->fromEmail = "billy@example.com";
            $imapMessage->to = array(
                array('email' => 'info@example.com'),
                array('email' => 'dropbox@example.com'),
            );
            $owner = EmailArchivingUtil::resolveOwnerOfEmailMessage($imapMessage);
            $this->assertEquals($user->id, $owner->id);

            // User sent CC copy of his email to dropbox
            // This also shouldn't be done in practice, because email recipient will see dropbox email account,
            // but we need to cover it.
            $imapMessage = new ImapMessage();
            $imapMessage->subject = "Test subject";
            $imapMessage->fromEmail = "billy@example.com";
            $imapMessage->to = array(
                array('email' => 'info@example.com'),
            );
            $imapMessage->cc = array(
                array('email' => 'dropbox@example.com'),
            );
            $owner = EmailArchivingUtil::resolveOwnerOfEmailMessage($imapMessage);
            $this->assertEquals($user->id, $owner->id);

            // User sent BCC copy of his email to dropbox
            $imapMessage = new ImapMessage();
            $imapMessage->subject = "Test subject";
            $imapMessage->fromEmail = "billy@example.com";
            $imapMessage->to = array(
                array('email' => 'info@example.com'),
            );
            $imapMessage->bcc = array(
                array('email' => 'dropbox@example.com'),
            );
            $owner = EmailArchivingUtil::resolveOwnerOfEmailMessage($imapMessage);
            $this->assertEquals($user->id, $owner->id);

            // Forwarded message, user should be in from field
            $imapMessage->subject = "Fwd: Test subject";
            $imapMessage->fromEmail = "billy@example.com";
            $imapMessage->to = array(
                array('email' => 'dropbox@example.com'),
            );
            $owner = EmailArchivingUtil::resolveOwnerOfEmailMessage($imapMessage);
            $this->assertEquals($user->id, $owner->id);
        }

        public function testResolvePersonOrAccountByEmailAddress()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            $user = UserTestHelper::createBasicUser('joseph');
            $anotherUser = UserTestHelper::createBasicUser('josephine');

            $emailAddress = 'sameone234@example.com';

            // There are no users in system with the email.
            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        false,
                                                                                        false,
                                                                                        false);
            $this->assertNull($personOrAccount);

            // There is user is system with the email.
            Yii::app()->user->userModel = $super;
            $anotherUser->primaryEmail->emailAddress = $emailAddress;
            $this->assertTrue($anotherUser->save());

            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        false,
                                                                                        false,
                                                                                        false);
            $this->assertEquals($anotherUser->id, $personOrAccount->id);
            $this->assertTrue($personOrAccount instanceof User);

            // Now test email with accounts.
            // User can access accounts, but there are no accounts in system with the email.
            $anotherUser->primaryEmail->emailAddress = 'sample45@example.com';
            $this->assertTrue($anotherUser->save());
            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        false,
                                                                                        false,
                                                                                        true);
            $this->assertNull($personOrAccount);

            // User can access accounts, but there are no accounts in system with the email.
            // But there is user is system with the email
            Yii::app()->user->userModel = $super;
            $anotherUser->primaryEmail->emailAddress = $emailAddress;
            $this->assertTrue($anotherUser->save());
            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        false,
                                                                                        false,
                                                                                        true);
            $this->assertEquals($anotherUser->id, $personOrAccount->id);
            $this->assertTrue($personOrAccount instanceof User);

            // User can access accounts, and there is account in system with the email.
            // But owner of email is super users, so it shouldn't return account
            Yii::app()->user->userModel = $super;
            $anotherUser->primaryEmail->emailAddress = 'sample45@example.com';
            $this->assertTrue($anotherUser->save());

            Yii::app()->user->userModel = $super;
            $email = new Email();
            $email->emailAddress = $emailAddress;
            $email2 = new Email();
            $email2->emailAddress = 'aabb@example.com';
            $account = new Account();
            $account->owner       = $super;
            $account->name        = 'Test Account';
            $account->primaryEmail = $email;
            $account->secondaryEmail = $email2;

            $this->assertTrue($account->save());
            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        false,
                                                                                        false,
                                                                                        true);
            $this->assertNull($personOrAccount);
            Yii::app()->user->userModel = $super;
            $account->owner       = $user;
            $this->assertTrue($account->save());
            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        false,
                                                                                        false,
                                                                                        true);

            $this->assertEquals($account->id, $personOrAccount->id);
            $this->assertTrue($personOrAccount instanceof Account);

            // Now test with contacts/leads. Please note that we are not removing email address
            // from users and accounts, so if contact or lead exist with this email, they should be returned
            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        true,
                                                                                        true,
                                                                                        false);
            $this->assertNull($personOrAccount);

            // User can access contacts, but there are no contact in system with the email.
            // But there is user and account is system with the email
            Yii::app()->user->userModel = $super;
            $anotherUser->primaryEmail->emailAddress = $emailAddress;
            $this->assertTrue($anotherUser->save());
            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        false,
                                                                                        false,
                                                                                        true);
            $this->assertEquals($account->id, $personOrAccount->id);
            $this->assertTrue($personOrAccount instanceof Account);

            // User can access contacts, and there is contact in system with the email.
            // But owner of email is super users, so it shouldn't return contact
            Yii::app()->user->userModel = $super;
            $this->assertTrue(ContactsModule::loadStartingData());
            $this->assertEquals(6, count(ContactState::GetAll()));
            $contactStates = ContactState::getByName('Qualified');
            $email = new Email();
            $email->emailAddress = $emailAddress;
            $email2 = new Email();
            $email2->emailAddress = 'aabb@example.com';
            $contact                = new Contact();
            $contact->state         = $contactStates[0];
            $contact->owner         = $super;
            $contact->firstName     = 'Super';
            $contact->lastName      = 'Man';
            $contact->primaryEmail = $email;
            $contact->secondaryEmail = $email;
            $this->assertTrue($account->save());

            $anotherUser->primaryEmail->emailAddress = 'sample45@example.com';
            $this->assertTrue($anotherUser->save());

            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        true,
                                                                                        true,
                                                                                        false);
            $this->assertNull($personOrAccount);

            Yii::app()->user->userModel = $super;
            $contact->owner       = $user;
            $this->assertTrue($contact->save());

            $anotherUser->primaryEmail->emailAddress = $emailAddress;
            $this->assertTrue($anotherUser->save());

            Yii::app()->user->userModel = $user;
            $personOrAccount = EmailArchivingUtil::resolvePersonOrAccountByEmailAddress($emailAddress,
                                                                                        true,
                                                                                        true,
                                                                                        true);
            $this->assertEquals($contact->id, $personOrAccount->id);
            $this->assertTrue($personOrAccount instanceof Contact);
        }

        public function testGetPersonsAndAccountsByEmailAddress()
        {
            //Create user, contact, lead and accout
            $user    = UserTestHelper::createBasicUser('newUser');
            $account = AccountTestHelper::createAccountByNameForOwner('newAccount', $user);
            $lead    = LeadTestHelper::createLeadbyNameForOwner('newLead', $user);
            $contact = ContactTestHelper::createContactWithAccountByNameForOwner('newContact', $user, $account);
            $lead->primaryEmail->emailAddress      = 'leademail@zurmoland.com';
            $lead->secondaryEmail->emailAddress    = 'leademail2@zurmoland.com';
            $account->primaryEmail->emailAddress   = 'accountemail@zurmoland.com';
            $account->secondaryEmail->emailAddress = 'accountemail2@zurmoland.com';
            $contact->primaryEmail->emailAddress   = 'contactemail@zurmoland.com';
            $contact->secondaryEmail->emailAddress = 'contactemail2@zurmoland.com';
            $this->assertTrue($user->save());
            $this->assertTrue($lead->save());
            $this->assertTrue($account->save());
            $this->assertTrue($contact->save());

            //Test with defaults
            $emailAddress    = 'useremail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddress($emailAddress);
            $this->assertEmpty($personsOrAccounts);
            $emailAddress    = 'leademail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddress($emailAddress);
            $this->assertEmpty($personsOrAccounts);
            $emailAddress    = 'accountemail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddress($emailAddress);
            $this->assertEmpty($personsOrAccounts);
            $emailAddress    = 'contactemail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddress($emailAddress);
            $this->assertEmpty($personsOrAccounts);

            //Test user can access contacts
            $emailAddress    = 'contactemail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddress($emailAddress, true);
            $this->assertNotEmpty($personsOrAccounts);
            $this->assertEquals($personsOrAccounts[0], $contact);

            //Test user can access leads
            $emailAddress    = 'leademail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddress($emailAddress, false, true);
            $this->assertNotEmpty($personsOrAccounts);
            $this->assertEquals($personsOrAccounts[0], $lead);

            //Test user can access accounts
            $emailAddress    = 'accountemail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddress($emailAddress, false, false, true);
            $this->assertNotEmpty($personsOrAccounts);
            $this->assertEquals($personsOrAccounts[0], $account);

            //Test user can access users
            $user->primaryEmail->emailAddress      = 'useremail@zurmoland.com';
            $this->assertTrue($user->save());
            $emailAddress    = 'useremail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddress($emailAddress, false, false, false);
            $this->assertNotEmpty($personsOrAccounts);
            $this->assertEquals($personsOrAccounts[0], $user);
        }

        /**
         * @depends testGetPersonsAndAccountsByEmailAddress
         */
        public function testGetPersonsAndAccountsByEmailAddressForUser()
        {
            $user = User::getByUsername('newUser');
            $emailAddress    = 'leademail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddressForUser($emailAddress, $user);
            $this->assertEmpty($personsOrAccounts);
            $emailAddress    = 'accountemail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddressForUser($emailAddress, $user);
            $this->assertEmpty($personsOrAccounts);
            $emailAddress    = 'contactemail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddressForUser($emailAddress, $user);
            $this->assertEmpty($personsOrAccounts);
            $emailAddress    = 'useremail@zurmoland.com';
            $personsOrAccounts = EmailArchivingUtil::getPersonsAndAccountsByEmailAddressForUser($emailAddress, $user);
            $this->assertEquals(1, count($personsOrAccounts));
            $this->assertTrue($personsOrAccounts[0] instanceof User);
        }

        /**
         *
         * Test EmailArchivingUtil::resolveSanitizeFromImapToUtf8 to ensure that email subject is UTF8
         */
        public function testResolveSanitizeMessageSubject()
        {
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;
            $subjectUTF8 = 'Тестовое письмо. Test email';
            $subjectKOI8R = '=?KOI8-R?Q?=F4=C5=D3=D4=CF=D7=CF=C5_=D0=C9=D3=D8=CD=CF=2E_Te?= =?KOI8-R?Q?st_email?='; // Not Coding Standard
            $emailMessage = new EmailMessage();
            $emailMessage->subject = $subjectKOI8R;
            $this->assertEquals($subjectKOI8R, $emailMessage->subject);
            EmailArchivingUtil::resolveSanitizeFromImapToUtf8($emailMessage);
            $this->assertEquals($subjectUTF8, $emailMessage->subject);
            //$this->assertTrue($emailMessage->save(false));
        }

        public function testCreateEmailMessageSender()
        {
            $user    = UserTestHelper::createBasicUser('senderTestUser');
            Yii::app()->user->userModel = $user;
            $account = AccountTestHelper::createAccountByNameForOwner('senderAccount', $user);
            $account->primaryEmail->emailAddress   = 'senderemail@example.org';
            $this->assertTrue($account->save());

            $contact = ContactTestHelper::createContactByNameForOwner('SenderContact', $user);
            $contact->primaryEmail->emailAddress   = 'senderemail@example.org';
            $this->assertTrue($contact->save());

            $senderInfo = array('name' => 'Sender Name', 'email' => 'senderemail@example.org');

            $emailMessageSender = EmailArchivingUtil::createEmailMessageSender($senderInfo, true, true, true);
            $this->assertTrue($emailMessageSender instanceof EmailMessageSender);
            $this->assertEquals($emailMessageSender->fromAddress, $senderInfo['email']);
            $this->assertEquals($emailMessageSender->fromName, $senderInfo['name']);
            $this->assertTrue($emailMessageSender->personsOrAccounts[0]->isSame($contact));
            $this->assertTrue($emailMessageSender->personsOrAccounts[1]->isSame($account));
            $this->assertEquals($emailMessageSender->personsOrAccounts->count(), 2);

            // Test when user can't access Contacts
            $emailMessageSender = EmailArchivingUtil::createEmailMessageSender($senderInfo, false, true, true);
            $this->assertEquals($emailMessageSender->fromAddress, $senderInfo['email']);
            $this->assertEquals($emailMessageSender->fromName, $senderInfo['name']);
            $this->assertEquals($emailMessageSender->personsOrAccounts->count(), 1);
            $this->assertTrue($emailMessageSender->personsOrAccounts[0]->isSame($account));

            // Test when user can't access Contacts
            $emailMessageSender = EmailArchivingUtil::createEmailMessageSender($senderInfo, false, true, false);
            $this->assertEquals($emailMessageSender->fromAddress, $senderInfo['email']);
            $this->assertEquals($emailMessageSender->fromName, $senderInfo['name']);
            $this->assertEquals($emailMessageSender->personsOrAccounts->count(), 0);

            $senderInfo = array('name' => 'Sender Name 2', 'email' => 'senderemail2@example.org');
            $emailMessageSender = EmailArchivingUtil::createEmailMessageSender($senderInfo, true, true, true);
            $this->assertTrue($emailMessageSender instanceof EmailMessageSender);
            $this->assertEquals($emailMessageSender->fromAddress, $senderInfo['email']);
            $this->assertEquals($emailMessageSender->fromName, $senderInfo['name']);
            $this->assertEquals($emailMessageSender->personsOrAccounts->count(), 0);
        }

        public function testCreateEmailMessageRecipient()
        {
            $user    = UserTestHelper::createBasicUser('recipientTestUser');
            Yii::app()->user->userModel = $user;
            $account = AccountTestHelper::createAccountByNameForOwner('RecipientAccount', $user);
            $account->primaryEmail->emailAddress   = 'recipientemail@example.org';
            $this->assertTrue($account->save());

            $contact = ContactTestHelper::createContactByNameForOwner('RecipientContact', $user);
            $contact->primaryEmail->emailAddress   = 'recipientemail@example.org';
            $this->assertTrue($contact->save());

            $recipientInfo = array('name' => 'Recipient Name', 'email' => 'recipientemail@example.org',
                                   'type' => EmailMessageRecipient::TYPE_CC);

            $emailMessageRecipient = EmailArchivingUtil::createEmailMessageRecipient($recipientInfo, true, true, true);
            $this->assertTrue($emailMessageRecipient instanceof EmailMessageRecipient);
            $this->assertEquals($emailMessageRecipient->toAddress, $recipientInfo['email']);
            $this->assertEquals($emailMessageRecipient->toName, $recipientInfo['name']);
            $this->assertEquals($emailMessageRecipient->type, $recipientInfo['type']);
            $this->assertTrue($emailMessageRecipient->personsOrAccounts[0]->isSame($contact));
            $this->assertTrue($emailMessageRecipient->personsOrAccounts[1]->isSame($account));
            $this->assertEquals($emailMessageRecipient->personsOrAccounts->count(), 2);

            // Test when user can't access Contacts
            $recipientInfo['type'] = EmailMessageRecipient::TYPE_TO;
            $emailMessageRecipient = EmailArchivingUtil::createEmailMessageRecipient($recipientInfo, false, true, true);
            $this->assertTrue($emailMessageRecipient instanceof EmailMessageRecipient);
            $this->assertEquals($emailMessageRecipient->toAddress, $recipientInfo['email']);
            $this->assertEquals($emailMessageRecipient->toName, $recipientInfo['name']);
            $this->assertEquals($emailMessageRecipient->type, $recipientInfo['type']);
            $this->assertEquals($emailMessageRecipient->personsOrAccounts->count(), 1);
            $this->assertTrue($emailMessageRecipient->personsOrAccounts[0]->isSame($account));

            // Test when user can't access Contacts
            $recipientInfo['type'] = EmailMessageRecipient::TYPE_BCC;
            $emailMessageRecipient = EmailArchivingUtil::createEmailMessageRecipient($recipientInfo, false, true, false);
            $this->assertTrue($emailMessageRecipient instanceof EmailMessageRecipient);
            $this->assertEquals($emailMessageRecipient->toAddress, $recipientInfo['email']);
            $this->assertEquals($emailMessageRecipient->toName, $recipientInfo['name']);
            $this->assertEquals($emailMessageRecipient->type, $recipientInfo['type']);
            $this->assertEquals($emailMessageRecipient->personsOrAccounts->count(), 0);

            $recipientInfo = array('name' => 'Recipient Name', 'email' => 'recipientemail2@example.org',
                                   'type' => EmailMessageRecipient::TYPE_CC);
            $emailMessageRecipient = EmailArchivingUtil::createEmailMessageRecipient($recipientInfo, true, true, true);
            $this->assertTrue($emailMessageRecipient instanceof EmailMessageRecipient);
            $this->assertEquals($emailMessageRecipient->toAddress, $recipientInfo['email']);
            $this->assertEquals($emailMessageRecipient->toName, $recipientInfo['name']);
            $this->assertEquals($emailMessageRecipient->type, $recipientInfo['type']);
            $this->assertEquals($emailMessageRecipient->personsOrAccounts->count(), 0);
        }

        public function testCreateEmailAttachment()
        {
            $user    = UserTestHelper::createBasicUser('attachmentTestUser');
            Yii::app()->user->userModel = $user;
            $imapMessage = new ImapMessage();

            $pathToFiles = Yii::getPathOfAlias('application.modules.emailMessages.tests.unit.files');
            $filePath_1  = $pathToFiles . DIRECTORY_SEPARATOR . 'table.csv';
            $data['attachments'] =
                array(
                    array('filename' => 'table.csv', 'attachment' => file_get_contents($filePath_1))
                );
            $imapMessage->attachments = $data['attachments'];
            $file = EmailArchivingUtil::createEmailAttachment($imapMessage->attachments[0]);
            $this->assertTrue($file instanceof FileModel);
            $this->assertEquals($file->name, $imapMessage->attachments[0]['filename']);
            $this->assertTrue($file->fileContent instanceof FileContent);
            $this->assertEquals($file->fileContent->content, $imapMessage->attachments[0]['attachment']);
            $this->assertEquals($file->type, 'text/csv');
            $this->assertEquals($file->size, strlen($imapMessage->attachments[0]['attachment']));

            // Test with not allowed extension
            $imapMessage = new ImapMessage();
            $data['attachments'] =
                array(
                    array('filename' => 'table.abc', 'attachment' => 'notAllowed')
                );
            $imapMessage->attachments = $data['attachments'];
            $file = EmailArchivingUtil::createEmailAttachment($imapMessage->attachments[0]);
            $this->assertFalse($file);
        }
    }
?>
