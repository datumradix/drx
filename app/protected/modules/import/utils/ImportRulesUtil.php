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
     * Helper class for working with ImportRules.
     */
    class ImportRulesUtil
    {
        /**
         * @param string $importRulesType
         * @return string
         * @throws NotSupportedException
         */
        public static function getImportRulesClassNameByType($importRulesType)
        {
            assert('is_string($importRulesType)');
            $importRulesClassName = $importRulesType . 'ImportRules';
            if (@class_exists($importRulesClassName) === false)
            {
                throw new NotSupportedException($importRulesClassName);
            }
            return $importRulesClassName;
        }

        /**
         * @param string $importRulesType
         * @return mixed
         */
        public static function makeImportRulesByType($importRulesType)
        {
            assert('is_string($importRulesType)');
            $importRulesClassName = self::getImportRulesClassNameByType($importRulesType);
            return new $importRulesClassName();
        }

        /**
         * Based on the current user, return the importRules types and their display labels.  Only include import rules
         * that the user has a right to access its corresponding module.
         * @return array of import rules types and display labels.
         */
        public static function getImportRulesTypesForCurrentUser()
        {
            //todo: cache results to improve performance if needed.
            $importRulesTypes = array();
            $modules = Module::getModuleObjects();
            foreach ($modules as $module)
            {
                $rulesClassNames = $module::getAllClassNamesByPathFolder('rules');
                foreach ($rulesClassNames as $ruleClassName)
                {
                    $classToEvaluate     = new ReflectionClass($ruleClassName);
                    if (is_subclass_of($ruleClassName, 'ImportRules') && !$classToEvaluate->isAbstract())
                    {
                        $moduleClassNames = $ruleClassName::getModuleClassNames();
                        $addToArray       = true;
                        foreach ($moduleClassNames as $moduleClassNameToCheckAccess)
                        {
                            if (!RightsUtil::canUserAccessModule($moduleClassNameToCheckAccess,
                                                                Yii::app()->user->userModel) ||
                                !RightsUtil::
                                doesUserHaveAllowByRightName($moduleClassNameToCheckAccess,
                                                             $moduleClassNameToCheckAccess::getCreateRight(),
                                                             Yii::app()->user->userModel))
                            {
                                $addToArray = false;
                            }
                        }
                        if ($addToArray)
                        {
                            $importRulesTypes[$ruleClassName::getType()] = $ruleClassName::getDisplayLabel();
                        }
                    }
                }
            }
            return $importRulesTypes;
        }

        /**
         * Given a collection of required attributes by attribute indexes and a collection of mapped attribute
         * rules, check if all of the required attributes are mapped.
         * @param array $requiredAttributeCollection
         * @param array $mappedAttributeImportRulesCollection
         * @throws NotSupportedException - Throws an error if the $mappedAttributeImportRulesCollection contains
         *                                 any attribute rules that are not AttributeImportRules.
         * @return boolean true - all required are mapped, otherwise false.
         */
        public static function areAllRequiredAttributesMappedOrHaveRules(& $requiredAttributeCollection,
                                                                         $mappedAttributeImportRulesCollection)
        {
            assert('is_array($requiredAttributeCollection)');
            assert('is_array($mappedAttributeImportRulesCollection)');
            foreach ($mappedAttributeImportRulesCollection as $attributeIndex => $attributeImportRules)
            {
                $modelAttributeNames        = array();
                if ($attributeImportRules instanceof DerivedAttributeImportRules)
                {
                    $modelAttributeNames    = $attributeImportRules->getRealModelAttributeNames();
                }
                elseif ($attributeImportRules instanceof NonDerivedAttributeImportRules)
                {
                    $modelAttributeNames[0] = $attributeIndex;
                }
                else
                {
                    throw new NotSupportedException();
                }
                foreach ($modelAttributeNames as $modelAttributeName)
                {
                    if (isset($requiredAttributeCollection[$modelAttributeName]))
                    {
                        unset($requiredAttributeCollection[$modelAttributeName]);
                    }
                }
            }
            if (count($requiredAttributeCollection) > 0)
            {
                return false;
            }
            else
            {
                return true;
            }
        }

        /**
         * Given an array of mapped attribute rules, determine if any of the mapped rules overlap in which
         * attributes they map to. This can happen if a derived attribute type contains multiple model attributes.
         * If that derived type is mapped to one column, and one of those individual model attributes is also
         * mapped to a different column, then this is considered an overlap and is not allowed. If this is found an
         * exception is thrown.
         * @param array $mappedAttributeImportRulesCollection
         * @throws ImportAttributeMappedMoreThanOnceException
         * @return null;
         */
        public static function checkIfAnyAttributesAreDoubleMapped($mappedAttributeImportRulesCollection)
        {
            assert('is_array($mappedAttributeImportRulesCollection)');
            $mappedModelAttributeNames = array();
            foreach ($mappedAttributeImportRulesCollection as $attributeIndexOrDerivedAttributeType => $attributeImportRules)
            {
                $relationNameAndAttributeName = explode(FormModelUtil::DELIMITER, $attributeIndexOrDerivedAttributeType);
                if (count($relationNameAndAttributeName) == 2)
                {
                    $relationName = $relationNameAndAttributeName[0];
                }
                else
                {
                    $relationName = 'None';
                }
                if ($attributeImportRules instanceof AttributeImportRules)
                {
                    $modelAttributeNames       = $attributeImportRules->getRealModelAttributeNames();
                    foreach ($modelAttributeNames as $modelAttributeName)
                    {
                        if (isset($mappedModelAttributeNames[$relationName]) &&
                            in_array($modelAttributeName, $mappedModelAttributeNames[$relationName]))
                        {
                            $displayLabel = $attributeImportRules->getDisplayLabelByAttributeName($modelAttributeName);
                            throw new ImportAttributeMappedMoreThanOnceException($displayLabel);
                        }
                        $mappedModelAttributeNames[$relationName][] = $modelAttributeName;
                    }
                }
                else
                {
                    throw new NotSupportedException();
                }
            }
        }
    }
?>