<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/13/2019
 * Time: 10:47 AM
 */

namespace Drupal\bdo_stories\Form;

use Drupal\bdo_stories\Exception\SettingsNameNotSetException;
use Drupal\bdo_stories\Service\UrlMappingService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BDOStoriesUrlMappingDeletion extends ConfirmFormBase
{
    /**
     * @var UrlMappingService $urlMappingService
     */
    protected $urlMappingService;

    /**
     * @var $mapId
     */
    protected $mapId;

    /**
     * @param ContainerInterface $container
     * @return BDOStoriesUrlMappingDeletion|ConfirmFormBase
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('bdo_stories.url_mapping')
        );
    }

    /**
     * BDOStoriesUrlMappingDeletion constructor.
     * @param UrlMappingService $urlMappingService
     */
    public function __construct(UrlMappingService $urlMappingService)
    {
        $this->urlMappingService = $urlMappingService;
    }

    /**
     * @inheritdoc
     */
    public function getQuestion()
    {
        return $this->t('Do you want to delete %mapId?', ['%mapId' => $this->mapId]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('bdo_stories.url_mapping');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "bdo_stories_url_mapping_deletion";
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, String $mapId = null)
    {
        $this->mapId = $mapId;
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            $message = null;
            $input = $form_state->getValues();

            if (in_array($input['op'], ['Confirm'])) {
                // Dummy array data. Just leave the mapId, for deletion
                $data[$this->mapId] = [];
                $this->urlMappingService->init('delete', $data);
                $message = 'URL Mapping deleted!';
            }

            if ($message) {
                $this->messenger()->addMessage($message);
            }

            $form_state->setRedirect('bdo_stories.url_mapping');

        } catch (SettingsNameNotSetException $e) {
            \Drupal::logger('bdo_stories')->error($e->getMessage());
        }
    }
}
