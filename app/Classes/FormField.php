<?php
namespace App\Classes;

/*
 * A FormField Class for generating a form field dynamically
 *
 */

class FormField
{
    var $label,
        $labelFor,
        $labelClass,
        $inputType,
        $inputId,
        $inputValue,
        $inputClass,
        $selectValues,
        $selectedValue,
        $placeholder,
        $disabled,
        $hidden,
        $fileUrl,
        $required,
        $oninput,
        $autoComplete,
        $min;

    public function __construct(
        String $label = 'Label',
        String $labelFor = 'LabelFor',
        String $labelClass = 'col-sm-4 control-label',
        String $inputType = 'text',
        String $inputId = 'input-id',
        String $oninput = 'oninput',
        String $inputValue = null,
        String $inputClass = 'form-control',
        array $selectValues = [],
        String $selectedValue=null,
        String $placeholder = 'Enter value here',
        String $labelValue=null,
        bool $disabled = false,
        bool $hidden = false,
        String $fileUrl = '',
        bool $required = false,
        String $autoComplete = "on",
        String $min = null)
    {
        $this->label = $label;
        $this->labelFor = $labelFor;
        $this->labelClass = $labelClass;
        $this->labelValue = $labelValue;
        $this->inputType = $inputType;
        $this->inputId = $inputId;
        $this->oninput = $oninput;
        $this->inputValue =$inputValue;
        $this->inputClass = $inputClass;
        $this->selectValues = $selectValues;
        $this->selectedValue = $selectedValue;
        $this->placeholder = $placeholder;
        $this->disabled = $disabled;
        $this->hidden = $hidden;
        $this->fileUrl = $fileUrl;
        $this->required = $required;
        $this->autoComplete = $autoComplete;
        $this->min = $min;
    }

}
