<?php

namespace App\Core\Interfaces;

interface PDFGeneratorInterface
{
    public function fromView(string $view, array $data): string;
}
