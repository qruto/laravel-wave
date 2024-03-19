<?php

namespace Qruto\Wave\Console\Commands;

use function realpath;

class ConfigPublishCommand extends \Illuminate\Foundation\Console\ConfigPublishCommand
{
    /**
     * Override the base broadcast configuration file.
     *
     * {@inheritdoc}
     */
    protected function getBaseConfigurationFiles(): array
    {
        return [
            'broadcasting' => realpath(__DIR__.'/../../../config/broadcasting.php'),
        ] + parent::getBaseConfigurationFiles();
    }
}
