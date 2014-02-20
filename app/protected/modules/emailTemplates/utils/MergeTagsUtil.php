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

    /*
     * Base class that defines Merge Tag delimiters, extracts them, and provides methods for converting them to values.
     */
    class MergeTagsUtil
    {
        const TAG_PREFIX            = '[[';

        const TAG_SUFFIX            = ']]';

        const PROPERTY_DELIMITER    = '__';

        const TIME_DELIMITER        = '%';

        const CAPITAL_DELIMITER     = '^';

        protected $mergeTags;

        protected $content;

        protected $language;

        public static function resolveAttributeStringToMergeTagString($attributeString)
        {
            $string = preg_replace('/(?<=\\w)(?=[A-Z])/', static::CAPITAL_DELIMITER . "$1", $attributeString);
            $string = strtolower($string);
            $string = str_replace(FormModelUtil::RELATION_DELIMITER, static::PROPERTY_DELIMITER, $string);
            $string = str_replace(static::PROPERTY_DELIMITER . static::CAPITAL_DELIMITER, static::PROPERTY_DELIMITER, $string);
            return static::TAG_PREFIX . strtoupper($string) . static::TAG_SUFFIX;
        }

        protected static function resolveUniqueMergeTags(& $mergeTags, $key)
        {
            $mergeTags = array_unique($mergeTags);
        }

        protected static function resolveFullyQualifiedMergeTagRegularExpression(& $value, $key)
        {
            $value = '/' . preg_quote($value) . '/';
        }

        /**
         * @param $language
         * @param string $content
         */
        public function __construct($language, $content) // TODO: @Shoaibi/@Jason Low: probably change it to locale object
        {
            $this->language = $language;
            $this->content  = $content;
        }

        /**
         * @param $model
         * @param array $invalidTags
         * @param null $language
         * @param bool $errorOnFirstMissing
         * @return bool | array
         */
        public function resolveMergeTagsArrayToAttributes($model, & $invalidTags = array(), $language = null, $errorOnFirstMissing = false)
        {
            if (!$language)
            {
                $language = $this->language;
            }
            if (empty($this->mergeTags))
            {
                return false;
            }
            else
            {
                return MergeTagsToModelAttributesAdapter::resolveMergeTagsArrayToAttributesFromModel($this->mergeTags[1],
                                        $model, $invalidTags, $language, $errorOnFirstMissing);
            }
        }

        /**
         * @param $model
         * @param array $invalidTags
         * @param null $language
         * @param bool $errorOnFirstMissing
         * @return bool | string
         */
        public function resolveMergeTags($model, & $invalidTags = array(), $language = null, $errorOnFirstMissing = false)
        {
            if (!$this->extractMergeTagsPlaceHolders() ||
                    $this->resolveMergeTagsArrayToAttributes($model, $invalidTags, $language, $errorOnFirstMissing) &&
                    $this->resolveMergeTagsInTemplateToAttributes())
            {
                return $this->content;
            }
            else
            {
                return false;
            }
        }

        public function extractMergeTagsPlaceHolders()
        {
            // Current RE: /((WAS\%)?(([A-Z0-9])(\^|__)?)+)/ // Not Coding Standard
            $pattern =  '/' . preg_quote(static::TAG_PREFIX) .
                        '((WAS' . preg_quote(static::TIME_DELIMITER) . ')?' .
                        '(([A-Z0-9])' . '(' . preg_quote(static::CAPITAL_DELIMITER) . '|' .
                        preg_quote(static::PROPERTY_DELIMITER) . ')?)+)' . // Not Coding Standard
                        preg_quote(static::TAG_SUFFIX) .
                        '/';
            // $this->mergeTags index 0 = with tag prefix and suffix, index 1 = without tag prefix and suffix
            $matchesCounts = preg_match_all($pattern, $this->content, $this->mergeTags);
            array_walk($this->mergeTags, 'static::resolveUniqueMergeTags');
            return $matchesCounts;
        }

        public function resolveMergeTagsInTemplateToAttributes()
        {
            $resolvedMergeTagsCount     = 0;
            $mergeTags                  = $this->mergeTags[0];
            $attributes                 = array_values($this->mergeTags[1]);
            $this->resolveFullyQualifiedMergeTagsRegularExpression($mergeTags);
            $content                    = preg_replace($mergeTags, $attributes, $this->content, -1, $resolvedMergeTagsCount);
            if (!empty($content))
            {
                $this->content = $content;
            }
            return $resolvedMergeTagsCount;
        }

        public function getContent()
        {
            return $this->content;
        }

        protected function resolveFullyQualifiedMergeTagsRegularExpression(& $mergeTags)
        {
            array_walk($mergeTags, 'static::resolveFullyQualifiedMergeTagRegularExpression');
        }
    }
?>