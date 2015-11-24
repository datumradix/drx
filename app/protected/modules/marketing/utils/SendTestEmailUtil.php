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

    abstract class SendTestEmailUtil
    {
        const LANGUAGE_DEFAULT                          = null;

        const SUPPORTS_RICH_TEXT_DEFAULT                = true;

        const PREFER_SERIALIZED_DATA_OVER_HTML_CONTENT  = true;

        const PREVIEW_MODE                              = true;

        const ENABLE_TRACKING_DEFAULT                   = false;

        const TEMPLATE_TYPE_DEFAULT                     = EmailTemplate::TYPE_WORKFLOW;

        const ERROR_ON_FIRST_MISSING_MERGE_TAG          = MergeTagsToModelAttributesAdapter::SUPPRESS_INVALID_TAG_ERRORS_KEEP_TAG;

        const ADD_GLOBAL_FOOTER_DEFAULT                 = MergeTagsContentResolverUtil::ADD_GLOBAL_FOOTER_MERGE_TAGS_IF_MISSING;

        public function sendTestEmail(array $recipientData, array $sourceData)
        {
            $userOrContact      = $this->resolveContactByPostRecipientDataOrResolveCurrentUser($recipientData);
            $recipientEmail     = $this->resolveRecipientEmailByPostRecipientDataAndUserOrContact($recipientData, $userOrContact);
            $emailData          = $this->resolveEmailDataByPostSourceDataAttributesOrReferredModel($sourceData, $userOrContact, $recipientEmail);
            $emailData          = $this->disableTrackingForTestEmails($emailData);
            $this->resolveAndSendEmailMessageByPostSourceDataAndUserOrContact($emailData, $userOrContact);
        }

        protected function resolveRecipientEmailByPostRecipientDataAndUserOrContact(array $recipientData, Item $userOrContact)
        {
            $emailAddress   = $this->resolveRecipientEmailByPostRecipientData($recipientData);
            if (!isset($emailAddress))
            {
                $emailAddress   = $this->resolveRecipientEmailByUserOrContact($userOrContact);
            }
            return $emailAddress;
        }

        protected function resolveRecipientEmailByPostRecipientData(array $recipientData)
        {
            if (isset($recipientData['emailAddress']))
            {
                return $recipientData['emailAddress'];
            }
            return null;
        }

        protected function resolveRecipientEmailByUserOrContact(Item $userOrContact)
        {
            return $userOrContact->primaryEmail->emailAddress;
        }

        protected function resolveContactByPostRecipientDataOrResolveCurrentUser(array $recipientData)
        {
            $userOrContact      = $this->resolveContactByPostRecipientData($recipientData);
            if (!isset($userOrContact))
            {
                $userOrContact  = Yii::app()->user->userModel;
            }
            return $userOrContact;
        }

        protected function resolveContactByPostRecipientData(array $recipientData)
        {
            if (isset($recipientData['contactId']))
            {
                return Contact::getById(intval($recipientData['contactId']));
            }
            return null;
        }

        protected function resolveEmailDataByPostSourceDataAttributesOrReferredModel(array $sourceData,
                                                                                        Item $userOrContact,
                                                                                        $recipientEmail)
        {
            $emailData              = array();
            $emailData['folder']    = $this->resolveFolder();
            $emailData['recipient'] = $this->resolveRecipient($userOrContact, $recipientEmail);
            if (isset($sourceData['id'], $sourceData['class']))
            {
                $sourceModel    = $this->resolveSourceModelByPostSourceDataAttributes($sourceData['id'], $sourceData['class']);
                $this->resolveEmailDataByModel($emailData, $sourceModel);
            }
            else
            {
                $this->resolveEmailDataByPostSourceDataAttributes($emailData, $sourceData);
            }
            $this->resolveDefaultsForMissingEmailData($emailData);
            return $emailData;
        }

        protected function resolveSourceModelByPostSourceDataAttributes($id, $className, $skipSecurityCheck = false)
        {
            $sourceModel        = $className::getById(intval($id));
            if (!$skipSecurityCheck)
            {
                ControllerSecurityUtil::resolveAccessCanCurrentUserReadModel($sourceModel);
            }
            return $sourceModel;
        }

        protected function resolveDefaultsForMissingEmailData(array & $emailData)
        {
            $defaultMappings    = array(
                // key                  => default value
                'sender'                => $this->resolveDefaultSender(),
                'enableTracking'        => static::ENABLE_TRACKING_DEFAULT,
                'supportsRichText'      => static::SUPPORTS_RICH_TEXT_DEFAULT,
                'type'                  => static::TEMPLATE_TYPE_DEFAULT,
                'language'              => static::LANGUAGE_DEFAULT,
            );
            foreach ($defaultMappings as $key => $defaultValue)
            {
                if (!isset($emailData[$key]))
                {
                    $emailData[$key]    = $defaultValue;
                }
            }
        }

        protected function resolveEmailDataByModel(array & $emailData, OwnedSecurableItem $sourceModel)
        {
            $mapping   = array(
                // key                  => resolve function name
                'subject'               => 'resolveSubjectByModel',
                'owner'                 => 'resolveOwnerByModel',
                'permissions'           => 'resolvePermissionsByModel',
                'sender'                => 'resolveSenderByModel',
                'attachments'           => 'resolveAttachmentsByModel',
                'content'               => 'resolveContentByModel',
                'enableTracking'        => 'resolveEnableTrackingByModel',
                'supportsRichText'      => 'resolveSupportsRichTextByModel',
                'type'                  => 'resolveTypeByModel',
                'language'              => 'resolveLanguageByModel',
                'marketingListId'       => 'resolveMarketingListIdByModel'
            );
            $this->resolveArrayByKeyToResolverFunctionNameMapping($emailData, $mapping, $sourceModel);
        }

        protected function resolveEmailDataByPostSourceDataAttributes(array & $emailData, array $sourceData)
        {
            $mapping   = array(
                // key                  => resolve function name
                'subject'               => 'resolveSubjectByPostSourceData',
                'owner'                 => 'resolveOwnerByPostSourceData',
                'permissions'           => 'resolvePermissionsByPostSourceData',
                'sender'                => 'resolveSenderByPostSourceData',
                'attachments'           => 'resolveAttachmentsByPostSourceData',
                'content'               => 'resolveContentByPostSourceData',
                'enableTracking'        => 'resolveEnableTrackingByPostSourceData',
                'supportsRichText'      => 'resolveSupportsRichTextByPostSourceData',
                'type'                  => 'resolveTypeByPostSourceData',
                'language'              => 'resolveLanguageByPostSourceData',
                'marketingListId'       => 'resolveMarketingListIdByPostSourceData',
            );
            $this->resolveArrayByKeyToResolverFunctionNameMapping($emailData, $mapping, $sourceData);
        }

        protected function resolveArrayByKeyToResolverFunctionNameMapping(array & $data, array $mapping, $resolverFunctionParameter)
        {
            foreach ($mapping as $key => $resolverFunctionName)
            {
                $data[$key]         = $this->$resolverFunctionName($resolverFunctionParameter);
            }
        }

        protected function resolveSubjectByModel(OwnedSecurableItem $model)
        {
            return $model->subject;
        }

        protected function resolveSubjectByPostSourceData(array $sourceData)
        {
            if (isset($sourceData['subject']))
            {
                return $sourceData['subject'];
            }
            return null;
        }

        protected function resolveOwnerByModel(OwnedSecurableItem $model)
        {
            return $model->owner;
        }

        protected function resolveOwnerByPostSourceData(array $sourceData)
        {
            // we do not accept owner from Post data, yet.
            return $this->resolveDefaultOwner();
        }

        protected function resolveDefaultOwner()
        {
            return Yii::app()->user->userModel;
        }

        protected function resolvePermissionsByModel(OwnedSecurableItem $model)
        {
            return ExplicitReadWriteModelPermissionsUtil::makeBySecurableItem($model);
        }

        protected function resolvePermissionsByPostSourceData(array $sourceData)
        {
            // not considering permissions from post for the time being
            return null;
        }

        protected function resolveSenderByModel(OwnedSecurableItem $model)
        {
            // we don't need it for emailTemplate
            // for campaign we have overridden it anyway.
            return null;
        }

        protected function resolveSenderByPostSourceData(array $sourceData)
        {
            if (isset($sourceData['fromName'], $sourceData['fromAddress']))
            {
                return $this->resolveSenderByNameAndEmailAddress($sourceData['fromName'], $sourceData['fromAddress']);
            }
            return null;
        }

        protected function resolveAttachmentsByModel(OwnedSecurableItem $model)
        {
            $attachments    = array();
            if (isset($model->files))
            {
                $attachments    = $model->files;
            }
            return $attachments;
        }

        protected function resolveAttachmentsByPostSourceData(array $sourceData)
        {
            $attachments = array();
            if (isset($sourceData['attachmentIds']) && is_array($sourceData['attachmentIds']))
            {
                foreach ($sourceData['attachmentIds'] as $attachmentId)
                {
                    $attachments[] = FileModel::getById(intval($attachmentId));
                }
            }
            return $attachments;
        }

        protected function resolveContentByModel(OwnedSecurableItem $model)
        {
            $textContent    = $model->textContent;
            $htmlContent    = $model->htmlContent;
            return compact('textContent', 'htmlContent');
        }

        protected function resolveContentByPostSourceData(array $sourceData)
        {
            if (isset($sourceData['textContent']))
            {
                $textContent    = $sourceData['textContent'];
            }
            if (isset($sourceData['htmlContent']))
            {
                $htmlContent    = $sourceData['htmlContent'];
            }
            return compact('textContent', 'htmlContent');
        }

        protected function resolveEnableTrackingByModel(OwnedSecurableItem $model)
        {
            // not needed for emailTemplate
            //overridden in campaign
            return null;
        }

        protected function resolveEnableTrackingByPostSourceData(array $sourceData)
        {
            if (isset($sourceData['enableTracking']))
            {
                return $sourceData['enableTracking'];
            }
            return null;
        }

        protected function resolveSupportsRichTextByModel(OwnedSecurableItem $model)
        {
            // not needed for emailTemplate
            // overridden in campaign
            return null;
        }

        protected function resolveSupportsRichTextByPostSourceData($sourceData)
        {
            if (isset($sourceData['supportsRichText']))
            {
                return $sourceData['supportsRichText'];
            }
            return null;
        }

        protected function resolveTypeByModel(OwnedSecurableItem $model)
        {
            // not needed for campaign
            // overridden for emailTemplate
            return null;
        }

        protected function resolveTypeByPostSourceData(array $sourceData)
        {
            if (isset($sourceData['type']))
            {
                return $sourceData['type'];
            }
            return null;
        }

        protected function resolveLanguageByModel(OwnedSecurableItem $model)
        {
            // overridden in emailTemplate
            // not needed for campaign
            return null;
        }

        protected function resolveLanguageByPostSourceData(array $sourceData)
        {
            if (isset($sourceData['language']))
            {
                return $sourceData['language'];
            }
            return null;
        }

        protected function resolveMarketingListIdByModel(OwnedSecurableItem $model)
        {
            // not needed for emailTemplate
            // overridden for campaign
            return null;
        }

        protected function resolveMarketingListIdByPostSourceData($sourceData)
        {
            if (isset($sourceData['marketingListId']))
            {
                return $sourceData['marketingListId'];
            }
            return null;
        }

        protected function resolveAndSendEmailMessageByPostSourceDataAndUserOrContact(array $emailData, Item $userOrContact)
        {
            $invalidTags                        = array();
            $emailMessage                       = new EmailMessage();
            $emailMessage->subject              = $emailData['subject'];
            $emailMessage->folder               = $emailData['folder'];
            $emailMessage->owner                = $emailData['owner'];
            $emailMessage->sender               = $emailData['sender'];
            MergeTagsContentResolverUtil::resolveContentsForGlobalFooterAndMergeTagsAndTracking(
                                    $emailData['content']['textContent'], $emailData['content']['htmlContent'],
                                    $userOrContact, intval($emailData['type']), static::ERROR_ON_FIRST_MISSING_MERGE_TAG,
                                    $emailData['language'], $invalidTags, $emailData['marketingListId'],
                                    static::PREVIEW_MODE, static::ADD_GLOBAL_FOOTER_DEFAULT, (bool) $emailData['enableTracking'],
                                    0, 'CampaignItem', 0); // sending these three just in the case where enableTracking is true
            $emailContent                       = new EmailMessageContent();
            $emailContent->textContent          = $emailData['content']['textContent'];
            if ($emailData['supportsRichText'])
            {
                $emailContent->htmlContent = $emailData['content']['htmlContent'];
            }
            $emailMessage->content              = $emailContent;
            $emailMessage->recipients->add($emailData['recipient']);
            $this->resolveAttachmentsForEmailMessage($emailMessage, $emailData['attachments']);
            Yii::app()->emailHelper->sendImmediately($emailMessage);
            if ($emailMessage->save())
            {
                if ($emailData['permissions'])
                {
                    ExplicitReadWriteModelPermissionsUtil::
                            resolveExplicitReadWriteModelPermissions($emailMessage, $emailData['permissions']);
                }
            }
            else
            {
                throw new FailedToSaveModelException("Unable to save EmailMessage");
            }
        }

        protected function resolveRecipient(Item $userOrContact, $recipientEmail)
        {
            $recipient                  = new EmailMessageRecipient();
            $recipient->toAddress       = $recipientEmail;
            $recipient->toName          = strval($userOrContact);
            $recipient->type            = EmailMessageRecipient::TYPE_TO;
            $recipient->personsOrAccounts->add($userOrContact);
            return $recipient;
        }

        protected function resolveFolder()
        {
            $box            = EmailBox::resolveAndGetByName(EmailBox::NOTIFICATIONS_NAME);
            $folder         = EmailFolder::getByBoxAndType($box, EmailFolder::TYPE_DRAFT);
            return $folder;
        }

        protected function resolveDefaultSender()
        {
            $fromAddress            = Yii::app()->emailHelper->resolveFromAddressByUser(Yii::app()->user->userModel);
            $fromName               = strval(Yii::app()->user->userModel);
            return $this->resolveSenderByNameAndEmailAddress($fromName, $fromAddress);
        }

        protected function resolveSenderByNameAndEmailAddress($fromName, $fromAddress)
        {
            $sender                         = new EmailMessageSender();
            $sender->fromAddress            = $fromAddress;
            $sender->fromName               = $fromName;
            return $sender;
        }

        protected function resolveAttachmentsForEmailMessage(EmailMessage & $emailMessage, $attachments)
        {
            if (!empty($attachments))
            {
                foreach ($attachments as $attachment)
                {
                    $emailMessageFile   = FileModelUtil::makeByFileModel($attachment);
                    $emailMessage->files->add($emailMessageFile);
                }
            }
        }

        protected function disableTrackingForTestEmails($emailData)
        {
            $emailData['enableTracking'] = false;
            return $emailData;
        }
    }
?>