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

    /**
     * Helper class for working with marketing list.
     */
    class MarketingListsUtil
    {
        /**
         * How many items of each type per one request - this is done for performance reasons
         * @var int
         */
        public static $pageSize = 50;

        /**
         * Resolve marketing list.
         * Because we allow users to either select existing marketingList or enter name for new marketing list,
         * we need to determine if we will use exisitng one or create new one.
         * @param $resolveSubscribersForm
         * @return MarketingList
         * @throws NotFoundException
         */
        public static function resolveMarketingList($resolveSubscribersForm)
        {
            // First check if user selected existing marketing list, if he didn't create new marketing list
            try
            {
                $marketingList = MarketingList::getById(intval($resolveSubscribersForm->marketingList['id']));
            }
            catch (NotFoundException $e)
            {
                if ($resolveSubscribersForm->newMarketingListName != '')
                {
                    $marketingList = new MarketingList();
                    $marketingList->name = $resolveSubscribersForm->newMarketingListName;
                    $marketingList->save();
                }
                else
                {
                    $message = Zurmo::t('MarketingListsModule', 'Invalid selected marketing list or not entered new marketing list name. Please go back and select marketing list!');
                    throw new NotFoundException($message);
                }
            }
            return $marketingList;
        }

        /**
         * Get contacts based on campaign and activity types
         * @param $resolveSubscribersForm
         * @param $campaign
         * @param $offset
         * @param $pageSize
         * @return array
         */
        public static function getContactsByResolveSubscribersFormAndCampaignAndOffsetAndPageSize($resolveSubscribersForm, $campaign, $offset, $pageSize)
        {
            $contacts = CampaignItem::getAllContactsFromCampaignItemBasedOnItemActivity($campaign->id, $offset, $pageSize,
                $resolveSubscribersForm->retargetNotViewedEmailRecipients,
                $resolveSubscribersForm->retargetNotClickedEmailRecipients,
                $resolveSubscribersForm->retargetOpenedEmailRecipients,
                $resolveSubscribersForm->retargetClickedEmailRecipients
                );
            return $contacts;
        }

        /**
         * Get number of pages
         * @param $resolveSubscribersForm
         * @param $campaign
         * @return float
         */
        public static function getNumberOfContactPagesByResolveSubscribersFormAndCampaign($resolveSubscribersForm, $campaign)
        {
            $maxItems = static::getCountOfContactsByResolveSubscribersFormAndCampaign($resolveSubscribersForm, $campaign);
            $numberOfPages = ceil($maxItems/static::$pageSize);
            return $numberOfPages;
        }

        /**
         * Get count of maximum items .
         * Because in one iteration we get paginatet results for all four types, to find out how many pages of results we have,
         * we need to find out maximum number of results of all types.
         * Public for test purposes only
         * @param $resolveSubscribersForm
         * @param $campaign
         * @return int
         */
        public static function getCountOfContactsByResolveSubscribersFormAndCampaign($resolveSubscribersForm, $campaign)
        {
            $maxItems = 0;
            if ($resolveSubscribersForm->retargetOpenedEmailRecipients)
            {
                $count = CampaignItem::getCountOfCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_OPEN, $campaign->id);
                if ($count > $maxItems)
                {
                    $maxItems = $count;
                }
            }
            if ($resolveSubscribersForm->retargetClickedEmailRecipients)
            {
                $count = CampaignItem::getCountOfCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_CLICK, $campaign->id);
                if ($count > $maxItems)
                {
                    $maxItems = $count;
                }
            }
            if ($resolveSubscribersForm->retargetNotViewedEmailRecipients)
            {
                $count = CampaignItem::getCountOfNotViewedContactIds($campaign->id);
                if ($count > $maxItems)
                {
                    $maxItems = $count;
                }
            }
            if ($resolveSubscribersForm->retargetNotClickedEmailRecipients)
            {
                $count = CampaignItem::getCountOfNotClickedOrUnsubscribedOrSpamContactIds($campaign->id);
                if ($count > $maxItems)
                {
                    $maxItems = $count;
                }
            }
            return $maxItems;
        }

        /**
         * Generate name for new marketing list based on $campaign that user is retargeting
         * @param Campaign $campaign
         * @return string
         */
        public static function generateRandomNameForCampaignRetargetingList(Campaign $campaign)
        {
            $text = Zurmo::t('MarketingListsModule', 'Retargeting List');
            return  $campaign->name . ' - ' . $text . ' - ' . DateTimeUtil::getTodaysDate();
        }

        /**
         * Add new subscribers to marketing list
         * @param $marketingListId
         * @param array $contacts
         * @param null $scenario
         * @return array
         * @throws NotFoundException
         */
        public static function addNewSubscribersToMarketingList($marketingListId, &$contacts, $scenario = null)
        {
            $subscriberInformation = array('subscribedCount' => 0, 'skippedCount' => 0);
            $marketingList         = MarketingList::getById((int) $marketingListId);
            ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($marketingList);
            foreach ($contacts as $contact)
            {
                if ($marketingList->addNewMember(null, false, $contact, $scenario))
                {
                    $subscriberInformation['subscribedCount']++;
                }
                else
                {
                    $subscriberInformation['skippedCount']++;
                }
            }
            return $subscriberInformation;
        }
    }
?>