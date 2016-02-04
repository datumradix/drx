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

    class CommentsMarkdownGuideView extends View
    {
        protected function renderContent()
        {
            $syntaxContent     = $this->renderSyntaxContent();
            $content            = Zurmo::t('CommentsModule', 'Markdown is a lightweight markup language with plain ' .
                                           'text formatting syntax designed so that it can be converted to HTML and ' .
                                           'many other formats using a tool by the same name. ');
            $content           .= $syntaxContent;
            $content            = ZurmoHtml::tag('div', array('id' => 'markdown-guide-modal-content',
                                                                'class' => 'markdown-guide-modal'),
                                                        $content);
            return $content;
        }

        protected function renderSyntaxContent()
        {
            $content            = ZurmoHtml::tag('h4', array(), 'Markdown syntax and examples');
            $content            = ZurmoHtml::tag('div', array('id' => 'markdown-examples-head'), $content);
            $syntaxContent     = "
Here’s an overview of Markdown syntax that you can use anywhere on GitHub.com or in your own text files.

<strong>Headers</strong>
# This is an &lt;h1&gt; tag
## This is an &lt;h2&gt tag
###### This is an &lt;h6&gt tag


<strong>Emphasis</strong>
*This text will be italic*
_This will also be italic_

**This text will be bold**
__This will also be bold__

_You **can** combine them_



<strong>Unordered List</strong>
* Item 1
* Item 2
  * Item 2a
  * Item 2b


<strong>Ordered List</strong>
1. Item 1
2. Item 2
3. Item 3
   * Item 3a
   * Item 3b


<strong>Links</strong>
http://github.com - automatic!
[GitHub](http://github.com)


<strong>Blockquotes</strong>
As Benjamin Franklin said:

> Do not fear mistakes.
> You will know failure.
> Continue to reach out.
";


            $syntaxContent    = ZurmoHtml::tag('div', array('id' => 'markdown-syntax-body'), nl2br($syntaxContent));
            $content            .= $syntaxContent;
            $content            = ZurmoHtml::tag('div', array('id' => 'markdown-syntax'), $content);
            return $content;
        }
    }
?>