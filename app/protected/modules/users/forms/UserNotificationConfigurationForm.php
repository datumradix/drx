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

    /**
     * Form to all editing and viewing of a user's configuration notification values in the user interface.
     */
    class UserNotificationConfigurationForm extends CFormModel
    {
        /**
         * Is set in order to properly route action elements in view.
         */
        private $user;
        
        public $notificationConfigurationTableHead;
        public $inboxAndEmailNotificationSettings = array();
        
        public $enableStuckMonitorJobNotification = array();
        public $enableStuckMonitorJobNotificationInbox;
        public $enableStuckMonitorJobNotificationEmail;
        
        public $enableStuckJobsNotification = array();
        public $enableStuckJobsNotificationInbox;
        public $enableStuckJobsNotificationEmail;
        
        public $enableJobCompletedWithErrorsNotification = array();
        public $enableJobCompletedWithErrorsNotificationInbox;
        public $enableJobCompletedWithErrorsNotificationEmail;
        
        public $enableNewZurmoVersionAvailableNotification = array();
        public $enableNewZurmoVersionAvailableNotificationInbox;
        public $enableNewZurmoVersionAvailableNotificationEmail;
        
        public $enableEmailMessageOwnerNotExistNotification = array();
        public $enableEmailMessageOwnerNotExistNotificationInbox;
        public $enableEmailMessageOwnerNotExistNotificationEmail;
        
        public $enableWorkflowValidityCheckNotification = array();
        public $enableWorkflowValidityCheckNotificationInbox;
        public $enableWorkflowValidityCheckNotificationEmail;
        
        public $enableWorkflowMaximumDepthNotification = array();
        public $enableWorkflowMaximumDepthNotificationInbox;
        public $enableWorkflowMaximumDepthNotificationEmail;
        
        public $enableConversationInvitesNotification = array();
        public $enableConversationInvitesNotificationInbox;
        public $enableConversationInvitesNotificationEmail;
        
        public $enableConversationNewCommentNotification = array();
        public $enableConversationNewCommentNotificationInbox;
        public $enableConversationNewCommentNotificationEmail;
        
        public $enableNewMissionNotification = array();
        public $enableNewMissionNotificationInbox;
        public $enableNewMissionNotificationEmail;
        
        public $enableMissionStatusChangeNotification = array();
        public $enableMissionStatusChangeNotificationInbox;
        public $enableMissionStatusChangeNotificationEmail;
        
        public $enableMissionNewCommentNotification = array();
        public $enableMissionNewCommentNotificationInbox;
        public $enableMissionNewCommentNotificationEmail;
        
        public $enableNewTaskNotification = array();
        public $enableNewTaskNotificationInbox;
        public $enableNewTaskNotificationEmail;
        
        public $enableDeliveredTaskNotification = array();
        public $enableDeliveredTaskNotificationInbox;
        public $enableDeliveredTaskNotificationEmail;
        
        public $enableAcceptedTaskNotification = array();
        public $enableAcceptedTaskNotificationInbox;
        public $enableAcceptedTaskNotificationEmail;
        
        public $enableRejectedTaskNotification = array();
        public $enableRejectedTaskNotificationInbox;
        public $enableRejectedTaskNotificationEmail;
        
        public $enableTaskOwnerChangeNotification = array();
        public $enableTaskOwnerChangeNotificationInbox;
        public $enableTaskOwnerChangeNotificationEmail;
        
        public $enableTaskNewCommentNotification = array();
        public $enableTaskNewCommentNotificationInbox;
        public $enableTaskNewCommentNotificationEmail;
        
        public $enableNewProjectNotification = array();
        public $enableNewProjectNotificationInbox;
        public $enableNewProjectNotificationEmail;
        
        public $enableProjectTaskAddedNotification = array();
        public $enableProjectTaskAddedNotificationInbox;
        public $enableProjectTaskAddedNotificationEmail;
        
        public $enableProjectTaskNewCommentNotification = array();
        public $enableProjectTaskNewCommentNotificationInbox;
        public $enableProjectTaskNewCommentNotificationEmail;
        
        public $enableProjectTaskStatusChangeNotification = array();
        public $enableProjectTaskStatusChangeNotificationInbox;
        public $enableProjectTaskStatusChangeNotificationEmail;
        
        public $enableArchivedProjectNotification = array();
        public $enableArchivedProjectNotificationInbox;
        public $enableArchivedProjectNotificationEmail;
        
        public $enableGameRewardRedeemedNotification = array();
        public $enableGameRewardRedeemedNotificationInbox;
        public $enableGameRewardRedeemedNotificationEmail;
        
        public $enableExportProcessCompletedNotification = array();
        public $enableExportProcessCompletedNotificationInbox;
        public $enableExportProcessCompletedNotificationEmail;
        
        public $enableEmailMessageArchivingEmailAddressNotMatchingNotification = array();
        public $enableEmailMessageArchivingEmailAddressNotMatchingNotificationInbox;
        public $enableEmailMessageArchivingEmailAddressNotMatchingNotificationEmail;
        
        public $enableRemoveApiTestEntryScriptFileNotification = array();
        public $enableRemoveApiTestEntryScriptFileNotificationInbox;
        public $enableRemoveApiTestEntryScriptFileNotificationEmail;
        
        public $enableEnableMinifyNotification = array();
        public $enableEnableMinifyNotificationInbox;
        public $enableEnableMinifyNotificationEmail;
        
        public $enableClearAssetsFolderNotification = array();
        public $enableClearAssetsFolderNotificationInbox;
        public $enableClearAssetsFolderNotificationEmail;

        public function __construct($user)
        {
            assert('$user instanceof User');
            assert('is_int($user->id) && $user->id > 0');
            $this->user = $user;
        }

        public function getUser()
        {
            return $this->user;
        }

        /**
         * When getId is called, it is looking for the user model id for the user
         * who's configuration values are being edited.
         */
        public function getId()
        {
            return $this->user->id;
        }

        public function rules()
        {
            return array(
                array('enableStuckMonitorJobNotificationInbox',     'boolean'),
                array('enableStuckMonitorJobNotificationEmail',     'boolean'),
                array('enableStuckJobsNotificationInbox',     'boolean'),
                array('enableStuckJobsNotificationEmail',     'boolean'),
                array('enableJobCompletedWithErrorsNotificationInbox',     'boolean'),
                array('enableJobCompletedWithErrorsNotificationEmail',     'boolean'),
                array('enableNewZurmoVersionAvailableNotificationInbox',     'boolean'),
                array('enableNewZurmoVersionAvailableNotificationEmail',     'boolean'),
                array('enableEmailMessageOwnerNotExistNotificationInbox',     'boolean'),
                array('enableEmailMessageOwnerNotExistNotificationEmail',     'boolean'),
                array('enableWorkflowValidityCheckNotificationInbox',     'boolean'),
                array('enableWorkflowValidityCheckNotificationEmail',     'boolean'),
                array('enableWorkflowMaximumDepthNotificationInbox',     'boolean'),
                array('enableWorkflowMaximumDepthNotificationEmail',     'boolean'),
                
                array('enableConversationInvitesNotificationInbox',     'boolean'),
                array('enableConversationInvitesNotificationEmail',     'boolean'),
                array('enableConversationNewCommentNotificationInbox',     'boolean'),
                array('enableConversationNewCommentNotificationEmail',     'boolean'),
                array('enableNewMissionNotificationInbox',     'boolean'),
                array('enableNewMissionNotificationEmail',     'boolean'),
                array('enableMissionStatusChangeNotificationInbox',     'boolean'),
                array('enableMissionStatusChangeNotificationEmail',     'boolean'),
                array('enableMissionNewCommentNotificationInbox',     'boolean'),
                array('enableMissionNewCommentNotificationEmail',     'boolean'),
                array('enableNewTaskNotificationInbox',     'boolean'),
                array('enableNewTaskNotificationEmail',     'boolean'),
                array('enableDeliveredTaskNotificationInbox',     'boolean'),
                array('enableDeliveredTaskNotificationEmail',     'boolean'),
                array('enableAcceptedTaskNotificationInbox',     'boolean'),
                array('enableAcceptedTaskNotificationEmail',     'boolean'),
                array('enableRejectedTaskNotificationInbox',     'boolean'),
                array('enableRejectedTaskNotificationEmail',     'boolean'),
                array('enableTaskOwnerChangeNotificationInbox',     'boolean'),
                array('enableTaskOwnerChangeNotificationEmail',     'boolean'),
                array('enableTaskNewCommentNotificationInbox',     'boolean'),
                array('enableTaskNewCommentNotificationEmail',     'boolean'),
                array('enableNewProjectNotificationInbox',     'boolean'),
                array('enableNewProjectNotificationEmail',     'boolean'),
                array('enableProjectTaskAddedNotificationInbox',     'boolean'),
                array('enableProjectTaskAddedNotificationEmail',     'boolean'),
                array('enableProjectTaskNewCommentNotificationInbox',     'boolean'),
                array('enableProjectTaskNewCommentNotificationEmail',     'boolean'),
                array('enableProjectTaskStatusChangeNotificationInbox',     'boolean'),
                array('enableProjectTaskStatusChangeNotificationEmail',     'boolean'),
                array('enableArchivedProjectNotificationInbox',     'boolean'),
                array('enableArchivedProjectNotificationEmail',     'boolean'),
                array('enableGameRewardRedeemedNotificationInbox',     'boolean'),
                array('enableGameRewardRedeemedNotificationEmail',     'boolean'),
                array('enableExportProcessCompletedNotificationInbox',     'boolean'),
                array('enableExportProcessCompletedNotificationEmail',     'boolean'),
                array('enableEmailMessageArchivingEmailAddressNotMatchingNotificationInbox',     'boolean'),
                array('enableEmailMessageArchivingEmailAddressNotMatchingNotificationEmail',     'boolean'),
            );
        }

        public function attributeLabels()
        {
            return array(
                'notificationConfigurationTableHead'            => Zurmo::t('UsersModule', 'Notification'),
                'enableStuckMonitorJobNotification'             => Zurmo::t('UsersModule', 'Monitor Job'),
                'enableStuckJobsNotification'                   => Zurmo::t('UsersModule', 'Scheduled Jobs'),
                'enableJobCompletedWithErrorsNotification'      => Zurmo::t('UsersModule', 'Job completes with errors'),
                'enableNewZurmoVersionAvailableNotification'    => Zurmo::t('UsersModule', 'New Zurmo Version available'),
                'enableEmailMessageOwnerNotExistNotification'   => Zurmo::t('UsersModule', 'Archived Email fails'),
                'enableWorkflowValidityCheckNotification'       => Zurmo::t('UsersModule', 'Workflow validity warnings'),
                'enableWorkflowMaximumDepthNotification'        => Zurmo::t('UsersModule', 'Workflow endless loop warnings'),
                'enableConversationInvitesNotification'         => Zurmo::t('UsersModule', 'Conversation Invites'),
                'enableConversationNewCommentNotification'      => Zurmo::t('UsersModule', 'New Conversation Comments'),
                'enableNewMissionNotification'                  => Zurmo::t('UsersModule', 'New Missions'),
                'enableMissionStatusChangeNotification'         => Zurmo::t('UsersModule', 'Mission status change'),
                'enableMissionNewCommentNotification'           => Zurmo::t('UsersModule', 'New Mission Comments'),
                'enableNewTaskNotification'                     => Zurmo::t('UsersModule', 'New Tasks'),
                'enableDeliveredTaskNotification'               => Zurmo::t('UsersModule', 'Delivered Tasks'),
                'enableAcceptedTaskNotification'                => Zurmo::t('UsersModule', 'Accepted Tasks'),
                'enableRejectedTaskNotification'                => Zurmo::t('UsersModule', 'Rejected Tasks'),
                'enableTaskOwnerChangeNotification'             => Zurmo::t('UsersModule', 'Task Owner Change'),
                'enableTaskNewCommentNotification'              => Zurmo::t('UsersModule', 'New Task Comments'),
                'enableNewProjectNotification'                  => Zurmo::t('UsersModule', 'New Project'),
                'enableProjectTaskAddedNotification'            => Zurmo::t('UsersModule', 'Project Task Added'),
                'enableProjectTaskNewCommentNotification'       => Zurmo::t('UsersModule', 'Project Task Comment Added'),
                'enableProjectTaskStatusChangeNotification'     => Zurmo::t('UsersModule', 'Project Task Status Changed'),
                'enableArchivedProjectNotification'             => Zurmo::t('UsersModule', 'Project Archived'),
                'enableGameRewardRedeemedNotification'          => Zurmo::t('UsersModule', 'Game reward redemptions'),
                'enableExportProcessCompletedNotification'      => Zurmo::t('UsersModule', 'Export ready'),
                'enableEmailMessageArchivingEmailAddressNotMatchingNotification'    
                                                                => Zurmo::t('UsersModule', 'Archived Email is unmatched'),
            );
        }
    }
?>