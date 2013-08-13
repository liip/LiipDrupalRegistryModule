<?php
/**
 * @file
 * Drush integragtion of the LiipDrupalRegistry module.
 */


/**
 * Implements hook_drush_command().
 */
function drupalregistrymodule_drush_command() {
  $items['drupalregistrymodule-download'] = array(
    'description' => dt('Downloads the drupalregistrymodule from https://github.com/liip/LiipDrupalRegistryModule.git.'),
    'arguments' => array(
      'path' => dt('Optional. A path to the download folder. If omitted Drush will use the sites/all/modules/drupalregistrymodule.'),
    ),
    'aliases' => array('liip_registry'),
  );
  return $items;
}

/**
 * Implementation of hook_drush_help().
 */
function drupalregistrymodule_drush_help($section) {
  switch ($section) {
    case 'drush:drupalregistrymodule-download':
      return dt('Downloads the drupalregistrymodule from https://github.com/liip/LiipDrupalRegistryModule.git. Places it in the sites module directory. Skips download if module already present. This all happens automatically if you enable this module using drush.');
  }
}

/**
 * A command callback. Download dependencies module using git.
 */
function drush_drupalregistrymodule_download() {
  $args = func_get_args();
  if (isset($args[0])) {
    $path = $args[0];
  }
  else {
    $path = drush_get_context('DRUSH_DRUPAL_ROOT');
    $path .= '/' . 'sites/all/modules/drupalregistrymodule';
  }

  if (is_dir($path)) {
    drush_log(' already present. No download required.', 'ok');
  }
  elseif (drush_shell_cd_and_exec(dirname($path), 'git clone https://github.com/liip/LiipDrupalRegistryModule.git drupalregistrymodule')) {
    drush_log(dt('The drupalregistrymodule has been cloned via git to @path.', array('@path' => $path)), 'success');
  }
  else {
    drush_log(dt('Drush was unable to clone to @path.', array('@path' => $path)), 'error');
  }
}

/**
 * Implements drush_MODULE_post_COMMAND().
 */
function drush_drupalregistrymodule_post_pm_enable() {
  $modules = func_get_args();
  if (in_array('drupalregistrymodule', $modules)) {
    drush_drupalregistrymodule_download();
  }
}
