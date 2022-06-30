<?php

/**
 * @copyright 2022 Alwin Garside
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace Yogarine\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Yogarine\Composer\Yaml\YamlFile;

/**
 * @author Alwin Garside <alwin@garsi.de>
 */
class YamlPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Max value to use as priority.
     */
    const PHP_MAX_SIGNED_INT = 2147483647;

    /**
     * @var \Yogarine\Composer\Yaml\YamlFile|null
     */
    protected $yamlFile;

    /**
     * @var \Composer\Json\JsonFile|null
     */
    protected $jsonFile;

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array{
     *             "init": array{ 0: string, 1: int }
     *         }
     */
    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::COMMAND => array('onCommand', self::PHP_MAX_SIGNED_INT),
            'require'             => array('onCommand', self::PHP_MAX_SIGNED_INT),
        );
    }

    /**
     * @return string
     */
    public static function getComposerYamlFilename()
    {
        return trim(getenv('COMPOSER')) ?: './composer.yaml';
    }

    /**
     * @param  \Composer\Composer        $composer
     * @param  \Composer\IO\IOInterface  $io
     * @return void
     *
     * @throws \Exception
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        if (null === $this->yamlFile) {
            $this->yamlFile = new YamlFile(self::getComposerYamlFilename(), null, $io);
        }

        if (null === $this->jsonFile) {
            $this->jsonFile = new JsonFile(Factory::getComposerFile(), null, $io);
        }

        // If no YAML file currently exist, generate it based on the current JSON file.
        if (! $this->yamlFile->exists()) {
            $this->yamlFile->importJsonFile($this->jsonFile);
        }

        // Always regenerate the JSON file based on the YAML file.
        $this->jsonFile->write($this->yamlFile->read());

        // Reinitialize the config so the new JSON file is loaded.
        $newComposer = Factory::create($io, $this->jsonFile->getPath(), true);

        $composer->setConfig($newComposer->getConfig());
        $composer->setPackage($newComposer->getPackage());
        $composer->setDownloadManager($newComposer->getDownloadManager());
        $composer->setAutoloadGenerator($newComposer->getAutoloadGenerator());
        $composer->setArchiveManager($newComposer->getArchiveManager());
    }

    /**
     * @param  \Composer\Plugin\CommandEvent  $commandEvent
     * @return bool
     *
     * @throws \Exception
     */
    public function onCommand(CommandEvent $commandEvent)
    {
        return true; //$this->updateYamlWithJson();
    }

    /**
     * @param  \Composer\Composer        $composer
     * @param  \Composer\IO\IOInterface  $io
     * @return bool
     *
     * @throws \Exception
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        $this->updateYamlWithJson();

        $this->jsonFile = null;
        $this->yamlFile = null;

        return true;
    }

    /**
     * @param  \Composer\Composer        $composer
     * @param  \Composer\IO\IOInterface  $io
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    private function updateYamlWithJson()
    {
        // If either of those is unset, no sense in comparing them.
        if (null !== $this->jsonFile && null !== $this->yamlFile) {
            $jsonData = $this->jsonFile->read();

            /**
             * Compare the JSON data with the YAML data.
             *
             * If they're the same, exit early.
             *
             * @noinspection TypeUnsafeComparisonInspection
             */
            if ($this->yamlFile->read() != $jsonData) {
                $this->yamlFile->write($jsonData);
            }
        }

        return true;
    }
}
