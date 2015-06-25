<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Composer;

use Composer\Console\Application;
use Composer\IO\BufferIO;
use Composer\Factory as ComposerFactory;
use Symfony\Component\Console\Output\BufferedOutput;

class MagentoComposerApplication
{
    /**
     * Trigger checks config
     *
     * @var bool
     */
    private $configIsSet = false;

    /**
     * Path to Composer home directory
     *
     * @var string
     */
    private $composerHome;

    /**
     * Path to composer.json file
     *
     * @var string
     */
    private $composerJson;

    /**
     * Buffered output
     *
     * @var BufferedOutput
     */
    private $consoleOutput;

    /**
     * Constructs class
     *
     * @param Application $consoleApplication
     * @param ConsoleArrayInputFactory $consoleArrayInputFactory
     * @param BufferedOutput $consoleOutput
     */
    public function __construct(
        Application $consoleApplication = null,
        ConsoleArrayInputFactory $consoleArrayInputFactory = null,
        BufferedOutput $consoleOutput = null
    ) {
        $this->consoleApplication = $consoleApplication ? $consoleApplication : new Application();
        $this->consoleArrayInputFactory = $consoleArrayInputFactory ? $consoleArrayInputFactory
            : new ConsoleArrayInputFactory();
        $this->consoleOutput = $consoleOutput ? $consoleOutput : new BufferedOutput();
    }

    /**
     * Sets composer environment config
     *
     * @param string $pathToComposerHome
     * @param string $pathToComposerJson
     */
    public function setConfig($pathToComposerHome, $pathToComposerJson)
    {
        $this->composerJson = $pathToComposerJson;
        $this->composerHome = $pathToComposerHome;

        putenv('COMPOSER_HOME=' . $pathToComposerHome);
        putenv('COMPOSER=' . $pathToComposerJson);

        $this->consoleApplication->setAutoExit(false);
        $this->configIsSet = true;

    }

    /**
     * Returns composer object
     *
     * @return \Composer\Composer
     * @throws \Exception
     */
    public function getComposer()
    {
        if (!$this->configIsSet) {
            throw new \Exception('Please call setConfig method to configure composer');
        }

        return ComposerFactory::create(new BufferIO(), $this->composerJson);

    }

    /**
     * Runs composer command
     *
     * @param array $commandParams
     * @return bool
     * @throws \Exception
     * @throws \RuntimeException
     */
    public function runComposerCommand(array $commandParams)
    {
        $this->consoleApplication->resetComposer();

        if (!$this->configIsSet) {
            throw new \Exception('Please call setConfig method to configure composer');
        }

        $input = $this->consoleArrayInputFactory->create($commandParams);
        $this->consoleApplication->setAutoExit(false);

        $exitCode = $this->consoleApplication->run($input, $this->consoleOutput);

        if ($exitCode) {
            throw new \RuntimeException(
                sprintf('Command "%s" failed: %s', $commandParams['command'], $this->consoleOutput->fetch())
            );
        }

        //TODO: parse output based on command

        return $this->consoleOutput->fetch();
    }
}
