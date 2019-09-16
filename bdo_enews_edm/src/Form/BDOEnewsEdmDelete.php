<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 1/30/2019
 * Time: 10:51 AM
 */

namespace Drupal\bdo_enews_edm\Form;

use Drupal\bdo_enews_edm\Service\EnewsEdmService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BDOEnewsEdmDelete extends ConfirmFormBase
{
    /**
     * @var $edm
     */
    private $edm;

    /**
     * @var EnewsEdmService $edmService
     */
    protected $edmService;

    /**
     * BDOEnewsEdmDelete constructor.
     * @param EnewsEdmService $edmService
     */
    public function __construct(EnewsEdmService $edmService)
    {
        $this->edmService = $edmService;
    }

    /**
     * @param ContainerInterface $container
     * @return BDOEnewsEdmDelete|ConfirmFormBase
     */
    public static function create(ContainerInterface $container)
    {
        /** @noinspection PhpParamsInspection */
        return new static(
            $container->get('bdo_enews_edm.enews_edm')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, String $edmid = null)
    {
        $this->edm = $this->edmService->get([
            'action' => 'get',
            'edmid' => $edmid
        ]);

        $form['nid'] = [
            '#type' => 'value',
            '#value' => $this->edm[0]->nid
        ];

        $form['edmid'] = [
            '#type' => 'value',
            '#value' => $this->edm[0]->edmid
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t(
            'Do you want to delete eNews EDM: %edm?',
            ['%edm' => $this->edm[0]->node_title]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('bdo_enews_edm.dashboard');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return "bdo_enews_edm_delete";
    }

    /**
     * {@inheritdoc}
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $input = $form_state->getValues();

        if ($input['op'] == 'Confirm') {
            $this->edmService->init([
                'action' => 'delete',
                'nid' => $input['nid'],
                'edmid' => $input['edmid']
            ]);

            $this->messenger()->addMessage('The content has been deleted');
            $form_state->setRedirect('bdo_enews_edm.dashboard');
        }
    }
}
