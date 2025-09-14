<?php

/**
 * Template for graduate metabox
 *
 * @package Graduates
 * @var array $data Data passed to the template
 */

declare(strict_types=1);

$first_name = $data['first_name'] ?? '';
$last_name = $data['last_name'] ?? '';
$labels = $data['labels'] ?? [];
$nonce_field = $data['nonce_field'] ?? '';
echo $nonce_field; ?>

<table class="form-table">
    <tr>
        <th><label for="graduate-first-name"><?php echo esc_html($labels['first_name']); ?></label></th>
        <td>
            <input type="text" id="graduate-first-name" name="graduate_first_name" 
                   value="<?php echo esc_attr($first_name); ?>" class="widefat" 
                   placeholder="<?php echo esc_attr($labels['first_name']); ?>">
        </td>
    </tr>
    <tr>
        <th><label for="graduate-last-name"><?php echo esc_html($labels['last_name']); ?></label></th>
        <td>
            <input type="text" id="graduate-last-name" name="graduate_last_name" 
                   value="<?php echo esc_attr($last_name); ?>" class="widefat" 
                   placeholder="<?php echo esc_attr($labels['last_name']); ?>">
        </td>
    </tr>
</table>
