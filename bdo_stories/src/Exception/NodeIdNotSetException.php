<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/14/2019
 * Time: 11:19 PM
 */

namespace Drupal\bdo_stories\Exception;


use Exception;

class NodeIdNotSetException extends Exception
{
    protected $message = "Node Id not set, or story path is invalid";
}