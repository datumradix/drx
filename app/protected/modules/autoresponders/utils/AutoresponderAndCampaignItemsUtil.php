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
     * Helper class for working with autoresponderItem and campaignItem
     * At places we intentionally use all lowercase variable names instead of camelCase to do easy
     * compact() on them and have them match column names in db on queries.
     */
    abstract class AutoresponderAndCampaignItemsUtil
    {
        protected $itemTableName            = null;

        protected $itemClass                = null;

        protected $itemId                   = null;

        protected $personId                 = null;

        protected $_instance                = null;

        public function processDueItem(OwnedModel $item)
        {
            assert('is_object($item)');
            $emailMessageId             = null;
            $this->itemId               = intval($item->id);
            $this->itemClass            = get_class($item);
            assert('$this->itemClass === "AutoresponderItem" || $this->itemClass === "CampaignItem"');
            $contact                    = $this->resolveContact($item);
            $itemOwnerModel             = $this->resolveItemOwnerModel($item);
            if ($itemOwnerModel->id < 0)
            {
                // the corresponding autoresponder/campaign has been deleted already.
                $item->delete();
                return false;
            }
            $this->personId           = $contact->getClassId('Person');

            if ($this->skipMessage($contact, $itemOwnerModel))
            {
               $this->createSkipActivity();
            }
            else
            {
                $marketingList              = $itemOwnerModel->marketingList;
                assert('is_object($marketingList)');
                assert('get_class($marketingList) === "MarketingList"');
                $textContent                = $itemOwnerModel->textContent;
                $htmlContent                = null;
                if ($this->supportsRichText($itemOwnerModel))
                {
                    $htmlContent = $itemOwnerModel->htmlContent;
                }
                $this->resolveContent($textContent, $htmlContent, $contact, $itemOwnerModel->enableTracking, (int)$marketingList->id);
                try
                {
                    $item->emailMessage   = $this->resolveEmailMessage($textContent, $htmlContent, $itemOwnerModel,
                                                                        $contact, $marketingList);
                }
                catch (MissingRecipientsForEmailMessageException $e)
                {
                   $this->createSkipActivity();
                }
            }
            $marked = $this->markItemAsProcessedWithSQL($item->emailMessage->id);
            return $marked;
        }

        protected function resolveContact(OwnedModel $item)
        {
            $contact                    = $item->contact;
            if (empty($contact) || $contact->id < 0)
            {
                throw new NotFoundException();
            }
            return $contact;
        }

        protected function resolveItemOwnerModel(OwnedModel $item)
        {
            $itemOwnerModel             = $item->{$this->resolveItemOwnerModelRelationName()};
            assert('is_object($itemOwnerModel)');
            assert('get_class($itemOwnerModel) === "Autoresponder" || get_class($itemOwnerModel) === "Campaign"');
            return $itemOwnerModel;
        }

        protected function skipMessage(Contact $contact, Item $itemOwnerModel)
        {
            return ($contact->primaryEmail->optOut ||
                // TODO: @Shoaibi: Critical0: We could use SQL for getByMarketingListIdContactIdandUnsubscribed to save further performance here.
                (get_class($itemOwnerModel) === "Campaign" && MarketingListMember::getByMarketingListIdContactIdAndUnsubscribed(
                        $itemOwnerModel->marketingList->id,
                        $contact->id,
                        true) != false));
        }

        protected function supportsRichText(Item $itemOwnerModel)
        {
            return (($this->itemClass == 'CampaignItem' && $itemOwnerModel->supportsRichText) ||
                        ($this->itemClass == 'AutoresponderItem'));
        }

        protected function createSkipActivity()
        {
            $activityClass  = $this->itemClass . 'Activity';
            $type           = $activityClass::TYPE_SKIP;
            $activityClass::createNewActivity($type, $this->itemId, $this->personId);
        }

        public function resolveContent(& $textContent, & $htmlContent, Contact $contact, $enableTracking,
                                            $marketingListId, $preview = false)
        {
            assert('is_int($marketingListId)');
            GlobalMarketingFooterUtil::resolveContentsForGlobalFooter($textContent, $htmlContent);
            $this->resolveContentsForMergeTags($textContent, $htmlContent, $contact,
                                                $marketingListId, $preview);
            if ($enableTracking)
            {
                ContentTrackingUtil::resolveContentsForTracking($textContent, $htmlContent, $enableTracking,
                    $this->itemId, $this->itemClass, $this->personId);
            }
        }

        public function resolveContentsForMergeTags(& $textContent, & $htmlContent, Contact $contact,
                                                            $marketingListId, $preview = false)
        {
            $this->resolveContentForMergeTags($textContent, $contact, false, $marketingListId, $preview);
            $this->resolveContentForMergeTags($htmlContent, $contact, true, $marketingListId, $preview);
        }

        protected function resolveContentForMergeTags(& $content, Contact $contact, $isHtmlContent,
                                                                $marketingListId, $preview = false)
        {
            $resolved   = $this->resolveMergeTags($content, $contact, $isHtmlContent,
                                                    $marketingListId, $preview);
            if ($resolved === false)
            {
                throw new NotSupportedException(Zurmo::t('EmailTemplatesModule', 'Provided content contains few invalid merge tags.'));
            }
        }

        protected function resolveLanguageForContent()
        {
            // TODO: @Shoaibi/@Jason: Low: we might add support for language
            return null;
        }

        protected function resolveEmailTemplateType()
        {
            return EmailTemplate::TYPE_CONTACT;
        }

        protected function resolveErrorOnFirstMissingMergeTag()
        {
            return true;
        }

        protected function resolveMergeTagsParams($marketingListId, $isHtmlContent = false, $preview = false)
        {
            $params     = GlobalMarketingFooterUtil::resolveFooterMergeTagsArray($this->personId, $marketingListId,
                                                                            $this->itemId, $this->itemClass, !$preview,
                                                                            $preview, $isHtmlContent);
            return $params;
        }

        protected function resolveMergeTagsUtil($content)
        {
            $language       = $this->resolveLanguageForContent();
            $templateType   = $this->resolveEmailTemplateType();
            $util           = MergeTagsUtilFactory::make($templateType, $language, $content);
            return $util;
        }

        protected function resolveMergeTags(& $content, Contact $contact, $isHtmlContent,
                                                   $marketingListId, $preview = false)
        {
            $invalidTags            = array();
            $language               = $this->resolveLanguageForContent();
            $errorOnFirstMissing    = $this->resolveErrorOnFirstMissingMergeTag();
            $params                 = $this->resolveMergeTagsParams($marketingListId, $isHtmlContent, $preview);
            $util                   = $this->resolveMergeTagsUtil($content);
            $resolvedContent        = $util->resolveMergeTags($contact, $invalidTags, $language,
                                                                $errorOnFirstMissing, $params);
            if ($resolvedContent !== false)
            {
                $content    = $resolvedContent;
                return true;
            }
            return false;
        }

        protected function resolveEmailMessage($textContent, $htmlContent, Item $itemOwnerModel,
                                                    Contact $contact, MarketingList $marketingList)
        {
            $emailMessage   = $this->saveEmailMessage($textContent, $htmlContent, $itemOwnerModel,
                                                        $contact, $marketingList);
            $this->sendEmailMessage($emailMessage);
            $this->resolveExplicitPermissionsForEmailMessage($emailMessage, $marketingList);
            return $emailMessage;
        }

        protected function saveEmailMessage($textContent, $htmlContent, Item $itemOwnerModel,
                                                    Contact $contact, MarketingList $marketingList)
        {
            $folder         = $this->resolveEmailFolder();
            $emailMessage   = AutoresponderAndCampaignItemsEmailMessageUtil::resolveAndSaveEmailMessage($textContent,
                                                                                                    $htmlContent,
                                                                                                    $itemOwnerModel,
                                                                                                    $contact,
                                                                                                    $marketingList,
                                                                                                    $this->itemId,
                                                                                                    $folder->id,
                                                                                                    $this->itemClass,
                                                                                                    $this->personId);
            return $emailMessage;
        }

        protected function sendEmailMessage(EmailMessage & $emailMessage)
        {
            Yii::app()->emailHelper->send($emailMessage, true, false);
        }

        protected function resolveExplicitPermissionsForEmailMessage(EmailMessage & $emailMessage, MarketingList $marketingList)
        {
            $explicitReadWriteModelPermissions  = ExplicitReadWriteModelPermissionsUtil::makeBySecurableItem($marketingList);
            ExplicitReadWriteModelPermissionsUtil::resolveExplicitReadWriteModelPermissions($emailMessage,
                                                                                    $explicitReadWriteModelPermissions);
        }

        protected function markItemAsProcessedWithSQL($emailMessageId = null)
        {
            $className              = $this->itemClass;
            $itemTableName          = $className::getTableName();
            $sql                    = "UPDATE " . DatabaseCompatibilityUtil::quoteString($itemTableName);
            $sql                    .= " SET " . DatabaseCompatibilityUtil::quoteString('processed') . ' = 1';
            if ($emailMessageId)
            {
                $emailMessageForeignKey = RedBeanModel::getForeignKeyName($this->itemClass, 'emailMessage');
                $sql .= ", " . DatabaseCompatibilityUtil::quoteString($emailMessageForeignKey);
                $sql .= " = ${emailMessageId}";
            }
            $sql                    .= " WHERE " . DatabaseCompatibilityUtil::quoteString('id') . " = {$this->itemId};";
            $effectedRows           = ZurmoRedBean::exec($sql);
            return ($effectedRows == 1);
        }

        protected function resolveItemOwnerModelRelationName()
        {
            if ($this->itemClass == 'AutoresponderItem')
            {
                return 'autoresponder';
            }
            else
            {
                return 'campaign';
            }
        }

        protected function resolveEmailBoxName()
        {
            if ($this->itemClass == "AutoresponderItem")
            {
                return EmailBox::AUTORESPONDERS_NAME;
            }
            else
            {
                return EmailBox::CAMPAIGNS_NAME;
            }
        }

        protected function resolveEmailFolder()
        {
            $boxName            = $this->resolveEmailBoxName();
            $box                = EmailBox::resolveAndGetByName($boxName);
            $folder             = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_DRAFT);
            return $folder;
        }
    }
?>