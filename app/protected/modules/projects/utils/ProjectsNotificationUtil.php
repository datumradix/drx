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
     * Helper class for working with projects notification
     */
    class ProjectsNotificationUtil extends NotificationsUtil
    {
        /**
         * Submit project notification message
         * @param Project $project
         * @param string $action
         * @param Task $task
         * @param null|User $relatedUser, the user associated with the project notification. In case of
         * @param null|Comment $comment
         * comment, it would be the user making the comment
         */
        public static function submitProjectNotificationMessage(Project $project, $action, Task $task = null, 
                                                                User $relatedUser = null, Comment $comment = null)
        {
            assert('is_string($action)');
            $message = static::getNotificationMessageByAction($project, $action, $task, $relatedUser, $comment);
            $notificationRulesClassName = static::resolveNotificationRulesClassByAction($action);
            $rule = new $notificationRulesClassName();
            $peopleToSendNotification = static::resolvePeopleToSendNotification($project, $action);
            foreach ($peopleToSendNotification as $person)
            {
                $rule->addUser($person);
            }
            $rule->setModel($project);
            $rule->setAdditionalModel($task);
            $rule->setAllowDuplicates(true);
            static::processProjectNotification($message, $rule, $action);
        }

        /**
         * Process project notification
         * @param NotificationMessage $message
         * @param ProjectNotificationRules $rule
         * @param string $action
         */
        protected static function processProjectNotification(NotificationMessage $message, ProjectNotificationRules $rule, $action)
        {
            assert('is_string($action)');
            $users = $rule->getUsers();
            //This scenario would be there when there is only one subscriber. In that case users would
            //be zero
            if (count($users) == 0)
            {
                return;
            }
            $notifications = static::resolveAndGetNotifications($users, $rule->getType(), $message, $rule->allowDuplicates());
            if (static::resolveShouldSendEmailIfCritical())
            {
                foreach ($notifications as $notification)
                {
                    static::sendProjectEmail($notification, $rule, $action);
                }
            }
        }

        /**
         * Gets notification message by action
         * @param Project $project
         * @param $action
         * @param Task $task
         * @param User $relatedUser
         * @param Comment $comment
         * @return NotificationMessage
         */
        protected static function getNotificationMessageByAction(Project $project, $action, Task $task = null, 
                                                                 User $relatedUser = null, Comment $comment = null)
        {
            assert('is_string($action)');
            $message                     = new NotificationMessage();
            $messageContent              = static::getEmailMessageContent($project, $action, $task, $relatedUser);
            $messageContentSecondPart    = static::getEmailMessageContentSecondPart($action, $comment);
            $url                         = Yii::app()->createAbsoluteUrl('projects/default/details/',
                                           array('id' => $project->id));
            $message->textContent        = $messageContent;
            if ($messageContentSecondPart != null)
            {
                $message->textContent .= "\n" . $messageContentSecondPart;
            }
            $message->textContent       .= "\n" . ZurmoHtml::link(Zurmo::t('Core', 'Click Here'), $url);
            $message->htmlContent        = $messageContent;
            if ($messageContentSecondPart != null)
            {
                $message->htmlContent .= "<br/>" . $messageContentSecondPart;
            }
            $message->htmlContent       .= "<br/>" . ZurmoHtml::link(Zurmo::t('Core', 'Click Here'), $url);
            return $message;
        }

        /**
         * Gets notification subscribers
         * @param Project $project
         * @param $action
         * @return array
         */
        public static function resolvePeopleToSendNotification(Project $project, $action)
        {
            assert('is_string($action)');
            $peopleToSendNotification = array();
            if ($action == ProjectAuditEvent::PROJECT_CREATED)
            {
                $peopleToSendNotification[] = $project->owner;
            }
            elseif ($action == ProjectAuditEvent::TASK_ADDED)
            {
                $peopleToSendNotification[] = $project->owner;
            }
            elseif ($action == ProjectAuditEvent::COMMENT_ADDED)
            {
                $peopleToSendNotification[] = $project->owner;
            }
            elseif ($action == ProjectAuditEvent::TASK_STATUS_CHANGED)
            {
                $peopleToSendNotification[] = $project->owner;
            }
            elseif ($action == ProjectAuditEvent::PROJECT_ARCHIVED)
            {
                $peopleToSendNotification[] = $project->owner;
            }
            return $peopleToSendNotification;
        }

        /**
         * Gets email subject for the notification
         * @param Project $project
         * @param $action
         * @param Task $task
         * @return string
         */
        public static function getProjectEmailSubject(Project $project, $action, Task $task)
        {
            assert('$project instanceof Project');
            $params = array('{project}'         => strval($project),
                            '{task}'            => strval($task));
            if ($action == ProjectAuditEvent::PROJECT_CREATED)
            {
                return Zurmo::t('ProjectsModule', 'PROJECT: {project}', $params);
            }
            elseif ($action == ProjectAuditEvent::TASK_ADDED)
            {
                return Zurmo::t('ProjectsModule', 'NEW TASK: {task} for PROJECT: {project}', $params);
            }
            elseif ($action == ProjectAuditEvent::COMMENT_ADDED)
            {
                return Zurmo::t('ProjectsModule', 'NEW COMMENT for TASK: {task}, PROJECT: {project}', $params);
            }
            elseif ($action == ProjectAuditEvent::TASK_STATUS_CHANGED)
            {
                return Zurmo::t('ProjectsModule', 'TASK STATUS CHANGE for TASK: {task}, PROJECT: {project}', $params);
            }
            elseif ($action == ProjectAuditEvent::PROJECT_ARCHIVED)
            {
                return Zurmo::t('ProjectsModule', 'PROJECT: {project}', $params);
            }
        }

        /**
         * Gets email message for the notification
         * @param Project $project
         * @param $action
         * @param Task $task
         * @param User $relatedUser
         * @return string
         */
        public static function getEmailMessageContent(Project $project, $action, Task $task = null, User $relatedUser = null)
        {
            assert('is_string($action)');
            if ($action == ProjectAuditEvent::PROJECT_CREATED)
            {
                return Zurmo::t('ProjectsModule', "The project, '{project}', is now owned by you.",
                                               array('{project}'   => strval($project)));
            }
            elseif ($action == ProjectAuditEvent::TASK_ADDED)
            {
                return Zurmo::t('ProjectsModule', "New task, {task}, was created for project, '{project}'. Created by {user}",
                                               array('{task}' => strval($task),
                                                     '{project}' => strval($project),
                                                     '{user}' => strval($relatedUser)));
            }
            elseif ($action == ProjectAuditEvent::COMMENT_ADDED)
            {
                return Zurmo::t('ProjectsModule', "{user} has commented on the project '{project}':",
                                               array('{project}'         => strval($project),
                                                     '{user}' => strval($relatedUser)));
            }
            elseif ($action == ProjectAuditEvent::TASK_STATUS_CHANGED)
            {
                return Zurmo::t('ProjectsModule', "The status has changed for task, {task}, in project, '{project}', updated by {user}.",
                    array('{task}' => strval($task),
                          '{project}'         => strval($project),
                          '{user}' => strval($relatedUser)));
            }
            elseif ($action == ProjectAuditEvent::PROJECT_ARCHIVED)
            {
                return Zurmo::t('ProjectsModule', "The project, '{project}', is now archived.",
                                               array('{project}'   => strval($project)));
            }
        }

        /**
         * @param $action
         * @param Comment $comment
         * @return string
         */
        public static function getEmailMessageContentSecondPart($action, Comment $comment = null)
        {
            assert('is_string($action)');
            if ($action == ProjectAuditEvent::COMMENT_ADDED)
            {
                return strval($comment);
            }
        }

        /**
         * Send task email
         * @param Notification $notification
         * @param ProjectNotificationRules $rule
         * @param string $action
         */
        protected static function sendProjectEmail(Notification $notification, ProjectNotificationRules $rule, $action)
        {
            assert('is_string($action)');
            $notificationSettingName = static::resolveNotificationSettingNameFromType($rule->getType());
            if ($notification->owner->primaryEmail->emailAddress !== null &&
                UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($notification->owner, 
                                                                                $notificationSettingName, 'email'))
            {
                $emailMessage               = static::makeEmailMessage($rule, $action);
                $emailMessage->content      = static::makeEmailContent($notification);
                $emailMessage->sender       = static::makeSender();
                $emailMessage->recipients->add(static::makeRecipient($notification));
                $box                        = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
                $emailMessage->folder       = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_DRAFT);
                try
                {
                    Yii::app()->emailHelper->sendImmediately($emailMessage);
                }
                catch (CException $e)
                {
                    //Not sure what to do yet when catching an exception here. Currently ignoring gracefully.
                }
            }
        }

        /**
         * 
         * @param ProjectNotificationRules $rule
         * @param string $action
         * @return EmailMessage
         */
        protected static function makeEmailMessage(ProjectNotificationRules $rule, $action)
        {
            assert('is_string($action)');
            $emailMessage               = new EmailMessage();
            $emailMessage->owner        = Yii::app()->user->userModel;
            $project                    = $rule->getModel();
            $task                       = $rule->getAdditionalModel();
            $emailMessage->subject      = static::getProjectEmailSubject($project, $action, $task);
            return $emailMessage;
        }

        /**
         * @param Notification $notification
         * @return EmailMessageContent
         */
        protected static function makeEmailContent(Notification $notification)
        {
            $emailContent               = new EmailMessageContent();
            $emailContent->textContent  = EmailNotificationUtil::
                                            resolveNotificationTextTemplate(
                                            $notification->notificationMessage->textContent);
            $emailContent->htmlContent  = EmailNotificationUtil::
                                            resolveNotificationHtmlTemplate(
                                            $notification->notificationMessage->htmlContent);
            return $emailContent;
        }

        /**
         * @return EmailMessageSender
         */
        protected static function makeSender()
        {
            $userToSendMessagesFrom     = BaseControlUserConfigUtil::getUserToRunAs();
            $sender                     = new EmailMessageSender();
            $sender->fromAddress        = Yii::app()->emailHelper->resolveFromAddressByUser($userToSendMessagesFrom);
            $sender->fromName           = strval($userToSendMessagesFrom);
            return $sender;
        }

        /**
         * @param Notification $notification
         * @return EmailMessageRecipient
         */
        protected static function makeRecipient(Notification $notification)
        {
            $recipient                  = new EmailMessageRecipient();
            $recipient->toAddress       = $notification->owner->primaryEmail->emailAddress;
            $recipient->toName          = strval($notification->owner);
            $recipient->type            = EmailMessageRecipient::TYPE_TO;
            $recipient->personsOrAccounts->add($notification->owner);
            return $recipient;
        }

        /**
         * Resolve to save notification
         * @return bool
         */
        protected static function resolveToSaveNotification()
        {
            return true;
        }
        
        /**
         * Resolve the notification rules class name by action name
         * @param string $action
         * @return string
         */
        protected static function resolveNotificationRulesClassByAction($action)
        {
            assert('is_string($action)');
            switch ($action)
            {
                case ProjectAuditEvent::PROJECT_CREATED:
                    return 'NewProjectNotificationRules';
                    break;
                case ProjectAuditEvent::TASK_ADDED:
                    return 'ProjectTaskAddedNotificationRules';
                    break;
                case ProjectAuditEvent::COMMENT_ADDED:
                    return 'ProjectTaskNewCommentNotificationRules';
                    break;
                case ProjectAuditEvent::TASK_STATUS_CHANGED:
                    return 'ProjectTaskStatusChangeNotificationRules';
                    break;
                case ProjectAuditEvent::PROJECT_ARCHIVED:
                    return 'ArchivedProjectNotificationRules';
                    break;
                default:
                    throw new NotFoundException();
            }
        }
    }
?>