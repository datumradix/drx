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
     * Helper class for working with products
     */
    class ProductsUtil
    {
        /**
         * @return string
         */
        public static function getModalContainerId()
        {
            return ModalContainerView::ID;
        }

        /**
         * @param $relationModelId
         * @return string
         */
        public static function resolveModalSaveActionNameForByRelationModelId($relationModelId)
        {
            assert('is_string($relationModelId) || is_int($relationModelId) || $relationModelId == null');
            if ($relationModelId != null)
            {
                return 'modalSaveFromRelation';
            }
            else
            {
                return 'modalSave';
            }
        }

        public static function resolveExtraCloseScriptForModalAjaxOptions($sourceId = null)
        {
            assert('is_string($sourceId) || $sourceId == null');
            if ($sourceId != null)
            {
                return "$('#{$sourceId}').yiiGridView('update');";
            }
        }

        /**
         * @return string
         */
        public static function getModalDetailsTitle()
        {
            $params = LabelUtil::getTranslationParamsForAllModules();
            $title = Zurmo::t('ProductsModule', 'ProductsModuleSingularLabel Details', $params);
            return $title;
        }

        /**
         * @return string
         */
        public static function getModalEditTitle()
        {
            $params = LabelUtil::getTranslationParamsForAllModules();
            $title = Zurmo::t('ProductsModule', 'Edit ProductsModuleSingularLabel', $params);
            return $title;
        }

        /**
         * Register product modal edit script
         * @param string $sourceId
         * @param array $routeParams
         */
        public static function registerProductModalEditScript($sourceId, $routeParams)
        {
            assert('is_string($sourceId)');
            assert('is_array($routeParams)');
            $modalId     = ProductsUtil::getModalContainerId();
            $url         = Yii::app()->createUrl('products/default/modalEdit', $routeParams);
            $script      = self::registerProductModalScript("Edit", $url, '.edit-related-product', $sourceId);
            Yii::app()->clientScript->registerScript('productModalEditScript', $script, ClientScript::POS_END);
        }

        /**
         * Get product modal script
         * @param string $type
         * @param string $url
         * @param string $selector
         * @param mixed $sourceId
         * @return string
         */
        public static function registerProductModalScript($type, $url, $selector, $sourceId = null)
        {
            assert('is_string($type)');
            assert('is_string($url)');
            assert('is_string($selector)');
            assert('is_string($sourceId) || $sourceId == null');
            $modalId     = ProductsUtil::getModalContainerId();
            $ajaxOptions = ProductsUtil::resolveAjaxOptionsForModalView($type, $sourceId);
            $ajaxOptions['beforeSend'] = new CJavaScriptExpression($ajaxOptions['beforeSend']);
            return "$(document).on('click', '{$selector}', function()
                         {
                            var id = $(this).attr('id');
                            var idParts = id.split('-');
                            var productId = parseInt(idParts[1]);
                            $.ajax(
                            {
                                'type' : 'GET',
                                'url'  : '{$url}' + '&id=' + productId,
                                'beforeSend' : {$ajaxOptions['beforeSend']},
                                'update'     : '{$ajaxOptions['update']}',
                                'success': function(html){
                                    jQuery('#{$modalId}').html(html);
                                }
                            });
                          }
                        );";
        }

        /**
         * @param $renderType
         * @param string|null $sourceId
         * @return array
         */
        public static function resolveAjaxOptionsForModalView($renderType, $sourceId = null)
        {
            assert('is_string($renderType)');
            $title = self::getModalTitleForProduct($renderType);
            if ($renderType == "Details")
            {
                $extraCloseScriptForModalAjaxOptions = null;
            }
            else
            {
                $extraCloseScriptForModalAjaxOptions = static::resolveExtraCloseScriptForModalAjaxOptions($sourceId);
            }
            return   ModalView::getAjaxOptionsForModalLink($title, self::getModalContainerId(), 'auto', 600,
                'center top+25', $class = "'product-dialog'", // Not Coding Standard
                $extraCloseScriptForModalAjaxOptions);
        }

        /**
         * Gets modal title for create product modal window
         * @param string $renderType
         * @return string
         */
        public static function getModalTitleForProduct($renderType = "Create")
        {
            $params = LabelUtil::getTranslationParamsForAllModules();
            if ($renderType == "Create")
            {
                $title = Zurmo::t('ProductsModule', 'Create ProductsModuleSingularLabel', $params);
            }
            elseif ($renderType == "Details")
            {
                $title = Zurmo::t('ProductsModule', 'ProductsModuleSingularLabel Details', $params);
            }
            else
            {
                $title = Zurmo::t('ProductsModule', 'Edit ProductsModuleSingularLabel', $params);
            }
            return $title;
        }

        /**
         * Resolve redirect url in case of product actions on details and relations view.
         * This is required else same params get added to create url.
         * @param $redirectUrl
         * @return string
         */
        public static function resolveProductsActionsRedirectUrlForDetailsAndRelationsView($redirectUrl)
        {
            if ($redirectUrl != null)
            {
                $routeData      = explode('?', $redirectUrl);
                if (count($routeData) > 1)
                {
                    $queryData      = explode('&', $routeData[1]);
                    foreach ($queryData as $val)
                    {
                        if (strpos($val, 'id=') !== false)
                        {
                            $routeData[1] = $val;
                            break;
                        }
                    }
                }
                $redirectUrl = implode('?', $routeData);
            }
            return $redirectUrl;
        }

        /**
         * @param Product $product
         * @param $controllerId
         * @param $moduleId
         * @return null|string
         */
        public static function getModalDetailsLink(Product $product,
                                                   $controllerId,
                                                   $moduleId)
        {
            assert('is_string($controllerId) || $controllerId === null');
            assert('is_string($moduleId)  || $moduleId === null');
            assert('is_string($moduleClassName)');

            $label =  StringUtil::getChoppedStringContent($product->name, ProductElementUtil::PRODUCT_NAME_LENGTH_IN_PORTLET_VIEW);
            if (ActionSecurityUtil::canCurrentUserPerformAction('Details', $product))
            {
                $params      = array('label' => $label, 'routeModuleId' => 'products',
                                     'wrapLabel' => false,
                                     'htmlOptions' => array('id' => 'product-' . $product->id)
                );
                $goToDetailsFromRelatedModalLinkActionElement = new GoToProductDetailsFromRelatedModalLinkActionElement(
                    $controllerId, $moduleId, $product->id, $params);
                $linkContent = $goToDetailsFromRelatedModalLinkActionElement->render();
                return $linkContent;
            }
            else
            {
                return null;
            }
        }

        /**
         * Register script for product detail link. This would be called from related model view
         * @param string $sourceId
         */
        public static function registerProductModalDetailsScript($sourceId)
        {
            assert('is_string($sourceId)');
            $modalId = ProductsUtil::getModalContainerId();
            $url = Yii::app()->createUrl('products/default/modalDetails');
            $ajaxOptions = ProductsUtil::resolveAjaxOptionsForModalView('Details', $sourceId);
            $ajaxOptions['beforeSend'] = new CJavaScriptExpression($ajaxOptions['beforeSend']);
            $script = " $(document).off('click.productDetailLink', '#{$sourceId} .product-modal-detail-link');
                        $(document).on('click.productDetailLink',  '#{$sourceId} .product-modal-detail-link', function()
                        {
                            var id = $(this).attr('id');
                            var idParts = id.split('-');
                            var productId = parseInt(idParts[1]);
                            $.ajax(
                            {
                                'type' : 'GET',
                                'url'  : '{$url}' + '?id=' + productId,
                                'beforeSend' : {$ajaxOptions['beforeSend']},
                                'update'     : '{$ajaxOptions['update']}',
                                'success': function(html){jQuery('#{$modalId}').html(html)}
                            });
                            return false;
                          }
                        );";
            Yii::app()->clientScript->registerScript('productModalDetailsScript' . $sourceId, $script);
        }
    }
?>