<?php

namespace Neemzy\Patchwork;

use Symfony\Component\Validator\Mapping\ClassMetadata;

interface ValidatableInterface
{
    public static function loadValidatorMetadata(ClassMetadata $metadata);
}
