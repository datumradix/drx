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

    /*
     * Class that converts MergeTags for Contact based EmailTemplate to relevant fields
     */
    class ContactMergeTagsUtil extends MergeTagsUtil
    {
        public static $contactAttributesMap = array(
            'COMPANY^NAME'           => 'companyname',
//            'CREATED^DATE^TIME'          => 'createddatetime',
//            'MODIFIED^DATE^TIME'          => 'modifieddatetime',
            'DEPARTMENT'          => 'department',
            'DESCRIPTION'            => 'description',
            'FIRST^NAME'         => 'firstname',
            'LAST^NAME'         => 'lastname',
//            'INDUSTRY' => 'industry',
//            'JOB^TITLE' => 'jobTitle',
            'MOBILE^PHONE' => 'mobilephone',
            'OFFICE^FAX'              => 'officefax',
            'OFFICE^PHONE'              => 'officephone',
 //           'TITLE'              => 'title',
 //           'SOURCE'              => 'source',
 //           'STATE'              => 'state',
            'WEBSITE'              => 'website',
        );

        public static $contactAddressesMap = array(
            'primary' => array(
                'PRIMARY^ADDRESS__CITY' => 'city',
                'PRIMARY^ADDRESS__COUNTRY' => 'country',
                'PRIMARY^ADDRESS__INVALID' => 'invalid',
                'PRIMARY^ADDRESS__LATITUDE' => 'latitude',
                'PRIMARY^ADDRESS__LONGITUDE' => 'longitude',
                'PRIMARY^ADDRESS__POSTAL^CODE' => 'postalcode',
                'PRIMARY^ADDRESS__STATE' => 'state',
                'PRIMARY^ADDRESS__STREET1' => 'street1',
                'PRIMARY^ADDRESS__STREET2' => 'street2',
            ),
            'secondary' => array(
                'SECONDARY^ADDRESS__CITY' => 'city',
                'SECONDARY^ADDRESS__COUNTRY' => 'country',
                'SECONDARY^ADDRESS__INVALID' => 'invalid',
                'SECONDARY^ADDRESS__LATITUDE' => 'latitude',
                'SECONDARY^ADDRESS__LONGITUDE' => 'longitude',
                'SECONDARY^ADDRESS__POSTAL^CODE' => 'postalcode',
                'SECONDARY^ADDRESS__STATE' => 'state',
                'SECONDARY^ADDRESS__STREET1' => 'street1',
                'SECONDARY^ADDRESS__STREET2' => 'street2',
            ),
        );

        public static $contactEmailsMap = array(
            'primary' => array(
                'PRIMARY^EMAIL__EMAIL^ADDRESS' => 'emailaddress',
                'PRIMARY^EMAIL__IS^INVALID' => 'isinvalid',
                'PRIMARY^EMAIL__OPT^OUT' => 'optout',
            ),
            'secondary' => array(
                'SECONDARY^EMAIL__EMAIL^ADDRESS' => 'emailaddress',
                'SECONDARY^EMAIL__IS^INVALID' => 'isinvalid',
                'SECONDARY^EMAIL__OPT^OUT' => 'optout',
            ),
        );

        public static $contactOwnerMap = array(
            'user' => array(
                //'OWNER__TITLE' => 'department',
                'OWNER__FIRST^NAME' => 'firstname',
                'OWNER__LAST^NAME' => 'lastname',
                'OWNER__USERNAME' => 'username',
                'OWNER__DEPARTMENT' => 'department',
                'OWNER__JOB^TITLE' => 'jobtitle',
                'OWNER__MOBILE^PHONE' => 'mobilephone',
                'OWNER__OFFICE^FAX' => 'officefax',
                'OWNER__OFFICE^PHONE' => 'officephone',
            ),
            'email' => array (
                'OWNER__PRIMARY^EMAIL__EMAIL^ADDRESS' => 'emailaddress',
                'OWNER__PRIMARY^EMAIL__IS^INVALID' => 'isinvalid',
                'OWNER__PRIMARY^EMAIL__OPT^OUT' => 'optout',
            )
        );

        public static $accountMap = array(
            'account' => array(
                //'ACCOUNT__INDUSTRY' => 'industry',
                'ACCOUNT__NAME' => 'name',
                'ACCOUNT__OFFICE^FAX' => 'officefax',
                'ACCOUNT__OFFICE^PHONE' => 'officephone',
                'ACCOUNT__WEBSITE' => 'website',
                //'ACCOUNT__TYPE' => 'type',
                'OWNER__OFFICE^FAX' => 'officefax',
                'OWNER__OFFICE^PHONE' => 'officephone',
            ),
            'billingAddress' => array(
                'ACCOUNT__BILLING^ADDRESS__CITY' => 'city',
                'ACCOUNT__BILLING^ADDRESS__COUNTRY' => 'country',
                'ACCOUNT__BILLING^ADDRESS__INVALID' => 'invalid',
                'ACCOUNT__BILLING^ADDRESS__LATITUDE' => 'latitude',
                'ACCOUNT__BILLING^ADDRESS__LONGITUDE' => 'longitude',
                'ACCOUNT__BILLING^ADDRESS__POSTAL^CODE' => 'postalcode',
                'ACCOUNT__BILLING^ADDRESS__STATE' => 'state',
                'ACCOUNT__BILLING^ADDRESS__STREET1' => 'street1',
                'ACCOUNT__BILLING^ADDRESS__STREET2' => 'street2',
            ),
            'primaryEmail' => array(
                'ACCOUNT__PRIMARY^EMAIL__EMAIL^ADDRESS' => 'emailaddress',
                'ACCOUNT__PRIMARY^EMAIL__IS^INVALID' => 'isinvalid',
                'ACCOUNT__PRIMARY^EMAIL__OPT^OUT' => 'optout',
            ),
            'owner' => array(
                'ACCOUNT__OWNER__FIRST^NAME' => 'firstname',
                'ACCOUNT__OWNER__LAST^NAME' => 'lastname',
                'ACCOUNT__OWNER__USERNAME' => 'username',
                'ACCOUNT__OWNER__DEPARTMENT' => 'department',
                'ACCOUNT__OWNER__JOB^TITLE' => 'jobtitle',
                'ACCOUNT__OWNER__MOBILE^PHONE' => 'mobilephone',
                'ACCOUNT__OWNER__OFFICE^FAX' => 'officefax',
                'ACCOUNT__OWNER__OFFICE^PHONE' => 'officephone',
                'ACCOUNT__PRIMARY^EMAIL__EMAIL^ADDRESS' => 'emailaddress',
                'ACCOUNT__PRIMARY^EMAIL__IS^INVALID' => 'isinvalid',
                'ACCOUNT__PRIMARY^EMAIL__OPT^OUT' => 'optout',
            )
        );



        /**
         * @param $model
         * @param array $invalidTags
         * @param null $language
         * @param int $errorOnFirstMissing
         * @param array $params
         * @return bool|string
         */
        public function resolveMergeTagsUsingRawSql($modelId, & $invalidTags = array(), $language = null,
                                         $errorOnFirstMissing = MergeTagsToModelAttributesAdapter::DO_NOT_ERROR_ON_FIRST_INVALID_TAG,
                                         $params = array())
        {
            if (!isset($language))
            {
                $language = $this->language;
            }

            if (!$this->extractMergeTagsPlaceHolders() ||
                $this->resolveMergeTagsArrayToAttributesUsingRawSql($modelId, $invalidTags, $language, $errorOnFirstMissing, $params) &&
                $this->resolveMergeTagsInTemplateToAttributes())
            {
                return $this->content;
            }
            else
            {
                return false;
            }
        }

        protected function resolveMergeTagsArrayToAttributesUsingRawSql($modelId, & $invalidTags = array(), $language = null,
                                                                        $errorOnFirstMissing = MergeTagsToModelAttributesAdapter::DO_NOT_ERROR_ON_FIRST_INVALID_TAG,
                                                                        $params = array())
        {
            $sql = 'SELECT contact.*, person.*, contactstate.name as contact_state_name, ownedsecurableitem.owner__user_id, item.createddatetime,
                    item.modifieddatetime, item.createdbyuser__user_id, item.modifiedbyuser__user_id FROM contact';
            $sql .= ' LEFT JOIN person ON person.id = contact.person_id';
            $sql .= ' LEFT JOIN ownedsecurableitem ON ownedsecurableitem.id = person.ownedsecurableitem_id';
            $sql .= ' LEFT JOIN securableitem ON securableitem.id = ownedsecurableitem.securableitem_id';
            $sql .= ' LEFT JOIN item on item.id = securableitem.item_id';
            $sql .= ' LEFT JOIN contactstate on contactstate.id = contact.state_contactstate_id';
            $sql .= ' WHERE contact.id = ' . intval($modelId);
            $contactRow = ZurmoRedBean::getRow($sql);

            $resolvedMergeTags   = array();
            $primaryEmailRow     = false;
            $secondaryEmailRow   = false;
            $primaryAddressRow   = false;
            $secondaryAddressRow = false;
            $contactOwnerRow     = false;
            $accountRow          = false;
            $accountBillingAddressRow = false;
            $accountPrimaryEmailAddressRow = false;
            $accountOwnerRow = false;
            foreach ($this->mergeTags[1] as $mergeTag)
            {
                $resolvedValue = '';
                $isValidTag = false;
                $attributeAccessorString    = MergeTagsToModelAttributesAdapter::resolveStringToAttributeAccessor($mergeTag);
                $attributeName = strtok($attributeAccessorString, '->');
                $timeQualifier              = MergeTagsToModelAttributesAdapter::stripTimeDelimiterAndReturnQualifier($attributeAccessorString);
                if (SpecialMergeTagsAdapter::isSpecialMergeTag($attributeName, $timeQualifier))
                {
                    $resolvedValue = SpecialMergeTagsAdapter::resolve($attributeName, null,
                        MergeTagsToModelAttributesAdapter::DO_NOT_ERROR_ON_FIRST_INVALID_TAG,
                        array('modelClassName' => 'Contact', 'modelId' => $modelId, 'stateName' => $contactRow['contact_state_name']));
                    $isValidTag = true;
               }

                if (!$isValidTag && array_key_exists($mergeTag, self::$contactAttributesMap))
                {
                    $resolvedValue = $contactRow[self::$contactAttributesMap[$mergeTag]];
                    $isValidTag = true;
                }
                if (!$isValidTag && array_key_exists($mergeTag, self::$contactEmailsMap['primary']) && $contactRow['primaryemail_email_id'] > 0)
                {
                    if ($primaryEmailRow === false)
                    {
                        $sql = 'SELECT * FROM email where id = ' . $contactRow['primaryemail_email_id'];
                        $primaryEmailRow = ZurmoRedBean::getRow($sql);
                    }
                    $resolvedValue = $primaryEmailRow[self::$contactEmailsMap['primary'][$mergeTag]];
                    $isValidTag = true;
                }

                if (!$isValidTag && array_key_exists($mergeTag, self::$contactEmailsMap['secondary']) && $contactRow['secondaryemail_email_id'] > 0)
                {
                    if ($secondaryEmailRow === false)
                    {
                        $sql = 'SELECT * FROM email where id = ' . $contactRow['secondaryemail_email_id'];
                        $secondaryEmailRow = ZurmoRedBean::getRow($sql);
                    }
                    $resolvedValue = $primaryEmailRow[self::$contactEmailsMap['secondary'][$mergeTag]];
                    $isValidTag = true;
                }

                if (!$isValidTag && array_key_exists($mergeTag, self::$contactAddressesMap['primary']) && $contactRow['primaryaddress_address_id'] > 0)
                {
                    if ($primaryAddressRow === false)
                    {
                        $sql = 'SELECT * FROM address where id = ' . $contactRow['primaryaddress_address_id'];
                        $primaryAddressRow = ZurmoRedBean::getRow($sql);
                    }
                    $resolvedValue = $primaryAddressRow[self::$contactAddressesMap['primary'][$mergeTag]];
                    $isValidTag = true;
                }

                if (!$isValidTag && array_key_exists($mergeTag, self::$contactAddressesMap['secondary']) && $contactRow['secondaryaddress_address_id'] > 0)
                {
                    if ($secondaryAddressRow === false)
                    {
                        $sql = 'SELECT * FROM address where id = ' . $contactRow['secondaryaddress_address_id'];
                        $secondaryAddressRow = ZurmoRedBean::getRow($sql);
                    }
                    $resolvedValue = $secondaryAddressRow[self::$contactAddressesMap['secondary'][$mergeTag]];
                    $isValidTag = true;
                }

                if (!$isValidTag &&
                    (array_key_exists($mergeTag, self::$contactOwnerMap['user']) ||
                        array_key_exists($mergeTag, self::$contactOwnerMap['email'])
                    ))
                {
                    if ($contactOwnerRow === false)
                    {
                        $sql = 'SELECT _user.*, person.*, email.* FROM _user';
                        $sql .= " LEFT JOIN person ON person.id = _user.person_id";
                        $sql .= " LEFT JOIN email ON email.id = person.primaryemail_email_id";
                        $sql .= ' WHERE _user.id = ' . $contactRow['owner__user_id'];

                        $contactOwnerRow = ZurmoRedBean::getRow($sql);
                    }
                    if (array_key_exists($mergeTag, self::$contactOwnerMap['user']))
                    {
                        $resolvedValue = $contactOwnerRow[self::$contactOwnerMap['user'][$mergeTag]];
                    }
                    else
                    {
                        $resolvedValue = $contactOwnerRow[self::$contactOwnerMap['email'][$mergeTag]];
                    }
                    $isValidTag = true;
                }

                if (!$isValidTag &&
                    (array_key_exists($mergeTag, self::$accountMap['account']) ||
                        array_key_exists($mergeTag, self::$accountMap['billingAddress']) ||
                        array_key_exists($mergeTag, self::$accountMap['primaryEmail']) ||
                        array_key_exists($mergeTag, self::$accountMap['owner'])
                    ))
                {
                    if ($accountRow === false)
                    {
                        $sql = 'SELECT account.*,  ownedsecurableitem.owner__user_id FROM account';
                        $sql .= ' LEFT JOIN ownedsecurableitem ON ownedsecurableitem.id = account.ownedsecurableitem_id';
                        $sql .= ' WHERE account.id = ' . intval($contactRow['account_id']);
                        $accountRow = ZurmoRedBean::getRow($sql);
                    }

                    if (array_key_exists($mergeTag, self::$accountMap['account']))
                    {
                        $resolvedValue = $accountRow[self::$accountMap['account'][$mergeTag]];
                        $isValidTag = true;
                    }
                    elseif (array_key_exists($mergeTag, self::$accountMap['billingAddress']) && $accountRow['billingaddress_address_id'] > 0)
                    {
                        if ($accountBillingAddressRow === false)
                        {
                            $sql = 'SELECT * FROM address where id = ' . $accountRow['billingaddress_address_id'];
                            $accountBillingAddressRow = ZurmoRedBean::getRow($sql);
                        }
                        if (isset($accountBillingAddressRow[self::$accountMap['billingAddress'][$mergeTag]]))
                        {
                            $resolvedValue = $accountBillingAddressRow[self::$accountMap['billingAddress'][$mergeTag]];
                        }
                        $isValidTag = true;
                    }
                    elseif (array_key_exists($mergeTag, self::$accountMap['primaryEmail']) && $accountRow['primaryemail_email_id'] > 0)
                    {
                        if ($accountPrimaryEmailAddressRow === false)
                        {
                            $sql = 'SELECT * FROM email where id = ' . $accountRow['primaryemail_email_id'];
                            $accountBillingAddressRow = ZurmoRedBean::getRow($sql);
                        }
                        if (isset($accountPrimaryEmailAddressRow[self::$accountMap['primaryEmail'][$mergeTag]]))
                        {
                            $resolvedValue = $accountPrimaryEmailAddressRow[self::$accountMap['primaryEmail'][$mergeTag]];
                        }
                        $isValidTag = true;
                    }
                    else
                    {
                        if ($accountOwnerRow === false)
                        {
                            $sql = 'SELECT _user.*, person.*, email.* FROM _user';
                            $sql .= " LEFT JOIN person ON person.id = _user.person_id";
                            $sql .= " LEFT JOIN email ON email.id = person.primaryemail_email_id";
                            $sql .= ' WHERE _user.id = ' . $contactRow['owner__user_id'];
                            $contactOwnerRow = ZurmoRedBean::getRow($sql);
                        }
                        if (array_key_exists($mergeTag, self::$accountMap['owner']))
                        {
                            $resolvedValue = $contactOwnerRow[self::$accountMap['owner'][$mergeTag]];
                        }
                        $isValidTag = true;
                    }
                }

                if ($isValidTag)
                {
                    $resolvedMergeTags[$mergeTag] = $resolvedValue;
                }
                else
                {
                    $invalidTags[] = $mergeTag;
                }
            }
            $this->mergeTags[1] = $resolvedMergeTags;
            return true;
        }
        /**
         * @param $model
         * @param array $invalidTags
         * @param null $language
         * @param int $errorOnFirstMissing
         * @param array $params
         * @return bool
         */
        public function resolveMergeTagsArrayToAttributes($model, & $invalidTags = array(), $language = null,
                                                          $errorOnFirstMissing = MergeTagsToModelAttributesAdapter::DO_NOT_ERROR_ON_FIRST_INVALID_TAG,
                                                          $params = array())
        {
            $mergeTagsToAttributes  = false;
            if (!$language)
            {
                $language = $this->language;
            }
            if (!empty($this->mergeTags))
            {
                $mergeTagsToAttributes = MergeTagsToModelAttributesAdapter::
                resolveMergeTagsArrayToAttributesFromModel($this->mergeTags[1], $model,
                    $invalidTags, $language,
                    $errorOnFirstMissing, $params);
            }
            return $mergeTagsToAttributes;
        }
    }
?>