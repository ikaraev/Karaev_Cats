<?php

declare(strict_types=1);

namespace Karaev\Cats\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class ConfigHelper
 * @package Karaev\Cats\Helper
 */
class ConfigHelper extends AbstractHelper
{
    /**
     * @var string
     */
    const XML_PATH_CAT_ENABLE = 'cat/general/enabled';
    const XML_PATH_CAT_PHRASE = 'cat/general/phrase';
    const XML_PATH_CAT_REMOVE = 'cat/general/remove';

    /**
     * @return bool
     */
    public function isModuleEnable(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CAT_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function isRemoveProductImages(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_CAT_REMOVE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getCatPhrase(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_CAT_PHRASE, ScopeInterface::SCOPE_STORE);
    }
}
