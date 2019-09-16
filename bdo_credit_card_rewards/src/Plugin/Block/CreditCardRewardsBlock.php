<?php

namespace Drupal\bdo_credit_card_rewards\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;

/**
 * {@inheritdoc}
 *
 * @Block(
 *      id = "bdo_credit_card_rewards_block",
 *      admin_label = @Translation("BDO Credit Card Rewards Filter"),
 *      category = @Translation("BDO")
 * )
 */
class CreditCardRewardsBlock extends BlockBase
{
    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $form = \Drupal::formBuilder()
            ->getForm('Drupal\bdo_credit_card_rewards\Form\CreditCardRewardsForm');

        return $form;
    }
}
