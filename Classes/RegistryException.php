<?php
namespace netmigrosintranet\modules\Registry\Classes;

class RegistryException extends \Exception
{
    const DUPLICATE_REGISTRATION_ATTEMPT_CODE = 1;
    const DUPLICATE_REGISTRATION_ATTEMPT_TEXT = "Given identifier already applies to an item of the registry. Please choose a different one.";

    const MODIFICATION_ATTEMPT_FAILED_CODE = 1;
    const MODIFICATION_ATTEMPT_FAILED_TEXT = "Given identifier does not applies to an item of the registry.";

    const UNKNOWN_IDENTIFIER_CODE = 2;
    const UNKNOWN_IDENTIFIER_TEXT = "Given identifier does not match a registered one.";

    const DUPLICATE_INITIATION_ATTEMPT_CODE = 3;
    const DUPLICATE_INITIATION_ATTEMPT_TEXT = "It is not possible to reinitiate an already active registry.";
}
