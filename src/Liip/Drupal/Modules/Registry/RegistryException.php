<?php
namespace Liip\Drupal\Modules\Registry;

class RegistryException extends \Exception
{
    const DUPLICATE_REGISTRATION_ATTEMPT_CODE = 1;
    const DUPLICATE_REGISTRATION_ATTEMPT_TEXT = "Given identifier already applies to an item of the registry. Please choose a different one.";

    const MODIFICATION_ATTEMPT_FAILED_CODE = 2;
    const MODIFICATION_ATTEMPT_FAILED_TEXT = "Given identifier does not applies to an item of the registry.";

    const UNKNOWN_IDENTIFIER_CODE = 3;
    const UNKNOWN_IDENTIFIER_TEXT = "Given identifier does not match a registered one.";

    const DUPLICATE_INITIATION_ATTEMPT_CODE = 4;
    const DUPLICATE_INITIATION_ATTEMPT_TEXT = "Registry does already exist!";

    const MISSING_DEPENDENCY_CODE = 5;
    const MISSING_DEPENDENCY_TEXT = "Mandatory dependency missing!";

    const INVALID_OPTIONS_CODE = 6;
    const INVALID_OPTIONS_TEXT = "Given elasticsearch options are not valid";
}
