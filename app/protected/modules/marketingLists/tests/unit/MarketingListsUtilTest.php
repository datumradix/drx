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
    class MarketingListsUtilTest extends ZurmoBaseTest
    {
        protected $super;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
        }

        public function setUp()
        {
            parent::setUp();
            $this->super                = User::getByUsername('super');
            Yii::app()->user->userModel = $this->super;
        }

        public function testResolveMarketingList()
        {

            $existingMarketingList = MarketingListTestHelper::createMarketingListByName('Test List');
            $count = count(MarketingList::getAll());
            $resolveSubscribersForm           = new MarketingListResolveSubscribersFromCampaignForm();
            $resolveSubscribersForm->marketingList['id'] = $existingMarketingList->id;
            $marketingList = MarketingListsUtil::resolveMarketingList($resolveSubscribersForm);
            $this->assertEquals($existingMarketingList->id, $marketingList->id);
            $this->assertEquals($count, count(MarketingList::getAll()));

            // Test case if we provide both marketingListId and newMarketingListName, existing marketing list should be used
            // However in real case this should never happen, because we have validation, that check if only one option is selected
            $resolveSubscribersForm->newMarketingListName = "AAA";
            $marketingList = MarketingListsUtil::resolveMarketingList($resolveSubscribersForm);
            $this->assertEquals($existingMarketingList->id, $marketingList->id);
            $this->assertEquals($count, count(MarketingList::getAll()));

            // Test with invalid marketingListId
            $resolveSubscribersForm->marketingList['id'] = 9999;
            $resolveSubscribersForm->newMarketingListName = null;
            try
            {
                $marketingList = MarketingListsUtil::resolveMarketingList($resolveSubscribersForm);
                $this->fail('Using invalid marketing list id and empty new marketing list name should thrown NotFoundException');
            }
            catch (NotFoundException $e)
            {
                $this->assertEquals('Invalid selected marketing list or not entered new marketing list name. Please go back and select marketing list!', $e->getMessage());
            }

            // Test when new marketing list name is entered
            $resolveSubscribersForm           = new MarketingListResolveSubscribersFromCampaignForm();
            $resolveSubscribersForm->newMarketingListName = "AAA";
            $marketingList = MarketingListsUtil::resolveMarketingList($resolveSubscribersForm);
            $this->assertTrue($marketingList instanceof MarketingList);
            $this->assertEquals($marketingList->name, $resolveSubscribersForm->newMarketingListName);
            $this->assertEquals($count + 1, count(MarketingList::getAll()));
        }

        public function testGetContactsByResolveSubscribersFormAndCampaignAndOffsetAndPageSize()
        {
            $contact1            = ContactTestHelper::createContactByNameForOwner('contact 01', $this->super);
            $contact2            = ContactTestHelper::createContactByNameForOwner('contact 02', $this->super);
            $contact3            = ContactTestHelper::createContactByNameForOwner('contact 03', $this->super);
            $contact4            = ContactTestHelper::createContactByNameForOwner('contact 04', $this->super);
            $contact5            = ContactTestHelper::createContactByNameForOwner('contact 05', $this->super);
            $contact6            = ContactTestHelper::createContactByNameForOwner('contact 06', $this->super);
            $contact7            = ContactTestHelper::createContactByNameForOwner('contact 07', $this->super);
            $contact8            = ContactTestHelper::createContactByNameForOwner('contact 08', $this->super);
            $contact9            = ContactTestHelper::createContactByNameForOwner('contact 09', $this->super);
            $contact10            = ContactTestHelper::createContactByNameForOwner('contact 10', $this->super);
            $contact11            = ContactTestHelper::createContactByNameForOwner('contact 11', $this->super);

            $marketingList = MarketingListTestHelper::createMarketingListByName('Test List 2');
            $marketingList->addNewMember($contact1->id, false, $contact1);
            $marketingList->addNewMember($contact2->id, false, $contact2);
            $marketingList->addNewMember($contact3->id, false, $contact3);
            $marketingList->addNewMember($contact4->id, false, $contact4);
            $marketingList->addNewMember($contact5->id, false, $contact5);
            $marketingList->addNewMember($contact6->id, false, $contact6);
            $marketingList->addNewMember($contact7->id, false, $contact7);
            $marketingList->addNewMember($contact8->id, false, $contact8);
            $marketingList->addNewMember($contact9->id, false, $contact9);
            $marketingList->addNewMember($contact10->id, false, $contact10);
            $marketingList->addNewMember($contact11->id, false, $contact11);
            $marketingListId = $marketingList->id;
            $marketingList->forgetAll();
            $marketingList = MarketingList::getById($marketingListId);

            $newMarketingList = MarketingListTestHelper::createMarketingListByName('Test List 3');
            $this->assertEquals(11, count($marketingList->marketingListMembers));
            $this->assertEquals(0, count($newMarketingList->marketingListMembers));

            $campaign           = CampaignTestHelper::createCampaign('campaign 01',
                'subject 01',
                'text Content 01',
                'html Content 01',
                'fromName 01',
                'fromAddress01@zurmo.com',
                null,
                null,
                null,
                null,
                $marketingList);
            $campaign->status = Campaign::STATUS_PROCESSING;
            $this->assertTrue($campaign->save());
            $campaign->status = Campaign::STATUS_COMPLETED;
            $this->assertTrue($campaign->save());

            $resolveSubscribersForm           = new MarketingListResolveSubscribersFromCampaignForm();
            $resolveSubscribersForm->marketingList['id'] = $newMarketingList->id; // This is how data are submitted from form
            $resolveSubscribersForm->retargetClickedEmailRecipients    = true;
            $resolveSubscribersForm->retargetNotClickedEmailRecipients = true;
            $resolveSubscribersForm->retargetNotViewedEmailRecipients  = true;
            $resolveSubscribersForm->retargetOpenedEmailRecipients     = true;

            // Now add items to list
            $campaignItem1       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact1);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem1, '121.212.122.112');

            $campaignItem2       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact2);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem2, '121.212.122.112');

            // No Activity for contact3, but it stills should be added to new marketing list, as unopened email
            $campaignItem3       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact3);

            $campaignItem4       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact4);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_HARD_BOUNCE, 1, $campaignItem4, '121.212.122.112');

            $campaignItem5       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact5);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_UNSUBSCRIBE, 1, $campaignItem5, '121.212.122.112');

            $campaignItem6       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact6);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_SPAM, 1, $campaignItem6, '121.212.122.112');

            $campaignItem7       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact7);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem7, '121.212.122.112');
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem7, '121.212.122.112');
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem7, '121.212.122.112');
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem7, '121.212.122.112');

            $campaignItem8       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact8);
            $campaignItem9       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact9);
            $campaignItem10       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact10);
            $campaignItem11       = CampaignItemTestHelper::createCampaignItem(1, $campaign, $contact11);

            $contacts = MarketingListsUtil::getContactsByResolveSubscribersFormAndCampaignAndOffsetAndPageSize($resolveSubscribersForm, $campaign, 0, 3);
            $this->assertEquals(6, count($contacts));
            $contactsIds = array();
            foreach ($contacts as $contact)
            {
                $contactsIds[] = $contact->id;
            }
            $this->assertTrue(in_array($contact1->id, $contactsIds));
            $this->assertTrue(in_array($contact2->id, $contactsIds));
            $this->assertTrue(in_array($contact3->id, $contactsIds));
            $this->assertTrue(in_array($contact7->id, $contactsIds));
            $this->assertTrue(in_array($contact8->id, $contactsIds));
            $this->assertTrue(in_array($contact9->id, $contactsIds));

            $contacts = MarketingListsUtil::getContactsByResolveSubscribersFormAndCampaignAndOffsetAndPageSize($resolveSubscribersForm, $campaign, 3, 3);
            $this->assertEquals(3, count($contacts));
            $contactsIds = array();
            foreach ($contacts as $contact)
            {
                $contactsIds[] = $contact->id;
            }
            $this->assertTrue(in_array($contact10->id, $contactsIds));
            $this->assertTrue(in_array($contact11->id, $contactsIds));
            $this->assertTrue(in_array($contact9->id, $contactsIds));
        }

        /**
         * @depends testGetContactsByResolveSubscribersFormAndCampaignAndOffsetAndPageSize
         */
        public function testGetCountOfContactsByResolveSubscribersFormAndCampaign()
        {
            $campaigns = Campaign::getByName('campaign 01');
            $this->assertEquals(1, count($campaigns));
            $campaign = $campaigns[0];

            $resolveSubscribersForm           = new MarketingListResolveSubscribersFromCampaignForm();
            $resolveSubscribersForm->newMarketingListName = 'AAA'; // This is how data are submitted from form
            $resolveSubscribersForm->retargetClickedEmailRecipients    = true;
            $resolveSubscribersForm->retargetNotClickedEmailRecipients = true;
            $resolveSubscribersForm->retargetNotViewedEmailRecipients  = true;
            $resolveSubscribersForm->retargetOpenedEmailRecipients     = true;

            $count = MarketingListsUtil::getCountOfContactsByResolveSubscribersFormAndCampaign($resolveSubscribersForm, $campaign);
            $this->assertEquals(6, $count);
        }

        /**
         * @depends testGetCountOfContactsByResolveSubscribersFormAndCampaign
         */
        public function testGetNumberOfContactPagesByResolveSubscribersFormAndCampaign()
        {
            $campaigns = Campaign::getByName('campaign 01');
            $this->assertEquals(1, count($campaigns));
            $campaign = $campaigns[0];

            $resolveSubscribersForm           = new MarketingListResolveSubscribersFromCampaignForm();
            $resolveSubscribersForm->newMarketingListName = 'AAA'; // This is how data are submitted from form
            $resolveSubscribersForm->retargetClickedEmailRecipients    = true;
            $resolveSubscribersForm->retargetNotClickedEmailRecipients = true;
            $resolveSubscribersForm->retargetNotViewedEmailRecipients  = true;
            $resolveSubscribersForm->retargetOpenedEmailRecipients     = true;

            $pageSize = MarketingListsUtil::$pageSize;
            MarketingListsUtil::$pageSize = 3;
            $count = MarketingListsUtil::getNumberOfContactPagesByResolveSubscribersFormAndCampaign($resolveSubscribersForm, $campaign);
            MarketingListsUtil::$pageSize = $pageSize;
            $this->assertEquals(2, $count);
        }

        /**
         * @depends testGetContactsByResolveSubscribersFormAndCampaignAndOffsetAndPageSize
         */
        public function testGenerateRandomNameForCampaignRetargetingList()
        {
            $campaigns = Campaign::getByName('campaign 01');
            $this->assertEquals(1, count($campaigns));
            $campaign = $campaigns[0];
            $marketingListName = MarketingListsUtil::generateRandomNameForCampaignRetargetingList($campaign);
            $this->assertEquals('campaign 01' . ' - Retargeting List - ' . DateTimeUtil::getTodaysDate(), $marketingListName);
        }

        public function testAddNewSubscribersToMarketingList()
        {
            $contact1            = ContactTestHelper::createContactByNameForOwner('contact 01', $this->super);
            $contact2            = ContactTestHelper::createContactByNameForOwner('contact 02', $this->super);
            $contact3            = ContactTestHelper::createContactByNameForOwner('contact 03', $this->super);
            $contacts = array($contact1, $contact2, $contact3);

            $marketingList = MarketingListTestHelper::createMarketingListByName('Test List 4');
            $this->assertEquals(0, count($marketingList->marketingListMembers));

            MarketingListsUtil::addNewSubscribersToMarketingList($marketingList->id, $contacts);
            $marketingListId = $marketingList->id;
            $marketingList->forgetAll();
            $marketingList = MarketingList::getById($marketingListId);
            $this->assertEquals(3, count($marketingList->marketingListMembers));
        }
    }
?>