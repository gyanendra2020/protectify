<?php

use Illuminate\Support\Str;

function duration_to_string($duration) {
    $totalSeconds = ceil($duration / 1000);
	$totalMinutes = floor($totalSeconds / 60);
	$seconds = $totalSeconds - $totalMinutes * 60;
	$totalHours = floor($totalMinutes / 60);
	$minutes = $totalMinutes - $totalHours * 60;
    $hours = $totalHours;

	return $hours . ':' . Str::padLeft($minutes, 2, '0') . ':' . Str::padLeft($seconds, 2, '0');
}

function resolve_url(string $uri, ?string $baseUri): string {
    $uri = \GuzzleHttp\Psr7\Utils::uriFor($uri);

    if (isset($baseUri)) {
        $uri = \GuzzleHttp\Psr7\UriResolver::resolve(\GuzzleHttp\Psr7\Utils::uriFor($baseUri), $uri);
    }

    $uri = $uri->getScheme() === '' && $uri->getHost() !== '' ? $uri->withScheme('http') : $uri;

    return (string) $uri;
}

function get_visual_resource_urls_from_html_old($html) {
    $document = new DOMDocument();
    libxml_clear_errors();
    $previousUseInternalErrorsFlag = libxml_use_internal_errors(true);
    $document->loadHTML($html, LIBXML_COMPACT);
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseInternalErrorsFlag);
    $elements = $document->getElementsByTagName('*');
    $resources = collect();

    foreach ($elements as $element) {
        if (
            $element->tagName === 'link' &&
            $element->getAttribute('rel') === 'stylesheet' &&
            $element->getAttribute('href')
        ) {
            $resources->push($element->getAttribute('href'));
        }

        // elseif (
        //     $element->tagName === 'object' &&
        //     $element->getAttribute('type') === 'image/svg+xml' &&
        //     $element->getAttribute('data')
        // ) {
        //     $resources->push($element->getAttribute('data'));
        // } elseif ($element->tagName === 'img' && $element->getAttribute('src')) {
        //     $resources->push($element->getAttribute('src'));
        // }
    }

    foreach ($elements as $element) {
        if ($element->tagName === 'style' && $element->textContent) {
            $resources = $resources->merge(get_visual_resource_urls_from_css($element->textContent));
        } elseif ($element->getAttribute('style')) {
            $resources = $resources->merge(get_visual_resource_urls_from_css($element->getAttribute('style')));
        }
    }

    $resources = $resources->unique()->values();

    return $resources->toArray();
}

function get_visual_resource_urls_from_html($html) {
    // preg_match_all('/<link\s{1}.*href=[\"|\']?(.*?)[\"|\']?.*rel=[\"|\']?stylesheet[\"|\']?.*>/si', $html, $matches, PREG_PATTERN_ORDER);
    $resourceUrls = collect();
    preg_match_all('/<\s*(?:link|object|img).+?>/si', $html, $tagMatches);

    foreach ($tagMatches[0] as $tagMatch) {
        if (preg_match('/^<\s*link\s+[^<>]*?rel=[\"|\']stylesheet[\"|\']\s*href\s*=\s*[\"|\']?(.+?)[\"|\']?(?:\s|\/>|>)/si', $tagMatch, $matches)) {
            $resourceUrls->push($matches[1]);
        } elseif (preg_match('/^<\s*link\s+[^<>]*?href\s*=\s*[\"|\']?(.+?)[\"|\']?\s*rel=[\"|\']stylesheet[\"|\']?(?:\s|\/>|>)/si', $tagMatch, $matches)) {
            $resourceUrls->push($matches[1]);
        } elseif (preg_match('/^<\s*object\s+[^<>]*?type=[\"|\']image\/svg+xml[\"|\']\s*data\s*=\s*[\"|\']?(.+?)[\"|\']?(?:\s|\/>|>)/si', $tagMatch, $matches)) {
            $resourceUrls->push($matches[1]);
        } elseif (preg_match('/^<\s*object\s+[^<>]*?data\s*=\s*[\"|\']?(.+?)[\"|\']?\s*type=[\"|\']image\/svg+xml[\"|\']?(?:\s|\/>|>)/si', $tagMatch, $matches)) {
            $resourceUrls->push($matches[1]);
        } elseif (preg_match('/^<\s*img\s+[^<>]*?src\s*=\s*[\"|\']?(.+?)[\"|\']?(?:\s|\/>|>)/si', $tagMatch, $matches)) {
            $resourceUrls->push($matches[1]);
        }
    }

    preg_match_all('/<\s*style[^<>]*?>.*?<\/\s*style\s*>/si', $html, $tagMatches);

    foreach ($tagMatches[0] as $tagMatch) {
        if (preg_match('/^<\s*style[^<>]*?>(.*?)<\/\s*style\s*>$/si', $tagMatch, $matches)) {
            $resourceUrls = $resourceUrls->merge(get_visual_resource_urls_from_css($matches[1]));
        }
    }

    $resourceUrls = $resourceUrls->unique();

    $resourceUrls = $resourceUrls->filter(function ($resourceUrl) {
        return !Str::startsWith($resourceUrl, 'data:') && !Str::startsWith($resourceUrl, 'chrome-extension:');
    });

    $resourceUrls = $resourceUrls->values();

    return $resourceUrls->toArray();
}

