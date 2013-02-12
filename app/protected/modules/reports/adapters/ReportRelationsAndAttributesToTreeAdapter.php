<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2012 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     * details.
     *
     * You should have received a copy of the GNU General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 113 McHenry Road Suite 207,
     * Buffalo Grove, IL 60089, USA. or at email address contact@zurmo.com.
     ********************************************************************************/

    /**
     * Helper class for adapting relation and attribute data into tree data
     */
    class ReportRelationsAndAttributesToTreeAdapter
    {
        /**
         * @var Report
         */
        protected $report;

        /**
         * @var string
         */
        protected $treeType;

        /**
         * @see ReportsDefaultController::actionAddAttributeFromTree for an example of where this is called from
         * The nodeId has the treeType as a prefix in order to distinguish from other nodes in the user interface.
         * @param string $nodeId
         * @param string $treeType
         * @return string nodeId without the prefixed treeType
         */
        public static function removeTreeTypeFromNodeId($nodeId, $treeType)
        {
            assert('is_string($nodeId)');
            assert('is_string($treeType)');
            $nodeIdParts  = explode($treeType . '_', $nodeId);
            return $nodeIdParts[1];
        }

        /**
         * @see ReportsDefaultController::actionAddAttributeFromTree for an example of where this is called from
         * @param string $formModelClassName
         * @param string $treeType
         * @param integer $rowNumber
         * @return array of input prefix parts.  Excludes the last element which is typically an attribute since this
         * is not part of the prefix for an Element.  Adds in the formModelClassName, a treeType, and then the rowNumber
         * as the first 2 elements.
         */
        public static function resolveInputPrefixData($formModelClassName, $treeType, $rowNumber)
        {
            assert('is_string($formModelClassName)');
            assert('is_string($treeType)');
            assert('is_int($rowNumber)');
            $inputPrefixData   = array();
            $inputPrefixData[] = $formModelClassName;
            $inputPrefixData[] = $treeType;
            $inputPrefixData[] = $rowNumber;
            return $inputPrefixData;
        }

        /**
         * Extracts the attribute which is the last part of the nodeId and @returns the attribute string.
         * @see ReportsDefaultController::actionAddAttributeFromTree for an example of where this is called from
         * @param string $nodeIdWithoutTreeType
         * @return string
         */
        public static function resolveAttributeByNodeId($nodeIdWithoutTreeType)
        {
            assert('is_string($nodeIdWithoutTreeType)');
            return $nodeIdWithoutTreeType;
        }

        /**
         * @param Report $report
         * @param string $treeType
         */
        public function __construct(Report $report, $treeType)
        {
            assert('is_string($treeType)');
            $this->report   = $report;
            $this->treeType = $treeType;
        }

        /**
         * @return string
         */
        public function getTreeType()
        {
            return $this->treeType;
        }

        /**
         * @param string $nodeId
         * @return array
         */
        public function getData($nodeId)
        {
            assert('is_string($nodeId)');
            $nodeId                   = $this->resolveNodeIdByRemovingTreeType($nodeId);
            $moduleClassName          = $this->report->getModuleClassName();
            $modelToReportAdapter     = $this->makeModelRelationsAndAttributesToReportAdapter(
                                        $moduleClassName, $moduleClassName::getPrimaryModelName());
            $nodeIdPrefix             = self::resolveNodeIdPrefixByNodeId($nodeId);
            $precedingModel           = null;
            $precedingRelation        = null;
            if($nodeId != 'source')
            {
                self::resolvePrecedingModelRelationAndAdapterByNodeId($nodeId, $modelToReportAdapter, $precedingModel,
                                                                      $precedingRelation);
            }
            else
            {
                $nodeIdPrefix = null;
            }
            if($nodeIdPrefix == null)
            {
                $data                       = array();
                $data[0]                    = array('expanded' => true,
                                                    'text'      => $moduleClassName::getModuleLabelByTypeAndLanguage('Singular'));
            }
            $childrenNodeData               = $this->getChildrenNodeData($modelToReportAdapter, $precedingModel,
                                                                         $precedingRelation, $nodeIdPrefix);
            if(!empty($childrenNodeData) && $nodeIdPrefix == null)
            {
                $data[0]['children'] = $childrenNodeData;
            }
            else
            {
                $data                = $childrenNodeData;
            }
            return $data;
        }

        /**
         * @param $nodeId
         * @return string
         */
        protected function resolveNodeIdByRemovingTreeType($nodeId)
        {
            assert('is_string($nodeId)');
            if($nodeId == 'source')
            {
                return $nodeId;
            }
            $nodeIdParts  = explode($this->treeType . '_', $nodeId);
            return $nodeIdParts[1];
        }

        /**
         * @param ModelRelationsAndAttributesToReportAdapter $modelToReportAdapter
         * @param RedBeanModel $precedingModel
         * @param null|string $precedingRelation
         * @param null|string $nodeIdPrefix
         * @return array
         * @throws NotSupportedException if one of the relations for the selectable data does not have a module class name
         * defined
         */
        protected function getChildrenNodeData(ModelRelationsAndAttributesToReportAdapter $modelToReportAdapter,
                                               RedBeanModel $precedingModel = null,
                                               $precedingRelation = null, $nodeIdPrefix = null)
        {
            $childrenNodeData        = array();
            $selectableRelationsData = $modelToReportAdapter->
                                       getSelectableRelationsData($precedingModel, $precedingRelation);
            $resolvedSelectableRelationsData = $modelToReportAdapter->
                                               getSelectableRelationsDataResolvedForUserAccess(
                                               Yii::app()->user->userModel,
                                               $selectableRelationsData);
            foreach($resolvedSelectableRelationsData as $relation => $relationData)
            {
                $relationModelClassName       = $modelToReportAdapter->getRelationModelClassName($relation);
                $relationModuleClassName      = $relationModelClassName::getModuleClassName();
                if($relationModuleClassName == null)
                {
                    throw new NotSupportedException($relationModelClassName);
                }
                $relationNode                 = array('id'		    => self::makeNodeId($relation, $nodeIdPrefix),
                                                      'text'        => $relationData['label'],
                                                      'expanded'    => false,
                                                      'hasChildren' => true);
                $childrenNodeData[]           = $relationNode;
            }
            $attributesData = $this->getAttributesData($modelToReportAdapter, $precedingModel, $precedingRelation);
            foreach($attributesData as $attribute => $attributeData)
            {
                $attributeNode      = array('id'		   => self::makeNodeId($attribute, $nodeIdPrefix),
                                            'text'         => $attributeData['label'],
                                            'wrapperClass' => 'attribute-to-place');
                $childrenNodeData[] = $attributeNode;
            }
            return $childrenNodeData;
        }

        /**
         * @param ModelRelationsAndAttributesToReportAdapter $modelToReportAdapter
         * @param RedBeanModel $precedingModel
         * @param null|string $precedingRelation
         * @throws NotSupportedException if the treeType is invalid or null
         */
        protected function getAttributesData(ModelRelationsAndAttributesToReportAdapter $modelToReportAdapter,
                                             RedBeanModel $precedingModel = null, $precedingRelation = null)
        {
            if($this->treeType == ComponentForReportForm::TYPE_FILTERS)
            {
                return $modelToReportAdapter->getAttributesForFilters($precedingModel, $precedingRelation);
            }
            elseif($this->treeType == ComponentForReportForm::TYPE_DISPLAY_ATTRIBUTES)
            {
                return $modelToReportAdapter->getAttributesForDisplayAttributes($this->report->getGroupBys(),
                                                                                $precedingModel, $precedingRelation);
            }
            elseif($this->treeType == ComponentForReportForm::TYPE_ORDER_BYS)
            {
                return $modelToReportAdapter->getAttributesForOrderBys($this->report->getGroupBys(),
                                                                       $this->report->getDisplayAttributes(),
                                                                       $precedingModel, $precedingRelation);
            }
            elseif($this->treeType == ComponentForReportForm::TYPE_GROUP_BYS)
            {
                return $modelToReportAdapter->getAttributesForGroupBys($precedingModel, $precedingRelation);
            }
            elseif($this->treeType == ComponentForReportForm::TYPE_DRILL_DOWN_DISPLAY_ATTRIBUTES)
            {
                return $modelToReportAdapter->getForDrillDownAttributes($precedingModel, $precedingRelation);
            }
            else
            {
                throw new NotSupportedException();
            }
        }

        /**
         * @param string $moduleClassName
         * @param string $modelClassName
         * @return ModelRelationsAndAttributesToReportAdapter based object
         */
        protected function makeModelRelationsAndAttributesToReportAdapter($moduleClassName, $modelClassName)
        {
            assert('is_string($moduleClassName)');
            assert('is_string($modelClassName)');
            return ModelRelationsAndAttributesToReportAdapter::make($moduleClassName, $modelClassName,
                                                                    $this->report->getType());
        }

        /**
         * @param string $relation
         * @param null|string  $nodeIdPrefix
         * @return string
         */
        protected function makeNodeId($relation, $nodeIdPrefix = null)
        {
            assert('is_string($relation)');
            assert('$nodeIdPrefix == null || is_string($nodeIdPrefix)');
            $content = null;
            if($nodeIdPrefix != null)
            {
                $content .= $nodeIdPrefix;
            }
            $content .= $relation;
            return $this->treeType . '_' . $content;
        }

        /**
         * @param string $nodeId
         * @return null|string
         */
        protected function resolveNodeIdPrefixByNodeId($nodeId)
        {
            assert('is_string($nodeId)');
            if($nodeId == 'source')
            {
                return null;
            }
            $relations    = explode(FormModelUtil::RELATION_DELIMITER, $nodeId);
            return implode(FormModelUtil::RELATION_DELIMITER, $relations) . FormModelUtil::RELATION_DELIMITER;
        }

        /**
         * @param string $nodeId
         * @param ModelRelationsAndAttributesToReportAdapter $modelToReportAdapter
         * @param RedBeanModel $precedingModel
         * @param string $precedingRelation
         */
        protected function resolvePrecedingModelRelationAndAdapterByNodeId(
                                $nodeId, & $modelToReportAdapter, & $precedingModel, & $precedingRelation)
        {
            assert('$modelToReportAdapter instanceof ModelRelationsAndAttributesToReportAdapter');
            if($nodeId == 'source')
            {
                return;
            }
            $relations    = explode(FormModelUtil::RELATION_DELIMITER, $nodeId);
            $lastRelation = end($relations);
            foreach($relations as $relation)
            {
                $relationModelClassName = $modelToReportAdapter->getRelationModelClassName($relation);
                $precedingRelation      = $relation;
                if($relation != $lastRelation)
                {
                    $precedingModel    = new $relationModelClassName(false);
                }
                elseif(count($relations) == 1)
                {
                    $precedingModel    = $modelToReportAdapter->getModel();
                }
                $modelToReportAdapter  = $this->makeModelRelationsAndAttributesToReportAdapter(
                                         $relationModelClassName::getModuleClassName(), $relationModelClassName);
            }
        }
    }
?>