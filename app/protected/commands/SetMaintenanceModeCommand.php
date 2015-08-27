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
     * Reset stuck jobs
     */
    class SetMaintenanceModeCommand extends CConsoleCommand
    {
        public function getHelp()
        {
            return <<<EOD
    USAGE
      zurmoc setMaintenanceMode <username> <maintenanceMode>

    DESCRIPTION
      This command will set maintenance mode in perInstance.php file

    PARAMETERS
     * username: username which run command.
     * maintenanceMode: 0 or 1
EOD;
        }

        /**
         * Execute the action
         * @param array $args - command line parameters specific for this command
         * @return int|void
         */
        public function run($args)
        {
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

            if (!isset($args[1]))
            {
                $this->usageError('You must provide value for maintenance mode!');
            }
            else
            {
                if ($args[1] == 1 || $args[1] == '1' || $args[1] == 'true')
                {
                    $maintenanceMode = true;
                }
                elseif ($args[1] == 0 || $args[1] == '0' || $args[1] == 'false')
                {
                    $maintenanceMode = false;
                }
                else
                {
                    $this->usageError('You must provide value for maintenance mode!');
                }
            }
            if (defined('IS_TEST'))
            {
                $perInstanceConfigFile     = INSTANCE_ROOT . '/protected/config/perInstanceTest.php';
            }
            else
            {
                $perInstanceConfigFile     = INSTANCE_ROOT . '/protected/config/perInstance.php';
            }
            $result = $this->setMaintenanceModeInPerInstanceFile($perInstanceConfigFile, $maintenanceMode);
            if ($result)
            {
                echo 'MaintenanceMode set successfully.' . "\n";
            }
            else
            {
                echo 'MaintenanceMode not set. Please set it manually in perInstance.php file.' . "\n";
            }
        }

        /**
         * @param string $perInstanceConfigFile
         * @param bool $value
         * @return bool
         */
        protected function setMaintenanceModeInPerInstanceFile($perInstanceConfigFile, $value)
        {
            if (is_bool($value))
            {
                $contents = file_get_contents($perInstanceConfigFile);
                if ($value)
                {
                    $contents = preg_replace('/\$maintenanceMode\s*=\s*false;/',
                        '$maintenanceMode = ' . var_export($value, true) . ';',
                        $contents);
                }
                else
                {
                    $contents = preg_replace('/\$maintenanceMode\s*=\s*true;/',
                        '$maintenanceMode = ' . var_export($value, true) . ';',
                        $contents);
                }
                if ($contents)
                {
                    file_put_contents($perInstanceConfigFile, $contents);
                    return true;
                }
            }
            return false;
        }
    }
?>