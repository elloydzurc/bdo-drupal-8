<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/23/2019
 * Time: 12:17 PM
 */

namespace Drupal\bdo_general_microsites\Form\Definition;


class Token
{
    const ITEMS = [
        'microsite_name' => 'Name of the Microsite',
        'microsite_created_date' => 'Date the microsite was created.',
        'microsite_created_by' => 'User who created the microsite.',
        'effective_from' => 'Effective date (Start)',
        'effective_to' => 'Effective date (End)',
        'action' => 'The action that will be performed - internally will be replaced 
                        with the appropriate action. (Enable/Expire)',
        'action_date' => 'The date when the action was performed.',
        'specific_date' => 'Computed date from the time the email reminder was sent, depends on the no. 
                        of days specified in the days before field.'
    ];
}