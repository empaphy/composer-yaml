<?php

/**
 * @copyright 2022 Alwin Garside
 * @license   https://opensource.org/licenses/MIT MIT
 */

declare(strict_types=1);

namespace Empaphy\Composer;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Plugin\PluginInterface;
use Empaphy\Composer\Yaml\YamlFile;

/**
 * @author Alwin Garside <alwin@garsi.de>
 * @noinspection PhpUnused
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
     * @return string
     */
    public static function getComposerYamlFilename(): string
    {
        return trim((string) getenv('COMPOSER')) ?: './composer.yaml';
    }

    /**
     * @param  \Composer\Composer        $composer
     * @param  \Composer\IO\IOInterface  $io
     * @return void
     *
     * @throws \Exception
     */
    public function activate(Composer $composer, IOInterface $io): void
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
     * @param  \Composer\Composer        $composer
     * @param  \Composer\IO\IOInterface  $io
     * @return bool
     *
     * @throws \Exception
     */
    public function deactivate(Composer $composer, IOInterface $io): bool
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
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // ¯\_(ツ)_/¯
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    private function updateYamlWithJson(): void
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
