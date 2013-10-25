<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2013 Zurmo Inc.
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
     * "Copyright Zurmo Inc. 2013. All rights reserved".
     ********************************************************************************/

    class TasksDefaultController extends ActivityModelsDefaultController
    {
        public function actionDetails($id, $redirectUrl = null)
        {
            $task = static::getModelAndCatchNotFoundAndDisplayError('Task', intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($task);
            if($task->project->id > 0)
            {
                $this->redirect(Yii::app()->createUrl('projects/default/details',
                                                      array('id' => $task->project->id, 'openToTaskId' => $task->id)));
            }
            elseif($task->activityItems->count() > 0)
            {
                try
                {
                    $castedDownModel = TasksUtil::castDownActivityItem($task->activityItems[0]);
                    $moduleClassName = StateMetadataAdapter::resolveModuleClassNameByModel($castedDownModel);
                    $this->redirect(Yii::app()->createUrl($moduleClassName::getDirectoryName() . '/default/details',
                        array('id' => $castedDownModel->id, 'kanbanBoard' => true, 'openToTaskId' => $task->id)));
                }
                catch (NotFoundException $e)
                {
                    //Something is missing or deleted. Fallback to home page
                    $this->redirect(Yii::app()->createUrl('home/default/index'));
                }
            }
            else
            {
                //todo: redirect to task list view, and open modal details, once we have a task details view
                $this->redirect(Yii::app()->createUrl('home/default/index'));
            }
        }

        public function actionEdit($id, $redirectUrl = null)
        {
            $task = Task::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($task);
            if($task->project->id > 0)
            {
                $this->redirect(Yii::app()->createUrl('projects/default/details',
                                                      array('id' => $task->project->id, 'openToTaskId' => $task->id)));
            }
            elseif($task->activityItems->count() > 0)
            {
                try
                {
                    $castedDownModel = TasksUtil::castDownActivityItem($task->activityItems[0]);
                    $moduleClassName = StateMetadataAdapter::resolveModuleClassNameByModel($castedDownModel);
                    $this->redirect(Yii::app()->createUrl($moduleClassName::getDirectoryName() . '/default/details',
                        array('id' => $castedDownModel->id, 'kanbanBoard' => true, 'openToTaskId' => $task->id)));
                }
                catch (NotFoundException $e)
                {
                    //Something is missing or deleted. Fallback to home page
                    $this->redirect(Yii::app()->createUrl('home/default/index'));
                }
            }
            else
            {
                //todo: redirect to task list view, and open modal details, once we have a task details view
                $this->redirect(Yii::app()->createUrl('home/default/index'));
            }
        }

        /**
         * Close task
         * @param $id
         * @throws NotSupportedException
         */
        public function actionCloseTask($id)
        {
            $task                    = Task::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($task);
            $task->completedDateTime = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
            $task->completed         = true;
            $saved                   = $task->save();
            if (!$saved)
            {
                throw new NotSupportedException();
            }
        }

        /**
         * Create comment via ajax for task
         * @param type $id
         * @param string $uniquePageId
         */
        public function actionInlineCreateCommentFromAjax($id, $uniquePageId)
        {
            $comment       = new Comment();
            $redirectUrl   = Yii::app()->createUrl('/tasks/default/inlineCreateCommentFromAjax',
                                                    array('id'           => $id,
                                                          'uniquePageId' => $uniquePageId));
            $urlParameters = array('relatedModelId'           => (int)$id,
                                   'relatedModelClassName'    => 'Task',
                                   'relatedModelRelationName' => 'comments',
                                   'redirectUrl'              => $redirectUrl); //After save, the url to go to.
            $uniquePageId  = 'CommentInlineEditForModelView';
            echo             ZurmoHtml::tag('h2', array(), Zurmo::t('CovnersationsModule', 'Add Comment'));
            $inlineView    = new CommentInlineEditView($comment, 'default', 'comments', 'inlineCreateSave',
                                                       $urlParameters, $uniquePageId);
            $view          = new AjaxPageView($inlineView);
            echo $view->render();
        }

        /**
         * Update owner or requested by user for task
         * @param int $id
         * @param string $attribute
         * @param int $userId
         */
        public function actionUpdateRelatedUsersViaAjax($id, $attribute, $userId)
        {
            $task = Task::getById(intval($id));
            $user = User::getById(intval($userId));
            if($attribute == 'owner')
            {
                  $task->owner = $user;
                  $task->save();
                  TasksUtil::sendNotificationOnTaskUpdate($task, Zurmo::t('TasksModule',
                                                          'The owner for the task #' . $task->id .
                                                          ' is updated to ' . $user->getFullName()),
                                                          array($user)
                                                          );

            }
            elseif($attribute == 'requestedByUser')
            {
                  $originalRequestedByUser = $task->requestedByUser;
                  if($user != $originalRequestedByUser)
                  {
                      $task->requestedByUser = $user;
                      $task->save();
                      $explicitReadWriteModelPermissions = ExplicitReadWriteModelPermissionsUtil::makeBySecurableItem($task);
                      TasksUtil::resolveExplicitPermissionsForRequestedByUser($task, $originalRequestedByUser,
                                                                               $task->requestedByUser,
                                                                               $explicitReadWriteModelPermissions);
                  }
            }
            else
            {
                throw new NotSupportedException();
            }
            echo $this->getPermissionContent($task);
        }

        /**
         * Update due data time using ajas
         * @param int $id
         * @param int $dateTime
         */
        public function actionUpdateDueDateTimeViaAjax($id, $dateTime)
        {
            $task         = Task::getById(intval($id));
            $dateTime     = strtotime($dateTime);
            $dueDateTime  = DateTimeUtil::convertTimestampToDbFormatDateTime($dateTime);
            $task->dueDateTime = $dueDateTime;
            $task->save();
            TasksUtil::sendNotificationOnTaskUpdate($task, Zurmo::t('TasksModule',
                                                                    'The due date for task #' . $task->id . ' is updated'),
                                                                    array(Yii::app()->user->userModel));
        }

        /**
         * Add subscriber for task
         * @param int $id
         */
        public function actionAddSubscriber($id)
        {
            $task    = $this->processSubscriptionRequest($id);
            $content = TasksUtil::getTaskSubscriberData($task);
            echo $content;
        }

        /**
         * Remove subscriber for task
         * @param int $id
         */
        public function actionRemoveSubscriber($id)
        {
            $task    = $this->processUnsubscriptionRequest($id);
            $content = TasksUtil::getTaskSubscriberData($task);
            if($content == null)
            {
                echo "";
            }
            else
            {
                echo $content;
            }
        }

        /**
         * Add kanban subscriber
         * @param string $id
         */
        public function actionAddKanbanSubscriber($id)
        {
            $this->processSubscriptionRequest($id);
        }

        /**
         * Unsubscribe the user from the task
         * @param string $id
         */
        public function actionRemoveKanbanSubscriber($id)
        {
            $this->processUnsubscriptionRequest($id);
        }

        /**
         * Gets the permission content
         * @param RedBeanModel $model
         * @return string
         */
        protected function getPermissionContent($model)
        {
            $ownedSecurableItemDetailsContent   = OwnedSecurableItemDetailsViewUtil::renderAfterFormLayoutForDetailsContent(
                                                                                                        $model,
                                                                                                        null);
            return $ownedSecurableItemDetailsContent;
        }

        /**
         * Create task from related view
         * @param null $relationAttributeName
         * @param null $relationModelId
         * @param null $relationModuleId
         */
        public function actionModalCreateFromRelation($relationAttributeName = null, $relationModelId = null,
                                                      $relationModuleId = null)
        {
            $task  = new Task();
            $task  = $this->resolveNewModelByRelationInformation($task, $relationAttributeName,
                     (int)$relationModelId, $relationModuleId);
            $this->processTaskEdit($task);
        }

        /**
         * Create task from top menu
         */
        public function actionModalCreate()
        {
            $task             = new Task();
            $this->processTaskEdit($task);
        }

        /**
         * Saves task in the modal view
         * @param string $relationAttributeName
         * @param string $relationModelId
         * @param string $relationModuleId
         * @param string $portletId
         * @param string $uniqueLayoutId
         */
        public function actionModalSaveFromRelation($relationAttributeName, $relationModelId, $relationModuleId, $id = null)
        {
            if($id == null)
            {
                $task  = new Task();
                TasksUtil::setDefaultValuesForTask($task);
                $task  = $this->resolveNewModelByRelationInformation( $task, $relationAttributeName,
                                                                             (int)$relationModelId,
                                                                             $relationModuleId);
            }
            else
            {
                $task   = Task::getById(intval($id));
            }
            $task       = $this->attemptToSaveModelFromPost($task, null, false);
            //Log event for project audit
            if($relationAttributeName == 'project')
            {
                ProjectsUtil::logAddTaskEvent($task);
            }
            $this->actionModalDetails($task->id);
        }

        /**
         * Saves task in the modal view
         */
        public function actionModalSave($id = null)
        {
            if($id == null)
            {
                $task = new Task();
                TasksUtil::setDefaultValuesForTask($task);
            }
            else
            {
                $task = Task::getById(intval($id));
            }
            $this->attemptToValidateAndSaveFromModalDetails($task);
            $task = $this->attemptToSaveModelFromPost($task, null, false);
            $this->processModalDetails($task);
        }

        /**
         * Copy task
         * @param string $id
         */
        public function actionModalCopy($id)
        {
            $copyToTask   = new Task();
            if (!isset($_POST['Task']))
            {
                $task = Task::getById((int)$id);
                ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($task);
                ActivityCopyModelUtil::copy($task, $copyToTask);
            }
            $this->processTaskEdit($copyToTask);
        }

        /**
         * Loads modal view from related view
         * @param string $id
         */
        public function actionModalDetails($id)
        {
            $task = Task::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($task);
            $this->attemptToValidateAndSaveFromModalDetails($task);
            $this->processModalDetails($task);
        }

        protected function processModalDetails(Task $task)
        {
            TasksUtil::markUserHasReadLatest($task, Yii::app()->user->userModel);
            echo ModalEditAndDetailsControllerUtil::setAjaxModeAndRenderModalDetailsView($this, 'TaskModalDetailsView',
                $task,
                'Details');
        }

        /**
         * Edit task from related view
         * @param string $id
         */
        public function actionModalEdit($id)
        {
            $task = Task::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($task);
            $this->processTaskEdit($task);
        }

        /**
         * Process Task Edit
         * @param Task $task
         */
        protected function processTaskEdit(Task $task)
        {
            if (RightsUtil::canUserAccessModule('TasksModule', Yii::app()->user->userModel))
            {
                if (isset($_POST['ajax']) && $_POST['ajax'] == 'task-modal-edit-form')
                {
                    $controllerUtil   = static::getZurmoControllerUtil();
                    $controllerUtil->validateAjaxFromPost($task, 'Task');
                    Yii::app()->getClientScript()->setToAjaxMode();
                    Yii::app()->end(0, false);
                }
                else
                {
                    echo ModalEditAndDetailsControllerUtil::setAjaxModeAndRenderModalEditView($this,
                                                                                            'TaskModalEditView',
                                                                                            $task);
                }
            }
        }

        /**
         * Should support in addition to custom field as well
         * @param string $id
         * @param string $attribute
         * @param string $value
         * @throws FailedToSaveModelException
         */
        public function actionUpdateAttributeValue($id, $attribute, $value)
        {
            $modelClassName = $this->getModule()->getPrimaryModelName();
            $model          = $modelClassName::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($model);
            $model->{$attribute}->value = $value;
            $saved                      = $model->save();
            if (!$saved)
            {
                throw new FailedToSaveModelException();
            }
        }

        /**
         * Update status for the task when dragging in the kanban view
         */
        public function actionUpdateStatusOnDragInKanbanView()
        {
            $getData = GetUtil::getData();
            $counter = 1;
            $response = array();
            if(count($getData['items']) > 0)
            {
                foreach($getData['items'] as $taskId)
                {
                    if($taskId != '')
                    {
                        $task               = Task::getById(intval($taskId));
                        $kanbanItem         = KanbanItem::getByTask(intval($taskId));
                        //if kanban type is completed
                        if($getData['type'] == KanbanItem::TYPE_COMPLETED)
                        {
                            $this->actionUpdateStatusInKanbanView(Task::STATUS_COMPLETED, $taskId);
                            $response['button'] = '';
                            $response['status'] = Task::getStatusDisplayName($task->status);
                        }
                        else
                        {
                            //When in the same column
                            if($getData['type'] == $kanbanItem->type)
                            {
                                $kanbanItem->sortOrder = $counter;
                            }
                            else
                            {
                                //This would be the one which is dragged across column
                                $kanbanItem->sortOrder = $counter;
                                $kanbanItem->type      = $getData['type'];
                                $targetStatus = TasksUtil::getDefaultTaskStatusForKanbanItemType($getData['type']);
                                $this->processStatusUpdateViaAjax($taskId, $targetStatus, false);
                                $content = TasksUtil::resolveActionButtonForTaskByStatus($targetStatus,
                                                                                        $this->getId(),
                                                                                        $this->getModule()->getId(),
                                                                                        $taskId);
                                $response['button'] = $content;
                                $response['status'] = Task::getStatusDisplayName($task->status);
                            }
                            $kanbanItem->save();
                            $counter++;
                        }
                    }
                }
            }
            echo CJSON::encode($response);
        }

        /**
        * Update task status in kanban view
        * @param int $targetStatus
        * @param int $taskId
        */
        public function actionUpdateStatusInKanbanView($targetStatus, $taskId)
        {
           $this->processKanbanTypeUpdate($targetStatus, $taskId);
           //Run update queries for update task staus and update type and sort order in kanban column
           $this->processStatusUpdateViaAjax($taskId, $targetStatus, false);
        }

        /**
         * Process kanban type update
         * @param string $targetStatus
         * @param string $taskId
         */
        protected function processKanbanTypeUpdate($targetStatus, $taskId)
        {
           assert('is_string($targetStatus)');
           assert('is_string($taskId)');
           $targetKanbanType = TasksUtil::resolveKanbanItemTypeForTaskStatus(intval($targetStatus));
           $sourceKanbanType = TasksUtil::resolveKanbanItemTypeForTask(intval($taskId));
           if($sourceKanbanType != $targetKanbanType)
           {
              $sortOrder             = KanbanItem::getMaximumSortOrderByType($targetKanbanType);
              $kanbanItem            = KanbanItem::getByTask(intval($taskId));
              if($kanbanItem != null)
              {
                  $kanbanItem->sortOrder = $sortOrder;
                  $kanbanItem->type      = $targetKanbanType;
                  $kanbanItem->save();
              }
           }
        }

        /**
         * Process status update via ajax
         * @param int $id
         * @param int $status
         * @param bool $showCompletionDate whether to show completion date
         */
        protected function processStatusUpdateViaAjax($id, $status, $showCompletionDate = true)
        {
            $task          = Task::getById(intval($id));
            $currentStatus = $task->status;
            $task->status = intval($status);
            if(intval($status) == Task::STATUS_COMPLETED)
            {
                foreach ($task->checkListItems as $checkItem)
                {
                    $checkItem->completed = true;
                    $checkItem->unrestrictedSave();
                }
                $task->status            = Task::STATUS_COMPLETED;
                $task->completedDateTime = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
                $task->completed         = true;
                $task->save();
                if($showCompletionDate)
                {
                    echo '<p>' . Zurmo::t('TasksModule', 'Completed On') . ': ' .
                                 DateTimeUtil::convertDbFormattedDateTimeToLocaleFormattedDisplay($task->completedDateTime) . '</p>';
                }
            }
            else
            {
                $task->completedDateTime = null;
                $task->completed         = false;
                $task->save();
            }
            ProjectsUtil::logTaskStatusChangeEvent($task,
                                                   Task::getStatusDisplayName(intval($currentStatus)),
                                                   Task::getStatusDisplayName(intval($status)));
            TasksUtil::sendNotificationOnTaskUpdate($task,
                                                    Zurmo::t('TasksModule', 'The status for the task #' . $task->id .
                                                                            ' has been updated to ' .
                                                                            Task::getStatusDisplayName(intval($status))),
                                                    array(Yii::app()->user->userModel)
                                                    );
        }

        /**
         * Process subscription request for task
         * @param int $id
         */
        protected function processSubscriptionRequest($id)
        {
            $task = Task::getById(intval($id));
            $user = Yii::app()->user->userModel;
            $notificationSubscriber = new NotificationSubscriber();
            $notificationSubscriber->person = $user;
            $notificationSubscriber->hasReadLatest = false;
            $task->notificationSubscribers->add($notificationSubscriber);
            $task->save();
            TasksUtil::sendNotificationOnTaskUpdate($task,
                                                    Zurmo::t('TasksModule', $user->getFullName() .
                                                    ' has subscribed for the task #' . $task->id),
                                                    array($user));
            return $task;
        }

        /**
         * Process unsubscription request for task
         * @param int $id
         */
        protected function processUnsubscriptionRequest($id)
        {
            $task = Task::getById(intval($id));
            $user = Yii::app()->user->userModel;
            foreach($task->notificationSubscribers as $notificationSubscriber)
            {
                if($notificationSubscriber->person->getClassId('Item') == $user->getClassId('Item'))
                {
                    $task->notificationSubscribers->remove($notificationSubscriber);
                    break;
                }
            }
            $task->save();
            TasksUtil::sendNotificationOnTaskUpdate($task,
                                                    Zurmo::t('TasksModule', $user->getFullName() .
                                                    ' has unsubscribed from the task #' . $task->id),
                                                    array($user));
            return $task;
        }

        /**
         * Update description
         * @param int $id
         */
        public function actionUpdateDescriptionViaAjax()
        {
            $getData = GetUtil::getData();
            $descriptionId = $getData['id'];
            $descriptionFieldArray = explode('_' , $descriptionId);
            $model   = Task::getById(intval($descriptionFieldArray[1]));
            ControllerSecurityUtil::resolveAccessCanCurrentUserWriteModel($model);
            $model->description = $getData['update_value'];
            if($model->save())
            {
                if($model->description != '')
                {
                    echo $model->description;
                }
                else
                {
                    echo Zurmo::t('TasksModule', 'Click here to enter description');
                }
            }
            else
            {
                throw new FailedToSaveModelException();
            }
        }

        /**
         * Gets zurmo controller util for task
         */
        protected static function getZurmoControllerUtil()
        {
            return new TaskZurmoControllerUtil('activityItems', 'ActivityItemForm');
        }

        /**
         * @param $id
         * @param null $redirectUrl
         * @throws FailedToDeleteModelException
         */
        public function actionDelete($id, $redirectUrl = null)
        {
            $task              = Task::getById(intval($id));
            ControllerSecurityUtil::resolveAccessCanCurrentUserDeleteModel($task);
            if(!$task->delete())
            {
                throw new FailedToDeleteModelException();
            }
        }

        protected function attemptToValidateAndSaveFromModalDetails(Task $task)
        {
            if (isset($_POST['ajax']) &&
                ($_POST['ajax'] == 'task-left-column-form-data' || $_POST['ajax'] == 'task-right-column-form-data'))
            {
                $this->attemptToSaveModelFromPost($task, null, false);
                $errorData        = ZurmoActiveForm::makeErrorsDataAndResolveForOwnedModelAttributes($task);
                echo CJSON::encode($errorData);
                Yii::app()->end(0, false);
            }
        }
    }
?>