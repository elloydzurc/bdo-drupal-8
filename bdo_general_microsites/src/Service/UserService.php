<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/21/2019
 * Time: 10:23 AM
 */

namespace Drupal\bdo_general_microsites\Service;

use Drupal\bdo_general_microsites\Service\Contract\StorageQuery;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class UserService extends StorageQuery
{
    /**
     * UserService constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        parent::__construct($entityTypeManager, 'user', 'bdo_general_microsites');
    }

    /**
     * Format storage query results according to your will
     * @param $results
     * @param $raw
     * @return mixed
     */
    protected function format($results, $raw)
    {
        if (!$raw) {
            $options = [];
            foreach ($results as $user) {
                $options[$user->id()] = $user->getAccountName();
            }
            return $options;
        }

        return $results;
    }
}
