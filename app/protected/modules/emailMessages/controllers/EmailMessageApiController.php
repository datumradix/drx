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
     * EmailMessage API Controller
     * Allow only Email Message creation via API
     */
    class EmailMessagesEmailMessageApiController extends ZurmoSecurableItemApiController
    {
        public function actionRead()
        {
            throw new ApiUnsupportedException();
        }

        public function actionUpdate()
        {
            throw new ApiUnsupportedException();
        }

        public function actionDelete()
        {
            throw new ApiUnsupportedException();
        }

        public function actionList()
        {
            throw new ApiUnsupportedException();
        }

        public function actionSearch()
        {
            throw new ApiUnsupportedException();
        }

        public function actionAddRelation()
        {
            throw new ApiUnsupportedException();
        }

        public function actionRemoveRelation()
        {
            throw new ApiUnsupportedException();
        }
        
        /**
         * Create new model
         * @param $data
         * @return ApiResult
         * @throws ApiException
         */
        protected function processCreate($data)
        {
            try
            {
                $model = new EmailMessage();
                $emailMessage = $this->getImapMessageFromEmailData($data);
                if (isset($data['sentFrom']))
                {
                    unset($data['sentFrom']);
                }
                if (isset($data['recipients']))
                {
                    unset($data['recipients']);
                }
                
                if (isset($data['owner']['id']))
                {
                    try
                    {
                        $emailOwner = User::getById((int)$data['owner']['id']);
                    }
                    catch (CException $e)
                    {
                        $message = Zurmo::t('ZurmoModule', 'Owner not found.');
                        throw new ApiException($message);
                    }
                }
                else
                {
                    $emailOwner = Yii::app()->user->userModel;
                }
                
                $emailSenderOrRecipientEmailFoundInSystem = false;
                $userCanAccessContacts = RightsUtil::canUserAccessModule('ContactsModule', $emailOwner);
                $userCanAccessLeads    = RightsUtil::canUserAccessModule('LeadsModule',    $emailOwner);
                $userCanAccessAccounts = RightsUtil::canUserAccessModule('AccountsModule', $emailOwner);
                
                if (!empty($emailMessage->fromEmail))
                {
                    $senderInfo['email'] = $emailMessage->fromEmail;
                    $senderInfo['name'] = $emailMessage->fromName;
                    $sender = EmailArchivingUtil::createEmailMessageSender($senderInfo, $userCanAccessContacts,
                                  $userCanAccessLeads, $userCanAccessAccounts);

                    if ($sender->personsOrAccounts->count() > 0)
                    {
                        $emailSenderOrRecipientEmailFoundInSystem = true;
                    }
                }
                else
                {
                    $message = Zurmo::t('ZurmoModule', 'Sender not found.');
                    throw new ApiException($message);
                }
                
                try
                {
                    $recipientsInfo = EmailArchivingUtil::resolveEmailRecipientsFromEmailMessage($emailMessage);
                }
                catch (NotSupportedException $exception)
                {
                    $message = Zurmo::t('ZurmoModule', 'No recipients found.');
                    throw new ApiException($message);
                }
                $emailRecipientFoundInSystem = false;
                foreach ($recipientsInfo as $recipientInfo)
                {
                    $recipient = EmailArchivingUtil::createEmailMessageRecipient($recipientInfo, $userCanAccessContacts,
                        $userCanAccessLeads, $userCanAccessAccounts);
                    $model->recipients->add($recipient);
                    // Check if at least one recipient email can't be found in Contacts, Leads, Account and User emails
                    // so we will save email message in EmailFolder::TYPE_ARCHIVED_UNMATCHED folder, and user will
                    // be able to match emails with items(Contacts, Accounts...) emails in systems
                    if ($recipient->personsOrAccounts->count() > 0)
                    {
                        $emailRecipientFoundInSystem = true;
                    }
                }
                if ($emailSenderOrRecipientEmailFoundInSystem == true)
                {
                    $emailSenderOrRecipientEmailFoundInSystem = $emailRecipientFoundInSystem;
                }
                
                if ($emailOwner instanceof User)
                {
                    $box = EmailBoxUtil::getDefaultEmailBoxByUser($emailOwner);
                }
                else
                {
                    $box = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
                }
                if (!$emailSenderOrRecipientEmailFoundInSystem)
                {
                    $model->folder  = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_ARCHIVED_UNMATCHED);
                    $this->sendArchivedUnmatchedNotificationToEmailOwner($emailOwner);
                }
                else
                {
                    $model->folder      = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_ARCHIVED);
                }
                $model->sender   = $sender;
                if (isset($data['textContent']) || isset($data['htmlContent']))
                {
                    $emailContent                   = new EmailMessageContent();
                    if (isset($data['textContent']))
                    {
                        $emailContent->textContent  = $data['textContent'];
                    }
                    else
                    {
                        $emailContent->textContent  = '';
                    }
                    if (isset($data['htmlContent']))
                    {
                        $emailContent->htmlContent  = $data['htmlContent'];
                    }
                    else
                    {
                        $emailContent->htmlContent  = '';
                    }
                    $model->content                 = $emailContent;
                    unset($data['textContent']);
                    unset($data['htmlContent']);
                }
                else
                {
                    $message = Zurmo::t('ZurmoModule', 'No email content found.');
                    throw new ApiException($message);
                }
                if (!empty($emailMessage->attachments))
                {
                    foreach ($emailMessage->attachments as $attachment)
                    {
                        if (isset($attachment['filename']) && isset($attachment['attachment']))
                        {
                            $file = EmailArchivingUtil::createEmailAttachment($attachment);
                            if ($file instanceof FileModel)
                            {
                                $model->files->add($file);
                            }
                        }
                    }
                }
                if (!isset($data['sentDateTime']))
                {
                    $model->sentDateTime = DateTimeUtil::convertTimestampToDbFormatDateTime(time());
                }
                
                $this->setModelScenarioFromData($model, $data);
                $model = $this->attemptToSaveModelFromData($model, $data, null, false);
                $id = $model->id;
                $model->forget();
                if (!count($model->getErrors()))
                {
                    $data = array('id' => $id);
                    $result = new ApiResult(ApiResponse::STATUS_SUCCESS, $data, null, null);
                }
                else
                {
                    $errors = $model->getErrors();
                    $message = Zurmo::t('ZurmoModule', 'Model was not created.');
                    $result = new ApiResult(ApiResponse::STATUS_FAILURE, null, $message, $errors);
                }
            }
            catch (Exception $e)
            {
                $message = $e->getMessage();
                throw new ApiException($message);
            }
            return $result;
        }
        
        /**
         * EmailArchivingUtil works with ImapMessage model so we need one
         * @param array $data
         * @return ImapMessage
         */
        protected function getImapMessageFromEmailData($data)
        {
            $emailMessage = new ImapMessage();
            if (isset($data['sentFrom']['email']))
            {
                $emailMessage->fromEmail = $data['sentFrom']['email'];
            }
            if (isset($data['sentFrom']['name']))
            {
                $emailMessage->fromName = $data['sentFrom']['name'];
            }
            if (isset($data['recipients']['to']) && is_array($data['recipients']['to']) && !empty($data['recipients']['to']))
            {
                foreach($data['recipients']['to'] as $to)
                {
                    $emailMessage->to[] = array('name'=>$to['name'], 
                        'email'=>$to['email'], 
                        'type'=>EmailMessageRecipient::TYPE_TO
                    );
                }
            }
            
            if (isset($data['recipients']['cc']) && is_array($data['recipients']['cc']) && !empty($data['recipients']['cc']))
            {
                foreach($data['recipients']['cc'] as $cc)
                {
                    $emailMessage->cc[] = array('name'=>$cc['name'], 
                        'email'=>$cc['email'], 
                        'type'=>EmailMessageRecipient::TYPE_CC
                    );
                }
            }
            if (isset($data['attachments']))
            {
                $emailMessage->attachments = $data['attachments'];
            }
            return $emailMessage;
        }
        
        /**
         * Send notification about unmatched email messages to email owner
         * @param mixed $emailOwner
         * @return void
         */
        protected function sendArchivedUnmatchedNotificationToEmailOwner($emailOwner)
        {
            $notificationMessage                    = new NotificationMessage();
            $notificationMessage->textContent       = Zurmo::t('EmailMessagesModule', 'At least one archived email message does ' .
                                                               'not match any records in the system. ' .
                                                               'To manually match them use this link: {url}.',
                array(
                    '{url}'      => Yii::app()->createUrl('emailMessages/default/matchingList'),
                )
            );
            $notificationMessage->htmlContent       = Zurmo::t('EmailMessagesModule', 'At least one archived email message does ' .
                                                             'not match any records in the system. ' .
                                                             '<a href="{url}">Click here</a> to manually match them.',
                array(
                    '{url}'      => Yii::app()->createUrl('emailMessages/default/matchingList'),
                )
            );
            if ($emailOwner instanceof User)
            {
                $rules                      = new EmailMessageArchivingEmailAddressNotMatchingNotificationRules();
                $rules->addUser($emailOwner);
                NotificationsUtil::submit($notificationMessage, $rules);
            }
        }
        

    }
?>
