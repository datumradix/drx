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
     * Helper class for working with comments notifications
     */
    class CommentsNotificationUtil extends NotificationsUtil
    {
        const COMMENT_CREATED_OR_UPDATED         = 'CommentCreatedOrUpdated';

        const COMMENT_DELETED                    = 'CommentDeleted';

        /**
         * Submit comment notification message
         * @param OwnedSecurableItem $model
         * @param $action
         * @param Comment $comment
         * @throws NotFoundException
         */
        public static function submitNotificationMessage(OwnedSecurableItem $model, $action, Comment $comment)
        {
            assert('is_string($action)');
            $relatedUser = null;
            if (isset($comment) && $comment instanceof Comment)
            {
                $relatedUser = $comment->modifiedByUser;
            }

            $message = static::getNotificationMessageByAction($model, $action, $comment);
            $notificationRulesClassName = static::resolveNotificationRulesClassByModelAndAction($model, $action);
            $rule = new $notificationRulesClassName();
            $peopleToSendNotification = static::resolvePeopleToSendNotification($model, $action, $relatedUser);
            foreach ($peopleToSendNotification as $person)
            {
                $rule->addUser($person);
            }
            $rule->setModel($model);
            $rule->setAllowDuplicates(true);
            static::processSubscriberNotification($message, $rule, $action);
        }

        /**
         * Get notification message by action
         * @param OwnedSecurableItem $model
         * @param $action
         * @param Comment|null $comment
         * @return NotificationMessage
         */
        protected static function getNotificationMessageByAction(OwnedSecurableItem $model, $action, Comment $comment = null)
        {
            assert('is_string($action)');
            $relatedUser = null;
            if (isset($comment) && $comment instanceof Comment)
            {
                $relatedUser = $comment->modifiedByUser;
            }
            $message                     = new NotificationMessage();
            $messageContent              = static::getEmailMessageContent($model, $action, $relatedUser);
            $messageContentSecondPart    = static::getEmailMessageContentSecondPart($action, $comment);

            $moduleClassName   = $model::getModuleClassName();
            $moduleId          = $moduleClassName::getDirectoryName();

            $url               = Yii::app()->createAbsoluteUrl($moduleId . '/default/details/', array('id' => $model->id));
            $message->textContent        = $messageContent;
            if ($messageContentSecondPart != null)
            {
                $message->textContent .= "\n" . $messageContentSecondPart;
            }
            $message->textContent       .= "\n" . ZurmoHtml::link(Zurmo::t('Core', 'Click Here'), $url,
                    array('target' => '_blank'));
            $message->htmlContent        = $messageContent;
            if ($messageContentSecondPart != null)
            {
                $message->htmlContent .= "<br/>" . $messageContentSecondPart;
            }
            $message->htmlContent       .= "<br/>" . ZurmoHtml::link(Zurmo::t('Core', 'Click Here'), $url,
                    array('target' => '_blank'));
            return $message;
        }

        /**
         * Resolve and get notifications
         * @param NotificationMessage $message
         * @param $rules
         * @throws NotSupportedException
         * @return Notification
         */
        protected static function resolveAndGetNotifications(NotificationMessage $message, NotificationRules $rules)
        {
            $notifications = array();
            foreach ($rules->getUsers() as $user)
            {
                //todo: !!!process duplication check
                if ($rules->allowDuplicates() || Notification::getCountByTypeAndUser($rules->getType(), $user) == 0)
                {
                    $notification                      = new Notification();
                    $notification->owner               = $user;
                    $notification->type                = $rules->getType();
                    $notification->notificationMessage = $message;
                    $notificationSettingName = static::resolveNotificationSettingNameFromType($rules->getType());
                    if (static::resolveToSaveNotification() &&
                        UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($user, $notificationSettingName, 'inbox'))
                    {
                        $saved = $notification->save();
                        if (!$saved)
                        {
                            throw new NotSupportedException();
                        }
                    }
                    $notifications[] = $notification;
                }
            }
            return $notifications;
        }

        /**
         * Process subscribers notifications
         * @param NotificationMessage $message
         * @param NotificationRules $rule
         * @param $action
         */
        protected static function processSubscriberNotification(NotificationMessage $message, NotificationRules $rule, $action)
        {
            assert('is_string($action)');
            $users = $rule->getUsers();
            //This scenario would be there when there is only one subscriber(owner). In that case users would be zero
            if (count($users) == 0)
            {
                return;
            }
            $notifications = static::resolveAndGetNotifications($message, $rule);
            if (static::resolveShouldSendEmailIfCritical())
            {
                foreach ($notifications as $notification)
                {
                    static::sendNotificationEmail($notification, $rule, $action);
                }
            }
        }

        /**
         * Send email
         * @param Notification $notification
         * @param NotificationRules $rule
         * @param string $action
         */
        protected static function sendNotificationEmail(Notification $notification, NotificationRules $rule, $action)
        {
            assert('is_string($action)');
            $notificationSettingName = static::resolveNotificationSettingNameFromType($rule->getType());
            if ($notification->owner->primaryEmail->emailAddress !== null &&
                UserNotificationUtil::isEnabledByUserAndNotificationNameAndType($notification->owner,
                    $notificationSettingName, 'email'))
            {
                $emailMessage               = static::makeEmailMessage();
                $emailMessage->subject      = static::getEmailSubject($notification, $rule);
                $emailMessage->content      = static::makeEmailContent($notification);
                $emailMessage->sender       = static::makeSender();
                $emailMessage->recipients->add(static::makeRecipient($notification));
                $box                        = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
                $emailMessage->folder       = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_DRAFT);
                try
                {
                    Yii::app()->emailHelper->send($emailMessage);
                }
                catch (CException $e)
                {
                    //Not sure what to do yet when catching an exception here. Currently ignoring gracefully.
                }
            }
        }

        /**
         * Gets email message for the notification
         * @param OwnedSecurableItem $model
         * @param string $action
         * @param User|null $relatedUser
         * @return string
         */
        public static function getEmailMessageContent(OwnedSecurableItem $model, $action, User $relatedUser = null)
        {
            assert('is_string($action)');
            if ($action == self::COMMENT_CREATED_OR_UPDATED)
            {
                return Zurmo::t('CommentsModule', "{user} has commented on the {modelClassName} '{model}':",
                    array('{model}'          => strval($model),
                          '{user}'           => strval($relatedUser),
                          '{modelClassName}' => $model->getModelLabelByTypeAndLanguage(
                              'SingularLowerCase')));
            }
        }

        /**
         * Get comment model content
         * @param $action
         * @param Comment $comment
         * @return string
         */
        public static function getEmailMessageContentSecondPart($action, Comment $comment = null)
        {
            assert('is_string($action)');
            if ($action == self::COMMENT_CREATED_OR_UPDATED)
            {
                return $comment->description;
            }
        }

        /**
         * Resolve the notification rules class name by action name
         * @param $action
         * @return string
         * @throws NotFoundException
         */
        protected static function resolveNotificationRulesClassByModelAndAction(OwnedSecurableItem $model, $action)
        {
            switch ($action)
            {
                case self::COMMENT_CREATED_OR_UPDATED:
                    return get_class($model) . 'CommentNotificationRules';
                    break;
                default:
                    throw new NotFoundException();
            }
        }

        /**
         * Gets notification subscribers
         * @param OwnedSecurableItem $model
         * @param $action
         * @param User|null $relatedUser
         * @return array
         */
        public static function resolvePeopleToSendNotification(OwnedSecurableItem $model, $action, User $relatedUser = null)
        {
            assert('is_string($action)');
            $peopleToSendNotification = array();
            if ($action == self::COMMENT_CREATED_OR_UPDATED)
            {
                $peopleToSendNotification = NotificationSubscriberUtil::getModelSubscribers($model);
                if ($relatedUser != null)
                {
                    foreach ($peopleToSendNotification as $key => $person)
                    {
                        if ($person->getClassId('Item') == $relatedUser->getClassId('Item'))
                        {
                            unset($peopleToSendNotification[$key]);
                        }
                    }
                }
            }
            return $peopleToSendNotification;
        }
    }
?>