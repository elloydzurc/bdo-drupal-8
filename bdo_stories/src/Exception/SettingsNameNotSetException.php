<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/12/2019
 * Time: 5:19 PM
 */

namespace Drupal\bdo_stories\Exception;

use Exception;

class SettingsNameNotSetException extends Exception
{
    protected $message = "Undefined module name!";
}