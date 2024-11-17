<?php

function get_logo_path($name) {
    $logo_dir = __DIR__ . '/../public/logos/';
    $name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    
    // Check for existing logo
    $extensions = ['svg', 'png', 'jpg', 'jpeg'];
    foreach ($extensions as $ext) {
        if (file_exists($logo_dir . $name . '.' . $ext)) {
            return '/logos/' . $name . '.' . $ext;
        }
    }
    
    return '/logos/default.svg';  // Return default logo if none found
}

function get_logo_filename($name) {
    $name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    return $name;
}
