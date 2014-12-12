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

    class MergeTagsContentResolverUtil
    {
        const ADD_GLOBAL_FOOTER_MERGE_TAGS_IF_MISSING       = 1;

        const REMOVE_GLOBAL_FOOTER_MERGE_TAGS_IF_PRESENT    = -1;

        /**
         * Resolve Content for Global Footers, MergeTags and Tracking
         * @param $textContent
         * @param $htmlContent
         * @param RedBeanModel $attachedModel
         * @param int $emailTemplateType
         * @param int $errorOnFirstMissing
         * @param null $language
         * @param array $invalidTags
         * @param null $marketingListId
         * @param bool $preview
         * @param int $addGlobalFooterMergeTags
         * @param bool $enableTracking
         * @param null $modelId
         * @param null $modelType
         * @param null $personId
         */
        public static function resolveContentsForGlobalFooterAndMergeTagsAndTracking(& $textContent, & $htmlContent,
                                             RedBeanModel $attachedModel = null, $emailTemplateType = EmailTemplate::TYPE_CONTACT,
                                              $errorOnFirstMissing = MergeTagsToModelAttributesAdapter::ERROR_ON_FIRST_INVALID_TAG,
                                              $language = null, array & $invalidTags = array(), $marketingListId = null,
                                              $preview = false, $addGlobalFooterMergeTags = self::ADD_GLOBAL_FOOTER_MERGE_TAGS_IF_MISSING,
                                              $enableTracking = true, $modelId = null, $modelType = null, $personId = null)
        {
            assert('is_null($textContent) || is_string($textContent)');
            assert('is_null($htmlContent) || is_string($htmlContent)');
            assert('is_null($attachedModel) || is_object($attachedModel)');
            assert('is_int($emailTemplateType)');
            assert('in_array($emailTemplateType, array_keys(EmailTemplate::getTypeDropDownArray()))');
            assert('is_int($errorOnFirstMissing)');
            assert('is_null($language) || is_string($language)');
            assert('is_array($invalidTags)');
            assert('is_null($marketingListId) || is_int($marketingListId)');
            assert('is_bool($preview)');
            assert('is_int($addGlobalFooterMergeTags)');
            assert('is_bool($enableTracking)');
            assert('is_null($modelId) || is_int($modelId)');
            assert('is_null($modelType) || is_string($modelType)');
            assert('is_null($personId) || is_int($personId)');
            static::resolveContentsForGlobalFooterMergeTags($textContent, $htmlContent, $addGlobalFooterMergeTags);
            static::resolveContentsForMergeTags($textContent, $htmlContent, $attachedModel, $emailTemplateType,
                                                    $errorOnFirstMissing, $language, $invalidTags, $marketingListId,
                                                    $preview, $modelId, $modelType, $personId);
            static::resolveContentsForTracking($textContent, $htmlContent, $enableTracking, $modelId, $modelType, $personId);
        }

        /**
         * Resolve content for global footer merge tags
         * @param $textContent
         * @param $htmlContent
         * @param int $addGlobalFooterMergeTags
         */
        public static function resolveContentsForGlobalFooterMergeTags(& $textContent, & $htmlContent,
                                                            $addGlobalFooterMergeTags = self::ADD_GLOBAL_FOOTER_MERGE_TAGS_IF_MISSING)
        {
            if ($addGlobalFooterMergeTags == static::ADD_GLOBAL_FOOTER_MERGE_TAGS_IF_MISSING)
            {
                GlobalMarketingFooterUtil::resolveContentsForGlobalFooter($textContent, $htmlContent);
            }
            else
            {
                static::removeGlobalFooterMergeTagsFromContents($textContent, $htmlContent);
            }
        }

        /**
         * Remove global merge tags from contents
         * @param $textContent
         * @param $htmlContent
         */
        public static function removeGlobalFooterMergeTagsFromContents(& $textContent, & $htmlContent)
        {
            GlobalMarketingFooterUtil::removeFooterMergeTags($textContent);
            GlobalMarketingFooterUtil::removeFooterMergeTags($htmlContent);
        }

        public static function resolveContentsForTracking(& $textContent, & $htmlContent, $enableTracking = true,
                                                            $modelId = null, $modelType = null, $personId = null)
        {
            if ($enableTracking)
            {
                ContentTrackingUtil::resolveContentsForTracking($textContent, $htmlContent, $enableTracking,
                                                                $modelId, $modelType, $personId);
            }
        }

        /**
         * Resolve contents for merge tags
         * @param $textContent
         * @param $htmlContent
         * @param RedBeanModel $attachedModel
         * @param int $emailTemplateType
         * @param int $errorOnFirstMissing
         * @param null $language
         * @param array $invalidTags
         * @param null $marketingListId
         * @param bool $preview
         * @param null $modelId
         * @param null $modelType
         * @param null $personId
         * @throws NotSupportedException
         */
        public static function resolveContentsForMergeTags(& $textContent, & $htmlContent, RedBeanModel $attachedModel = null,
                                                           $emailTemplateType = EmailTemplate::TYPE_CONTACT,
                                                           $errorOnFirstMissing = MergeTagsToModelAttributesAdapter::ERROR_ON_FIRST_INVALID_TAG,
                                                           $language = null, array & $invalidTags = array(),
                                                           $marketingListId = null, $preview = false, $modelId = null,
                                                           $modelType = null, $personId = null)
        {
            static::resolveContentForMergeTagsWithExceptionOnFailure($textContent, $attachedModel, $emailTemplateType,
                                                                        $errorOnFirstMissing, $language, $invalidTags,
                                                                        $marketingListId, $preview, $modelId,
                                                                        $modelType, $personId, false);
            static::resolveContentForMergeTagsWithExceptionOnFailure($htmlContent, $attachedModel, $emailTemplateType,
                                                                        $errorOnFirstMissing, $language, $invalidTags,
                                                                        $marketingListId, $preview, $modelId,
                                                                        $modelType, $personId, true);
        }

        /**
         * Resolve content for merge tag and throw exception if resolution fails
         * @param $content
         * @param RedBeanModel $attachedModel
         * @param int $emailTemplateType
         * @param int $errorOnFirstMissing
         * @param null $language
         * @param array $invalidTags
         * @param null $marketingListId
         * @param bool $preview
         * @param null $modelId
         * @param null $modelType
         * @param null $personId
         * @param bool $isHtmlContent
         * @throws NotSupportedException
         */
        public static function resolveContentForMergeTagsWithExceptionOnFailure(& $content, RedBeanModel $attachedModel = null,
                                                          $emailTemplateType = EmailTemplate::TYPE_CONTACT,
                                                          $errorOnFirstMissing = MergeTagsToModelAttributesAdapter::ERROR_ON_FIRST_INVALID_TAG,
                                                          $language = null, array & $invalidTags = array(),
                                                          $marketingListId = null, $preview = false, $modelId = null,
                                                          $modelType = null, $personId = null, $isHtmlContent = false)
        {
            $resolved   = static::resolveContentForMergeTags($content, $attachedModel, $emailTemplateType, $errorOnFirstMissing,
                                                    $language, $invalidTags, $marketingListId, $preview, $modelId,
                                                    $modelType, $personId, $isHtmlContent);
            if ($resolved === false)
            {
                throw new NotSupportedException(Zurmo::t('EmailTemplatesModule', 'Provided content contains few invalid merge tags.'));
            }
        }

        /**
         * Resolve content for merge tags and return resolution result
         * @param $content
         * @param RedBeanModel $attachedModel
         * @param int $emailTemplateType
         * @param int $errorOnFirstMissing
         * @param null $language
         * @param array $invalidTags
         * @param null $marketingListId
         * @param bool $preview
         * @param null $modelId
         * @param null $modelType
         * @param null $personId
         * @param bool $isHtmlContent
         * @return bool
         * @throws NotSupportedException
         */
        public static function resolveContentForMergeTags(& $content, RedBeanModel $attachedModel = null,
                                                $emailTemplateType = EmailTemplate::TYPE_CONTACT,
                                                $errorOnFirstMissing = MergeTagsToModelAttributesAdapter::ERROR_ON_FIRST_INVALID_TAG,
                                                $language = null, array & $invalidTags = array(),
                                                $marketingListId = null, $preview = false, $modelId = null,
                                                $modelType = null, $personId = null, $isHtmlContent = false)
        {
            $params             = GlobalMarketingFooterUtil::resolveFooterMergeTagsArray($personId, $marketingListId,
                                                                                        $modelId, $modelType, !$preview,
                                                                                        $preview, $isHtmlContent);
            $util               = MergeTagsUtilFactory::make($emailTemplateType, $language, $content);
            $resolvedContent    = $util->resolveMergeTags($attachedModel, $invalidTags, $language,$errorOnFirstMissing, $params);
            if ($resolvedContent !== false)
            {
                $content    = $resolvedContent;
                return true;
            }
            return false;
        }
    }
?>