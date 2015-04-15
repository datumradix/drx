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
     * ElevateCommand allows a user to be elevated to the 'root' user. There can only be one root
     * user in the application.
     */
    class ElevateCommand extends CConsoleCommand
    {
        public function getHelp()
        {
            return <<<EOD
    USAGE
      zurmoc elevate <username>

    DESCRIPTION
      This command will elevate the specified user to 'root' unless there is already a root user.  The user specified
      must be in the super administrator group.

    PARAMETERS
     * username: username to elevate to root.
EOD;
    }

        /**
         * Execute the action.
         * @param array command line parameters specific for this command
         */
        public function run($args)
        {
            set_time_limit('900');
            if (!isset($args[0]))
            {
                $this->usageError('A username must be specified.');
            }
            try
            {
                Yii::app()->user->userModel = User::getByUsername($args[0]);
            }
            catch (NotFoundException $e)
            {
                $this->usageError('The specified username does not exist.');
            }
            $group = Group::getByName(Group::SUPER_ADMINISTRATORS_GROUP_NAME);
            if (!$group->users->contains(Yii::app()->user->userModel))
            {
                $this->usageError('The specified user is not a super administrator.');
            }
            if (User::getRootUserCount() > 0)
            {
                echo 'There is already a root user. A new one cannot be specified.';
                Yii::app()->end();
            }
            Yii::app()->user->userModel->setIsRootUser();
            Yii::app()->user->userModel->hideFromSelecting   = true;
            Yii::app()->user->userModel->hideFromLeaderboard = true;
            $saved = Yii::app()->user->userModel->save();
            if (!$saved)
            {
                throw new FailedToSaveModelException();
            }
            $template        = "{message}\n";
            $messageStreamer = new MessageStreamer($template);
            $messageStreamer->setExtraRenderBytes(0);
            $messageStreamer->add('');
            $messageStreamer->add(Zurmo::t('Commands', 'User with username {username} elevated to root.',
                                                        array('{username}' => Yii::app()->user->userModel->username)));
        }
    }
?>