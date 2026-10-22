<?php

namespace App\Twig;

use App\SearchService;
use App\StatsProvider;
use App\Twig\Helper\RenderingHelper;
use JoliTypo\Fixer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    /** @var Fixer */
    private $fixer;

    /** @var RenderingHelper */
    private $helper;

    /** @var StatsProvider */
    private $statsProvider;

    /** @var SearchService $search */
    private $search;

    private static $forceUppercase = [
        'Arabic',
        'Aristotelian logic',
        'Catalan',
        'Chinese writing system',
        'Czech',
        'English',
        'French',
        'German',
        'Greek',
        'Hebrew',
        'Infima',
        'Italian',
        'Latin',
        'Latin poetry',
        'Portuguese',
        'Russian',
        'Spanish',
        'Turkish',
    ];

    public function __construct(SearchService $search, RenderingHelper $helper, StatsProvider $provider)
    {
        $this->search = $search;
        $this->helper = $helper;
        $this->fixer = new Fixer(
            ['Ellipsis', 'Dash', 'EnglishQuotes', 'CurlyQuote']
        );
        $this->statsProvider = $provider;
    }
    public function getFilters(): array
    {
        return [
            new TwigFilter('unset', [$this, 'unsetArray']),
            new TwigFilter('bit_add', [$this, 'addBit']),
            new TwigFilter('bit_remove', [$this, 'removeBit']),
            new TwigFilter('str_add', [$this, 'addString']),
            new TwigFilter('str_remove', [$this, 'removeString']),
            new TwigFilter('smart_quotes', [$this, 'smartQuotes'], [
                'is_safe' => ['html']
            ]),
            new TwigFilter('replace_links', [$this->helper, 'renderDescription'], [
                'is_safe' => ['html']
            ]),
            new TwigFilter('format_country', ['App\Helper', 'formatCountry']),
            new TwigFilter('slugify', ['App\Helper', 'slugify']),
            new TwigFilter('format_continent', ['App\Helper', 'formatContinent']),
            new TwigFilter('lcfirst', 'lcfirst'),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('lowercaseable', [$this, 'lowercaseable']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('stats', [$this->statsProvider, 'get']),
            new TwigFunction('subjectGroupTree', [$this->search, 'getSubjectGroupTree']),
        ];
    }
    public function smartQuotes($value)
    {
        return $this->fixer->fix($value);
    }
    public function unsetArray($array, $key)
    {
        if (!is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            unset($array[$k]);
        }

        return $array;
    }

    public function addString($params, $key, $str)
    {
        if (array_key_exists($key, $params)) {
            $items = explode(' ', $params[$key]);
        } else {
            $items = array();
        }

        $items[] = $str;
        $items = array_unique($items);

        $params[$key] = implode(' ', $items);

        return $params;
    }

    public function removeString($params, $key, $str)
    {
        if (!array_key_exists($key, $params)) {
            return $params;
        }

        $items = explode(' ', $params[$key]);
        $items = array_filter($items, function ($i) use ($str) {
            return $i != $str;
        });
        $params[$key] = implode(' ', $items);

        return $params;
    }

    public function addBit($params, $key, $bit)
    {
        $params[$key] = $params[$key] | $bit;
        return $params;
    }

    public function removeBit($params, $key, $bit)
    {
        $params[$key] = $params[$key] & ~$bit;
        return $params;
    }

    public function lowercaseable($value)
    {
        if (in_array($value, self::$forceUppercase)) {
            return false;
        }

        return true;
    }
}
