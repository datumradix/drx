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
     * Test class used to demonstrate that isModified is returning the correct value when there are custom fields
     *  that have default values.
     * Class OpportunityIsModifiedTest
     */
    class OpportunityIsModifiedTest extends ZurmoBaseTest
    {
        public static function setUpBeforeClass()
        {
            parent::setUpBeforeClass();
            SecurityTestHelper::createSuperAdmin();
            OpportunityTestHelper::createOpportunityStagesIfDoesNotExist();
        }

        public function testIsModifiedIsFalseOnBasicInstantiation()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $opportunity = new Opportunity();
            $this->assertFalse($opportunity->isModified());
        }

        public function testIsModifiedIsFalseWhenCustomDropDownsAreAdded()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $values = array(
                '747',
                'A380',
                'Seaplane',
                'Dive Bomber',
            );
            $labels = array('fr' => array('747 fr', 'A380 fr', 'Seaplane fr', 'Dive Bomber fr'),
                'de' => array('747 de', 'A380 de', 'Seaplane de', 'Dive Bomber de'),
            );
            $airplanesFieldData = CustomFieldData::getByName('Airplanes');
            $airplanesFieldData->serializedData = serialize($values);
            $this->assertTrue($airplanesFieldData->save());

            $attributeForm = new DropDownAttributeForm();
            $attributeForm->attributeName       = 'testAirPlane';
            $attributeForm->attributeLabels  = array(
                'de' => 'Test Airplane 2 de',
                'en' => 'Test Airplane 2 en',
                'es' => 'Test Airplane 2 es',
                'fr' => 'Test Airplane 2 fr',
                'it' => 'Test Airplane 2 it',
            );
            $attributeForm->isAudited             = true;
            $attributeForm->isRequired            = true;
            $attributeForm->defaultValue          = '747';
            $attributeForm->defaultValueOrder     = 1;
            $attributeForm->customFieldDataData   = $values;
            $attributeForm->customFieldDataName   = 'Airplanes';
            $attributeForm->customFieldDataLabels = $labels;

            $modelAttributesAdapterClassName = $attributeForm::getModelAttributeAdapterNameForSavingAttributeFormData();
            $adapter = new $modelAttributesAdapterClassName(new Opportunity());
            try
            {
                $adapter->setAttributeMetadataFromForm($attributeForm);
            }
            catch (FailedDatabaseSchemaChangeException $e)
            {
                echo $e->getMessage();
                $this->fail();
            }
            //Now make sure isModified is still false
            //https://www.pivotaltracker.com/story/show/82699138 - this would fail here and return true;
            $opportunity = new Opportunity();
            $this->assertFalse($opportunity->isModified());
        }

        public function testIsModifiedIsFalseWithCustomTagCloud()
        {
            Yii::app()->user->userModel = User::getByUsername('super');
            $values = array(
                'English',
                'French',
                'Danish',
                'Spanish',
            );
            $labels = array('fr' => array('English fr', 'French fr', 'Danish fr', 'Spanish fr'),
                'de' => array('English de', 'French de', 'Danish de', 'Spanish de'),
            );
            $languageFieldData = CustomFieldData::getByName('Languages');
            $languageFieldData->serializedData = serialize($values);
            $this->assertTrue($languageFieldData->save());

            $attributeForm                   = new TagCloudAttributeForm();
            $attributeForm->attributeName    = 'tagCloud';
            $attributeForm->attributeLabels  = array(
                'de' => 'Test Languages 2 de',
                'en' => 'Test Languages 2 en',
                'es' => 'Test Languages 2 es',
                'fr' => 'Test Languages 2 fr',
                'it' => 'Test Languages 2 it',
            );
            $attributeForm->isAudited             = true;
            $attributeForm->isRequired            = true;
            $attributeForm->defaultValue          = 'Danish';
            $attributeForm->defaultValueOrder     = 1;
            $attributeForm->customFieldDataData   = $values;
            $attributeForm->customFieldDataName   = 'Languages';
            $attributeForm->customFieldDataLabels = $labels;
            $modelAttributesAdapterClassName = $attributeForm::getModelAttributeAdapterNameForSavingAttributeFormData();
            $adapter = new $modelAttributesAdapterClassName(new Opportunity());
            try
            {
                $adapter->setAttributeMetadataFromForm($attributeForm);
            }
            catch (FailedDatabaseSchemaChangeException $e)
            {
                echo $e->getMessage();
                $this->fail();
            }
            //Now make sure isModified is still false
            //https://www.pivotaltracker.com/story/show/82699138 - this would fail here and return true;
            $opportunity = new Opportunity();
            $this->assertFalse($opportunity->isModified());
        }
    }
?>