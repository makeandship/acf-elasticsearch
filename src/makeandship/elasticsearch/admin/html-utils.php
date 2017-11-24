<?php

namespace makeandship\elasticsearch\admin;

use makeandship\elasticsearch\settings\SettingsHelper;

class HtmlUtils
{
    protected function __construct()
    {
    }

    public static function render_field($label, $name, $args)
    {
        $html = [];

        $type = isset($args['type']) ? $args['type'] : 'text';

        if (isset($type) && !empty($type)) {
            $field = '';

            switch ($type) {
                case 'text':
                    $field = self::render_text_field($name, $args);
                    break;
            }

            $html = [
                '<div class="acf-elasticsearch-row">',
                '	<div class="twocol">',
                '		<label for="">'.$label.'</label>',
                '	</div>',
                '	<div class="tencol last">',
                '		'.$field,
                '	</div>',
                '</div>'
            ];
        }

        return implode($html, PHP_EOL);
    }

    public static function render_text_field($name, $args)
    {
        $value = null;

        if (is_multisite()) {
            $option = get_site_option($name);
        } else {
            $option = get_option($name);
        }

        if (isset($option)) {
            $value = $option;
        } else {
            if (array_key_exists('value', $args)) {
                $value = $args['value'];
            }
        }

        $clazz = isset($args) && array_key_exists('class', $args) ? $args['class'] : '';

        $html = [
            '<input type="text" ',
            '	class="'.$clazz.'"',
            '	name="'.$name.'"',
            '	value="'.$value.'"'
        ];
        
        unset($args['value']);
        unset($args['class']);

        foreach ($args as $key => $value) {
            $html[] = '	'.$key.'="'.$value.'"';
        }

        $html[] = '/>';
        

        return implode($html, PHP_EOL);
    }

    public static function render_buttons($buttons)
    {
        $html = [];

        $html[] = '<div class="acf-elasticsearch-row">';
        $html[] = '	<div class="twelvecol last acf-elasticsearch-button-container">';

        foreach ($buttons as $button) {
            $html[] = self::render_button($button);
        }

        $html[] = '	</div>';
        $html[] = '</div>';

        return implode($html, PHP_EOL);
    }

    public static function render_button($args)
    {
        $html = [
            '<input type="submit" '
        ];

        foreach ($args as $key => $value) {
            $html[] = '	'.$key.'="'.$value.'"';
        }
            
        $html[] = '/>';
        

        return implode($html, PHP_EOL);
    }

    public static function render_post_type_choices($label)
    {
        $html = [];

        $first = true;

        $checkboxes = SettingsHelper::get_post_type_checkbox_data();

        foreach ($checkboxes as $checkbox) {
            $html[] = '<div class="acf-elasticsearch-row">';
            $html[] = '    <div class="twocol">';
            $html[] = '	       <label for="">'.($first ? $label : "").'</label>';
            $html[] = '    </div>';
            $html[] = '    <div class="twocol">';
            $html[] = self::render_checkbox($checkbox);
            $html[] = '    </div>';
            $html[] = '    <div class="fourcol">';
            $html[] = '         <label class="textarea-label" for="">Exclude fields from indexing</label>';
            $html[] = '         <textarea name="'.$checkbox['value'].'_exclude">'.$checkbox['exclude'].'</textarea>';
            $html[] = '    </div>';
            $html[] = '    <div class="fourcol last">';
            $html[] = '         <label class="textarea-label" for="">Fields for private searches only</label>';
            $html[] = '         <textarea name="'.$checkbox['value'].'_private">'.$checkbox['private'].'</textarea>';
            $html[] = '    </div>';
            $html[] = '</div>';

            $first = false;
        }

        return implode($html, PHP_EOL);
    }

    public static function render_checkbox($args)
    {
        $id = $args['id'];
        $name = $args['name'];
        $value = $args['value'];
        $checked = $args['checked'];

        $html[] = '<label for="'.$id.'">';
        $html[] = '    <input type="checkbox" value="'.$value.'" name="'.$name.'" id="'.$id.'"';
        
        if ($checked) {
            $html[] = 'checked="checked">';
        } else {
            $html[] = '>';
        }
        
        $html[] = $value;
        
        $html[] = '</label>';
              
        return implode($html, PHP_EOL);
    }

    public static function create_post_types()
    {
        $post_types = array();
        $types = $_POST['acf_elasticsearch_post_types'];
        foreach($types as $type){
            $post_type = array();
            $post_type['type'] = $type;
            $post_type['exclude'] = self::get_array_data($type, 'exclude');
            $post_type['private'] = self::get_array_data($type, 'private');
            $post_types[] = $post_type;
        }
        
        return $post_types;
    }

    private static function get_array_data($type, $category)
    {
        if(isset($_POST[$type.'_'.$category])){
            $input = $_POST[$type.'_'.$category];
            return explode("\n", str_replace("\r", "", $input));
        }
        else {
            return array();
        }
    }
}
