<?php

namespace makeandship\elasticsearch;

use makeandship\elasticsearch\settings\SettingsManager;
use makeandship\elasticsearch\transformer\DateFieldTransformer;
use makeandship\elasticsearch\transformer\FileFieldTransformer;
use makeandship\elasticsearch\transformer\HtmlFieldTransformer;
use makeandship\elasticsearch\transformer\ImageFieldTransformer;
use makeandship\elasticsearch\Util;

class PostDocumentBuilder extends DocumentBuilder
{

    /**
     * Is this document private
     */
    public function is_private($post)
    {
        if ($post->post_type === 'attachment' && $post->post_parent) {
            $parent = get_post($post->post_parent);
            return $parent && $parent->post_status === 'private';
        } elseif ($post->post_status === 'private') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Does this document have private fields
     */
    public function has_private_fields()
    {
        return true;
    }

    /**
     * Check whether this document should be stored in an index
     * using the configuration field <code>Constants::OPTION_EXCLUSION_FIELD</code>
     *
     * Index a post if
     * - the slug / id is not in an exclusion list
     * - the exclusion field is not true for this post
     *
     * @param post the post the confirm for indexing
     * @return boolean true if the document should be indexed
     */
    public function is_indexable($post)
    {
        if ($post) {
            if ($post->post_type === 'attachment' && $this->is_orphaned_media($post)) {
                return false;
            }

            $post_id = $post->ID;

            // check if the exclusion field is set
            $exclusion_field_name = SettingsManager::get_instance()->get(Constants::OPTION_EXCLUSION_FIELD);
            if ($exclusion_field_name && $post_id) {
                $exclude = get_field($exclusion_field_name, $post_id);
                if ($exclude) {
                    return false;
                }
            }
            // check if the path is part of the exclusion slugs
            $slug          = $post->post_name;
            $exclude_slugs = SettingsManager::get_instance()->get(Constants::OPTION_SLUGS_TO_EXCLUDE);
            if (
                $exclude_slugs &&
                is_array($exclude_slugs) &&
                count($exclude_slugs) > 0 &&
                in_array($slug, $exclude_slugs)) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     */
    public function build($post, $include_private = false, $relationships = false)
    {
        $document = null;

        if (isset($post)) {
            $post->type = $post->post_type;
            $document   = array();

            foreach ($this->get_core_fields('WP_Post') as $name => $definition) {
                if ($name === 'link') {
                    $link             = get_permalink($post->ID);
                    $document['link'] = $link;
                } elseif ($name === 'parent_id') {
                    $document['parent_id'] = $post->post_parent;
                } elseif ($name === 'parent_title') {
                    $parent                   = get_post($post->post_parent);
                    $document['parent_title'] = $parent ? $parent->post_title : null;
                } else {
                    $value = $post->{$name};

                    // transform if required
                    if (is_array($definition) && array_key_exists('transformer', $definition)) {
                        $transformer = new $definition['transformer'];

                        if (isset($transformer)) {
                            $value = $transformer->transform($value);
                        }
                    }

                    $document[$name] = $value;

                    if (is_array($definition) && array_key_exists('suggest', $definition)) {
                        if ($definition['suggest']) {
                            $document[$name . '_suggest'] = $value;
                        }
                    }

                    if (is_array($definition) && array_key_exists('sortable', $definition)) {
                        if ($definition['sortable']) {
                            $document[$name . '_sortable'] = $value;
                        }
                    }
                }
            }

            $post_type = $post->post_type;

            // acf fields
            if (class_exists('acf')) {
                $field_groups = array();

                $templates = $this->get_page_templates($post);

                $has_templates = $templates && is_array($templates) && count($templates) > 0;
                if ($has_templates) {
                    foreach ($templates as $template) {
                        // field groups for default template
                        $args = array(
                            'post_type'     => $post_type,
                            'post_template' => $template,
                            'page_template' => $template,
                        );

                        $template_field_groups = acf_get_field_groups($args);
                        if ($template_field_groups) {
                            $field_groups = array_merge($field_groups, $template_field_groups);
                        }
                    }
                } else {
                    $args = array(
                        'post_type'     => $post_type,
                        'post_template' => 'default',
                        'page_template' => 'default',
                    );

                    $field_groups = acf_get_field_groups($args);
                }

                if (isset($field_groups) && !empty($field_groups)) {
                    foreach ($field_groups as $field_group) {
                        $field_group_id = $field_group['ID'];
                        if ($field_group_id) {
                            $fields = acf_get_fields($field_group_id);

                            foreach ($fields as $field) {
                                $field_document = $this->build_acf_field($field, $post, $include_private, $relationships);
                                if (isset($field_document)) {
                                    $document = array_merge(
                                        $document,
                                        $field_document
                                    );
                                }
                            }
                        }
                    }
                }
            }

            // taxonomies
            $taxonomies = get_object_taxonomies($post_type, 'objects');
            foreach ($taxonomies as $name => $taxonomy) {
                if (!in_array($name, MappingBuilder::EXCLUDE_TAXONOMIES)) {
                    $document = array_merge(
                        $document,
                        $this->build_taxonomy($post, $name, $taxonomy)
                    );
                }
            }
        }

        return $document;
    }

    private function build_acf_field($field, $post, $include_private, $relationships)
    {
        $document = null;

        $post_type = $this->get_type($post);

        $excluded_fields = SettingsManager::get_instance()->get_exclude_fields($post_type);
        $private_fields  = SettingsManager::get_instance()->get_private_fields($post_type);

        if (isset($field) && isset($post)) {
            $name = Util::safely_get_attribute($field, 'name');
            $type = Util::safely_get_attribute($field, 'type');

            if ($name && $type) {
                // safe to index if the field isn't excluded or private
                if (
                    !in_array($name, $excluded_fields) &&
                    (!in_array($name, $private_fields) || $include_private)) {
                    $key   = Util::safely_get_attribute($field, 'key');
                    $value = get_field($key, $post->ID);

                    if (isset($value) && !empty($value)) {
                        $value = $this->transform_acf_value($field, $value, $type, $relationships);

                        if ($value) {
                            $document        = array();
                            $document[$name] = $value;
                        }
                    }
                }
            }
        }

        return $document;
    }

    private function transform_acf_value($field, $value, $type, $relationships)
    {
        $transformer = null;

        switch ($type) {
            case 'checkbox':
                break;
            case 'color_picker':
                $value = null;
                break;
            case 'date_picker':
                $transformer = new DateFieldTransformer();
                break;
            case 'date_time_picker':
                $transformer = new DateFieldTransformer();
                break;
            case 'file':
                $transformer = new FileFieldTransformer();
                break;
            case 'google_map':
                $value = null;
                break;
            case 'image':
                $transformer = new ImageFieldTransformer();
                // nested
                break;
            case 'message':
                $value = null;
                break;
            case 'number':
                break;
            case 'oembed':
                // nested
                $value = null;
                break;
            case 'page_link':
                $value = null;
                break;
            case 'password':
                // dont index
                $value = null;
                break;
            case 'post_object':
                $value = null;
                // id?
                break;
            case 'relationship':
                $built = null;
                if ($relationships) {
                    if (is_array($value) && count($value) > 0) {
                        $built = array();
                        foreach ($value as $post_id) {
                            $post = get_post($post_id);
                            if ($post) {
                                $built[] = $this->build($post, false, false);
                            }
                        }
                    } else {
                        $post = get_post($value);
                        if ($post) {
                            $built = $this->build($post, false, false);
                        }
                    }
                }
                $value = $built;
                break;
            case 'group':
            case 'repeater':
                if ($value && is_array($value) && count($value) > 0) {
                    // create an index to lookup from
                    // name => subfield
                    $sub_fields_by_name = array();
                    foreach ($field['sub_fields'] as $sub_field) {
                        $sub_field_name                      = Util::safely_get_attribute($sub_field, 'name');
                        $sub_fields_by_name[$sub_field_name] = $sub_field;
                    }

                    if (Util::is_array_associative($value)) {
                        foreach ($value as $sub_field_name => &$sub_field_value) {
                            $sub_field       = $sub_fields_by_name[$sub_field_name];
                            $sub_field_type  = Util::safely_get_attribute($sub_field, 'type');
                            $sub_field_value = $this->transform_acf_value($sub_field, $sub_field_value, $sub_field_type, $relationships);
                            if (!isset($sub_field_value) || empty($sub_field_value)) {
                                unset($value[$sub_field_name]);
                            }
                        }
                    } else {
                        // sequential
                        foreach ($value as $index => &$item) {
                            foreach ($item as $sub_field_name => &$sub_field_value) {
                                $sub_field       = $sub_fields_by_name[$sub_field_name];
                                $sub_field_type  = Util::safely_get_attribute($sub_field, 'type');
                                $sub_field_value = $this->transform_acf_value($sub_field, $sub_field_value, $sub_field_type, $relationships);
                                if (!isset($sub_field_value) || empty($sub_field_value)) {
                                    unset($item[$sub_field_name]);
                                }
                            }
                        }
                    }
                }
                break;
            case 'taxonomy':
                $value = null;
                break;
            case 'time_picker':
                $value = null;
                break;
            case 'true_false':
                break;
            case 'user':
                $value = null;
                // custom
                break;
            case 'wysiwyg':
                $transformer = new HtmlFieldTransformer();
                break;
        }

        if ($transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
    }

    private function build_taxonomy($post, $name, $taxonomy)
    {
        $document = array();

        if ($post && $name && $taxonomy) {
            $post_id = $post->ID;

            $terms = wp_get_object_terms($post_id, $name);
            foreach ($terms as $term) {
                // set up taxonomy arrays in the document to index
                if (Util::safely_get_attribute($document, $name) === null) {
                    $document[$name]              = array();
                    $document[$name . '_name']    = array();
                    $document[$name . '_suggest'] = array();
                }

                // index the current term
                $document[$name][]              = $term->slug;
                $document[$name . '_name'][]    = $term->name;
                $document[$name . '_suggest'][] = $term->name;

                // index parent terms if they exist
                $parent_id = Util::safely_get_attribute($term, 'parent');
                if ($parent_id && $parent_id !== 0) {
                    $parent = get_term($term->parent, $name);
                    while ($parent !== null) {
                        $document[$name][]              = $parent->slug;
                        $document[$name . '_name'][]    = $parent->name;
                        $document[$name . '_suggest'][] = $parent->name;

                        $parent_id = Util::safely_get_attribute($parent, 'parent');
                        if ($parent_id && $parent_id !== 0) {
                            $parent = get_term($parent_id, $name);
                        } else {
                            $parent = null;
                        }
                    }
                }
            }
        }

        return $document;
    }

    /**
     *
     */
    public function get_id($post)
    {
        return $post->ID;
    }

    /**
     * Get the document post type - used for validating fields
     */
    public function get_type($post)
    {
        return $post->post_type;
    }

    /**
     * Get the template of a post
     */
    public function get_page_templates($post)
    {
        $id        = Util::safely_get_attribute($post, 'ID');
        $templates = get_post_meta($id, '_wp_page_template');

        if ($templates && is_array($templates) && count($templates)) {
            return $templates;
        }

        return null;
    }

    /**
     * Get the document mapping type - used for indexing into elastic search
     */
    public function get_mapping_type($post)
    {
        return Constants::DEFAULT_MAPPING_TYPE;
    }

    /**
     * Check if an attachment is orphaned media
     */
    public function is_orphaned_media($attachment)
    {
        $post_type   = Util::safely_get_attribute($attachment, 'post_type');
        $post_parent = Util::safely_get_attribute($attachment, 'post_parent');
        $post        = get_post($post_parent);
        $post_status = Util::safely_get_attribute($post, 'post_status');

        // allow draft for newly creating documents
        $is_valid_existing = ($post_status === 'publish' || $post_status === 'private');
        $is_valid_creating = ($post_status === 'draft' && $post_type === 'attachment');

        $is_available = $post && ($is_valid_existing || $is_valid_creating);

        $orphaned = !$is_available;

        // allow more thorough check in theme
        $orphaned = Util::apply_filters('is_orphaned_media', $orphaned, $attachment);

        return $orphaned;
    }
}
