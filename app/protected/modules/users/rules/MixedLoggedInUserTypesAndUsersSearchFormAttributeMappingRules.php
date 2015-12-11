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
     * Rule used in search form to define how the different date types are proceesed.
     */
    class MixedLoggedInUserTypesAndUsersSearchFormAttributeMappingRules extends SearchFormAttributeMappingRules
    {
        const TYPE_SELECT_USER   = 'Select User';

        const TYPE_LOGGED_IN_USER   = 'Logged In User';

        const NAME_VALUE_FOR_LOGGED_IN_USER = "Logged In User";

        /**
         * In the event that the type is BEFORE or AFTER, and the firstDate value is not populated, it will be treated
         * as null, and the search on this attribute will be ignored.  At some point in the future the search form
         * could have validation added, so that the empty firstDate combined with a type of BEFORE or AFTER would not
         * get this far, but for now this is the easiest approach to ensuring a valid BEFORE or AFTER value.
         * @param mixed $value
         * @return mixed
         */
        public static function resolveValueDataIntoUsableValue($value)
        {
            if (isset($value['type']) && $value['type'] != null)
            {
                $validValueTypes = static::getValidValueTypes();
                if (!in_array($value['type'], $validValueTypes))
                {
                    throw new NotSupportedException();
                }
                if ($value['type'] == self::TYPE_LOGGED_IN_USER)
                {
                    return Yii::app()->user->userModel->id;
                }
                else
                {
                    //@ToDO: change id with parameter name
                    if ($value["id"] == null)
                    {
                        return null;
                    }
                    return $value['id'];
                }
            }
            else
            {
                // Allow because backward compatibility, so users do not need to edit their existing reports.
                // However when they try to edit an existing report taht use user, they will be asked to select valueType
                //@ToDO: change id with parameter name
                if ($value["id"] != null)
                {
                    return $value['id'];
                }
            }
            return null;
        }

        /**
         * @return array
         */
        public static function getValidValueTypes()
        {
            return array(
                self::TYPE_SELECT_USER,
                self::TYPE_LOGGED_IN_USER,
            );
        }

        /**
         * @return array
         */
        public static function getValueTypesAndLabels()
        {
            return array(
                self::TYPE_SELECT_USER    => Zurmo::t('Users', 'Select User'),
                self::TYPE_LOGGED_IN_USER    => Zurmo::t('Users', 'Logged In User'),
            );
        }

        /**
         * The value['type'] deterimines how the attributeAndRelations is structured.
         * @param string $attributeName
         * @param array $attributeAndRelations
         * @param mixed $value
         */
        public static function resolveAttributesAndRelations($attributeName, & $attributeAndRelations, $value)
        {
            assert('is_string($attributeName)');
            assert('$attributeAndRelations == "resolveEntireMappingByRules"');
            assert('empty($value) || $value == null || is_array($value)');
            $delimiter                      = FormModelUtil::DELIMITER;
            $parts = explode($delimiter, $attributeName);
            if (count($parts) < 2)
            {
                throw new NotSupportedException();
            }
            elseif (count($parts) > 2)
            {
                $count = count($parts);
                $realAttributeName = $parts[$count - 2];
                $type              = $parts[$count - 1];
            }
            else
            {
                list($realAttributeName, $type) = $parts;
            }
            if (isset($value['type']) && $value['type'] != null)
            {
                if ($value['type'] == self::TYPE_LOGGED_IN_USER || $value['type'] == self::TYPE_SELECT_USER)
                {
                    $attributeAndRelations = array(array($realAttributeName, null, 'equals', 'resolveValueByRules'));
                }
                else
                {
                    throw new NotSupportedException();
                }
            }
            else
            {
                $attributeAndRelations = array(array($realAttributeName, null, null, 'resolveValueByRules'));
            }
        }

        /**
         * @return array
         */
        public static function getValueTypesRequiringSelectUserInput()
        {
            return array(self::TYPE_SELECT_USER);
        }

        /**
         * @return array
         */
        public static function getValueTypesWhereValueIsRequired()
        {
            return array(self::TYPE_SELECT_USER);
        }

        public static function getValueTypesWhereValueIsNotRequired()
        {
            return array(self::TYPE_LOGGED_IN_USER);
        }
    }
?>