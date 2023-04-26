<?php

/**
 * @copyright 2023 Alwin Garside
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace Empaphy\Composer;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Plugin\PluginInterface;
use Composer\Util\Platform;
use Empaphy\Composer\Yaml\YamlFile;

/**
 * @author Alwin Garside <alwin@garsi.de>
 */
class YamlPlugin implements PluginInterface
{
    /**
     * @var \Empaphy\Composer\Yaml\YamlFile|null
     */
    protected $yamlFile;

    /**
     * @var \Composer\Json\JsonFile|null
     */
    protected $jsonFile;

    /**
     * @param  string  $composerJsonFilename
     * @return string
     */
    public static function getYamlFilename($composerJsonFilename)
    {
        $pathinfo = pathinfo($composerJsonFilename);

        return "{$pathinfo['dirname']}/{$pathinfo['filename']}.yaml";
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
        $config     = $composer->getConfig();
        $configPath = $config->getSourceOfValue('allow-plugins.empaphy/composer-yaml');

        if (null === $this->jsonFile) {
            $this->jsonFile = new JsonFile($configPath, null, $io);
        }

        if (null === $this->yamlFile) {
            $this->yamlFile = new YamlFile(self::getYamlFilename($configPath), null, $io);
        }

        // If no YAML file currently exist, generate it based on the current JSON file.
        if (! $this->yamlFile->exists()) {
            $io->writeError("Generating new composer.yaml file from JSON", true, IOInterface::VERBOSE);
            $this->yamlFile->importJsonFile($this->jsonFile);
        }

        // Always regenerate the JSON file based on the YAML file.
        $io->writeError("Writing composer.json file", true, IOInterface::VERBOSE);
        $this->jsonFile->write($this->yamlFile->read());

        // Reinitialize the config so the new JSON file is loaded.
        $factory     = new Factory();
        $newComposer = $factory->createComposer($io, $configPath, true, Platform::getCwd(true));

        $io->writeError("Reinitializing Composer config", true, IOInterface::VERBOSE);

        $composer->setConfig($newComposer->getConfig());
        $composer->setPackage($newComposer->getPackage());
        $composer->setDownloadManager($newComposer->getDownloadManager());
        $composer->setAutoloadGenerator($newComposer->getAutoloadGenerator());
        $composer->setArchiveManager($newComposer->getArchiveManager());
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
        // ¯\_(ツ)_/¯
    }

    /**
     * @return void
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
    }
}
