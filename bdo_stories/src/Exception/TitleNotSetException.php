<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/14/2019
 * Time: 11:19 PM
 */

namespace Drupal\bdo_stories\Exception;


use Exception;

class TitleNotSetException extends Exception
{
    protected $message = "Story title not set on http request";
}