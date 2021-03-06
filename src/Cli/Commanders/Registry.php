<?php
/**
 * This file is part of the O2System Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author         Steeve Andrian Salim
 * @copyright      Copyright (c) Steeve Andrian Salim
 */

// ------------------------------------------------------------------------

namespace O2System\Reactor\Cli\Commanders;

// ------------------------------------------------------------------------

use O2System\Reactor\Cli\Commander;
use O2System\Kernel\Cli\Writers\Format;
use O2System\Kernel\Cli\Writers\Table;

/**
 * Class Registry
 *
 * @package O2System\Reactor\Cli\Commanders
 */
class Registry extends Commander
{
    /**
     * Make::$commandVersion
     *
     * Command version.
     *
     * @var string
     */
    protected $commandVersion = '1.0.0';

    /**
     * Make::$commandDescription
     *
     * Command description.
     *
     * @var string
     */
    protected $commandDescription = 'CLI_REGISTRY_DESC';

    /**
     * Make::$commandOptions
     *
     * Command options.
     *
     * @var array
     */
    protected $commandOptions = [
        'update'   => [
            'description' => 'CLI_REGISTRY_UPDATE_DESC',
            'help'        => 'CLI_REGISTRY_UPDATE_HELP',
        ],
        'flush'    => [
            'description' => 'CLI_REGISTRY_FLUSH_DESC',
        ],
        'info'     => [
            'description' => 'CLI_REGISTRY_INFO_DESC',
        ],
        'metadata' => [
            'description' => 'CLI_REGISTRY_METADATA_DESC',
            'help'        => 'CLI_REGISTRY_METADATA_HELP',
        ],
    ];

    /**
     * Registry::$optionLanguages
     *
     * @var bool
     */
    protected $optionLanguages = false;

    // ------------------------------------------------------------------------

    /**
     * Registry::optionLanguages
     */
    public function optionLanguages()
    {
        $this->optionLanguages = true;
    }

    // ------------------------------------------------------------------------

    /**
     * Registry::update
     *
     * @throws \Exception
     */
    public function update()
    {
        if($this->optionLanguages) {
            language()->updateRegistry();
        } else {
            modules()->updateRegistry();
            language()->updateRegistry();
        }

        exit(EXIT_SUCCESS);
    }

    // ------------------------------------------------------------------------

    /**
     * Registry::flush
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function flush()
    {
        if($this->optionLanguages) {
            language()->flushRegistry();
        } else {
            language()->flushRegistry();
        }

        exit(EXIT_SUCCESS);
    }

    // ------------------------------------------------------------------------

    /**
     * Registry::info
     */
    public function info()
    {
        $table = new Table();

        $table
            ->addHeader('Metadata')
            ->addHeader('Total');

        $table
            ->addRow()
            ->addColumn('Language')
            ->addColumn(language()->getTotalRegistry());

        output()->write(
            (new Format())
                ->setString($table->render())
                ->setNewLinesBefore(1)
                ->setNewLinesAfter(2)
        );

        exit(EXIT_SUCCESS);
    }

    // ------------------------------------------------------------------------

    /**
     * Registry::metadata
     */
    public function metadata()
    {
        if($this->optionLanguages) {
            $line = PHP_EOL . print_r(language()->getRegistry(), true);
        } else {
            $line.= PHP_EOL . print_r(language()->getRegistry(), true);
        }

        if (isset($line)) {
            output()->write($line);

            exit(EXIT_SUCCESS);
        }
    }
}