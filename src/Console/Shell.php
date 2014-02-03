<?php

/*
 * This file is part of the Insulin CLI
 *
 * Copyright (c) 2008-2013 Filipe Guerra, João Morais
 * http://cli.sugarmeetsinsulin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Insulin\Console;

use Symfony\Component\Console\Shell as BaseShell;

/**
 * An Insulin Shell wraps an Application to add shell capabilities to it.
 *
 * This extends from Symfony's Shell default abilities.
 *
 * @codeCoverageIgnore because we are only applying the logo in the header.
 */
class Shell extends BaseShell
{
    /**
     * Returns the shell header.
     *
     * @return string The header string
     */
    protected function getHeader()
    {
        $app = $this->getApplication();
        return '<info>' . $app::$logo . '</info>' . parent::getHeader();
    }
}
