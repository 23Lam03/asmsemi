<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('random_sample', [$this, 'randomSample']),
        ];
    }

    public function randomSample($array, $count)
    {
        $array = is_array($array) ? $array : json_decode(json_encode($array), true);
        shuffle($array);
        return array_slice($array, 0, $count);
    }

}