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

    class ExistingMarketingListOrNewMarketingListNameRequiredValidator extends CValidator
    {
        public $marketingListPropertyName        = 'marketingList';

        public $newMarketingListNamePropertyName = 'newMarketingListName';

        /**
         * Validates the attribute of the model.
         * If there is any error, the error message is added to the model.
         * @param RedBeanModel $object the model being validated
         * @param string $attribute the attribute being validated
         * @return boolean true if validation passes
         */
        protected function validateAttribute($object, $attribute)
        {
            $marketingList        = $this->marketingListPropertyName;
            $newMarketingListName = $this->newMarketingListNamePropertyName;
            // $attribute == $marketingList so we don't add duplicate error message.
            if (
                (
                    (empty($object->$marketingList) || !isset($object->{$marketingList}['id']) || empty($object->{$marketingList}['id'])) &&
                    empty($object->$newMarketingListName)
                ) && ($attribute == $marketingList))
            {
                if ($this->message !== null)
                {
                    $message = $this->message;
                }
                else
                {
                    $message = $this->resolveErrorMessageWhenNothingSelected($object);
                }
                $this->addError($object, $attribute, $message);
            }
            elseif (
                (
                    (isset($object->$marketingList) && isset($object->{$marketingList}['id']) && $object->{$marketingList}['id'] != null) &&
                    $object->$newMarketingListName != null
                ) && ($attribute == $marketingList))
            {
                if ($this->message !== null)
                {
                    $message = $this->message;
                }
                else
                {
                    $message = $this->resolveErrorMessageWhenBothSelected($object);
                }
                $this->addError($object, $attribute, $message);
            }
            return true;
        }

        /**
         * Error message when none of options selected
         * @param $object
         * @return string
         */
        protected function resolveErrorMessageWhenNothingSelected($object)
        {
            return Zurmo::t('MarketingListsModule', 'Please select existing marketing list or enter name for new marketing list.');
        }

        /**
         * Error message wehn both options selected
         * @param $object
         * @return string
         */
        protected function resolveErrorMessageWhenBothSelected($object)
        {
            return Zurmo::t('MarketingListsModule', 'Please select either existing marketing list or enter name for new marketing list, but not both.');
        }
    }
?>