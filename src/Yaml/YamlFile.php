<?php

/**
 * @copyright 2022 Alwin Garside
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace Yogarine\Composer\Yaml;

use Composer\Downloader\TransportException;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Util\RemoteFilesystem;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

/**
 * @author Alwin Garside <alwin@garsi.de>
 */
class YamlFile
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var \Composer\Util\RemoteFilesystem|null
     */
    private $rfs;

    /**
     * @var \Composer\IO\IOInterface|null
     */
    private $io;

    /**
     * Initializes json file reader/parser.
     *
     * @param  string                           $path              Path to a YAML file.
     * @param  \Composer\Util\RemoteFilesystem  $remoteFilesystem  Required for loading http/https yaml files.
     * @param  \Composer\IO\IOInterface         $io
     *
     * @throws \InvalidArgumentException                        If a RemoteFilesystem instance is needed but not passed.
     * @throws \RuntimeException                                If the config data couldn't be converted to JSON.
     * @throws \Symfony\Component\Yaml\Exception\ParseException If the file could not be read or the YAML is not valid.
     */
    public function __construct($path, RemoteFilesystem $remoteFilesystem = null, IOInterface $io = null)
    {
        if (null === $remoteFilesystem && preg_match('{^https?://}i', $path)) {
            throw new InvalidArgumentException('http urls require a RemoteFilesystem instance to be passed');
        }

        $this->path = $path;
        $this->rfs  = $remoteFilesystem;
        $this->io   = $io;
    }

    /**
     * @param  \Composer\Json\JsonFile  $jsonFile
     * @return void
     *
     * @throws \Exception
     */
    public function importJsonFile(JsonFile $jsonFile)
    {
        $this->write($jsonFile->read());
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Checks whether json file exists.
     *
     * @return bool
     */
    public function exists()
    {
        return is_file($this->path);
    }

    /**
     * Reads YAML file.
     *
     * @return array|\Symfony\Component\Yaml\Tag\TaggedValue|null
     *
     * @throws \RuntimeException
     */
    public function read()
    {
        try {
            if ($this->rfs) {
                $yaml = $this->rfs->getContents($this->path, $this->path, false);
            } else {
                if ($this->io && $this->io->isDebug()) {
                    $this->io->writeError('Reading ' . $this->path);
                }
                $yaml = file_get_contents($this->path);
            }
        } catch (TransportException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        } catch (Exception $e) {
            throw new RuntimeException('Could not read '.$this->path."\n\n".$e->getMessage());
        }

        return static::parseYaml($yaml);
    }

    /**
     * Writes YAMl file.
     *
     * @param  array  $hash     Writes hash into yaml file.
     * @param  int    $options  A bit field of DUMP_* constants to customize the dumped YAML string.
     * @return void
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException If target dir doesn't exist or is inaccessible.
     */
    public function write(array $hash, $options = 0)
    {
        $dir = dirname($this->path);

        if (! is_dir($dir)) {
            if (file_exists($dir)) {
                throw new UnexpectedValueException("{$dir} exists and is not a directory.");
            }
            if (! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
                throw new UnexpectedValueException("Directory {$dir} does not exist and could not be created.");
            }
        }

        $retries = 3;
        while ($retries--) {
            try {
                $this->filePutContentsIfModified($this->path, static::encode($hash, $options));
                break;
            } catch (Exception $e) {
                if ($retries) {
                    usleep(500000);
                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Modify file properties only if content modified.
     *
     * @param  string  $path
     * @param  string  $content
     * @return int|false The number of bytes that were written to the file, or false on failure.
     *
     * @noinspection PhpReturnValueOfMethodIsNeverUsedInspection
     */
    private function filePutContentsIfModified($path, $content)
    {
        $currentContent = @file_get_contents($path);
        if (! $currentContent || ($currentContent !== $content)) {
            return file_put_contents($path, $content);
        }

        return 0;
    }

    /**
     * Validates the schema of the current json file according to composer-schema.json rules
     *
     * @param  int                     $schema a JsonFile::*_SCHEMA constant
     * @param  string|null             $schemaFile a path to the schema file
     * @return bool                    true on success
     *
     * @throws \Composer\Json\JsonValidationException  If the data does not match the expected JSON schema.
     * @throws \RuntimeException                       If the JSON encoding fails.
     */
    public function validateJsonSchema($schema = JsonFile::STRICT_SCHEMA, $schemaFile = null)
    {
        $data = $this->read();
        $json = JsonFile::encode($data, 448);

        // Wrap JSON data in a BASE64 encoded `data://` stream.
        $jsonPath = 'data://text/plain;base64,' . base64_encode($json);

        $jsonFile = new JsonFile($jsonPath, null, $this->io);
        return $jsonFile->validateSchema($schema, $schemaFile);
    }

    /**
     * Encodes an array into (optionally pretty-printed) JSON
     *
     * @param  mixed  $data     Data to encode into a formatted JSON string.
     * @param  int    $options  A bit field of DUMP_* constants to customize the dumped YAML string.
     * @return string Encoded YAML.
     */
    public static function encode($data, $options = 0)
    {
        return Yaml::dump($data, 2, 4, $options);
    }

    /**
     * Parses YAML string and returns hash.
     *
     * @param  string|null  $yaml  YAML string.
     * @return array|\Symfony\Component\Yaml\Tag\TaggedValue|null
     *
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public static function parseYaml($yaml)
    {
        if (null === $yaml) {
            return null;
        }

        return Yaml::parse($yaml, 0);
    }
}
