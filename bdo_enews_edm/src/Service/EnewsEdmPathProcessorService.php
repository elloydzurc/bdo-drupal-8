<?php
/**
 * Created by PhpStorm.
 * User: Elloyd Cruz
 * Date: 2/1/2019
 * Time: 9:26 AM
 */

namespace Drupal\bdo_enews_edm\Service;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

class EnewsEdmPathProcessorService implements OutboundPathProcessorInterface, InboundPathProcessorInterface
{
    /**
     * @var EnewsEdmService $edmServince
     */
    protected $edmService;

    /**
     * @var AliasManager $aliasManager
     */
    protected $aliasManager;

    /**
     * @var LanguageManager $languageManager
     */
    protected $languageManager;

    /**
     * EnewsEdmPathProcessorService constructor.
     * @param EnewsEdmService $edmService
     * @param AliasManager $aliasManager
     * @param LanguageManager $languageManager
     */
    public function __construct(
        EnewsEdmService $edmService,
        AliasManager $aliasManager,
        LanguageManager $languageManager
    ) {
        $this->edmService = $edmService;
        $this->aliasManager = $aliasManager;
        $this->languageManager = $languageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function processOutbound(
        $path,
        &$options = [],
        Request $request = null,
        BubbleableMetadata $bubbleable_metadata = null
    ) {
        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function processInbound($path, Request $request)
    {
        $pathArray = explode('/', $path);
        $last = array_pop($pathArray);

        if ($last == 'email') {
            $edmPath = implode('/', $pathArray);

            $edmInternalPath = $this->aliasManager->getPathByAlias(
                $edmPath,
                $this->languageManager->getDefaultLanguage()->getId()
            );

            if (preg_match('/node\/(\d+)/', $edmInternalPath, $node)) {
                $edm = $this->edmService->get([
                    'action' => 'get',
                    'nid' => $node[1]
                ]);

                if ($edm) {
                    $path = str_replace($edmPath, $edmInternalPath, $path);
                }
            }
        }

        return $path;
    }
}
