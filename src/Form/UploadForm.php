<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2017 Serhii Popov
 * This source file is subject to The MIT License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @category Popov
 * @package Popov_<package>
 * @author Serhii Popov <popow.sergiy@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */
namespace Popov\ZfcFileUpload\From;

use Zend\Form\Form;

class UploadForm extends Form
{
    public function init()
    {
        // Define form name.
        $this->setName('files-form');

        // Set POST method for this form.
        $this->setAttribute('method', 'post');

        // Set binary content encoding.
        $this->setAttribute('enctype', 'multipart/form-data');

        $this->addElements();
    }

    protected function addElements()
    {
        // Add "file" field.
        $this->add([
            'type'  => 'file',
            'name' => 'file',
            'attributes' => [
                'id' => 'file'
            ],
            'options' => [
                'label' => 'Image file',
            ],
        ]);

        // Add the submit button.
        $this->add([
            'type'  => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Upload',
                'id' => 'submitbutton',
            ],
        ]);
    }
}