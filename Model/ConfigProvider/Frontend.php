<?php
/**
 * Copyright (C) 2023 Pixel Développement
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PixelOpen\CloudflareTurnstile\Model\ConfigProvider;

use Magento\Framework\Locale\ResolverInterface;
use PixelOpen\CloudflareTurnstile\Helper\Config;
use PixelOpen\CloudflareTurnstile\Model\ConfigProviderInterface;

class Frontend implements ConfigProviderInterface
{
    protected Config $config;

    protected ResolverInterface $localeResolver;

    /**
     * @param Config $config
     * @param ResolverInterface $localeResolver
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver
    ) {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return [
            'config' => [
                'enabled'  => $this->config->isEnabledOnFront(),
                'sitekey'  => $this->config->getSiteKey(),
                'theme'    => $this->config->getFrontendTheme(),
                'size'     => $this->config->getFrontendSize(),
                'forms'    => $this->config->getFrontendForms(),
                'language' => $this->config->getWidgetLanguage($this->localeResolver->getLocale()),
            ]
        ];
    }
}
