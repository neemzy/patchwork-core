<?php

namespace Neemzy\Patchwork;

use Symfony\Component\Validator\Mapping\ClassMetadata;

interface ValidatableInterface
{
    /**
     * Valorizes model validation metadata
     *
     * @return void
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata);
}
