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
     * Helper class to work with notification subscribers.
     */
    class NotificationSubscriberUtil
    {
        /**
         * Get task subscriber data
         * @param OwnedSecurableItem $model
         * @return string
         */
        public static function getSubscriberData(OwnedSecurableItem $model)
        {
            $content = null;
            $modelDerivationPathToItem = RuntimeUtil::getModelDerivationPathToItem('User');
            $alreadySubscribedUsers = array();
            foreach ($model->notificationSubscribers as $subscriber)
            {
                $user     = $subscriber->person->castDown(array($modelDerivationPathToItem));
                //Take care of duplicates if any
                if (!in_array($user->id, $alreadySubscribedUsers))
                {
                    $content .= static::renderSubscriberImageAndLinkContent($user);
                    $alreadySubscribedUsers[] = $user->id;
                }
            }
            return $content;
        }

        /**
         * Renders subscriber image and link content
         * @param User $user
         * @param int $imageSize
         * @param string $class
         * @return string
         */
        public static function renderSubscriberImageAndLinkContent(User $user, $imageSize = 36, $class = null)
        {
            assert('is_int($imageSize)');
            assert('is_string($class) || $class === null');
            $htmlOptions = array('title' => strval($user));
            if ($class != null)
            {
                $htmlOptions['class'] = $class;
            }
            $userUrl     = Yii::app()->createUrl('/users/default/details', array('id' => $user->id));
            return ZurmoHtml::link($user->getAvatarImage($imageSize), $userUrl, $htmlOptions);
        }

        /**
         * Gets model participant
         * @param OwnedSecurableItem $model
         * @return array
         */
        public static function getModelSubscribers(OwnedSecurableItem $model)
        {
            $subscribers = array();
            $modelDerivationPathToItem = RuntimeUtil::getModelDerivationPathToItem('User');
            foreach ($model->notificationSubscribers as $subscriber)
            {
                $subscribers[] = $subscriber->person->castDown(array($modelDerivationPathToItem));
            }
            return $subscribers;
        }

        /**
         * Given a model and a user, mark that the user has read or not read the latest changes as a model
         * owner, requested by user or subscriber
         * @param OwnedSecurableItem $model
         * @param User $user
         * @param Boolean $hasReadLatest
         */
        public static function markUserHasReadLatest(OwnedSecurableItem $model, User $user, $hasReadLatest = true)
        {
            assert('$model->id > 0');
            assert('$user->id > 0');
            assert('is_bool($hasReadLatest)');
            $save = false;
            foreach ($model->notificationSubscribers as $position => $subscriber)
            {
                if ($subscriber->person->getClassId('Item') ==
                    $user->getClassId('Item') && $subscriber->hasReadLatest != $hasReadLatest)
                {
                    $model->notificationSubscribers[$position]->hasReadLatest = $hasReadLatest;
                    $save                                                    = true;
                }
            }

            if ($save)
            {
                $model->save();
            }
        }

        /**
         * Resolves Subscribe Url
         * @param int $taskId
         * @return string
         * @todo Check if we remove it
         */
        public static function NotUsedresolveSubscribeUrl(OwnedSecurableItem $model)
        {
            $moduleClassName   = $model::getModuleClassName();
            $moduleId        = $moduleClassName::getDirectoryName();
            return Yii::app()->createUrl($moduleId . '/default/addSubscriber', array('id' => $model->id));
        }

        /**
         * Resolve subscriber ajax options
         * @return array
         * @todo Check if we remove it
         */
        public static function NotUsedresolveSubscriberAjaxOptions()
        {
            return array(
                'type'     => 'GET',
                'dataType' => 'html',
                'data'     => array(),
                'success'  => 'function(data)
                               {
                                 $("#subscribe-task-link").hide();
                                 $("#subscriberList").replaceWith(data);
                               }'
            );
        }

        /**
         * Register subscription script
         * @param string $modelClassName
         * @param OwnedSecurableItem $model
         */
        public static function registerSubscriptionScript($modelClassName, $model = null)
        {
            $title  = Zurmo::t('Core', 'Unsubscribe');
            $unsubscribeLink = ZurmoHtml::tag('i', array('class' => 'icon-unsubscribe', 'title' => $title), '');

            $moduleClassName   = $modelClassName::getModuleClassName();
            $moduleId        = $moduleClassName::getDirectoryName();

            if ($model == null)
            {
                $url     = Yii::app()->createUrl($moduleId . '/default/addKanbanSubscriber');
                $script  = self::getKanbanSubscriptionScript($url, 'subscribe-task-link', 'unsubscribe-task-link', $unsubscribeLink);
                Yii::app()->clientScript->registerScript('kanban-subscribe-task-link-script', $script);
            }
            else
            {
                $url     = Yii::app()->createUrl($moduleId . 'default/addSubscriber', array('id' => $model->id));
                $script  = self::getDetailSubscriptionScript($url, 'detail-subscribe-task-link', 'detail-unsubscribe-task-link', $unsubscribeLink, $model->id);
                Yii::app()->clientScript->registerScript('detail-subscribe-task-link-script', $script);
            }
        }

        /**
         * Register unsubscription script
         * @param string $modelClassName
         * @param OwnedSecurableItem $model
         */
        public static function registerUnsubscriptionScript($modelClassName, $model = null)
        {
            $title  = Zurmo::t('Core', 'Subscribe');
            $subscribeLink = ZurmoHtml::tag('i', array('class' => 'icon-subscribe', 'title' => $title), '');

            $moduleClassName   = $modelClassName::getModuleClassName();
            $moduleId        = $moduleClassName::getDirectoryName();

            if ($model == null)
            {
                $url    = Yii::app()->createUrl($moduleId . '/default/removeKanbanSubscriber');
                $script = self::getKanbanSubscriptionScript($url, 'unsubscribe-task-link', 'subscribe-task-link', $subscribeLink);
                Yii::app()->clientScript->registerScript('kanban-unsubscribe-task-link-script', $script);
            }
            else
            {
                $url    = Yii::app()->createUrl($moduleId . '/default/removeSubscriber', array('id' => $model->id));
                $script = self::getDetailSubscriptionScript($url, 'detail-unsubscribe-task-link', 'detail-subscribe-task-link', $subscribeLink, $model->id);
                Yii::app()->clientScript->registerScript('detail-unsubscribe-task-link-script', $script);
            }
        }

        /**
         * Get subscription script
         * @param string $url
         * @param string $sourceClass
         * @param string $targetClass
         * @param string $link
         * @return string
         */
        public static function getKanbanSubscriptionScript($url, $sourceClass, $targetClass, $link)
        {
            // Begin Not Coding Standard
            return "$('body').on('click', '." . $sourceClass . "', function()
                                                    {
                                                        var element     = $(this).parent().parent().parent();
                                                        var id          = $(element).attr('id');
                                                        var idParts     = id.split('_');
                                                        var taskId      = parseInt(idParts[1]);
                                                        var linkParent  = $(this).parent();
                                                        console.log(linkParent);
                                                        $.ajax(
                                                        {
                                                            type : 'GET',
                                                            data : {'id':taskId},
                                                            url  : '" . $url . "',
                                                            beforeSend : function(){
                                                              $('.ui-overlay-block').fadeIn(50);
                                                              $(this).makeLargeLoadingSpinner(true, '.ui-overlay-block');
                                                            },
                                                            success : function(data)
                                                                      {
                                                                        $(linkParent).html(data);
                                                                        $(this).makeLargeLoadingSpinner(false, '.ui-overlay-block');
                                                                        $('.ui-overlay-block').fadeOut(100);
                                                                      }
                                                        }
                                                        );
                                                    }
                                                );";
            // End Not Coding Standard
        }

        /**
         * Get subscription script
         * @param string $url
         * @param string $sourceClass
         * @param string $targetClass
         * @param string $link
         * @return string
         */
        public static function getDetailSubscriptionScript($url, $sourceClass, $targetClass, $link, $taskId)
        {
            // Begin Not Coding Standard
            return "$('body').on('click', '." . $sourceClass . "', function()
                                                    {
                                                        $.ajax(
                                                        {
                                                            type : 'GET',
                                                            url  : '" . $url . "',
                                                            beforeSend : function(){
                                                              $('#subscriberList').html('');
                                                              $(this).makeLargeLoadingSpinner(true, '#subscriberList');
                                                            },
                                                            success : function(data)
                                                                      {
                                                                        $(this).html('" . $link . "');
                                                                        $(this).attr('class', '" . $targetClass . "');
                                                                        if (data == '')
                                                                        {
                                                                            $('#subscriberList').html('');
                                                                        }
                                                                        else
                                                                        {
                                                                            $('#subscriberList').html(data);
                                                                        }
                                                                        $(this).makeLargeLoadingSpinner(false, '#subscriberList');
                                                                      }
                                                        }
                                                        );
                                                    }
                                                );";
            // End Not Coding Standard
        }

        /**
         * Get kanban subscription link for the model. This would be in kanban view for a related model
         * for e.g Project
         * @param OwnedSecurableItem $model
         * @param int $row
         * @return string
         */
        public static function getKanbanSubscriptionLink(OwnedSecurableItem $model, $row)
        {
            return self::resolveSubscriptionLink($model, 'subscribe-task-link', 'unsubscribe-task-link');
        }

        /**
         * Get subscription link on the task detail view
         * @param OwnedSecurableItem $model
         * @param int $row
         * @return string
         */
        public static function getDetailSubscriptionLink(OwnedSecurableItem $model, $row)
        {
            return self::resolveSubscriptionLink($model, 'detail-subscribe-task-link', 'detail-unsubscribe-task-link');
        }

        /**
         * Resolve subscription link for detail and kanban view
         * @param OwnedSecurableItem $model
         * @param string $subscribeLinkClass
         * @param string $unsubscribeLinkClass
         * @return string
         */
        public static function resolveSubscriptionLink(OwnedSecurableItem $model, $subscribeLinkClass, $unsubscribeLinkClass)
        {
            assert('is_string($subscribeLinkClass)');
            assert('is_string($unsubscribeLinkClass)');
            if ($model->owner->id == Yii::app()->user->userModel->id)
            {
                return null;
            }
            if ($model instanceof Task &&
                $model->requestedByUser->id == Yii::app()->user->userModel->id)
            {
                return null;
            }
            if (static::doNotificationSubscribersContainPerson($model, Yii::app()->user->userModel) === false)
            {
                $label       = Zurmo::t('Core', 'Subscribe');
                $class       = $subscribeLinkClass;
                $iconContent = ZurmoHtml::tag('i', array('class' => 'icon-subscribe'), '');
            }
            else
            {
                $label       = Zurmo::t('Core', 'Unsubscribe');
                $class       = $unsubscribeLinkClass;
                $iconContent = ZurmoHtml::tag('i', array('class' => 'icon-unsubscribe'), '');
            }
            return ZurmoHtml::link($iconContent, '#', array('class' => $class, 'title' => $label)) ;
        }

        /**
         * Add subscriber to the task
         * @param User $user
         * @param OwnedSecurableItem $model
         * @param bool $hasReadLatest
         */
        public static function addSubscriber(User $user, OwnedSecurableItem $model, $hasReadLatest = false)
        {
            assert('is_bool($hasReadLatest)');
            if (static::doNotificationSubscribersContainPerson($model, $user) === false)
            {
                $notificationSubscriber = new NotificationSubscriber();
                $notificationSubscriber->person = $user;
                $notificationSubscriber->hasReadLatest = $hasReadLatest;
                $model->notificationSubscribers->add($notificationSubscriber);
            }
        }

        /**
         * Resolve and render task card details subscribers content
         * @param OwnedSecurableItem $model
         * @return type
         */
        public static function resolveAndRenderTaskCardDetailsSubscribersContent(OwnedSecurableItem $model)
        {
            $content         = null;
            $subscribedUsers = static::getModelSubscribers($model);
            foreach ($subscribedUsers as $user)
            {
                if ($user->isSame($model->owner))
                {
                    $content .= NotificationSubscriberUtil::renderSubscriberImageAndLinkContent($user, 20, 'task-owner');
                    break;
                }
            }
            //To take care of the case of duplicates
            $addedSubscribers = array();
            foreach ($subscribedUsers as $user)
            {
                if (!$user->isSame($model->owner))
                {
                    if (!in_array($user->id, $addedSubscribers))
                    {
                        $content .= static::renderSubscriberImageAndLinkContent($user, 20);
                        $addedSubscribers[] = $user->id;
                    }
                }
            }
            return $content;
        }

        /**
         * Process subscription request for model
         * @param OwnedSecurableItem $model
         * @param User $user
         * @return Task $task | error
         * @throws Exception
         * @throws NotFoundException
         * @throws NotSupportedException
         */
        public static function processSubscriptionRequest(OwnedSecurableItem $model, User $user)
        {
            assert('$user instanceof User');
            if (!static::doNotificationSubscribersContainPerson($model, $user))
            {
                $notificationSubscriber = new NotificationSubscriber();
                $notificationSubscriber->person = $user;
                $notificationSubscriber->hasReadLatest = false;
                $model->notificationSubscribers->add($notificationSubscriber);
            }
            $model->save();
            return $model;
        }

        /**
         * Process unsubscription request for model
         * @param OwnedSecurableItem $model
         * @param User $user
         * @return Task $task
         * @throws Exception
         * @throws FailedToSaveModelException
         * @throws NotFoundException
         * @throws NotSupportedException
         */
        public static function processUnsubscriptionRequest(OwnedSecurableItem $model, User $user)
        {
            assert('$user instanceof User');
            foreach ($model->notificationSubscribers as $notificationSubscriber)
            {
                if ($notificationSubscriber->person->getClassId('Item') == $user->getClassId('Item'))
                {
                    $model->notificationSubscribers->remove($notificationSubscriber);
                    break;
                }
            }
            $saved = $model->save();
            if (!$saved)
            {
                throw new FailedToSaveModelException();
            }
            return $model;
        }

        /**
         * Check if model have person(item) in list of its subscribers
         * @param $model
         * @param Item $item
         * @return bool
         */
        public static function doNotificationSubscribersContainPerson(OwnedSecurableItem $model, Item $item)
        {
            foreach ($model->notificationSubscribers as $notificationSubscriber)
            {
                if ($notificationSubscriber->person->getClassId('Item') == $item->getClassId('Item'))
                {
                    return true;
                }
            }
            return false;
        }
    }
?>