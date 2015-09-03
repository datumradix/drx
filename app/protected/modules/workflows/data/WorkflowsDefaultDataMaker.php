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
     * Class to make default data that needs to be created upon an installation.
     */
    class WorkflowsDefaultDataMaker extends DefaultDataMaker
    {
        public function make()
        {
            $this->makeSampleOnTimeWorkflow();
            $this->makeSampleOnSaveWorkflow();
        }

        public function makeSampleOnTimeWorkflow()
        {
            Yii::app()->user->userModel    = User::getByUsername('super');

            $action                       = new ActionForWorkflowForm('Contact', Workflow::TYPE_BY_TIME);
            $action->type                 = ActionForWorkflowForm::TYPE_CREATE;
            $action->relation             = 'tasks';
            $action->relationFilter = ActionForWorkflowForm::RELATION_FILTER_ALL;
            $attributes                   = array(
                'name'        => array('shouldSetValue'    => '1',
                                       'type'   => WorkflowActionAttributeForm::TYPE_STATIC,
                                       'value'  => 'Follow up with contact'),
                'owner__User' => array('shouldSetValue'    => '1',
                                       'type'   => UserWorkflowActionAttributeForm::TYPE_DYNAMIC_OWNER_OF_TRIGGERED_MODEL,
                                       'value'  => null),
                'status' => array(     'shouldSetValue'    => '1',
                                       'type'   => WorkflowActionAttributeForm::TYPE_STATIC,
                                       'value'  => Task::STATUS_NEW),
                'permissions' => array('shouldSetValue'    => '1',
                                       'type'   => ExplicitReadWriteModelPermissionsWorkflowActionAttributeForm::TYPE_DYNAMIC_SAME_AS_TRIGGERED_MODEL,
                                       'value'  => null),
                );
            $action->setAttributes(array(ActionForWorkflowForm::ACTION_ATTRIBUTES => $attributes));

            $timeTrigger = new TimeTriggerForWorkflowForm('ContactsModule', 'Contact', Workflow::TYPE_BY_TIME);
            $timeTrigger->durationInterval = 1;
            $timeTrigger->durationType     = TimeDurationUtil::DURATION_TYPE_MONTH;
            $timeTrigger->durationSign     = TimeDurationUtil::DURATION_SIGN_POSITIVE;
            $timeTrigger->attributeIndexOrDerivedType     = 'latestActivityDateTime';
            $timeTrigger->valueType = MixedDateTypesSearchFormAttributeMappingRules::TYPE_IS_TIME_FOR;

            $workflow = new Workflow();
            $workflow->setDescription    ('This will create a task for the Contact owner to follow up with a contact if there has been no activity for 1 month');
            $workflow->setIsActive       (false);
            $workflow->setOrder          (1);
            $workflow->setModuleClassName('ContactsModule');
            $workflow->setName           ('Contact follow up Task');
            $workflow->setTriggerOn      (Workflow::TRIGGER_ON_NEW_AND_EXISTING);
            $workflow->setType           (Workflow::TYPE_BY_TIME);
            $workflow->setTriggersStructure('1');

            $workflow->addAction($action);
            $workflow->setTimeTrigger($timeTrigger);
            $savedWorkflow = new SavedWorkflow();
            SavedWorkflowToWorkflowAdapter::resolveWorkflowToSavedWorkflow($workflow, $savedWorkflow);
            $savedWorkflow->save();
        }

        protected function makeSampleOnSaveWorkflow()
        {
            Yii::app()->user->userModel    = User::getByUsername('super');

            $emailTemplate                  = new EmailTemplate();
            $emailTemplate->type            = EmailTemplate::TYPE_WORKFLOW;
            $emailTemplate->builtType       = EmailTemplate::BUILT_TYPE_BUILDER_TEMPLATE;
            $emailTemplate->subject         = 'We closed a deal';
            $emailTemplate->name            = 'We closed a deal - Sample Email Template';
            $emailTemplate->textContent     = 'Hello!!!
We just closed new deal, please check details: [[MODEL^URL]]
Thanks!';
            $emailTemplate->htmlContent     = '<p>Hello!!!</p>
<p>We just closed new deal, please check details: [[MODEL^URL]]</p>
<p>Thanks!</p>';
            $emailTemplate->modelClassName  = 'Opportunity';
            $emailTemplate->isDraft = false;
            $emailTemplate->save();

            $trigger = new TriggerForWorkflowForm('OpportunitiesModule', 'Opportunity', Workflow::TYPE_ON_SAVE);
            $trigger->attributeIndexOrDerivedType = 'stage';
            $trigger->value                       = 'Closed Won';
            $trigger->operator                    = OperatorRules::TYPE_BECOMES;
            $trigger->relationFilter              = TriggerForWorkflowForm::RELATION_FILTER_ANY;

            $message       = new EmailMessageForWorkflowForm('Opportunity', Workflow::TYPE_ON_SAVE);
            $message->sendAfterDurationInterval = 0;
            $message->sendAfterDurationType     = TimeDurationUtil::DURATION_TYPE_MINUTE;
            $message->emailTemplateId          = $emailTemplate->id;
            $message->sendFromType             = EmailMessageForWorkflowForm::SEND_FROM_TYPE_DEFAULT;
            $recipients = array(array('type' => WorkflowEmailMessageRecipientForm::TYPE_STATIC_ADDRESS,
                                      'audienceType'     => EmailMessageRecipient::TYPE_TO,
                                      'toName'  => 'The Sales Team',
                                      'toAddress' => 'SalesTeam@mycompany.com'));
            $message->setAttributes(array(EmailMessageForWorkflowForm::EMAIL_MESSAGE_RECIPIENTS => $recipients));

            $workflow = new Workflow();
            $workflow->setDescription    ('This will send an email to recipients that you choose when you close a deal!');
            $workflow->setIsActive       (false);
            $workflow->setOrder          (2);
            $workflow->setModuleClassName('OpportunitiesModule');
            $workflow->setName           ('Closed won Opportunity alert');
            $workflow->setTriggerOn      (Workflow::TRIGGER_ON_NEW_AND_EXISTING);
            $workflow->setType           (Workflow::TYPE_ON_SAVE);
            $workflow->setTriggersStructure('1');

            $workflow->addTrigger($trigger);
            $workflow->addEmailMessage($message);
            $savedWorkflow = new SavedWorkflow();
            SavedWorkflowToWorkflowAdapter::resolveWorkflowToSavedWorkflow($workflow, $savedWorkflow);
            $savedWorkflow->save();
        }
    }
?>