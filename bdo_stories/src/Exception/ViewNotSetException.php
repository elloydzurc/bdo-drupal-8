<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 3:23 PM
 */

namespace Drupal\bdo_stories\Exception;

use Exception;

class ViewNotSetException extends Exception
{
    protected $message = "Undefined View Id!";
}