function get_visual_resource_urls_from_css($css) {
    $resourceUrls = collect();
    preg_match_all('/@import\s+(?:url\()?[\"|\']?(.*?)[\"|\']?\s*\)?\s*;/si', $css, $matches, PREG_PATTERN_ORDER);
    $resourceUrls = $resourceUrls->merge($matches[1]);
    preg_match_all('/url\(\s*[\"|\']?(.*?)[\"|\']?\s*\)/si', $css, $matches, PREG_PATTERN_ORDER);
    $resourceUrls = $resourceUrls->merge($matches[1]);
    $resourceUrls = $resourceUrls->unique();

    $resourceUrls = $resourceUrls->filter(function ($resourceUrl) {
        return !Str::startsWith($resourceUrl, 'data:') && !Str::startsWith($resourceUrl, 'chrome-extension:');
    });

    $resourceUrls = $resourceUrls->values();

    return $resourceUrls->toArray();
}

function replace_resource_urls_in_html($html, $replacements) {
    foreach ($replacements as $replacement) {
        $html = preg_replace(
            '/<([^<>]+?)=\s*([\"|\']?)\s*' . preg_quote($replacement['old'], '/') . '\s*([\"|\']?)([^<>]*?)/si',
            '<$1=$2' . $replacement['new'] . '$3$4',
            $html
        );

        $html = preg_replace(
            '/<\s*style([^<>]*?)>([^<' . '>]*?)@import\s*([\"|\']?)' . preg_quote($replacement['old'], '/') . '([\"|\']?);([^<' . '>]*?)<\s*\/\s*style\s*>/si',
            '<sty' . 'le$1>$2@import $3' . $replacement['new'] . '$4;$5</style>',
            $html
        );

        $html = preg_replace(
            '/<\s*style([^<>]*?)>([^<' . '>]*?)url\(([\"|\']?)' . preg_quote($replacement['old'], '/') . '([\"|\']?)\)([^<' . '>]*?)<\s*\/\s*style\s*>/si',
            '<sty' . 'le$1>$2url($3' . $replacement['new'] . '$4)$5</style>',
            $html
        );
    }

    return $html;
}

function replace_resource_urls_in_css($css, $replacements) {
    foreach ($replacements as $replacement) {
        $css = preg_replace(
            '/@import\s*([\"|\']?)' . preg_quote($replacement['old'], '/') . '([\"|\']?);/si',
            '@import $1' . $replacement['new'] . '$2;',
            $css
        );

        $css = preg_replace(
            '/url\(([\"|\']?)' . preg_quote($replacement['old'], '/') . '([\"|\']?)\)/si',
            'url($1' . $replacement['new'] . '$2)',
            $css
        );
    }

    return $css;
}
