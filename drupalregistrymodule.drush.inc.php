<?php
/**
 * @file
 * Drush integragtion of the LiipDrupalRegistry module.
 */


/**
 * Implements hook_drush_command().
 */
function drupalregistrymodule_drush_command() {
  $items['drupalconnectormodule-download'] = array(
    'description' => dt('Downloads the drupalconnectormodule from https://github.com/liip/LiipDrupalConnectorModule.git.'),
    'arguments' => array(
      'path' => dt(
          'Optional. A path to the download folder. '.
          'If omitted Drush will use the »sites/all/modules/drupalconnectormodule« as install location.'
      ),
    ),
    'aliases' => array('liip_drupal_connector'),
  );

  $items['registryadaptor-download'] = array(
    'description' => dt('Downloads the registryadaptor from https://github.com/liip/LiipDrupalConnectorModule.git.'),
    'arguments' => array(
      'path' => dt(
          'Optional. A path to the download folder. '.
          'If omitted Drush will use the library installation folder as dropzone.'
      ),
    ),
    'aliases' => array('liip_registry_adaptor'),
  );

  $items['assert-download'] = array(
    'description' => dt('Downloads an assertion library from https://github.com/beberlei/Assert.git.'),
    'arguments' => array(
      'path' => dt('Optional. A path to the download folder. If omitted Drush will use the library folder as dropzone.'),
    ),
    'aliases' => array('beberlei_assert'),
  );

  $items['elastica-download'] = array(
    'description' => dt('Downloads the registryadaptor from https://github.com/liip/LiipDrupalConnectorModule.git.'),
    'arguments' => array(
      'path' => dt('Optional. A path to the download folder. If omitted Drush will use the library folder as dropzone.'),
    ),
    'aliases' => array('ruflin_elastica'),
  );

  return $items;
}

/**
 * Implementation of hook_drush_help().
 */
function drupalregistrymodule_drush_help($section) {
  switch ($section) {
    case 'drush:drupalconnectormodule-download':
      return dt(
          'Downloads the drupal connectorfrom https://github.com/liip/LiipDrupalRegistryModule.git. '.
          'Places it in the sites module directory. Skips download if module already present. '.
          'This all happens automatically if you enable this module using drush.'
      );

    case 'drush:registry-adaptor-download':
      return dt(
          'Downloads the registry adaptor from https://github.com/liip/lRegistryAdaptor.git. '.
          'Places it in the sites module directory. Skips download if module already present. '.
          'This all happens automatically if you enable this module using drush.'
      );

    case 'drush:assert-download':
      return dt(
          'Downloads an assertion library from https://github.com/beberlei/Assert.git. '.
          'Places it in the sites module directory. Skips download if module already present. '.
          'This all happens automatically if you enable this module using drush.'
      );

    case 'drush:elastica-download':
      return dt(
          'Downloads an assertion library from https://github.com/ruflin/Elastica.git. '.
          'Places it in the sites module directory. Skips download if module already present. '.
          'This all happens automatically if you enable this module using drush.'
      );

    default:
      return null;
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
  elseif (drush_shell_cd_and_exec(
    dirname($path),
    'git clone https://github.com/liip/LiipDrupalRegistryModule.git drupalregistrymodule'
  )) {
    drush_log(dt('The drupalregistrymodule has been cloned via git to @path.', array('@path' => $path)), 'success');
  }
  else {
    drush_log(dt('Drush was unable to clone to @path.', array('@path' => $path)), 'error');
  }
}

/**
 * Downloads the given module from the specified url.
 *
 * @param string $name
 * @param string $location
 * @param string $url
 */
function _drupalregistrymodule_download_dependency($name, $location, $url)
{
    $path = _drupalregistrymodule_determine_path($name, $location);

    if (is_dir($path)) {
        drush_log(' already present. No download required.', 'ok');
    }
    elseif (drush_shell_cd_and_exec(dirname($path), 'git clone '. $url .' '. $name)) {
        drush_log(
            dt(
                'The @name has been cloned via git to @path.',
                array(
                    '@pname' => $name,
                    '@path' => $path,
                )
            ),
            'success'
        );
    }
    else {
        drush_log(dt('Drush was unable to clone to @path.', array('@path' => $path)), 'error');
    }
}

function _drupalregistrymodule_determine_path($dependency, $location = '')
{
    if (!empty($location)) {
        return $location;
    }

    $path = drush_get_context('DRUSH_DRUPAL_ROOT');

    if (module_exists('libraries')) {
        $path .= '/' . libraries_get_path($dependency);
    } else {

        $path .= '/site/all/modules/' . $dependency;
    }

    return $path;
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
