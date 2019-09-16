<?php

namespace Drupal\bdo_credit_card_rewards\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RequiredFieldValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $item The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($item, Constraint $constraint)
    {
        if ($item instanceof FieldItemListInterface) {
            $entity = $item->getEntity();
            $type = $entity->get('field_cc_rewards_points_type')->getValue()[0]['value'];

            $pointsField = [
                'field_rewards_code',
                'field_rewards_points'
            ];

            $rewardsField = [
                'field_cc_regular_code',
                'field_cc_regular_points',
                'field_cc_elite_code',
                'field_cc_elite_points'
            ];

            if ($type == 'PT') {
                if (in_array($item->getName(), $pointsField)) {
                    if (!$item->value) {
                        $this->context->addViolation(
                            $constraint->empty,
                            ['%label' => $item->getFieldDefinition()->getLabel()]
                        );
                    }
                }
            }

            if ($type == 'RP') {
                if (in_array($item->getName(), $rewardsField)) {
                    if (!$item->value) {
                        $this->context->addViolation(
                            $constraint->empty,
                            ['%label' => $item->getFieldDefinition()->getLabel()]
                        );
                    }
                }
            }
        }
    }
}
