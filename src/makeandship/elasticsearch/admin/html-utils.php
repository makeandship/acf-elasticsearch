<?php

namespace makeandship\elasticsearch\admin;

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
                '	<div class="acf-elasticsearch-label">',
                '		<label for="">'.$label.'</label>',
                '	</div>',
                '	<div class="acf-elasticsearch-field">',
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
        $html[] = '	<div class="acf-elasticsearch-button-container">';

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

    public static function render_checkboxes($label, $checkboxes)
    {
        $html = [];

        $html[] = '<div class="acf-elasticsearch-row">';
        $html[] = '    <div class="acf-elasticsearch-label">';
        $html[] = '	       <label for="">'.$label.'</label>';
        $html[] = '    </div>';
        $html[] = '    <div class="acf-elasticsearch-checkboxes">';
        
        foreach ($checkboxes as $checkbox) {
            $html[] = self::render_checkbox($checkbox);
        }
        
        $html[] = '    </div>';
        $html[] = '</div>';

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
}
