<?php

/**
 * @file
 * Hooks provided by the ECK module.
 */

/**
 * Provide a hook for eck content form alteration.
 */
function hook_eck_content_FORM_ID_alter(array &$form) {
  // Add a checkbox to form.
  $form['terms_of_use'] = [
    '#type' => 'checkbox',
    '#title' => t("I agree."),
    '#required' => TRUE,
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
