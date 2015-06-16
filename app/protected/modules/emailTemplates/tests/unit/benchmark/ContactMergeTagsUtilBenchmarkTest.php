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
    class ContactMergeTagsUtilBenchmarkTest extends ZurmoBaseTest
    {
        protected static $contactId;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            $super = User::getByUsername('super');
            Yii::app()->user->userModel = $super;

            $loaded = ContactsModule::loadStartingData();
            if (!$loaded)
            {
                throw new NotSupportedException();
            }
            $emailSignature = new EmailSignature();
            $emailSignature->htmlContent = 'my email signature';
            $super->emailSignatures->add($emailSignature);
            $super->save();

            $currencies                                     = Currency::getAll();
            $currencyValue                                  = new CurrencyValue();
            $currencyValue->value                           = 100;
            $currencyValue->currency                        = $currencies[0];

            $super->lastName                                 = 'Kevin';
            $super->language                                 = 'es';
            $super->timeZone                                 = 'America/Chicago';
            $super->currency                                 = $currencies[0];
            $saved = $super->save();
            assert('$saved'); // Not Coding Standard

            $account                          = AccountTestHelper:: createAccountByNameForOwner('Account', $super);
            $account->billingAddress->street1 = 'AccountStreet1';
            $account->website = "http://www.example.com";
            $account->annualRevenue = "1000000";
            $saved                            = $account->save();
            if (!$saved)
            {
                throw new FailedToSaveModelException();
            }

            $contactStates = ContactState::getByName('Qualified');

            $contact                = new Contact();
            $contact->companyName   = "ABC Soft";
            $contact->owner         = $super;
            $contact->title->value  = 'Mr.';
            $contact->firstName     = 'Super';
            $contact->lastName      = 'Man';
            $contact->jobTitle      = 'Superhero';
            $contact->source->value = 'Outbound';
            $contact->account       = $account;
            $contact->description   = 'Some Description';
            $contact->department    = 'Red Tape';
            $contact->officePhone   = '1234567890';
            $contact->mobilePhone   = '0987654321';
            $contact->officeFax     = '1222222222';
            $contact->state         = $contactStates[0];

            $contact->primaryEmail->emailAddress   = 'thejman@zurmoinc.com';
            $contact->primaryEmail->optOut         = 0;
            $contact->primaryEmail->isInvalid      = 0;
            $contact->secondaryEmail->emailAddress = 'digi@magic.net';
            $contact->secondaryEmail->optOut       = 1;
            $contact->secondaryEmail->isInvalid    = 1;
            $contact->primaryAddress->street1      = '129 Noodle Boulevard';
            $contact->primaryAddress->street2      = 'Apartment 6000A';
            $contact->primaryAddress->city         = 'Noodleville';
            $contact->primaryAddress->postalCode   = '23453';
            $contact->primaryAddress->country      = 'The Good Old US of A';
            $contact->secondaryAddress->street1    = '25 de Agosto 2543';
            $contact->secondaryAddress->street2    = 'Local 3';
            $contact->secondaryAddress->city       = 'Ciudad de Los Fideos';
            $contact->secondaryAddress->postalCode = '5123-4';
            $contact->secondaryAddress->country    = 'Latinoland';

            $saved = $contact->save();
            assert('$saved'); // Not Coding Standard
            self::$contactId = $contact->id;
        }

        public function setUp()
        {
            parent::setUp();
            $this->user                 = User::getByUsername('super');
            Yii::app()->user->userModel = $this->user;
        }

        public function testSingleItem()
        {
            //$this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(1, 2);
        }

        public function testSingleItemRaw()
        {
            $this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(1, 0.1, true);
        }
        /**
         * @depends testSingleItem
         */
        public function testFiveItems()
        {
            //$this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(5, 5);
        }

        /**
         * @depends testFiveItems
         */
        public function testTenItems()
        {
            //$this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(10, 9);
        }

        /**
         * @depends testTenItems
         */
        public function testFiftyItems()
        {
            //$this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(50, 46);
        }

        /**
         * @depends testFiftyItems
         */
        public function testHundredItems()
        {
            //$this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(100, 95);
        }

        /**
         * @depends testHundredItems
         */
        public function testTwoFiftyItems()
        {
            //$this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(250, 240);
        }

        /**
         * @depends testTwoFiftyItems
         */
        public function testFiveHundredItems()
        {
            //$this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(500, 490);
        }

        /**
         * @depends testFiveHundredItems
         */
        public function testThousandItems()
        {
            //$this->ensureTimeSpentIsLessOrEqualThanExpectedForCount(1000, 950);
        }

        protected function ensureTimeSpentIsLessOrEqualThanExpectedForCount($count, $expectedTime, $useRawSqlQuery = false)
        {
            $timeSpent  = $this->mergeContactTags($count, $useRawSqlQuery);
            echo PHP_EOL. $count . ' items took ' . $timeSpent . ' seconds';
            $this->assertLessThanOrEqual($expectedTime, $timeSpent);
        }

        public function mergeContactTags($count, $useRawSqlQuery = false)
        {
            $content                    = <<<MTG
[[COMPANY^NAME]]
[[CREATED^DATE^TIME]]
[[DEPARTMENT]]
[[DESCRIPTION]]
[[FIRST^NAME]]
[[GOOGLE^WEB^TRACKING^ID]]
[[INDUSTRY]]
[[JOB^TITLE]]
[[LAST^NAME]]
[[LATEST^ACTIVITY^DATE^TIME]]
[[MOBILE^PHONE]]
[[MODIFIED^DATE^TIME]]
[[OFFICE^FAX]]
[[OFFICE^PHONE]]
[[TITLE]]
[[SOURCE]]
[[STATE]]
[[WEBSITE]]

[[MODEL^URL]]
[[BASE^URL]]
[[APPLICATION^NAME]]
[[CURRENT^YEAR]]
[[LAST^YEAR]]

[[OWNERS^AVATAR^SMALL]]
[[OWNERS^AVATAR^MEDIUM]]
[[OWNERS^AVATAR^LARGE]]
[[OWNERS^EMAIL^SIGNATURE]]

[[PRIMARY^EMAIL__EMAIL^ADDRESS]]
[[PRIMARY^EMAIL__EMAIL^ADDRESS]]
[[SECONDARY^ADDRESS__CITY]]
[[SECONDARY^ADDRESS__COUNTRY]]
[[SECONDARY^ADDRESS__INVALID]]
[[SECONDARY^ADDRESS__LATITUDE]]
[[SECONDARY^ADDRESS__LONGITUDE]]
[[SECONDARY^ADDRESS__POSTAL^CODE]]
[[SECONDARY^ADDRESS__STATE]]
[[SECONDARY^ADDRESS__STREET1]]
[[SECONDARY^ADDRESS__STREET2]]

[[OWNER__DEPARTMENT]]
[[OWNER__FIRST^NAME]]
[[OWNER__IS^ACTIVE]]
[[OWNER__MOBILE^PHONE]]
[[OWNER__LAST^LOGIN^DATE^TIME]]
[[OWNER__LAST^NAME]]

[[CREATED^BY^USER__FIRST^NAME]]
[[CREATED^BY^USER__LAST^NAME]]
[[CREATED^BY^USER__MOBILE^PHONE]]
[[CREATED^BY^USER__TITLE]]
[[CREATED^BY^USER__USERNAME]]

[[ACCOUNT__ANNUAL^REVENUE]]
[[ACCOUNT__INDUSTRY]]
[[ACCOUNT__NAME]]
[[ACCOUNT__WEBSITE]]
[[ACCOUNT__BILLING^ADDRESS__COUNTRY]]
[[ACCOUNT__BILLING^ADDRESS__CITY]]
[[ACCOUNT__OWNER__FIRST^NAME]]
 ' " ` " '
MTG;
            $startedAt      = microtime(true);

            for ($i = 0; $i < $count; $i++)
            {
                ForgetAllCacheUtil::forgetAllCaches();
                $invalidTags   = array();
                $mergeTagsUtil = MergeTagsUtilFactory::make(EmailTemplate::TYPE_CONTACT, null, $content);
                $this->assertTrue($mergeTagsUtil instanceof MergeTagsUtil);
                $this->assertTrue($mergeTagsUtil instanceof ContactMergeTagsUtil);

                if (!$useRawSqlQuery)
                {
                    $contact = Contact::getById(self::$contactId);
                    $this->assertTrue($contact instanceof Contact);
                    $resolvedContent = $mergeTagsUtil->resolveMergeTags($contact, $invalidTags);
                }
                else
                {
                    $resolvedContent = $mergeTagsUtil->resolveMergeTagsUsingRawSql(self::$contactId, $invalidTags);
                }
                $this->assertTrue($resolvedContent !== false);
                $this->assertNotEquals($resolvedContent, $content);
                //print_r($resolvedContent);
                print_r($invalidTags);
                //$this->assertEmpty($invalidTags);
            }
            $timeTaken      = microtime(true) - $startedAt;
            return $timeTaken;
        }
    }
?>