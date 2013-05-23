<?php
    /*********************************************************************************
     * Zurmo is a customer relationship management program developed by
     * Zurmo, Inc. Copyright (C) 2013 Zurmo Inc.
     *
     * Zurmo is free software; you can redistribute it and/or modify it under
     * the terms of the GNU General Public License version 3 as published by the
     * Free Software Foundation with the addition of the following permission added
     * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
     * IN WHICH THE COPYRIGHT IS OWNED BY ZURMO, ZURMO DISCLAIMS THE WARRANTY
     * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
     *
     * Zurmo is distributed in the hope that it will be useful, but WITHOUT
     * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
     * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
     * details.
     *
     * You should have received a copy of the GNU General Public License along with
     * this program; if not, see http://www.gnu.org/licenses or write to the Free
     * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
     * 02110-1301 USA.
     *
     * You can contact Zurmo, Inc. with a mailing address at 27 North Wacker Drive
     * Suite 370 Chicago, IL 60606. or at email address contact@zurmo.com.
     *
     * The interactive user interfaces in original and modified versions
     * of this program must display Appropriate Legal Notices, as required under
     * Section 5 of the GNU General Public License version 3.
     *
     * In accordance with Section 7(b) of the GNU General Public License version 3,
     * these Appropriate Legal Notices must retain the display of the Zurmo
     * logo and Zurmo copyright notice. If the display of the logo is not reasonably
     * feasible for technical reasons, the Appropriate Legal Notices must display the words
     * "Copyright Zurmo Inc. 2013. All rights reserved".
     ********************************************************************************/

    /**
     * View that renders a list of categories in the form of a
     * tree or noded list view.
     */
    class ProductCategoriesTreeListView extends SecurityTreeListView
    {
        protected function renderContent()
        {
            $singularLabel  = ProductCategory::getModelLabelByTypeAndLanguage('Singular');
            $pluralLabel    = Zurmo::t('ProductTemplatesModule', 'Categories');
            $content	    = $this->renderViewToolBar(false);
            $content	    .= '<div>';
            $content	    .= $this->renderTreeMenu('productCategory', 'productCategories', $singularLabel);
            $content	    .= '</div>';
            return $content;
        }

        protected function renderTreeListView($data)
        {
            assert('is_array($data)');
            $content  = '<table class="configuration-list">';
            $content .= '<colgroup>';
            $content .= '<col style="width:50%" />';
            $content .= '<col style="width:25%" />';
            $content .= '<col style="width:25%" />';
            $content .= '</colgroup>';
            $content .= '<tbody>';
            $content .= '<tr><th>' . Zurmo::t('ProductTemplatesModule', 'Name') . '</th><th></th><th></th></tr>';
            static::renderTreeListViewNode($content, $data, 0);
            $content .= '</tbody>';
            $content .= '</table>';
            return $content;
        }

        protected static function renderTreeListViewNode(& $content, $data, $indent)
        {
            assert('is_string($content)');
            assert('is_array($data)');
            foreach ($data as $node)
            {
                $content .= '<tr>';
                $content .= '<td class="level-' . $indent . '">';
                $content .= $node['link'];
                $content .= '</td>';
                $content .= '<td>';
                $content .= '</td>';
                $content .= '<td>';
                if (isset($node['route']) && $node['route'] != null && static::shouldRenderConfigureLink())
                {
                    $content .= ZurmoHtml::link(ZurmoHtml::wrapLabel(Zurmo::t('ProductTemplatesModule', 'Configure') ),
                        $node['route']);
                }
                $content .= '</td>';
                $content .= '</tr>';
                if (isset($node['children']))
                {
                    static::renderTreeListViewNode($content, $node['children'], $indent + 1);
                }
            }
        }

        protected static function shouldRenderConfigureLink()
        {
            return false;
        }

        protected function resolveUserCountForItem(Item $item)
        {
            return 0;
        }
    }
?>
