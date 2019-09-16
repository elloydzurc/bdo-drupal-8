<?php

namespace Drupal\bdo_general_microsites\Batch;

use Drupal;
use Drupal\bdo_general_microsites\Service\MicrositeService;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Messenger\Messenger;
use Drupal\node\NodeStorage;

class UserSettingBatchProcess
{
    use DependencySerializationTrait;

    const LIMIT = 20;

    /**
     * @var NodeStorage $nodeStorage
     */
    protected $userStorage;

    /**
     * @var Messenger $messenger
     */
    protected $messenger;

    /**
     * @var MicrositeService $micrositeService
     */
    protected $micrositeService;

    /**
     * @var array $multiLingual
     */
    protected $multiLingual;

    /**
     * @var string $languageId
     */
    protected $languageId;

    /**
     * @var array $input
     */
    protected $input;

    /**
     * An array of ID for Microsite editor
     * @var array $editorsId
     */
    protected $editorsId;

    /**
     * An array of ID for Microsite publisher
     * @var array $publishersId
     */
    protected $publishersId;

    /**
     * SortBatchProcess constructor.
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param Messenger $messenger
     * @param LanguageManager $languageManager
     * @param MicrositeService $micrositeService
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Messenger $messenger,
        LanguageManager $languageManager,
        MicrositeService $micrositeService
    ) {
        $this->userStorage = $entityTypeManager->getStorage('user');
        $this->languageId = $languageManager->getDefaultLanguage()->getId();

        $this->messenger = $messenger;
        $this->micrositeService = $micrositeService;

        // Initialize to an empty array
        $this->editorsId = [];
        $this->publishersId = [];
    }

    /**
     * Processor for batch operations.
     * @param array $input
     * @param array $users
     * @param array $multiLingual
     * @param array $context
     * @throws EntityStorageException
     */
    public function processUsers(
        array $input,
        array $users,
        array $multiLingual,
        array &$context
    ) {
        $counter = 0;
        $this->input = $input;
        $this->multiLingual = $multiLingual;

        // Set default progress values.
        if (empty($context['sandbox']['progress'])) {
            $context['sandbox']['progress'] = 0;
            $context['sandbox']['max'] = count($users);
        }

        // Save users to array which will be changed during processing.
        if (empty($context['sandbox']['users'])) {
            $context['sandbox']['users'] = $users;
            $context['results']['msid'] = $this->input['msid'];
            $this->removePreviousRoles();
        }

        if (!empty($context['sandbox']['users'])) {
            // Remove already processed users.
            if ($context['sandbox']['progress'] != 0) {
                array_splice($context['sandbox']['users'], 0, self::LIMIT);
            }

            foreach ($context['sandbox']['users'] as $userId) {
                if ($counter != self::LIMIT) {
                    $processOutput = $this->processUser($userId);

                    // This will return the processed users and their role to finished() callback
                    foreach ($processOutput as $key => $output) {
                        foreach ($output as $lang => $user) {
                            $context['results'][$key][$lang][] = $user;
                        }
                    }

                    $counter++;
                    $context['sandbox']['progress']++;

                    $context['message'] = t('Now processing node :progress of :count', [
                        ':progress' => $context['sandbox']['progress'],
                        ':count' => $context['sandbox']['max'],
                    ]);

                    // Increment total processed user values. Will be used in finished callback
                    $context['results']['processed'] = $context['sandbox']['progress'];
                }
            }
        }

        // If not finished all tasks, we count percentage of process. 1 = 100%.
        if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
            $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        }
    }

    /**
     * Add role to user. Processed single user at a time
     * @param $userId
     * @return mixed
     * @throws EntityStorageException
     */
    public function processUser($userId)
    {
        $output = [];
        $user = $this->userStorage->load($userId);
        $uid = $user->id();

        $machineName = $this->input['machine_name'];

        $editorRole['default'] = $machineName . '_default_content_editor';
        $publisherRole['default'] = $machineName . '_default_content_publisher';

        if (in_array($uid, $this->input['default_editor'])) {
            $output['editors']['default'] = $uid;
            if (!$user->hasRole($editorRole['default'])) {
                $user->addRole($editorRole['default']);
            }
        }

        if (in_array($uid, $this->input['default_publisher'])) {
            $output['publishers']['default'] = $uid;
            if (!$user->hasRole($publisherRole['default'])) {
                $user->addRole($publisherRole['default']);
            }
        }

        if (in_array($uid, $this->multiLingual) && $this->isSerialized($this->input['site_languages'])) {
            $supportedLanguages = unserialize($this->input['site_languages']);
            foreach ($supportedLanguages as $language) {
                if ($language != $this->languageId) {
                    $editorRole[$language] = $machineName . '_' . $language . '_content_editor';
                    $publisherRole[$language] = $machineName . '_' . $language . '_content_publisher';

                    if (in_array($user->id(), $this->input[$language . '_content_editor'])) {
                        $output['editors'][$language] = $uid;
                        if (!$user->hasRole($editorRole[$language])) {
                            $user->addRole($editorRole[$language]);
                        }
                    }

                    if (in_array($user->id(), $this->input[$language . '_content_publisher'])) {
                        $output['publishers'][$language] = $uid;
                        if (!$user->hasRole($publisherRole[$language])) {
                            $user->addRole($publisherRole[$language]);
                        }
                    }
                }
            }
        }

        $user->save();
        return $output;
    }

    /**
     * Update Microsite editors and publishers list
     * @param $success
     * @param $results
     * @param $operations
     */
    public function finished($success, $results, $operations)
    {
        $fields = [
            'editors' => serialize($results['editors']),
            'publishers' => serialize($results['publishers']),
            'changed' => Drupal::time()->getRequestTime()
        ];

        $conditions = [
            ['field' => 'msid', 'value' => $results['msid']]
        ];

        $this->micrositeService->update($fields, $conditions);

        $message = t('Number of users assigned as Editor/Publisher: @count', [
            '@count' => $results['processed']
        ]);

        $this->messenger
             ->addMessage($message);
    }

    /**
     * Remove previous users role on Microsite
     * @throws EntityStorageException
     */
    private function removePreviousRoles()
    {
        $machineName = $this->input['machine_name'];

        if ($this->isSerialized($this->input['current_editors'])) {
            $currentEditors = unserialize($this->input['current_editors']);
            foreach ($currentEditors as $lang => $editors) {
                foreach ($editors as $editor) {
                    $user = $this->userStorage->load($editor);
                    $user->removeRole($machineName . '_' . $lang . '_content_editor');
                    $user->save();
                }
            }
        }

        if ($this->isSerialized($this->input['current_publishers'])) {
            $currentPublishers = unserialize($this->input['current_publishers']);
            foreach ($currentPublishers as $lang => $publishers) {
                foreach ($publishers as $publisher) {
                    $user = $this->userStorage->load($publisher);
                    $user->removeRole($machineName . '_' . $lang . '_content_publisher');
                    $user->save();
                }
            }
        }
    }

    /**
     * Check if string can be serialize
     * @param $string
     * @return bool
     */
    private function isSerialized($string)
    {
        return (@unserialize($string) !== false);
    }
}
