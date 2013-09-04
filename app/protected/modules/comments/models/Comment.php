<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2013 Zurmo Inc.
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
     * "Copyright Zurmo Inc. 2013. All rights reserved".
     ********************************************************************************/

    /**
     * Model for storing comments that relate to a variety of different models across the application.
     */
    class Comment extends Item
    {
        /**
         * @param string $description
         */
        public static function getByDescription($description)
        {
            assert('is_string($description) && $description != ""');
            return self::getSubset(null, null, null, "description = '$description'");
        }

        protected static function translatedAttributeLabels($language)
        {
            return array_merge(parent::translatedAttributeLabels($language),
                array(
                    'description'   => Zurmo::t('ZurmoModule',    'Description',  array(), null, $language),
                    'files'         => Zurmo::t('ZurmoModule',    'Files',        array(), null, $language),
                )
            );
        }

        public function __toString()
        {
            if (trim($this->description) == '')
            {
                return Zurmo::t('CommentsModule', '(Unnamed)');
            }
            return $this->description;
        }

        /**
         * Given a related model type, a related model id, and a page size, return a list of comment models.
         * @param string $type
         * @param integer $relatedId
         * @param integer $pageSize
         */
        public static function getCommentsByRelatedModelTypeIdAndPageSize($type,  $relatedId, $pageSize)
        {
            assert('is_string($type)');
            assert('is_int($relatedId)');
            assert('is_int($pageSize) || $pageSize == null');
            $joinTablesAdapter = new RedBeanModelJoinTablesQueryAdapter('Comment');
            $orderByColumnName = RedBeanModelDataProvider::
                                 resolveSortAttributeColumnName('Comment', $joinTablesAdapter, 'createdDateTime');
            $where             = "relatedmodel_type = '" . strtolower($type) . "' AND relatedmodel_id = '" . $relatedId . "'";
            $orderBy           = $orderByColumnName . ' desc';
            return self::getSubset($joinTablesAdapter, null, $pageSize, $where, $orderBy);
        }

        public static function getModuleClassName()
        {
            return 'CommentsModule';
        }

        public static function canSaveMetadata()
        {
            return false;
        }

        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'description',
                ),
                'relations' => array(
                    'files' => array(RedBeanModel::HAS_MANY,  'FileModel', RedBeanModel::OWNED,
                                     RedBeanModel::LINK_TYPE_POLYMORPHIC, 'relatedModel'),
                ),
                'rules' => array(
                    array('description', 'required'),
                    array('description', 'type',    'type' => 'string'),
                ),
                'elements' => array(
                    'description'        => 'TextArea',
                    'files'              => 'Files',
                ),
            );
            return $metadata;
        }

        public static function isTypeDeletable()
        {
            return true;
        }

        public static function getGamificationRulesType()
        {
            return 'CommentGamification';
        }
    }
?>