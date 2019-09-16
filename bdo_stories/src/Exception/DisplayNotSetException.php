<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 3:21 PM
 */

namespace Drupal\bdo_stories\Exception;

use Exception;

class DisplayNotSetException extends Exception
{
    protected $message = "Undefined Display Id!";
}