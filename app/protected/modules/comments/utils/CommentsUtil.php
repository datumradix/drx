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
     * Helper class for working with comments
     */
    class CommentsUtil
    {
        /**
         * @param RedBeanModel $relatedModel
         * @param Comment $comment
         * @param array $peopleToSendNotification
         */
        public static function sendNotificationOnNewComment(RedBeanModel $relatedModel, Comment $comment, $peopleToSendNotification)
        {
            if (count($peopleToSendNotification) > 0)
            {
                $notificationRulesClassName = static::resolveNotificationRulesClassByModel($relatedModel);
                $rules = new $notificationRulesClassName();
                foreach ($peopleToSendNotification as $people)
                {
                    $rules->addUser($people);
                }
                $notificationMessage = static::createNotificationMessage($relatedModel, $comment);
                NotificationsUtil::submit($notificationMessage, $rules);
            }
            else
            {
                return;
            }
        }

        /**
         * @param RedBeanModel $model
         * @param Comment $comment
         * @param User $user
         * @return EmailMessageContent
         */
        protected static function getEmailContent(RedBeanModel $model, Comment $comment, User $user)
        {
            $emailContent  = new EmailMessageContent();
            $url           = static::getUrlToEmail($model);
            $shortUrl      = ShortUrlUtil::createShortUrl($url);
            $textContent   = Zurmo::t('CommentsModule', 'Hello, {lineBreak} {updaterName} added a new comment to the ' .
                                             '{strongStartTag}{modelName}{strongEndTag}: {lineBreak}' .
                                             '"{commentDescription}." {lineBreak}{lineBreak} {url} ',
                                    array('{lineBreak}'           => "\n",
                                          '{strongStartTag}'      => null,
                                          '{strongEndTag}'        => null,
                                          '{updaterName}'         => strval($comment->createdByUser),
                                          '{modelName}'           => $model->getModelLabelByTypeAndLanguage(
                                                                     'SingularLowerCase'),
                                          '{commentDescription}'  => strval($comment),
                                          '{url}'                 => $shortUrl
                                        ));
            $emailContent->textContent  = EmailNotificationUtil::
                                                resolveNotificationTextTemplate($textContent);
            $htmlContent = Zurmo::t('CommentsModule', 'Hello, {lineBreak} {updaterName} added a new comment to the ' .
                                             '{strongStartTag}{url}{strongEndTag}: {lineBreak}' .
                                             '"{commentDescription}."',
                               array('{lineBreak}'           => "<br/>",
                                     '{strongStartTag}'      => '<strong>',
                                     '{strongEndTag}'        => '</strong>',
                                     '{updaterName}'         => strval($comment->createdByUser),
                                     '{commentDescription}'  => strval($comment),
                                     '{url}'                 => ZurmoHtml::link($model->getModelLabelByTypeAndLanguage(
                                                                'SingularLowerCase'), $url)
                                   ));
            $emailContent->htmlContent  = EmailNotificationUtil::resolveNotificationHtmlTemplate($htmlContent);
            return $emailContent;
        }

        /**
         * @param $model
         * @return string
         */
        public static function getEmailSubject($model)
        {
            if ($model instanceof Conversation || $model instanceof Mission)
            {
                return Zurmo::t('CommentsModule', 'New comment on {modelName}: {subject}',
                                    array('{subject}'   => strval($model),
                                          '{modelName}' => $model->getModelLabelByTypeAndLanguage('SingularLowerCase')));
            }
        }

        /**
         * @param $model
         * @return mixed
         */
        public static function getUrlToEmail($model)
        {
            if ($model instanceof Conversation)
            {
                return ConversationParticipantsUtil::getUrlToConversationDetailAndRelationsView($model->id);
            }
            elseif ($model instanceof Mission)
            {
                return Yii::app()->createAbsoluteUrl('missions/default/details/', array('id' => $model->id));
            }
            elseif ($model instanceof Task)
            {
                return Yii::app()->createAbsoluteUrl('tasks/default/details/', array('id' => $model->id));
            }
        }

        /**
         * Creates notification message for new comment
         * @param $model
         * @param Comment $comment
         * @return NotificationMessage
         */
        protected static function createNotificationMessage($model, Comment $comment)
        {
            $notificationMessage  = new NotificationMessage();
            $url           = static::getUrlToEmail($model);
            $shortUrl      = ShortUrlUtil::createShortUrl($url);
            $textContent   = Zurmo::t('CommentsModule', 'Hello, {lineBreak} {updaterName} added a new comment to the ' .
                '{strongStartTag}{modelName}{strongEndTag}: {lineBreak}' .
                '"{commentDescription}." {lineBreak}{lineBreak} {url} ',
                array('{lineBreak}'           => "\n",
                    '{strongStartTag}'      => null,
                    '{strongEndTag}'        => null,
                    '{updaterName}'         => strval($comment->createdByUser),
                    '{modelName}'           => $model->getModelLabelByTypeAndLanguage(
                        'SingularLowerCase'),
                    '{commentDescription}'  => strval($comment),
                    '{url}'                 => $shortUrl
                ));
            $notificationMessage->textContent  = $textContent;
            $htmlContent = Zurmo::t('CommentsModule', 'Hello, {lineBreak} {updaterName} added a new comment to the ' .
                '{strongStartTag}{url}{strongEndTag}: {lineBreak}' .
                '"{commentDescription}."',
                array('{lineBreak}'           => "<br/>",
                    '{strongStartTag}'      => '<strong>',
                    '{strongEndTag}'        => '</strong>',
                    '{updaterName}'         => strval($comment->createdByUser),
                    '{commentDescription}'  => strval($comment),
                    '{url}'                 => ZurmoHtml::link($model->getModelLabelByTypeAndLanguage(
                        'SingularLowerCase'), $url)
                ));
            $notificationMessage->htmlContent  = DataUtil::purifyHtml($htmlContent);
            return $notificationMessage;
        }

        /**
         * Resolve the notification setting name by model
         * @return string
         */
        protected static function resolveOnNewCommentNotificationSettingNameByModel(RedBeanModel $model)
        {
            return 'enable' . get_class($model) . 'NewCommentNotification';
        }

        /**
         * Resolve the notification rules class name by model
         * @return string
         */
        protected static function resolveNotificationRulesClassByModel(RedBeanModel $model)
        {
            return get_class($model) . 'NewCommentNotificationRules';
        }
    }
?>