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

    class OpportunitiesModuleTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
        }

        public function testCreateStageValues()
        {
            $stageValues = array(
                'Prospecting',
                'Negotiating',
                'Closed Won',
            );
            $stageFieldData = CustomFieldData::getByName('SalesStages');
            $stageFieldData->serializedData = serialize($stageValues);
            $this->assertTrue($stageFieldData->save());
        }
        
        /**
         * @depends testCreateStageValues
         */
        public function testGetStageToProbabilityMappingData()
        {
            $this->assertEquals(6, count(OpportunitiesModule::getStageToProbabilityMappingData()));
        }

        /**
         * @depends testGetStageToProbabilityMappingData
         */
        public function testGetProbabilityByStageValue()
        {
            $this->assertEquals(10,  OpportunitiesModule::getProbabilityByStageValue ('Prospecting'));
            $this->assertEquals(25,  OpportunitiesModule::getProbabilityByStageValue ('Qualification'));
            $this->assertEquals(50,  OpportunitiesModule::getProbabilityByStageValue ('Negotiating'));
            $this->assertEquals(75,  OpportunitiesModule::getProbabilityByStageValue ('Verbal'));
            $this->assertEquals(100, OpportunitiesModule::getProbabilityByStageValue ('Closed Won'));
            $this->assertEquals(0, OpportunitiesModule::getProbabilityByStageValue ('Closed Lost'));
        }
        
        /**
         * @depends testCreateStageValues
         */
        public function testGetStageToRottingMappingData()
        {
            $this->assertEquals(6, count(OpportunitiesModule::getStageToRottingMappingData()));
        }

        /**
         * @depends testGetStageToRottingMappingData
         */
        public function testGetRottingByStageValue()
        {
            $this->assertEquals(0,  OpportunitiesModule::getRottingByStageValue ('Prospecting'));
            $this->assertEquals(0,  OpportunitiesModule::getRottingByStageValue ('Qualification'));
            $this->assertEquals(0,  OpportunitiesModule::getRottingByStageValue ('Negotiating'));
            $this->assertEquals(0,  OpportunitiesModule::getRottingByStageValue ('Verbal'));
            $this->assertEquals(0, OpportunitiesModule::getRottingByStageValue ('Closed Won'));
            $this->assertEquals(0, OpportunitiesModule::getRottingByStageValue ('Closed Lost'));
        }
        
        /**
         * @depends testCreateStageValues
         */
        public function testIsRottingMappingEnabled()
        {
            $this->assertEquals(false, OpportunitiesModule::isRottingMappingEnabled());
            $metadata = OpportunitiesModule::getMetadata();
            $metadata['global']['opportunityRottingMappingEnabled'] = true;
            OpportunitiesModule::setMetadata($metadata);
            $this->assertEquals(true, OpportunitiesModule::isRottingMappingEnabled());
        }
    }
?>