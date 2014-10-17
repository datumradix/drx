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

    class ExternalApiEmailMessageActivityTest extends ZurmoBaseTest
    {
        protected $user;

        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
        }

        public function setUp()
        {
            parent::setUp();
            $this->user                 = User::getByUsername('super');
            Yii::app()->user->userModel = $this->user;
        }

        public function testGetCountByTypeAndEmailMessageActivity()
        {
            // setup pre-req data
            $contact            = ContactTestHelper::createContactByNameForOwner('contact 01', $this->user);
            $marketingList      = MarketingListTestHelper::createMarketingListByName('marketingList 01',
                                                                                        'description 01',
                                                                                        'fromName 01',
                                                                                        'fromAddress01@domain.com');
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
            $processed          = 0;
            $campaignItem       = CampaignItemTestHelper::createCampaignItem($processed, $campaign, $contact);
            $modelId            = $campaignItem->id;
            $modelType          = get_class($campaignItem);
            $personId           = $contact->getClassId('Person');
            $this->assertNotNull($personId);
            $activityData               = array('modelId'   => $campaignItem->id,
                                                'modelType' => 'CampaignItem',
                                                'personId'  => $personId,
                                                'url'       => null,
                                                'type'      => CampaignItemActivity::TYPE_OPEN);
            $activityCreatedOrUpdated   = CampaignItemActivityUtil::createOrUpdateActivity($activityData);
            $emailMessageActivities     = CampaignItemActivity::getByTypeAndModelIdAndPersonIdAndUrl(CampaignItemActivity::TYPE_OPEN, $campaignItem->id, $personId, null);
            $externalMessageActivityCount = ExternalApiEmailMessageActivity::getByTypeAndEmailMessageActivity(CampaignItemActivity::TYPE_OPEN, $emailMessageActivities[0], "sendgrid");
            $this->assertEquals(0, $externalMessageActivityCount);

            $externalApiEmailMessageActivity = new ExternalApiEmailMessageActivity();
            $externalApiEmailMessageActivity->emailMessageActivity = $emailMessageActivities[0];
            $externalApiEmailMessageActivity->api       = 'sendgrid';
            $externalApiEmailMessageActivity->type      = CampaignItemActivity::TYPE_OPEN;
            $externalApiEmailMessageActivity->reason    = 'Test reason';
            $externalApiEmailMessageActivity->emailAddress    = 'abc@yahoo.com';
            $externalApiEmailMessageActivity->itemClass = 'CampaignItem';
            $externalApiEmailMessageActivity->itemId = $campaignItem->id;
            $this->assertTrue($externalApiEmailMessageActivity->save());
            $id          = $externalApiEmailMessageActivity->id;
            $externalMessageActivityCount = ExternalApiEmailMessageActivity::getByTypeAndEmailMessageActivity(CampaignItemActivity::TYPE_OPEN, $emailMessageActivities[0], "sendgrid");
            $this->assertEquals(1, $externalMessageActivityCount);
            $externalApiActivity = ExternalApiEmailMessageActivity::getById($id);
            $externalApiActivity->reason = 'New reason 1';
            $this->assertTrue($externalApiActivity->save());
            $externalApiActivity = ExternalApiEmailMessageActivity::getById($id);
            $this->assertEquals('New reason 1', $externalApiActivity->reason);
            $activities = ExternalApiEmailMessageActivity::getByEmailAddress('abc@yahoo.com', "sendgrid", false);
            $this->assertEquals(1, count($activities));
            $this->assertEquals('New reason 1', $activities[0]->reason);
        }
    }
?>