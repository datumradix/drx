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
     * Render a file upload element that can allow for multiple file uploads and calls ajax to upload the files to
     * the server as you add them.
     * Utilizes file upload plugin here: https://github.com/blueimp/jQuery-File-Upload
     */
    class MentionInput extends ZurmoWidget
    {
        public $scriptFile = array('lib/jquery.events.input.js',
            'lib/jquery.elastic.js', 'lib/underscore.js', 'jquery.mentionsInput.js', 'lib/purl.js');

        public $cssFile    = 'jquery.mentionsInput.css';

        public $assetFolderName = 'mentionInput';

        public $callBackUrl = '';

        public $triggerChar = '@';

        public $minChars = 2;

        public $showAvatars = true;

        public $classes = '';

        public $templates = '';

        public $defaultValue = '';

        /**
         * Initializes the widget.
         * This method will publish assets if necessary.
         * It will also register jquery and JUI JavaScript files and the theme CSS file.
         * If you override this method, make sure you call the parent implementation first.
         */
        public function init()
        {
            Yii::app()->getClientScript()->registerCoreScript('jquery.ui');
            parent::init();
        }

        public function run()
        {
            $id = $this->getId();
            $additionalSettingsJs = "showAvatars: " . var_export($this->showAvatars, true) . ",";
            if ($this->classes)
            {
                $additionalSettingsJs .=  $this->classes . ',';
            };
            if ($this->templates)
            {
                $additionalSettingsJs .=  $this->templates;
            };
            $defaultValue = '';
            if ($this->defaultValue)
            {
                $defaultValue = str_replace("'", "\'", $this->defaultValue);
                $defaultValue = preg_replace("/\n/m", '\n', $defaultValue); // Fix issues with new lines in javascript default value
            }

            // Begin Not Coding Standard
            $javaScript = <<<EOD
var action = $('#$id').closest("form").attr('action');
var relatedModelClassName = purl(action).param('relatedModelClassName');
var relatedModelId = purl(action).param('relatedModelId');
var queryString = '';
if (relatedModelClassName != 'undefined' && relatedModelId != 'undefined')
{
    queryString = '&relatedModelClassName=' + relatedModelClassName + '&relatedModelId=' + relatedModelId;
}
$('#$id').mentionsInput({
onDataRequest:function (mode, query, callback) {
  $.ajax({
    dataType: "json",
    url: '{$this->callBackUrl}?term=' + query + queryString,
    data: [],
    success: function (responseData) {
      callback.call(this, responseData);
    }
  });
    },
    onCaret: true,
    allowRepeat: true,
    triggerChar: '{$this->triggerChar}',
    minChars:    '{$this->minChars}',
    defaultValue: '{$defaultValue}'.replace(/\\'/g, "'"),
    {$additionalSettingsJs}
  });
  $('.mentions-input-box').find('textarea').off('blur');
EOD;
            // End Not Coding Standard
            Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $id, $javaScript, CClientScript::POS_END);
        }
    }
?>