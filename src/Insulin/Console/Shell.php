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

class Shell extends BaseShell
{
    /**
     * Returns the shell header.
     *
     * @return string
     *   The header string.
     */
    protected function getHeader()
    {
        return <<<EOF
<info>
     ______                           ___
    /\__  _\                         /\_ \    __
    \/_/\ \/     ___     ____  __  __\//\ \  /\_\    ___
       \ \ \   /' _ `\  /',__\/\ \/\ \ \ \ \ \/\ \ /' _ `\
        \_\ \__/\ \/\ \/\__, `\ \ \_\ \ \_\ \_\ \ \/\ \/\ \
        /\_____\ \_\ \_\/\____/\ \____/ /\____\\\ \_\ \_\ \_\
        \/_____/\/_/\/_/\/___/  \/___/  \/____/ \/_/\/_/\/_/

</info>
EOF
        .parent::getHeader();
    }
}
