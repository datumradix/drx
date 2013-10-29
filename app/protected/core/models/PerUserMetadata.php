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
     * Holds metadata for a class on a per user basis.
     * @see GlobalMetadata
     */
    class PerUserMetadata extends RedBeanModel
    {
        /**
         * Get by specifying a class name and user. The class name is unique
         * and the data is saved in such a way that only one record will be
         * created per class name / user combination.
         * @see GlobalMetadata
         */
        public static function getByClassNameAndUser($className, User $user)
        {
            assert('is_string($className)');
            assert('$className != ""');
            $bean = ZurmoRedBean::findOne('perusermetadata', "className = '$className' and _user_id = {$user->id}");
            assert('$bean === false || $bean instanceof RedBean_OODBBean');
            if ($bean === false)
            {
                throw new NotFoundException();
            }
            return self::makeModel($bean);
        }

        public static function getDefaultMetadata()
        {
            $metadata = parent::getDefaultMetadata();
            $metadata[__CLASS__] = array(
                'members' => array(
                    'className',
                    'serializedMetadata',
                ),
                'relations' => array(
                    'user' => array(static::HAS_ONE, 'User'),
                ),
                'rules' => array(
                    array('className',           'required'),
                    array('className',           'type', 'type' => 'string'),
                    array('className',          'length', 'max'  => 64),
                    array('serializedMetadata',  'required'),
                    array('serializedMetadata',  'type', 'type' => 'string'),
                    array('user',                'required'),
                )
            );
            return $metadata;
        }
    }
?>
