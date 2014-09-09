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

    class ModelJoinBuilderUtilTest extends BaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            static::buildRelationModels();
        }

        public static function getDependentTestModelClassNames()
        {
            return array('TestHasManyAndHasOneSide', 'TestHasManyBelongsToSide', 'TestHasOneBelongsToSide');
        }

        public function testGettingValidJoinForHasManyBelongsTo()
        {
            $searchAttributeData = array();
            $searchAttributeData['clauses'] = array(
                1 => array(
                    'attributeName'        => 'name',
                    'operatorType'         => 'equals',
                    'value'                => 'Parent',
                ),
                2 => array(
                    'attributeName'        => 'testHasManyBelongsToSide',
                    'relatedAttributeName' => 'hasManyBelongsToField',
                    'operatorType'         => 'equals',
                    'value'                => 'hasManyBelongsTo1'
                )
            );
            $searchAttributeData['structure'] = '1 and 2';
            $joinTablesAdapter   = new RedBeanModelJoinTablesQueryAdapter('TestHasManyAndHasOneSide');

            $quote         = DatabaseCompatibilityUtil::getQuote();
            $where         = ModelDataProviderUtil::makeWhere('TestHasManyAndHasOneSide', $searchAttributeData, $joinTablesAdapter);
            echo $where;
            //$compareWhere  = "({$quote}analias{$quote}.{$quote}imember{$quote} = 'somevalue1')";
            //$this->assertEquals($compareWhere, $where);
        }

        protected static function buildRelationModels()
        {
            $modelParent                                        = new TestHasManyAndHasOneSide();
            $modelParent->name                                  = "Parent";
            $modelParent->hasManyAndHasOneField                 = "hasManyAndHasOne";
            $modelHasManyBelongsTo1                             = new TestHasManyBelongsToSide();
            $modelHasManyBelongsTo1->name                       = "ChildMany1";
            $modelHasManyBelongsTo1->hasManyBelongsToField      = "hasManyBelongsTo1";
            $modelHasManyBelongsTo2                             = new TestHasManyBelongsToSide();
            $modelHasManyBelongsTo2->name                       = "ChildMany2";
            $modelHasManyBelongsTo2->hasManyBelongsToField      = "hasManyBelongsTo2";
            $modelHasOneBelongsTo                               = new TestHasOneBelongsToSide();
            $modelHasOneBelongsTo->name                         = "ChildOne";
            $modelHasOneBelongsTo->hasOneBelongsToField         = "hasOneBelongsTo";

            $modelParent->testHasManyBelongsToSide->add($modelHasManyBelongsTo1);
            $modelParent->testHasManyBelongsToSide->add($modelHasManyBelongsTo2);
            $modelParent->testHasOneBelongsToSide = $modelHasOneBelongsTo;

            $saved = $modelParent->save();
            assert('$saved');
        }
    }
?>