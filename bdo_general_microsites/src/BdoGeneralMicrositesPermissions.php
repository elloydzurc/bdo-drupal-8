<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/24/2019
 * Time: 9:03 AM
 */

namespace Drupal\bdo_general_microsites;

use Drupal\bdo_general_microsites\Service\MicrositeService;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BdoGeneralMicrositesPermissions implements ContainerInjectionInterface
{
    use StringTranslationTrait;

    /**
     * @var MicrositeService $micrositeService
     */
    protected $micrositeService;

    /**
     * BdoGeneralMicrositesPermissions constructor.
     * @param MicrositeService $micrositeService
     */
    public function __construct(MicrositeService $micrositeService)
    {
        $this->micrositeService = $micrositeService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('bdo_general_microsites.microsite')
        );
    }

    /**
     * Generate microsites permissions
     * @return array
     */
    public function permissions()
    {
        $permissions = [];
        $microsites = $this->micrositeService->all();

        foreach ($microsites as $microsite) {
            $permissions["administer " . $microsite->machine_name .  " microsite"] = [
                'title' => $this->t('Administer %microsite_name', ['%microsite_name' => $microsite->name]),
                'description' => $this->t(
                    'Perform administrative tasks on the %microsite_name Microsite',
                    ['%microsite_name' => $microsite->name]
                )
            ];

            $permissions["administer " . $microsite->machine_name . " content"] = [
                'title' => $this->t('Manage content for %microsite_name', ['%microsite_name' => $microsite->name]),
                'description' => $this->t(
                    'Manage the content for %microsite_name Microsite',
                    ['%microsite_name' => $microsite->name]
                )
            ];

            $permissions["administer " . $microsite->machine_name . " audit logs"] = [
                'title' => $this->t('Administer '. $microsite->name .' Audit Logs'),
                'description' => $this->t(
                    'Perform administrative tasks on the %microsite_name Audit Logs.',
                    ['%microsite_name' => $microsite->name]
                )
            ];
        }

        return $permissions;
    }
}
