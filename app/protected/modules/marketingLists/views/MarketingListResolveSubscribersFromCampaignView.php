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
     * View to display to users upon first login.  Allows them to confirm their timezone.
     */
    class MarketingListResolveSubscribersFromCampaignView extends EditView
    {
        const NOTIFICATION_BAR_ID                           = 'FlashMessageBar';

        public static function getDefaultMetadata()
        {
            $metadata = array(
                'global' => array(
                    'toolbar' => array(
                        'elements' => array(
                            array('type' => 'SaveButton', 'label' => 'Retarget'),
                        ),
                    ),
                    'panelsDisplayType' => FormLayout::PANELS_DISPLAY_TYPE_ALL,
                    'panels' => array(
                        array(
                            'rows' => array(
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'marketingList', 'type' => 'MarketingList'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                    array(
                                        array(
                                            'elements' => array(
                                                array('attributeName' => 'newMarketingListName', 'type' => 'Text'),
                                            ),
                                        ),
                                    )
                                ),
                                array('cells' =>
                                          array(
                                              array(
                                                  'elements' => array(
                                                      array('attributeName' => 'retargetOpenedEmailRecipients', 'type' => 'CheckBox'),
                                                  ),
                                              ),
                                          )
                                ),
                                array('cells' =>
                                          array(
                                              array(
                                                  'elements' => array(
                                                      array('attributeName' => 'retargetClickedEmailRecipients', 'type' => 'CheckBox'),
                                                  ),
                                              ),
                                          )
                                ),
                                array('cells' =>
                                          array(
                                              array(
                                                  'elements' => array(
                                                      array('attributeName' => 'retargetNotViewedEmailRecipients', 'type' => 'CheckBox'),
                                                  ),
                                              ),
                                          )
                                ),
                                array('cells' =>
                                          array(
                                              array(
                                                  'elements' => array(
                                                      array('attributeName' => 'retargetNotClickedEmailRecipients', 'type' => 'CheckBox'),
                                                  ),
                                              ),
                                          )
                                ),
                            ),
                        ),
                    ),
                ),
            );
            return $metadata;
        }

        protected function renderAfterFormLayout($form)
        {
            parent::renderAfterFormLayout($form);
            $this->registerSubscribeCampaignItemsToMarketingListScript($form);
            $this->registerUpdateFlashBarScript($form);
        }

        protected function resolveActiveFormAjaxValidationOptions()
        {
            $ajaxValidationOptions = array('enableAjaxValidation' => true,
                                           'clientOptions'        => array(
                                               'validateOnSubmit'  => true,
                                               'validateOnChange'  => false,
                                               'beforeValidate'    => 'js:$(this).beforeValidateAction',
                                               'afterValidate'     => 'js:$(this).afterValidateAjaxAction',
                                               'afterValidateAjax' => '',
                                               'inputContainer'    => 'td',
                                           ));
            return array_merge($ajaxValidationOptions,
                array('action' => $this->getSubscribeUrl()));
        }

        protected function getValidateAndSaveUrl()
        {
            return Yii::app()->createUrl('/marketingLists/default/attemptToValidateAjaxSubscriberFormFromPost', $_GET);
        }

        protected function registerSubscribeCampaignItemsToMarketingListScript($form)
        {
            // Begin Not Coding Standard
            $unloadWindowMessage = Zurmo::t('MarketingListsModule',
                'Are you sure you want to leave this page, remaining contacts will not be retargeted.');
            $script = '$("#' . $form->getId() . '").submit(function(event){
                           event.preventDefault();
                           subscribeCampaignItemsToMarketingList(event, 0, 0, 0);
                           return false;
                       });

                        function subscribeCampaignItemsToMarketingList(event, page, subscribedCount, skippedCount, marketingListId) {
                            marketingListId = marketingListId || null; // set default value
                            var notificationBarId   = "' . static::NOTIFICATION_BAR_ID . '";
                            var url                 = "' . $this->getSubscribeUrl() . '&page=" + page + "&subscribedCount=" + subscribedCount + "&skippedCount=" + skippedCount;
                            if (marketingListId)
                            {
                                url = url + "&marketingListId=" + marketingListId;
                            }
                            var event               = event;
                            var page                = page;
                            var subscribedCount     = subscribedCount;
                            var skippedCount        = skippedCount;
                            var formId = "' . $form->getId() . '";
                            $.ajax(
                                {
                                    url:        url,
                                    dataType:   "json",
                                    type:       "post",
                                    data:       $("#" + formId).serialize(),
                                    beforeSend: function(request, settings)
                                                {
                                                    $(window).off("beforeunload");
                                                    $(window).on("beforeunload", function(){
                                                        return "' . $unloadWindowMessage . '";
                                                    });
                                                    event.preventDefault();
                                                    $(this).makeLargeLoadingSpinner(true,  $("#' . $form->getId() . '").parent());
                                                },
                                    success:    function(data, status, request)
                                                {
                                                    updateFlashBar(data, notificationBarId);
                                                    if (data.nextPage)
                                                    {
                                                        subscribeCampaignItemsToMarketingList(event, data.nextPage, data.subscribedCount, data.skippedCount, data.marketingListId);
                                                    }
                                                    else if (data.redirectUrl)
                                                    {
                                                        $(window).off("beforeunload")
                                                        window.location = data.redirectUrl;
                                                    }
                                                },
                                    error:      function(request, status, error)
                                                {
                                                    var data = {' .
                                                                '   "message" : "' .
                                                                    Zurmo::t('Core',
                                                                        'There was an error processing your request'). '",
                                                                    "type"    : "error"
                                                                };
                                                    updateFlashBar(data, notificationBarId);
                                                    $(window).off("beforeunload");
                                                },
                                    complete:   function(request, status)
                                                {
                                                    $(this).makeLargeLoadingSpinner(false,  $("#' . $form->getId() . '").parent());
                                                    $("#' . $form->getId() . ' span.z-spinner").text("");
                                                    return false;
                                                }
                                }
                            );
                        }';
            // End Not Coding Standard
            Yii::app()->clientScript->registerScript('SubscribeCampaignItemsToMarketingList', $script);
        }

        protected function registerUpdateFlashBarScript()
        {
            $scriptName = $this->getId() . '-updateFlashBar';
            if (Yii::app()->clientScript->isScriptRegistered($scriptName))
            {
                return;
            }
            else
            {
                Yii::app()->clientScript->registerScript($scriptName, '
                    function updateFlashBar(data, flashBarId)
                    {
                        $("#" + flashBarId).jnotifyAddMessage(
                        {
                            text: data.message,
                            permanent: true,
                            showIcon: true,
                            type: data.type,
                            removeExisting: true
                        });
                    }
                ');
            }
        }

        protected function getSubscribeUrl()
        {
            return Yii::app()->createUrl('/marketingLists/default/saveSubscribersFromCampaign', $_GET);
        }

        protected function getAfterClickSubmitButtonScript()
        {
            return "subscribeCampaignItemsToMarketingList(event, 0, 0, 0);";
        }

        protected function resolveElementDuringFormLayoutRender(& $element)
        {
            if ($element->getAttribute() == 'marketingList')
            {
                $title    = Zurmo::t('MarketingListsModule', 'Add subscribers to existing marketing list. Please select either existing marketing list or enter name for new one, but not both!');
                $content  = '<span id="existing-marketing-list-tooltip" class="tooltip"  title="' . $title . '">?</span>';
                $qtip     = new ZurmoTip();
                $qtip->addQTip("#existing-marketing-list-tooltip");
                $element->editableTemplate = '<th>{label}' . $content . '</th>' .
                    '<td colspan="{colspan}">{content}{error}</td>';
            }
            elseif ($element->getAttribute() == 'newMarketingListName')
            {
                $title    = Zurmo::t('MarketingListsModule', 'Create new marketing list and add subscribers to it. Please select either existing marketing list or enter name for new one, but not both!');
                $content  = '<span id="new-marketing-list-tooltip" class="tooltip"  title="' . $title . '">?</span>';
                $qtip     = new ZurmoTip();
                $qtip->addQTip("#new-marketing-list-tooltip");
                $element->editableTemplate = '<th>{label}' . $content . '</th>' .
                    '<td colspan="{colspan}">{content}{error}</td>';
            }
        }
    }
?>
