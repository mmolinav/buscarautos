<?php

/****************************************************************************************\
 **   @name		EXP Autos  2.0                                                  **
 **   @package          Joomla 1.6                                                      **
 **   @author		EXP TEAM::Alexey Kurguz (Grusha)                                **
 **   @copyright	Copyright (C) 2005 - 2011  EXP TEAM::Alexey Kurguz (Grusha)     **
 **   @link             http://www.feellove.eu                                          **
 **   @license		Commercial License                                              **
 \****************************************************************************************/


defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');
require_once JPATH_COMPONENT.'/helpers/expfields.php';

class JFormFieldExpmodel extends JFormField {

    protected $type = 'Expmodel';
    
    public function getInput($data=0) {
        
        $model_id = $this->form->getValue('model');
        $make_id = $this->form->getValue('make');
        $options=ExpAutosProFields::getExpvariables($make_id,'model','name','makeid',$model_id,'name','',$this->name);

        return $options;
    }

}
