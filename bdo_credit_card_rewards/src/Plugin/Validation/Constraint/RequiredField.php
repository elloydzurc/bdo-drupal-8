<?php

namespace Drupal\bdo_credit_card_rewards\Plugin\Validation\Constraint;

use Drupal\Core\Annotation\Translation;
use Symfony\Component\Validator\Constraint;

/**
 * Class RequiredField
 * @package Drupal\bdo_credit_card_rewards\Plugin\Validation\Constraint
 *
 *  @Constraint(
 *   id = "RequiredField",
 *   label = @Translation("Required Field", context = "Validation"),
 *   type = "string"
 * )
 */
class RequiredField extends Constraint
{
    /**
     * Message shown when field is empty
     * @var string $empty
     */
    public $empty = '%label field is required!';
}