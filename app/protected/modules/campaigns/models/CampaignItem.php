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

    class CampaignItem extends OwnedModel
    {
        public static function getModuleClassName()
        {
            return 'CampaignsModule';
        }

        /**
         * Returns the display name for the model class.
         * @return dynamic label name based on module.
         */
        protected static function getLabel($language = null)
        {
            return Zurmo::t('CampaignsModule', 'Campaign Item', array(), null, $language);
        }

        /**
         * Returns the display name for plural of the model class.
         * @return dynamic label name based on module.
         */
        protected static function getPluralLabel($language = null)
        {
            return Zurmo::t('CampaignsModule', 'Campaign Items', array(), null, $language);
        }

        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'processed',
                ),
                'relations' => array(
                    'contact'                       => array(static::HAS_ONE, 'Contact', static::NOT_OWNED),
                    'emailMessage'                  => array(static::HAS_ONE, 'EmailMessage'),
                    'campaignItemActivities'        => array(static::HAS_MANY, 'CampaignItemActivity'),
                    'campaign'                      => array(static::HAS_ONE, 'Campaign', static::NOT_OWNED),
                ),
                'rules' => array(
                    array('processed',              'boolean'),
                    array('processed',              'default', 'value' => false),
                ),
                'elements' => array(
                ),
                'indexes' => array( 'campaign_id' => array(
                                        'members' => array('campaign_id'),
                                        'unique' => false),
                                    'contact_id' => array(
                                        'members' => array('contact_id'),
                                        'unique' => false)
                ),
            );
            return $metadata;
        }

        public static function isTypeDeletable()
        {
            return true;
        }

        public static function canSaveMetadata()
        {
            return true;
        }

        /**
         * @param int $processed
         * @param null|int $pageSize
         */
        public static function getByProcessed($processed, $pageSize = null)
        {
            assert('is_int($processed)');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'processed',
                    'operatorType'              => 'equals',
                    'value'                     => intval($processed),
                ),
            );
            $searchAttributeData['structure'] = '1';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter(get_called_class());
            $where = RedBeanModelDataProvider::makeWhere(get_called_class(), $searchAttributeData, $joinTablesAdapter);
            return self::getSubset($joinTablesAdapter, null, $pageSize, $where, null);
        }

        /**
         * @param int $processed
         * @param null $timestamp
         * @param null|int $pageSize
         */
        public static function getByProcessedAndSendOnDateTime($processed, $timestamp = null, $pageSize = null)
        {
            if (empty($timestamp))
            {
                $timestamp = time();
            }
            $dateTime = DateTimeUtil::convertTimestampToDbFormatDateTime($timestamp);
            assert('is_int($processed)');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'processed',
                    'operatorType'              => 'equals',
                    'value'                     => intval($processed),
                ),
                2 => array(
                    'attributeName'             => 'campaign',
                    'relatedAttributeName'      => 'sendOnDateTime',
                    'operatorType'              => 'lessThan',
                    'value'                     => $dateTime,
                ),
            );
            $searchAttributeData['structure'] = '(1 and 2)';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter(get_called_class());
            $where = RedBeanModelDataProvider::makeWhere(get_called_class(), $searchAttributeData, $joinTablesAdapter);
            return self::getSubset($joinTablesAdapter, null, $pageSize, $where, null);
        }

        public static function getByProcessedAndStatusAndSendOnDateTime($processed, $status, $timestamp = null, $pageSize = null)
        {
            if (empty($timestamp))
            {
                $timestamp = time();
            }
            $dateTime = DateTimeUtil::convertTimestampToDbFormatDateTime($timestamp);
            assert('is_int($processed)');
            assert('is_int($status)');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'processed',
                    'operatorType'              => 'equals',
                    'value'                     => intval($processed),
                ),
                2 => array(
                    'attributeName'             => 'campaign',
                    'relatedAttributeName'      => 'status',
                    'operatorType'              => 'equals',
                    'value'                     => intval($status),
                ),
                3 => array(
                    'attributeName'             => 'campaign',
                    'relatedAttributeName'      => 'sendOnDateTime',
                    'operatorType'              => 'lessThan',
                    'value'                     => $dateTime,
                ),
            );
            $searchAttributeData['structure'] = '(1 and 2 and 3)';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter(get_called_class());
            $where = RedBeanModelDataProvider::makeWhere(get_called_class(), $searchAttributeData, $joinTablesAdapter);
            return self::getSubset($joinTablesAdapter, null, $pageSize, $where, null);
        }

        /**
         * @param int $processed
         * @param int $campaignId
         * @param null|int $pageSize
         */
        public static function getByProcessedAndCampaignId($processed, $campaignId, $pageSize = null)
        {
            assert('is_int($processed)');
            assert('is_int($campaignId) || is_string($campaignId)');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'processed',
                    'operatorType'              => 'equals',
                    'value'                     => intval($processed),
                ),
                2 => array(
                    'attributeName'             => 'campaign',
                    'relatedAttributeName'      => 'id',
                    'operatorType'              => 'equals',
                    'value'                     => $campaignId,
                ),
            );
            $searchAttributeData['structure'] = '(1 and 2)';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter(get_called_class());
            $where = RedBeanModelDataProvider::makeWhere(get_called_class(), $searchAttributeData, $joinTablesAdapter);
            return self::getSubset($joinTablesAdapter, null, $pageSize, $where, null);
        }

        /**
         * @param int $type
         * @param int $campaignId
         * @param null|int $pageSize
         * @param null|bool $countOnly
         */
        public static function getByTypeAndCampaignId($type, $campaignId, $pageSize = null, $countOnly = false)
        {
            assert('is_int($type)');
            assert('is_int($campaignId) || is_string($campaignId)');
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'             => 'campaignItemActivities',
                    'relatedAttributeName'      => 'type',
                    'operatorType'              => 'equals',
                    'value'                     => intval($type),
                ),
                2 => array(
                    'attributeName'             => 'campaign',
                    'relatedAttributeName'      => 'id',
                    'operatorType'              => 'equals',
                    'value'                     => $campaignId,
                ),
            );
            $searchAttributeData['structure'] = '(1 and 2)';
            $joinTablesAdapter                = new RedBeanModelJoinTablesQueryAdapter(get_called_class());
            $where = RedBeanModelDataProvider::makeWhere(get_called_class(), $searchAttributeData, $joinTablesAdapter);
            if ($countOnly)
            {
                return self::getCount($joinTablesAdapter, $where, get_called_class(), true);
            }
            return self::getSubset($joinTablesAdapter, null, $pageSize, $where, null);
        }

        /**
         * Return true if the related email message in on the outbox folder
         * @return bool
         */
        public function isQueued()
        {
            if ($this->emailMessage->folder->type ==  EmailFolder::TYPE_OUTBOX ||
                $this->emailMessage->folder->type ==  EmailFolder::TYPE_OUTBOX_ERROR)
            {
                return true;
            }
            return false;
        }

        /**
         * @return bool
         */
        public function isSkipped()
        {
            $count = CampaignItemActivity::getByTypeAndModelIdAndPersonIdAndUrl(CampaignItemActivity::TYPE_SKIP,
                                                                                $this->id,
                                                                                $this->contact->getClassId('Person'),
                                                                                null,
                                                                                'latestDateTime',
                                                                                null,
                                                                                true);
            if ($count > 0)
            {
                return true;
            }
            return false;
        }

        /**
         * Return true if the email message has been sent
         * @return bool
         */
        public function isSent()
        {
            if ($this->emailMessage->id > 0 && $this->emailMessage->folder->type ==  EmailFolder::TYPE_SENT)
            {
                return true;
            }
            return false;
        }

        /**
         * @return bool
         */
        public function hasFailedToSend()
        {
            if ($this->emailMessage->id > 0 && $this->emailMessage->folder->type ==  EmailFolder::TYPE_OUTBOX_FAILURE)
            {
                return true;
            }
            return false;
        }

        /**
         * @return bool;
         */
        public function hasAtLeastOneOpenActivity()
        {
            return $this->hasAtLeastOneEventActivity(CampaignItemActivity::TYPE_OPEN);
        }

        /**
         * @return bool;
         */
        public function hasAtLeastOneClickActivity()
        {
            return $this->hasAtLeastOneEventActivity(CampaignItemActivity::TYPE_CLICK);
        }

        /**
         * @return bool;
         */
        public function hasAtLeastOneUnsubscribeActivity()
        {
            return $this->hasAtLeastOneEventActivity(CampaignItemActivity::TYPE_UNSUBSCRIBE);
        }

        /**
         * @return bool;
         */
        public function hasAtLeastOneBounceActivity()
        {
            return $this->hasAtLeastOneEventActivity(CampaignItemActivity::TYPE_BOUNCE);
        }

        /**
         * @return bool;
         */
        public function hasAtLeastOneEventActivity($eventType)
        {
            $count = CampaignItemActivity::getByTypeAndModelIdAndPersonIdAndUrl($eventType,
                                                                                $this->id,
                                                                                $this->contact->getClassId('Person'),
                                                                                null,
                                                                                'latestDateTime',
                                                                                null,
                                                                                true);
            if ($count > 0)
            {
                return true;
            }
            return false;
        }

        protected function afterDelete()
        {
            $this->emailMessage->delete();
            foreach ($this->campaignItemActivities as $activity)
            {
                $activity->delete();
            }
            return parent::afterDelete();
        }

        public static function getAllContactsFromCampaignItemBasedOnItemActivity($campaignId, $offset, $pageSize,
                                                              $getNotViewedItem = false, $getNotClickedItems = false,
                                                              $getOpenedItems = false, $getClickedItems = false)
        {
            $ids = array();
            if ($getNotViewedItem)
            {
                $ids = self::getNotViewedContactIds($campaignId, $offset, $pageSize);
            }
            if ($getNotClickedItems)
            {
                $ids = array_merge($ids, self::getNotClickedOrUnsubscribedOrSpamContactIds($campaignId, $offset, $pageSize));
            }
            if ($getOpenedItems)
            {
                $ids = array_merge($ids, self::getContactIdsFromCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_OPEN, $campaignId, $offset, $pageSize));
            }
            if ($getClickedItems)
            {
                $ids = array_merge($ids, self::getContactIdsFromCampaignItemsByActivityTypeAndCampaign(CampaignItemActivity::TYPE_CLICK, $campaignId, $offset, $pageSize));
            }
            $ids = array_unique($ids);
            $beans = ZurmoRedBean::batch ('contact', $ids);
            return self::makeModels($beans, 'Contact');
        }

        /**
         * Get not viewed contacts from campaigns.
         * @param int $campaignId
         * @param int $offset
         * @param int $pageSize
         * @return array
         *
         */
        public static function getNotViewedContactIds($campaignId, $offset, $pageSize)
        {
            assert('is_int($campaignId)');
            assert('is_int($offset)');
            assert('is_int($pageSize)');
            $sql = "select DISTINCT(`campaignitem`.`contact_id`) as id
                    from `campaignitem`
                    left join `campaignitemactivity` on `campaignitemactivity`.`campaignitem_id` = campaignitem.id
                    where `campaignitem`.`campaign_id` = $campaignId
                    and `campaignitemactivity`.`id` is null
                    order by `campaignitem`.`id`
                    limit $offset, $pageSize
                    ";
            $ids   = ZurmoRedBean::getCol($sql);
            return $ids;
        }

        /**
         * Get count of not viewed items.
         * @param int $campaignId
         * @return int
         *
         */
        public static function getCountOfNotViewedContactIds($campaignId)
        {
            assert('is_int($campaignId)');
            $sql = "select COUNT(DISTINCT(`campaignitem`.`contact_id`))
                    from `campaignitem`
                    left join `campaignitemactivity` on `campaignitemactivity`.`campaignitem_id` = campaignitem.id
                    where `campaignitem`.`campaign_id` = $campaignId
                    and `campaignitemactivity`.`id` is null
                    ";
            $count   = ZurmoRedBean::getCell($sql);
            return $count;
        }

        /**
         * Get contacts from campaign items that are not clicked, but do not return those that are marked as spam, unsubscribed, or hard bounced
         * @param int $campaignId
         * @param int offset
         * @param int pageSize
         * @return array
         */
        public static function getNotClickedOrUnsubscribedOrSpamContactIds($campaignId, $offset, $pageSize)
        {
            assert('is_int($campaignId)');
            assert('is_int($offset)');
            assert('is_int($pageSize)');
            $sql = "select DISTINCT(`campaignitem`.`contact_id`) as id
                    from `campaignitem`
                    left join `campaignitemactivity` on `campaignitemactivity`.`campaignitem_id` = campaignitem.id
                    where `campaignitem`.`campaign_id` = $campaignId
                    and
                      (
                        `campaignitemactivity`.`id` is null OR
                        `campaignitem`.`id` NOT IN (
                          select DISTINCT(`campaignitemactivity`.`campaignitem_id`) from campaignitemactivity
                          left join `emailmessageactivity` on `emailmessageactivity`.`id` = `campaignitemactivity`.`emailmessageactivity_id`
                          where
                          `emailmessageactivity`.type =  " . EmailMessageActivity::TYPE_CLICK . " OR
                          `emailmessageactivity`.type =  " . EmailMessageActivity::TYPE_UNSUBSCRIBE . " OR
                          `emailmessageactivity`.type =  " . EmailMessageActivity::TYPE_SPAM . " OR
                          `emailmessageactivity`.type =  " . EmailMessageActivity::TYPE_HARD_BOUNCE . "
                        )
                      )
                    order by `campaignitem`.`id`
                    limit $offset, $pageSize
                    ";
            $ids   = ZurmoRedBean::getCol($sql);
            return $ids;
        }

        /**
         * Get count of contacts that are not clicked, but do not return those that are marked as spam, unsubscribed, or hard bounced
         * @param int $campaignId
         * @return int
         */
        public static function getCountOfNotClickedOrUnsubscribedOrSpamContactIds($campaignId)
        {
            assert('is_int($campaignId)');
            $sql = "select COUNT(DISTINCT(`campaignitem`.`contact_id`))
                    from `campaignitem`
                    left join `campaignitemactivity` on `campaignitemactivity`.`campaignitem_id` = campaignitem.id
                    where `campaignitem`.`campaign_id` = $campaignId
                    and
                      (
                        `campaignitemactivity`.`id` is null OR
                        `campaignitem`.`id` NOT IN (
                          select DISTINCT(`campaignitemactivity`.`campaignitem_id`) from campaignitemactivity
                          left join `emailmessageactivity` on `emailmessageactivity`.`id` = `campaignitemactivity`.`emailmessageactivity_id`
                          where
                          `emailmessageactivity`.type =  " . EmailMessageActivity::TYPE_CLICK . " OR
                          `emailmessageactivity`.type =  " . EmailMessageActivity::TYPE_UNSUBSCRIBE . " OR
                          `emailmessageactivity`.type =  " . EmailMessageActivity::TYPE_SPAM . " OR
                          `emailmessageactivity`.type =  " . EmailMessageActivity::TYPE_HARD_BOUNCE . "
                        )
                      )
                    ";
            $count   = ZurmoRedBean::getCell($sql);
            return $count;
        }

        /**
         * Get distinct contact ids by activity type and campaign
         * @param $type
         * @param int $campaignId
         * @param $offset
         * @param $pageSize
         * @return An
         */
        public static function getContactIdsFromCampaignItemsByActivityTypeAndCampaign($type, $campaignId, $offset, $pageSize)
        {
            assert('is_int($type)');
            assert('is_int($campaignId)');
            assert('is_int($offset)');
            assert('is_int($pageSize)');
            $sql = "select DISTINCT(`campaignitem`.`contact_id`) as id
                    from `campaignitem`
                    left join `campaignitemactivity` on `campaignitemactivity`.`campaignitem_id` = campaignitem.id
                    left join `emailmessageactivity` on `emailmessageactivity`.`id` = `campaignitemactivity`.`emailmessageactivity_id`
                    where `campaignitem`.`campaign_id` = $campaignId
                    and `emailmessageactivity`.`type` = $type
                    order by `campaignitem`.`id`
                    limit $offset, $pageSize
                    ";
            $ids   = ZurmoRedBean::getCol($sql);
            return $ids;
        }

        /**
         * Get number of distinct contacts by activity type and campaign
         * @param $type
         * @param int $campaignId
         * @return string
         */
        public static function getCountOfCampaignItemsByActivityTypeAndCampaign($type, $campaignId)
        {
            assert('is_int($type)');
            assert('is_int($campaignId)');
            $sql = "select COUNT(DISTINCT(`campaignitem`.`contact_id`))
                    from `campaignitem`
                    left join `campaignitemactivity` on `campaignitemactivity`.`campaignitem_id` = campaignitem.id
                    left join `emailmessageactivity` on `emailmessageactivity`.`id` = `campaignitemactivity`.`emailmessageactivity_id`
                    where `campaignitem`.`campaign_id` = $campaignId
                    and `emailmessageactivity`.`type` = $type
                    ";
            $count   = ZurmoRedBean::getCell($sql);
            return $count;
        }
    }
?>