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
    class CampaignItemTest extends AutoresponderOrCampaignBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            SecurityTestHelper::createUsers();
        }

        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel = User::getByUsername('super');
        }

        public function testGetModuleClassName()
        {
            $this->assertEquals('CampaignsModule', CampaignItem::getModuleClassName());
        }

        public function testCreateAndGetCampaignItemById()
        {
            $campaignItem                          = new CampaignItem();
            $campaignItem->processed               = 1;
            $this->assertTrue($campaignItem->unrestrictedSave());
            $id = $campaignItem->id;
            unset($campaignItem);
            $campaignItem = CampaignItem::getById($id);
            $this->assertEquals(1,   $campaignItem->processed);
        }

        /**
         * @depends testCreateAndGetCampaignItemById
         */
        public function testRequiredAttributes()
        {
            $campaignItem                          = new CampaignItem();
            $this->assertTrue($campaignItem->unrestrictedSave());
            $id = $campaignItem->id;
            unset($campaignItem);
            $campaignItem = CampaignItem::getById($id);
            $this->assertEquals(0,   $campaignItem->processed);
        }

        /**
         * @depends testCreateAndGetCampaignItemById
         */
        public function testGetByProcessed()
        {
            CampaignItem::deleteAll();
            for ($i = 0; $i < 5; $i++)
            {
                $processed                          = 0;
                if ($i % 2)
                {
                    $processed      = 1;
                }
                $campaignItem                  = new CampaignItem();
                $campaignItem->processed       = $processed;
                $this->assertTrue($campaignItem->unrestrictedSave());
            }
            $campaignItems         =   CampaignItem::getAll();
            $this->assertCount(5, $campaignItems);
            $processedItems             =   CampaignItem::getByProcessed(1);
            $this->assertCount(2, $processedItems);
            $notProcessedItems          =   CampaignItem::getByProcessed(0);
            $this->assertCount(3, $notProcessedItems);
        }

        /**
         * @depends testGetByProcessed
         */
        public function testGetByProcessedAndSendOnDateTime()
        {
            CampaignItem::deleteAll();
            $marketingList                  = MarketingListTestHelper::createMarketingListByName('marketingList 01');
            $this->assertNotNull($marketingList);
            $campaignToday                  = CampaignTestHelper::createCampaign('campaign Today',
                                                                                    'subject Today',
                                                                                    'text Today',
                                                                                    'html Today',
                                                                                    null,
                                                                                    null,
                                                                                    null,
                                                                                    null,
                                                                                    null,
                                                                                    null,
                                                                                    $marketingList);
            $this->assertNotNull($campaignToday);
            $tenDaysFromNowTimestamp    = time() + 60*60*24*10;
            $tenDaysFromNowDateTime     = DateTimeUtil::convertTimestampToDbFormatDateTime($tenDaysFromNowTimestamp);
            $campaignTenDaysFromNow     = CampaignTestHelper::createCampaign('campaign Ten Days',
                                                                                    'subject Ten Days',
                                                                                    'text Ten Days',
                                                                                    'html Ten Days',
                                                                                    null,
                                                                                    null,
                                                                                    null,
                                                                                    null,
                                                                                    $tenDaysFromNowDateTime,
                                                                                    null,
                                                                                    $marketingList);
            $this->assertNotNull($campaignTenDaysFromNow);
            for ($i = 0; $i < 10; $i++)
            {
                $contact = ContactTestHelper::createContactByNameForOwner('contact ' . $i, Yii::app()->user->userModel);
                $this->assertNotNull($contact);
                if ($i % 3)
                {
                    $processed      = 1;
                }
                else
                {
                    $processed      = 0;
                }
                if ($i % 2)
                {
                    $campaign  = $campaignToday;
                }
                else
                {
                    $campaign  = $campaignTenDaysFromNow;
                }
                $campaignItem       = CampaignItemTestHelper::createCampaignItem($processed, $campaign);
                $this->assertNotNull($campaignItem);
            }
            $tenDaysFromNowTimestamp                    += 100; // incrementing it a bit so the records we just created show up.
            $campaignItems         = CampaignItem::getAll();
            $this->assertNotEmpty($campaignItems);
            $this->assertCount(10, $campaignItems);
            $campaignTodayProcessed  = CampaignItem::getByProcessedAndSendOnDateTime(1);
            $this->assertNotEmpty($campaignTodayProcessed);
            $this->assertCount(3, $campaignTodayProcessed);
            $campaignTodayNotProcessed  = CampaignItem::getByProcessedAndSendOnDateTime(0);
            $this->assertNotEmpty($campaignTodayNotProcessed);
            $this->assertCount(2, $campaignTodayNotProcessed);
            $campaignTenDaysFromNowProcessed  = CampaignItem::getByProcessedAndSendOnDateTime(1,
                                                                                            $tenDaysFromNowTimestamp);
            $this->assertNotEmpty($campaignTenDaysFromNowProcessed);
            $this->assertCount(6, $campaignTenDaysFromNowProcessed);
            $campaignTenDaysFromNowNotProcessed  = CampaignItem::getByProcessedAndSendOnDateTime(
                                                                                            0,
                                                                                            $tenDaysFromNowTimestamp);
            $this->assertNotEmpty($campaignTenDaysFromNowNotProcessed);
            $this->assertCount(4, $campaignTenDaysFromNowNotProcessed);
        }

        /**
         * @depends testGetByProcessed
         */
        public function testGetByProcessedAndStatusAndSendOnDateTime()
        {
            CampaignItem::deleteAll();
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 02');
            $this->assertNotNull($marketingList);
            $campaignTodayActive        = CampaignTestHelper::createCampaign('campaign Today Active',
                                                                                    'subject Today Active',
                                                                                    'text Today Active',
                                                                                    'html Today Active',
                                                                                    null,
                                                                                    null,
                                                                                    null,
                                                                                    Campaign::STATUS_ACTIVE,
                                                                                    null,
                                                                                    null,
                                                                                    $marketingList);
            $this->assertNotNull($campaignTodayActive);
            $campaignTodayPaused        = CampaignTestHelper::createCampaign('campaign Today Paused',
                                                                                    'subject Today Paused',
                                                                                    'text Today Paused',
                                                                                    'html Today Paused',
                                                                                    null,
                                                                                    null,
                                                                                    null,
                                                                                    Campaign::STATUS_PAUSED,
                                                                                    null,
                                                                                    null,
                                                                                    $marketingList);
            $this->assertNotNull($campaignTodayPaused);
            $this->assertNotNull($campaignTodayActive);
            $tenDaysFromNowTimestamp        = time() + 60*60*24*10;
            $tenDaysFromNowDateTime         = DateTimeUtil::convertTimestampToDbFormatDateTime($tenDaysFromNowTimestamp);
            $campaignTenDaysFromNowActive   = CampaignTestHelper::createCampaign('campaign Ten Days Active',
                                                                                'subject Ten Days Active',
                                                                                'text Ten Days Active',
                                                                                'html Ten Days Active',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                Campaign::STATUS_ACTIVE,
                                                                                $tenDaysFromNowDateTime,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaignTenDaysFromNowActive);
            $campaignTenDaysFromNowPaused       = CampaignTestHelper::createCampaign('campaign Ten Days Paused',
                                                                                'subject Ten Days Paused',
                                                                                'text Ten Days Paused',
                                                                                'html Ten Days Paused',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                Campaign::STATUS_PAUSED,
                                                                                $tenDaysFromNowDateTime,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaignTenDaysFromNowPaused);
            $campaignsArray = array($campaignTodayActive, $campaignTodayPaused,
                                    $campaignTenDaysFromNowActive, $campaignTenDaysFromNowPaused);
            for ($i = 0; $i < 20; $i++)
            {
                $contact = ContactTestHelper::createContactByNameForOwner('contact ' . $i, Yii::app()->user->userModel);
                $this->assertNotNull($contact);
                if ($i % 3)
                {
                    $processed      = 1;
                }
                else
                {
                    $processed      = 0;
                }
                $campaign           = $campaignsArray[$i % 4];
                $campaignItem       = CampaignItemTestHelper::createCampaignItem($processed, $campaign);
                $this->assertNotNull($campaignItem);
            }
            $tenDaysFromNowTimestamp                    += 100; // incrementing it a bit so the records we just created show up.
            $campaignItems                              = CampaignItem::getAll();
            $this->assertNotEmpty($campaignItems);
            $this->assertCount(20, $campaignItems);
            $campaignTodayActiveProcessed               = CampaignItem::getByProcessedAndStatusAndSendOnDateTime(
                                                                                    1,
                                                                                    Campaign::STATUS_ACTIVE);
            $this->assertNotEmpty($campaignTodayActiveProcessed);
            $this->assertCount(3, $campaignTodayActiveProcessed);
            $campaignTodayActiveNotProcessed            = CampaignItem::getByProcessedAndStatusAndSendOnDateTime(
                                                                                    0,
                                                                                    Campaign::STATUS_ACTIVE);
            $this->assertNotEmpty($campaignTodayActiveNotProcessed);
            $this->assertCount(2, $campaignTodayActiveNotProcessed);
            $campaignTodayPausedProcessed               = CampaignItem::getByProcessedAndStatusAndSendOnDateTime(
                                                                                    1,
                                                                                    Campaign::STATUS_PAUSED);
            $this->assertNotEmpty($campaignTodayPausedProcessed);
            $this->assertCount(4, $campaignTodayPausedProcessed);
            $campaignTodayPausedNotProcessed            = CampaignItem::getByProcessedAndStatusAndSendOnDateTime(
                                                                                    0,
                                                                                    Campaign::STATUS_PAUSED);
            $this->assertNotEmpty($campaignTodayPausedNotProcessed);
            $this->assertCount(1, $campaignTodayPausedNotProcessed);
            $campaignTenDaysFromNowActiveProcessed      = CampaignItem::getByProcessedAndStatusAndSendOnDateTime(
                                                                                    1,
                                                                                    Campaign::STATUS_ACTIVE,
                                                                                    $tenDaysFromNowTimestamp);
            $this->assertNotEmpty($campaignTenDaysFromNowActiveProcessed);
            $this->assertCount(6, $campaignTenDaysFromNowActiveProcessed);
            $campaignTenDaysFromNowActiveNotProcessed   = CampaignItem::getByProcessedAndStatusAndSendOnDateTime(
                                                                                    0,
                                                                                    Campaign::STATUS_ACTIVE,
                                                                                    $tenDaysFromNowTimestamp);
            $this->assertNotEmpty($campaignTenDaysFromNowActiveNotProcessed);
            $this->assertCount(4, $campaignTenDaysFromNowActiveNotProcessed);
            $campaignTenDaysFromNowPausedProcessed      = CampaignItem::getByProcessedAndStatusAndSendOnDateTime(
                                                                                    1,
                                                                                    Campaign::STATUS_PAUSED,
                                                                                    $tenDaysFromNowTimestamp);
            $this->assertNotEmpty($campaignTenDaysFromNowPausedProcessed);
            $this->assertCount(7, $campaignTenDaysFromNowPausedProcessed);
            $campaignTenDaysFromNowPausedNotProcessed   = CampaignItem::getByProcessedAndStatusAndSendOnDateTime(
                                                                                    0,
                                                                                    Campaign::STATUS_PAUSED,
                                                                                    $tenDaysFromNowTimestamp);
            $this->assertNotEmpty($campaignTenDaysFromNowPausedNotProcessed);
            $this->assertCount(3, $campaignTenDaysFromNowPausedNotProcessed);
        }

        /**
         * @depends testGetByProcessed
         */
        public function testGetByProcessedAndCampaignId()
        {
            CampaignItem::deleteAll();
            $marketingList      = MarketingListTestHelper::createMarketingListByName('marketingList 03');
            $this->assertNotNull($marketingList);
            $campaign1          = CampaignTestHelper::createCampaign('campaign 01',
                                                                        'subject 01',
                                                                        'text 01',
                                                                        'html 01',
                                                                        null,
                                                                        null,
                                                                        null,
                                                                        null,
                                                                        null,
                                                                        null,
                                                                        $marketingList);
            $this->assertNotNull($campaign1);
            $campaign2          = CampaignTestHelper::createCampaign('campaign 02',
                                                                        'subject 02',
                                                                        'text 02',
                                                                        'html 02',
                                                                        null,
                                                                        null,
                                                                        null,
                                                                        null,
                                                                        null,
                                                                        null,
                                                                        $marketingList);
            $this->assertNotNull($campaign2);
            for ($i = 0; $i < 10; $i++)
            {
                $contact = ContactTestHelper::createContactByNameForOwner('contact 0' . $i, Yii::app()->user->userModel);
                $this->assertNotNull($contact);
                if ($i % 3)
                {
                    $processed      = 1;
                }
                else
                {
                    $processed      = 0;
                }
                if ($i % 2)
                {
                    $campaign  = $campaign1;
                }
                else
                {
                    $campaign  = $campaign2;
                }
                $campaignItem = CampaignItemTestHelper::createCampaignItem($processed, $campaign);
                $this->assertNotNull($campaignItem);
            }
            $campaignItems         = CampaignItem::getAll();
            $this->assertNotEmpty($campaignItems);
            $this->assertCount(10, $campaignItems);
            $campaign1Processed  = CampaignItem::getByProcessedAndCampaignId(1,
                                                                                            $campaign1->id);
            $this->assertNotEmpty($campaign1Processed);
            $this->assertCount(3, $campaign1Processed);
            $campaign1NotProcessed  = CampaignItem::getByProcessedAndCampaignId(0,
                                                                                                $campaign1->id);
            $this->assertNotEmpty($campaign1NotProcessed);
            $this->assertCount(2, $campaign1NotProcessed);
            $campaign2Processed  = CampaignItem::getByProcessedAndCampaignId(1,
                                                                                            $campaign2->id);
            $this->assertNotEmpty($campaign2Processed);
            $this->assertCount(3, $campaign2Processed);
            $campaign2NotProcessed  = CampaignItem::getByProcessedAndCampaignId(0,
                                                                                                $campaign2->id);
            $this->assertNotEmpty($campaign2NotProcessed);
            $this->assertCount(2, $campaign2NotProcessed);
        }

        /**
         * @depends testGetByProcessedAndCampaignId
         */
        public function testGetLabel()
        {
            $campaignItem = RandomDataUtil::getRandomValueFromArray(CampaignItem::getAll());
            $this->assertNotNull($campaignItem);
            $this->assertEquals('Campaign Item',  $campaignItem::getModelLabelByTypeAndLanguage('Singular'));
            $this->assertEquals('Campaign Items', $campaignItem::getModelLabelByTypeAndLanguage('Plural'));
        }

        /**
         * @depends testCreateAndGetCampaignItemById
         */
        public function testDeleteCampaignItem()
        {
            $campaignItems   = CampaignItem::getAll();
            $this->assertNotEmpty($campaignItems);
            $this->assertCount(10, $campaignItems);

            $campaignItemActivity                           = new CampaignItemActivity();
            $campaignItemActivity->type                     = CampaignItemActivity::TYPE_CLICK;
            $campaignItemActivity->quantity                 = 1;
            $campaignItemActivity->campaignItem             = $campaignItems[0];
            $campaignItemActivity->latestSourceIP           = '121.212.122.112';
            $this->assertTrue($campaignItemActivity->save());

            $campaignItems[0]->emailMessage = $this->resolveEmailMessage();
            $this->assertTrue($campaignItems[0]->unrestrictedSave());

            $this->assertEquals(1, EmailMessage::getCount());
            $this->assertEquals(1, EmailMessageContent::getCount());
            $this->assertEquals(1, EmailMessageSender::getCount());
            $this->assertEquals(1, EmailMessageRecipient::getCount());

            $campaignItemActivities = CampaignItemActivity::getAll();
            $this->assertCount(1, $campaignItemActivities);

            $campaignItems[0]->delete();
            $campaignItems   = CampaignItem::getAll();
            $this->assertNotEmpty($campaignItems);
            $this->assertCount(9, $campaignItems);

            $campaignItemActivities = CampaignItemActivity::getAll();
            $this->assertCount(0, $campaignItemActivities);

            $this->assertEquals(0, EmailMessage::getCount());
            $this->assertEquals(0, EmailMessageContent::getCount());
            $this->assertEquals(0, EmailMessageSender::getCount());
            $this->assertEquals(0, EmailMessageRecipient::getCount());
        }

        /**
         * @depends testDeleteCampaignItem
         */
        public function testIsQueued()
        {
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 06');
            $campaign                   = CampaignTestHelper::createCampaign('campaign 05',
                                                                                'subject 05',
                                                                                'text 05',
                                                                                'html 05',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaign);
            $contact        = ContactTestHelper::createContactByNameForOwner('campaignContact 06',
                                                                                        Yii::app()->user->userModel);
            MarketingListMemberTestHelper::createMarketingListMember(0, $marketingList, $contact);
            $email                  = new Email();
            $email->emailAddress    = 'info@zurmo.com';
            $contact->primaryEmail  = $email;
            $this->assertTrue($contact->save());
            $campaignItem   = CampaignItemTestHelper::createCampaignItem(0, $campaign, $contact);
            $this->assertNotNull($campaignItem);
            $this->assertFalse($campaignItem->isQueued());
            $this->processDueItem($campaignItem);
            $this->assertTrue($campaignItem->isQueued());
        }

        /**
         * @depends testIsQueued
         */
        public function testIsSkipped()
        {
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 07');
            $campaign                   = CampaignTestHelper::createCampaign('campaign 06',
                                                                                'subject 06',
                                                                                'text 06',
                                                                                'html 06',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaign);
            $contact        = ContactTestHelper::createContactByNameForOwner('campaignContact 07',
                                                                                        Yii::app()->user->userModel);
            $email                  = new Email();
            $email->emailAddress    = 'info@zurmo.com';
            $contact->primaryEmail  = $email;
            $this->assertTrue($contact->save());
            $campaignItem   = CampaignItemTestHelper::createCampaignItem(0, $campaign, $contact);
            $this->assertNotNull($campaignItem);
            $this->assertFalse($campaignItem->isSkipped());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_SKIP, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->isSkipped());
        }

        /**
         * @depends testIsSkipped
         */
        public function testIsSent()
        {
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 08');
            $campaign                   = CampaignTestHelper::createCampaign('campaign 07',
                                                                                'subject 07',
                                                                                'text 07',
                                                                                'html 07',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaign);
            $contact        = ContactTestHelper::createContactByNameForOwner('campaignContact 08',
                                                                                        Yii::app()->user->userModel);
            MarketingListMemberTestHelper::createMarketingListMember(0, $marketingList, $contact);
            $email                  = new Email();
            $email->emailAddress    = 'info@zurmo.com';
            $contact->primaryEmail  = $email;
            $this->assertTrue($contact->save());
            $campaignItem   = CampaignItemTestHelper::createCampaignItem(0, $campaign, $contact);
            $this->assertNotNull($campaignItem);
            $this->assertFalse($campaignItem->isSent());
            $this->processDueItem($campaignItem);
            $this->assertFalse($campaignItem->isSent()); // Folder is outbox at the end of processDueItem and hence it fails
            $box                                    = EmailBox::resolveAndGetByName(EmailBox::CAMPAIGNS_NAME);
            $campaignItem->emailMessage->folder     = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_SENT);
            $this->assertTrue($campaignItem->unrestrictedSave());
            $this->assertTrue($campaignItem->isSent());
        }

        /**
         * @depends testIsSent
         */
        public function testHasFailedToSend()
        {
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 09');
            $campaign                   = CampaignTestHelper::createCampaign('campaign 08',
                                                                                'subject 08',
                                                                                'text 08',
                                                                                'html 08',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaign);
            $contact        = ContactTestHelper::createContactByNameForOwner('campaignContact 09',
                                                                                        Yii::app()->user->userModel);
            MarketingListMemberTestHelper::createMarketingListMember(0, $marketingList, $contact);
            $email                  = new Email();
            $email->emailAddress    = 'info@zurmo.com';
            $contact->primaryEmail  = $email;
            $this->assertTrue($contact->save());
            $campaignItem   = CampaignItemTestHelper::createCampaignItem(0, $campaign, $contact);
            $this->assertNotNull($campaignItem);
            $this->assertFalse($campaignItem->hasFailedToSend());
            $this->processDueItem($campaignItem);
            $this->assertFalse($campaignItem->hasFailedToSend()); // Folder is outbox at the end of processDueItem and hence it fails
            $box                                    = EmailBox::resolveAndGetByName(EmailBox::CAMPAIGNS_NAME);
            $campaignItem->emailMessage->folder     = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_OUTBOX_FAILURE);
            $this->assertTrue($campaignItem->unrestrictedSave());
            $this->assertTrue($campaignItem->hasFailedToSend());
        }

        /**
         * @depends testHasFailedToSend
         */
        public function testHasAtLeastOneOpenActivity()
        {
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 10');
            $campaign                   = CampaignTestHelper::createCampaign('campaign 09',
                                                                                'subject 09',
                                                                                'text 09',
                                                                                'html 09',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaign);
            $contact        = ContactTestHelper::createContactByNameForOwner('campaignContact 10',
                                                                                            Yii::app()->user->userModel);
            $campaignItem   = CampaignItemTestHelper::createCampaignItem(0, $campaign, $contact);
            $this->assertNotNull($campaignItem);
            $this->assertFalse($campaignItem->hasAtLeastOneOpenActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_SKIP, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertFalse($campaignItem->hasAtLeastOneOpenActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_OPEN, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->hasAtLeastOneOpenActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_OPEN, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->hasAtLeastOneOpenActivity());
        }

        /**
         * @depends testHasAtLeastOneOpenActivity
         */
        public function testHasAtLeastOneClickActivity()
        {
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 11');
            $campaign                   = CampaignTestHelper::createCampaign('campaign 10',
                                                                                'subject 10',
                                                                                'text 10',
                                                                                'html 10',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaign);
            $contact        = ContactTestHelper::createContactByNameForOwner('campaignContact 11',
                                                                                        Yii::app()->user->userModel);
            $campaignItem   = CampaignItemTestHelper::createCampaignItem(0, $campaign, $contact);
            $this->assertNotNull($campaignItem);
            $this->assertFalse($campaignItem->hasAtLeastOneClickActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_OPEN, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertFalse($campaignItem->hasAtLeastOneClickActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_CLICK, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->hasAtLeastOneClickActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_CLICK, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->hasAtLeastOneClickActivity());
        }

        /**
         * @depends testHasAtLeastOneClickActivity
         */
        public function testHasAtLeastOneUnsubscribeActivity()
        {
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 12');
            $campaign                   = CampaignTestHelper::createCampaign('campaign 11',
                                                                                'subject 11',
                                                                                'text 11',
                                                                                'html 11',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaign);
            $contact        = ContactTestHelper::createContactByNameForOwner('campaignContact 12',
                                                                                        Yii::app()->user->userModel);
            $campaignItem   = CampaignItemTestHelper::createCampaignItem(0, $campaign, $contact);
            $this->assertNotNull($campaignItem);
            $this->assertFalse($campaignItem->hasAtLeastOneUnsubscribeActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_OPEN, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertFalse($campaignItem->hasAtLeastOneUnsubscribeActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_UNSUBSCRIBE, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->hasAtLeastOneUnsubscribeActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_UNSUBSCRIBE, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->hasAtLeastOneUnsubscribeActivity());
        }

        /**
         * @depends testHasAtLeastOneUnsubscribeActivity
         */
        public function testHasAtLeastOneBounceActivity()
        {
            $marketingList              = MarketingListTestHelper::createMarketingListByName('marketingList 13');
            $campaign                   = CampaignTestHelper::createCampaign('campaign 12',
                                                                                'subject 12',
                                                                                'text 12',
                                                                                'html 12',
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                null,
                                                                                $marketingList);
            $this->assertNotNull($campaign);
            $contact        = ContactTestHelper::createContactByNameForOwner('campaignContact 13',
                                                                                        Yii::app()->user->userModel);
            $campaignItem   = CampaignItemTestHelper::createCampaignItem(0, $campaign, $contact);
            $this->assertNotNull($campaignItem);
            $this->assertFalse($campaignItem->hasAtLeastOneBounceActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_OPEN, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertFalse($campaignItem->hasAtLeastOneBounceActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_BOUNCE, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->hasAtLeastOneBounceActivity());
            CampaignItemActivity::createNewActivity(CampaignItemActivity::TYPE_BOUNCE, $campaignItem->id,
                                                            $contact->getClassId('Person'), null, null, $campaignItem);
            $this->assertTrue($campaignItem->hasAtLeastOneBounceActivity());
        }

        public function testGetNotViewedContactIds()
        {
            $user = User::getByUsername('super');
            $marketingList = MarketingListTestHelper::createMarketingListByName('Test List');
            $campaign1     = CampaignTestHelper::createCampaign('campaign new 01',
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

            $campaign2     = CampaignTestHelper::createCampaign('campaign new 02',
                'subject 02',
                'text Content 02',
                'html Content 02',
                'fromName 02',
                'fromAddress02@zurmo.com',
                null,
                null,
                null,
                null,
                $marketingList);

            $contact1            = ContactTestHelper::createContactByNameForOwner('contact1', $user);
            $contact2            = ContactTestHelper::createContactByNameForOwner('contact2', $user);
            $contact3            = ContactTestHelper::createContactByNameForOwner('contact3', $user);
            $contact4            = ContactTestHelper::createContactByNameForOwner('contact4', $user);
            $contact5            = ContactTestHelper::createContactByNameForOwner('contact5', $user);
            $contact6            = ContactTestHelper::createContactByNameForOwner('contact6', $user);
            $contact7            = ContactTestHelper::createContactByNameForOwner('contact7', $user);
            $contact8            = ContactTestHelper::createContactByNameForOwner('contact8', $user);
            $contact9            = ContactTestHelper::createContactByNameForOwner('contact9', $user);
            $contact10            = ContactTestHelper::createContactByNameForOwner('contact10', $user);

            // Without campaign activity
            $campaignItem1       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact1);
            $campaignItem2       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact2);
            $campaignItem6       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact6);
            $campaignItem7       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact7);
            $campaignItem8       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact8);
            $campaignItem9       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact9);
            $campaignItem10       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact10);

            $campaignItem3       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact3);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem3, '121.212.122.112');

            $campaignItem4       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact4);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem4, '121.212.122.112');

            $campaignItem5       = CampaignItemTestHelper::createCampaignItem(1, $campaign2, $contact5);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_BOUNCE, 1, $campaignItem5, '121.212.122.112');

            $contactsIds = CampaignItem::getNotViewedContactIds($campaign1->id, 0, 2);
            $this->assertEquals(2, count($contactsIds));
            $this->assertTrue(in_array($contact1->id, $contactsIds));
            $this->assertTrue(in_array($contact2->id, $contactsIds));

            $contactsIds = CampaignItem::getNotViewedContactIds($campaign1->id, 2, 2);
            $this->assertEquals(2, count($contactsIds));
            $this->assertTrue(in_array($contact6->id, $contactsIds));
            $this->assertTrue(in_array($contact7->id, $contactsIds));

            $contactsIds = CampaignItem::getNotViewedContactIds($campaign1->id, 4, 2);
            $this->assertEquals(2, count($contactsIds));
            $this->assertTrue(in_array($contact8->id, $contactsIds));
            $this->assertTrue(in_array($contact9->id, $contactsIds));

            $contactsIds = CampaignItem::getNotViewedContactIds($campaign1->id, 6, 2);
            $this->assertEquals(1, count($contactsIds));
            $this->assertTrue(in_array($contact10->id, $contactsIds));

            $contactsIds = CampaignItem::getNotViewedContactIds($campaign1->id, 8, 2);
            $this->assertEquals(0, count($contactsIds));
        }

        /**
         * @depends testGetNotViewedContactIds
         */
        public function testGetCountOfNotViewedContactIds()
        {
            $campaigns = Campaign::getByName('campaign new 01');
            $this->assertEquals(1, count($campaigns));
            $campaign1 = $campaigns[0];

            $campaigns = Campaign::getByName('campaign new 02');
            $this->assertEquals(1, count($campaigns));
            $campaign2 = $campaigns[0];

            $count = CampaignItem::getCountOfNotViewedContactIds($campaign1->id);
            $this->assertEquals(7, $count);

            $count = CampaignItem::getCountOfNotViewedContactIds($campaign2->id);
            $this->assertEquals(0, $count);
        }

        public function testGetNotClickedOrUnsubscribedOrSpamContactIds()
        {
            $user = User::getByUsername('super');
            $marketingList = MarketingListTestHelper::createMarketingListByName('Test List');
            $campaign1     = CampaignTestHelper::createCampaign('campaign new 03',
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

            $campaign2     = CampaignTestHelper::createCampaign('campaign new 04',
                'subject 02',
                'text Content 02',
                'html Content 02',
                'fromName 02',
                'fromAddress02@zurmo.com',
                null,
                null,
                null,
                null,
                $marketingList);

            $contact1            = ContactTestHelper::createContactByNameForOwner('contact11', $user);
            $contact2            = ContactTestHelper::createContactByNameForOwner('contact22', $user);
            $contact3            = ContactTestHelper::createContactByNameForOwner('contact33', $user);
            $contact4            = ContactTestHelper::createContactByNameForOwner('contact44', $user);
            $contact5            = ContactTestHelper::createContactByNameForOwner('contact55', $user);
            $contact6            = ContactTestHelper::createContactByNameForOwner('contact66', $user);
            $contact7            = ContactTestHelper::createContactByNameForOwner('contact77', $user);
            $contact8            = ContactTestHelper::createContactByNameForOwner('contact88', $user);
            $contact9            = ContactTestHelper::createContactByNameForOwner('contact99', $user);
            $contact10           = ContactTestHelper::createContactByNameForOwner('contact100', $user);
            $contact11           = ContactTestHelper::createContactByNameForOwner('contact110', $user);
            $contact12           = ContactTestHelper::createContactByNameForOwner('contact112', $user);
            $contact13           = ContactTestHelper::createContactByNameForOwner('contact113', $user);
            $contact14           = ContactTestHelper::createContactByNameForOwner('contact114', $user);

            // Without campaign activity
            $campaignItem1       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact1);

            $campaignItem2       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact2);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem2, '121.212.122.112');

            $campaignItem3       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact3);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem3, '121.212.122.112');

            $campaignItem4       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact4);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_UNSUBSCRIBE, 1, $campaignItem4, '121.212.122.112');

            $campaignItem5       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact5);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_BOUNCE, 1, $campaignItem5, '121.212.122.112');

            $campaignItem6       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact6);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_SKIP, 1, $campaignItem6, '121.212.122.112');

            $campaignItem7       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact7);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_SPAM, 1, $campaignItem7, '121.212.122.112');

            $campaignItem8       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact8);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_SOFT_BOUNCE, 1, $campaignItem8, '121.212.122.112');

            $campaignItem9       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact9);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_HARD_BOUNCE, 1, $campaignItem9, '121.212.122.112');

            $campaignItemSec       = CampaignItemTestHelper::createCampaignItem(1, $campaign2, $contact10);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItemSec, '121.212.122.112');

            $campaignItem11       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact11);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem11, '121.212.122.112');
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem11, '121.212.122.112');

            $campaignItem12      = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact12);
            $campaignItem13      = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact13);
            $campaignItem14      = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact14);

            $contactsIds = CampaignItem::getNotClickedOrUnsubscribedOrSpamContactIds($campaign1->id, 0, 5);
            $this->assertEquals(5, count($contactsIds));
            $this->assertTrue(in_array($contact2->id, $contactsIds));
            $this->assertTrue(in_array($contact5->id, $contactsIds));
            $this->assertTrue(in_array($contact6->id, $contactsIds));
            $this->assertTrue(in_array($contact8->id, $contactsIds));
            $this->assertTrue(in_array($contact1->id, $contactsIds));

            $contactsIds = CampaignItem::getNotClickedOrUnsubscribedOrSpamContactIds($campaign1->id, 5, 5);
            $this->assertEquals(3, count($contactsIds));
            $this->assertTrue(in_array($contact12->id, $contactsIds));
            $this->assertTrue(in_array($contact13->id, $contactsIds));
            $this->assertTrue(in_array($contact14->id, $contactsIds));
        }

        /**
         * @depends testGetNotClickedOrUnsubscribedOrSpamContactIds
         */
        public function testGetCountOfNotClickedOrUnsubscribedOrSpamContactIds()
        {
            $campaigns = Campaign::getByName('campaign new 03');
            $this->assertEquals(1, count($campaigns));
            $campaign1 = $campaigns[0];

            $campaigns = Campaign::getByName('campaign new 04');
            $this->assertEquals(1, count($campaigns));
            $campaign2 = $campaigns[0];

            $count = CampaignItem::getCountOfNotClickedOrUnsubscribedOrSpamContactIds($campaign1->id);
            $this->assertEquals(8, $count);

            $count = CampaignItem::getCountOfNotClickedOrUnsubscribedOrSpamContactIds($campaign2->id);
            $this->assertEquals(1, $count);
        }

        public function testGetContactIdsFromCampaignItemsByActivityTypeAndCampaign()
        {
            $user = User::getByUsername('super');
            $marketingList = MarketingListTestHelper::createMarketingListByName('Test List');
            $campaign1     = CampaignTestHelper::createCampaign('campaign new 05',
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

            $campaign2     = CampaignTestHelper::createCampaign('campaign new 06',
                'subject 02',
                'text Content 02',
                'html Content 02',
                'fromName 02',
                'fromAddress02@zurmo.com',
                null,
                null,
                null,
                null,
                $marketingList);

            $contact1            = ContactTestHelper::createContactByNameForOwner('contact1', $user);
            $contact2            = ContactTestHelper::createContactByNameForOwner('contact2', $user);
            $contact3            = ContactTestHelper::createContactByNameForOwner('contact3', $user);
            $contact4            = ContactTestHelper::createContactByNameForOwner('contact4', $user);
            $contact5            = ContactTestHelper::createContactByNameForOwner('contact5', $user);
            $contact6            = ContactTestHelper::createContactByNameForOwner('contact6', $user);
            $contact7            = ContactTestHelper::createContactByNameForOwner('contact7', $user);
            $contact8            = ContactTestHelper::createContactByNameForOwner('contact8', $user);
            $contact9            = ContactTestHelper::createContactByNameForOwner('contact9', $user);
            $contact10           = ContactTestHelper::createContactByNameForOwner('contact10', $user);
            $contact11           = ContactTestHelper::createContactByNameForOwner('contact110', $user);
            $contact12           = ContactTestHelper::createContactByNameForOwner('contact112', $user);
            $contact13           = ContactTestHelper::createContactByNameForOwner('contact113', $user);
            $contact14           = ContactTestHelper::createContactByNameForOwner('contact114', $user);

            // Without campaign activity
            $campaignItem1       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact1);

            $campaignItem2       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact2);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem2, '121.212.122.112');
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem2, '121.212.122.112');

            $campaignItem3       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact3);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem3, '121.212.122.112');
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem3, '121.212.122.112');
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem3, '121.212.122.112');

            $campaignItem4       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact4);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_UNSUBSCRIBE, 1, $campaignItem4, '121.212.122.112');

            $campaignItem5       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact5);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_BOUNCE, 1, $campaignItem5, '121.212.122.112');

            $campaignItem6       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact6);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_SKIP, 1, $campaignItem6, '121.212.122.112');

            $campaignItem7       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact7);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_SPAM, 1, $campaignItem7, '121.212.122.112');

            $campaignItem8       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact8);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_SOFT_BOUNCE, 1, $campaignItem8, '121.212.122.112');

            $campaignItem9       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact9);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_HARD_BOUNCE, 1, $campaignItem9, '121.212.122.112');

            $campaignItemSec       = CampaignItemTestHelper::createCampaignItem(1, $campaign2, $contact10);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItemSec, '121.212.122.112');

            $campaignItem11       = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact11);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem11, '121.212.122.112');
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_CLICK, 1, $campaignItem11, '121.212.122.112');

            $campaignItem12      = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact12);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem12, '121.212.122.112');

            $campaignItem13      = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact13);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem13, '121.212.122.112');

            $campaignItem14      = CampaignItemTestHelper::createCampaignItem(1, $campaign1, $contact14);
            CampaignItemActivityTestHelper::createCampaignItemActivity(CampaignItemActivity::TYPE_OPEN, 1, $campaignItem14, '121.212.122.112');

            $contactsIds = CampaignItem::getContactIdsFromCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_OPEN, $campaign1->id, 0, 3);
            $this->assertEquals(3, count($contactsIds));
            $this->assertTrue(in_array($contact2->id, $contactsIds));
            $this->assertTrue(in_array($contact11->id, $contactsIds));
            $this->assertTrue(in_array($contact12->id, $contactsIds));

            $contactsIds = CampaignItem::getContactIdsFromCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_OPEN, $campaign1->id, 3, 3);
            $this->assertEquals(2, count($contactsIds));
            $this->assertTrue(in_array($contact13->id, $contactsIds));
            $this->assertTrue(in_array($contact14->id, $contactsIds));

            $contactsIds = CampaignItem::getContactIdsFromCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_CLICK, $campaign1->id, 0, 10);
            $this->assertEquals(2, count($contactsIds));
            $this->assertTrue(in_array($contact3->id, $contactsIds));
            $this->assertTrue(in_array($contact11->id, $contactsIds));

            $contactsIds = CampaignItem::getContactIdsFromCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_OPEN, $campaign2->id, 0, 10);
            $this->assertEquals(1, count($contactsIds));
            $this->assertTrue(in_array($contact10->id, $contactsIds));

            $contactsIds = CampaignItem::getContactIdsFromCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_CLICK, $campaign2->id, 0, 10);
            $this->assertEquals(0, count($contactsIds));
        }

        /**
         * @depends testGetContactIdsFromCampaignItemsByActivityTypeAndCampaign
         */
        public function testGetCountOfCampaignItemsByActivityTypeAndCampaign()
        {
            $campaigns = Campaign::getByName('campaign new 05');
            $this->assertEquals(1, count($campaigns));
            $campaign1 = $campaigns[0];

            $campaigns = Campaign::getByName('campaign new 06');
            $this->assertEquals(1, count($campaigns));
            $campaign2 = $campaigns[0];

            $count = CampaignItem::getCountOfCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_OPEN, $campaign1->id);
            $this->assertEquals(5, $count);

            $count = CampaignItem::getCountOfCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_CLICK, $campaign1->id);
            $this->assertEquals(2, $count);

            $count = CampaignItem::getCountOfCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_OPEN, $campaign2->id);
            $this->assertEquals(1, $count);

            $count = CampaignItem::getCountOfCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_CLICK, $campaign2->id);
            $this->assertEquals(0, $count);
        }
    }
?>