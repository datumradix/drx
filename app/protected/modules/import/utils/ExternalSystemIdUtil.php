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
     * Helper utility for handling the external system id and models.
     */
    class ExternalSystemIdUtil
    {
        /**
         * Database column name for the external system id on each model.
         * @var string
         */
        const EXTERNAL_SYSTEM_ID_COLUMN_NAME = 'external_system_id';

        /**
         * Given a model and external system id, update the external system id in the database for that model
         * @param object $model
         * @param string $externalSystemId
         */
        public static function updateByModel(RedBeanModel $model, $externalSystemId)
        {
            assert('$externalSystemId == null || is_string($externalSystemId)');
            $columnName     = self::EXTERNAL_SYSTEM_ID_COLUMN_NAME;
            $tableName      = $model::getTableName();
            static::addExternalIdColumnIfMissing($tableName);
            ZurmoRedBean::exec("update " . $tableName . " set $columnName = '" . $externalSystemId . "' where id = " . $model->id);
        }

        /**
         * Adds externalSystemId column to specific table if it does not exist
         * @param $tableName
         * @param $maxLength
         * @param $columnName
         */
        public static function addExternalIdColumnIfMissing($tableName, $maxLength = 255, $columnName = null)
        {
            if (!isset($columnName))
            {
                $columnName           = static::EXTERNAL_SYSTEM_ID_COLUMN_NAME;
            }
            // check if external_system_id exists in fields
            $columnExists   = ZurmoRedBean::$writer->doesColumnExist($tableName, $columnName);
            if (!$columnExists)
            {
                // if not, update model and add an external_system_id field
                $type       = 'string';
                $length     = null;
                RedBeanModelMemberRulesToColumnAdapter::resolveStringTypeAndLengthByMaxLength($type, $length, $maxLength);
                $columns    = array();
                $columns[]  = RedBeanModelMemberToColumnUtil::resolveColumnMetadataByHintType($columnName, $type, $length);
                $schema     = CreateOrUpdateExistingTableFromSchemaDefinitionArrayUtil::getTableSchema($tableName,
                                                                                                        $columns);
                CreateOrUpdateExistingTableFromSchemaDefinitionArrayUtil::generateOrUpdateTableBySchemaDefinition(
                                                                                        $schema, new MessageLogger());
            }
        }
    }
?>