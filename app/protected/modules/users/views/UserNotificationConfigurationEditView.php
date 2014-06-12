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
     * Edit and details view for the admin configuration view for Sip.
     */
    class UserNotificationConfigurationEditView extends EditAndDetailsView
    {
        protected static $notificationConfigurationPanelRows = array();
        
        public function __construct($renderType, $controllerId, $moduleId, $model, $title)
        {
            parent::__construct($renderType, $controllerId, $moduleId, $model);
            $this->title = $title;
            $user = $this->model->user;
            self::$notificationConfigurationPanelRows = $this->resolveNotificationConfigurationViewContentByUserType($user);
        }
        
        public function getTitle()
        {
            return $this->title;
        }

        public static function getDefaultMetadata()
        {
            $metadata = array(
                'global' => array(
                    'toolbar' => array(
                        'elements' => array(
                            array('type' => 'ConfigurationLink',
                                            'label' => "eval:Zurmo::t('UsersModule', 'Cancel')"),
                            array('type' => 'SaveButton',    'renderType' => 'Edit'),
                            array('type' => 'EditLink',      'renderType' => 'Details'),
                        ),
                    ),
                    'panelsDisplayType' => FormLayout::PANELS_DISPLAY_TYPE_ALL,
                    'panels' => array(
                        array(
                            'rows' => self::$notificationConfigurationPanelRows
                        ),
                    ),
                ),
            );
            return $metadata;
        }

        protected function getNewModelTitleLabel()
        {
            return null;
        }
        
        protected function resolveNotificationConfigurationViewContentByUserType($user)
        {
            $tableHeadRow = array(
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'notificationConfigurationTableHead',
                                       'type' => 'NotificationConfigurationTableHead',),
                              ),
                         ),
                     ),
                ),
            );
            $superAdministratorRows = array(
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableStuckMonitorJobNotification',
                                       'type' => 'StuckMonitorJobNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableStuckJobsNotification',
                                       'type' => 'StuckJobsNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableJobCompletedWithErrorsNotification',
                                       'type' => 'JobCompletedWithErrorsNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableNewZurmoVersionAvailableNotification',
                                       'type' => 'NewZurmoVersionAvailableNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableEmailMessageOwnerNotExistNotification',
                                       'type' => 'EmailMessageOwnerNotExistNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableWorkflowValidityCheckNotification',
                                       'type' => 'WorkflowValidityCheckNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableWorkflowMaximumDepthNotification',
                                       'type' => 'WorkflowMaximumDepthNotification',),
                              ),
                         ),
                     ),
                ),
            );
            $regularUserRows = array(
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableConversationInvitesNotification',
                                       'type' => 'ConversationInvitesNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableConversationNewCommentNotification',
                                       'type' => 'ConversationNewCommentNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableNewMissionNotification',
                                       'type' => 'NewMissionNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableMissionStatusChangeNotification',
                                       'type' => 'MissionStatusChangeNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableMissionNewCommentNotification',
                                       'type' => 'MissionNewCommentNotification',),
                              ),
                         ),
                     ),
                ),  
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableNewTaskNotification',
                                       'type' => 'NewTaskNotification',),
                              ),
                         ),
                     ),
                ),  
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableDeliveredTaskNotification',
                                       'type' => 'DeliveredTaskNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableAcceptedTaskNotification',
                                       'type' => 'AcceptedTaskNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableRejectedTaskNotification',
                                       'type' => 'RejectedTaskNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableTaskOwnerChangeNotification',
                                       'type' => 'TaskOwnerChangeNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableTaskNewCommentNotification',
                                       'type' => 'TaskNewCommentNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableNewProjectNotification',
                                       'type' => 'NewProjectNotification',),
                              ),
                         ),
                     ),
                ),  
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableProjectTaskAddedNotification',
                                       'type' => 'ProjectTaskAddedNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableProjectTaskNewCommentNotification',
                                       'type' => 'ProjectTaskNewCommentNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableProjectTaskStatusChangeNotification',
                                       'type' => 'ProjectTaskStatusChangeNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableArchivedProjectNotification',
                                       'type' => 'ArchivedProjectNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableGameRewardRedeemedNotification',
                                       'type' => 'GameRewardRedeemedNotification',),
                              ),
                         ),
                     ),
                ), 
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableExportProcessCompletedNotification',
                                       'type' => 'ExportProcessCompletedNotification',),
                              ),
                         ),
                     ),
                ),
                array('cells' =>
                     array(
                         array(
                             'elements' => array(
                                 array('attributeName' => 'enableEmailMessageArchivingEmailAddressNotMatchingNotification',
                                       'type' => 'EmailMessageArchivingEmailAddressNotMatchingNotification',),
                              ),
                         ),
                     ),
                ),
            );
            if($user->isSuperAdministrator())
            {
                return array_merge($tableHeadRow, $superAdministratorRows, $regularUserRows);
            }
            else
            {
                return array_merge($tableHeadRow, $regularUserRows);
            }
        }
    }
?>