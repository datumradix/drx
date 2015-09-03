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

    class WorkflowValidityCheckJobTest extends WorkflowBaseTest
    {
        public function setUp()
        {
            parent::setUp();
            Yii::app()->user->userModel->primaryEmail->emailAddress = 'super@zurmo.com';
        }

        public function atestRunWithValidAndInvalidAction()
        {
            //Create workflow
            $workflow = new Workflow();
            $workflow->setDescription    ('aDescription');
            $workflow->setIsActive       (true);
            $workflow->setOrder          (5);
            $workflow->setModuleClassName('WorkflowsTest2Module');
            $workflow->setName           ('myFirstWorkflow');
            $workflow->setTriggerOn      (Workflow::TRIGGER_ON_NEW_AND_EXISTING);
            $workflow->setType           (Workflow::TYPE_ON_SAVE);
            $workflow->setTriggersStructure('1');
            //Add action that is missing required owner
            $action                       = new ActionForWorkflowForm('WorkflowModelTestItem2', Workflow::TYPE_ON_SAVE);
            $action->type                 = ActionForWorkflowForm::TYPE_CREATE;
            $action->relation             = 'hasMany2';
            $attributes                   = array('string' => array('shouldSetValue'    => '1',
                'type'   => WorkflowActionAttributeForm::TYPE_STATIC,
                'value'  => 'jason'),
                'lastName' => array('shouldSetValue'    => '1',
                    'type'     => WorkflowActionAttributeForm::TYPE_STATIC,
                    'value'    => 'jason'));
            $action->setAttributes(array(ActionForWorkflowForm::ACTION_ATTRIBUTES => $attributes));
            $workflow->addAction($action);
            //Create the saved Workflow
            $savedWorkflow = new SavedWorkflow();
            SavedWorkflowToWorkflowAdapter::resolveWorkflowToSavedWorkflow($workflow, $savedWorkflow);
            $saved = $savedWorkflow->save();
            $this->assertTrue($saved);

            $this->assertEquals(0, Notification::getCount());
            $this->assertEquals(0, EmailMessage::getCount());
            $job = new WorkflowValidityCheckJob();
            $this->assertTrue($job->run());
            $this->assertEquals(1, Notification::getCount());
            $this->assertEquals(1, EmailMessage::getCount());
        }

        public function testRunWithMissingTriggerPicklistValue()
        {
            $this->clearNotificationsWorkflowsAndEmailMessages();
            $this->createStageValues();

            //Create workflow
            $workflow = new Workflow();
            $workflow->setDescription    ('bDescription');
            $workflow->setIsActive       (true);
            $workflow->setOrder          (5);
            $workflow->setModuleClassName('OpportunitiesModule');
            $workflow->setName           ('mySecondWorkflow');
            $workflow->setTriggerOn      (Workflow::TRIGGER_ON_NEW_AND_EXISTING);
            $workflow->setType           (Workflow::TYPE_ON_SAVE);
            $workflow->setTriggersStructure('1');

            $trigger = new TriggerForWorkflowForm('OpportunitiesModule', 'Opportunity', Workflow::TYPE_ON_SAVE);
            $trigger->attributeIndexOrDerivedType = 'stage';
            $trigger->value                       = 'Closed Won';
            $trigger->operator                    = OperatorRules::TYPE_BECOMES;
            $trigger->relationFilter              = TriggerForWorkflowForm::RELATION_FILTER_ANY;
            $workflow->addTrigger($trigger);
            //Create the saved Workflow
            $savedWorkflow = new SavedWorkflow();
            SavedWorkflowToWorkflowAdapter::resolveWorkflowToSavedWorkflow($workflow, $savedWorkflow);
            $saved = $savedWorkflow->save();
            $this->assertTrue($saved);

            $this->assertEquals(0, Notification::getCount());
            $this->assertEquals(0, EmailMessage::getCount());
            $job = new WorkflowValidityCheckJob();
            $this->assertTrue($job->run());
            $this->assertEquals(0, Notification::getCount());
            $this->assertEquals(0, EmailMessage::getCount());

            $this->clearNotificationsWorkflowsAndEmailMessages();
            $workflow = new Workflow();
            $workflow->setDescription    ('cDescription');
            $workflow->setIsActive       (true);
            $workflow->setOrder          (5);
            $workflow->setModuleClassName('OpportunitiesModule');
            $workflow->setName           ('mySecondWorkflow');
            $workflow->setTriggerOn      (Workflow::TRIGGER_ON_NEW_AND_EXISTING);
            $workflow->setType           (Workflow::TYPE_ON_SAVE);
            $workflow->setTriggersStructure('1');

            $trigger = new TriggerForWorkflowForm('OpportunitiesModule', 'Opportunity', Workflow::TYPE_ON_SAVE);
            $trigger->attributeIndexOrDerivedType = 'stage';
            $trigger->value                       = 'Unexisting state';
            $trigger->operator                    = OperatorRules::TYPE_BECOMES;
            $trigger->relationFilter              = TriggerForWorkflowForm::RELATION_FILTER_ANY;
            $workflow->addTrigger($trigger);
            //Create the saved Workflow
            $savedWorkflow = new SavedWorkflow();
            SavedWorkflowToWorkflowAdapter::resolveWorkflowToSavedWorkflow($workflow, $savedWorkflow);
            $saved = $savedWorkflow->save();
            $this->assertTrue($saved);

            $this->assertEquals(0, Notification::getCount());
            $this->assertEquals(0, EmailMessage::getCount());
            $job = new WorkflowValidityCheckJob();
            $this->assertTrue($job->run());
            $this->assertEquals(1, Notification::getCount());
            $this->assertEquals(1, EmailMessage::getCount());
        }

        public function testRunWithMissingTriggerMultiselectPicklistValue()
        {
            $this->clearNotificationsWorkflowsAndEmailMessages();
            $this->createStageValues();

            //Create workflow
            $workflow = new Workflow();
            $workflow->setDescription    ('bDescription');
            $workflow->setIsActive       (true);
            $workflow->setOrder          (5);
            $workflow->setModuleClassName('OpportunitiesModule');
            $workflow->setName           ('mySecondWorkflow');
            $workflow->setTriggerOn      (Workflow::TRIGGER_ON_NEW_AND_EXISTING);
            $workflow->setType           (Workflow::TYPE_ON_SAVE);
            $workflow->setTriggersStructure('1');

            $trigger = new TriggerForWorkflowForm('OpportunitiesModule', 'Opportunity', Workflow::TYPE_ON_SAVE);
            $trigger->attributeIndexOrDerivedType = 'stage';
            $trigger->value                       = 'Closed Won,Negotiating';
            $trigger->operator                    = OperatorRules::TYPE_ONE_OF;
            $trigger->relationFilter              = TriggerForWorkflowForm::RELATION_FILTER_ANY;
            $workflow->addTrigger($trigger);
            //Create the saved Workflow
            $savedWorkflow = new SavedWorkflow();
            SavedWorkflowToWorkflowAdapter::resolveWorkflowToSavedWorkflow($workflow, $savedWorkflow);
            $saved = $savedWorkflow->save();
            $this->assertTrue($saved);

            $this->assertEquals(0, Notification::getCount());
            $this->assertEquals(0, EmailMessage::getCount());
            $job = new WorkflowValidityCheckJob();
            $this->assertTrue($job->run());
            $this->assertEquals(0, Notification::getCount());
            $this->assertEquals(0, EmailMessage::getCount());

            $this->clearNotificationsWorkflowsAndEmailMessages();
            $workflow = new Workflow();
            $workflow->setDescription    ('cDescription');
            $workflow->setIsActive       (true);
            $workflow->setOrder          (5);
            $workflow->setModuleClassName('OpportunitiesModule');
            $workflow->setName           ('mySecondWorkflow');
            $workflow->setTriggerOn      (Workflow::TRIGGER_ON_NEW_AND_EXISTING);
            $workflow->setType           (Workflow::TYPE_ON_SAVE);
            $workflow->setTriggersStructure('1');

            $trigger = new TriggerForWorkflowForm('OpportunitiesModule', 'Opportunity', Workflow::TYPE_ON_SAVE);
            $trigger->attributeIndexOrDerivedType = 'stage';
            $trigger->value                       = 'Closed Won,Unexisting state';
            $trigger->operator                    = OperatorRules::TYPE_BECOMES;
            $trigger->relationFilter              = TriggerForWorkflowForm::RELATION_FILTER_ANY;
            $workflow->addTrigger($trigger);
            //Create the saved Workflow
            $savedWorkflow = new SavedWorkflow();
            SavedWorkflowToWorkflowAdapter::resolveWorkflowToSavedWorkflow($workflow, $savedWorkflow);
            $saved = $savedWorkflow->save();
            $this->assertTrue($saved);

            $this->assertEquals(0, Notification::getCount());
            $this->assertEquals(0, EmailMessage::getCount());
            $job = new WorkflowValidityCheckJob();
            $this->assertTrue($job->run());
            $this->assertEquals(1, Notification::getCount());
            $this->assertEquals(1, EmailMessage::getCount());
        }

        protected function clearNotificationsWorkflowsAndEmailMessages()
        {
            $notifications = Notification::getAll();
            foreach ($notifications  as $notification)
            {
                $notification->delete();
            }

            $emailMessages = EmailMessage::getAll();
            foreach ($emailMessages as $emailMessage)
            {
                $emailMessage->delete();
            }

            $workflows = SavedWorkflow::getAll();
            foreach ($workflows as $workflow)
            {
                $workflow->delete();
            }
        }

        protected function createStageValues()
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
    }
?>