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
     * Extended class to support saving comments against a related model
     */
    class CommentZurmoControllerUtil extends FileZurmoControllerUtil
    {
        protected $relatedModel;

        protected $relationName;

        /**
         * @param $relatedModel
         * @param string $relationName
         */
        public function __construct($relatedModel, $relationName)
        {
            assert('is_string($relationName)');
            $this->relatedModel = $relatedModel;
            $this->relationName = $relationName;
        }

       /**
         * Override to handle saving the comment against the conversation/mission/social item
         * if it is not already connected.
         * (non-PHPdoc)
         * @see ModelHasRelatedItemsZurmoControllerUtil::afterSetAttributesDuringSave()
         */
        protected function afterSetAttributesDuringSave($model, $explicitReadWriteModelPermissions)
        {
            assert('$model instanceof Item');
            parent::afterSetAttributesDuringSave($model, $explicitReadWriteModelPermissions);
            if ($this->relatedModel->getRelationType($this->relationName) == RedBeanModel::HAS_MANY)
            {
                if (!$this->relatedModel->{$this->relationName}->contains($model))
                {
                    $this->relatedModel->{$this->relationName}->add($model);
                    $saved = $this->relatedModel->save();
                    if (!$saved)
                    {
                        throw new FailedToSaveModelException();
                    }
                }
            }
            else
            {
                //If a comment is connected only HAS_ONE from a related model, then add support for that here.
                throw new NotImplementedException();
            }
        }

        /**
         * Override to handle sending email messages on new comment
         */
        protected function afterSuccessfulSave($model)
        {
            assert('$model instanceof Item');
            parent::afterSuccessfulSave($model);
            $user = Yii::app()->user->userModel;
            if ($this->relatedModel instanceof Conversation)
            {
                $mentionedUsers = CommentsUtil::getMentionedUsersForNotification($model);
                $itemIds = array();
                $conversationPeople = ConversationsUtil::resolvePeopleOnConversation($this->relatedModel);
                foreach ($conversationPeople as $user)
                {
                    $itemIds[] = $user->getClassId('Item');
                }
                foreach ($mentionedUsers as $mentionedUser)
                {
                    $itemIds[] = $mentionedUser->getClassId('Item');
                }
                $itemIdsImploded = array();
                $itemIdsImploded['itemIds'] = implode(',', $itemIds);

                $explicitReadWriteModelPermissions = ExplicitReadWriteModelPermissionsUtil::makeBySecurableItem($this->relatedModel);
                ConversationParticipantsUtil::resolveConversationHasManyParticipantsFromPost($this->relatedModel,
                    $itemIdsImploded,
                    $explicitReadWriteModelPermissions);
                $saved = $this->relatedModel->save();
                if ($saved)
                {
                    ExplicitReadWriteModelPermissionsUtil::
                        resolveExplicitReadWriteModelPermissions($this->relatedModel, $explicitReadWriteModelPermissions);
                    $this->relatedModel->save();
                }
                else
                {
                    throw new FailedToSaveModelException();
                }

                $participants = ConversationsUtil::resolvePeopleToSendNotificationToOnNewComment($this->relatedModel, $user);
                CommentsUtil::sendNotificationOnNewComment($this->relatedModel, $model, $participants);
            }
            elseif ($this->relatedModel instanceof Mission)
            {
                $mentionedUsers = CommentsUtil::getMentionedUsersForNotification($model);
                $participants = MissionsUtil::resolvePeopleToSendNotificationToOnNewComment($this->relatedModel, $user);
                $participants  = array_merge($participants, $mentionedUsers);
                CommentsUtil::sendNotificationOnNewComment($this->relatedModel, $model, $participants);
            }
            elseif ($this->relatedModel instanceof Task)
            {
                $mentionedUsers = CommentsUtil::getMentionedUsersForNotification($model);
                foreach ($mentionedUsers as $user)
                {
                    TasksUtil::processTaskSubscriptionRequest($this->relatedModel->id, $user);
                }

                TasksNotificationUtil::submitTaskNotificationMessage($this->relatedModel,
                                                                    TasksNotificationUtil::TASK_NEW_COMMENT,
                                                                    $model->createdByUser, $model);
                //Log the event
                if ($this->relatedModel->project->id > 0)
                {
                    ProjectsUtil::logAddCommentEvent($this->relatedModel, $model->description);
                }
            }
        }
    }
?>