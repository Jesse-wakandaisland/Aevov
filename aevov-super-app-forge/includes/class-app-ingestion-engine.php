<?php

namespace AevovSuperAppForge;

// Ensure the PatternAnalyzer is available.
if (file_exists(WP_PLUGIN_DIR . '/AevovPatternSyncProtocol/Includes/Analysis/PatternAnalyzer.php')) {
    require_once WP_PLUGIN_DIR . '/AevovPatternSyncProtocol/Includes/Analysis/PatternAnalyzer.php';
}


class AppIngestionEngine {

    public function __construct() {
        // We will add our hooks and filters here.
    }

    public function ingest_app( $url ) {
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => 'Could not fetch URL: ' . $response->get_error_message() ];
        }

        $html = wp_remote_retrieve_body( $response );

        if ( empty( $html ) ) {
            return [ 'error' => 'No content found at URL.' ];
        }

        $doc = new \DOMDocument();
        @$doc->loadHTML( $html );

        $body_node = $doc->getElementsByTagName('body')->item(0);
        $dom_array = $this->dom_to_array($body_node);

        if (!class_exists('APS\Analysis\PatternAnalyzer')) {
            return ['error' => 'PatternAnalyzer class not found. Is AevovPatternSyncProtocol active?'];
        }

        $analyzer = new \APS\Analysis\PatternAnalyzer();
        $uad = $analyzer->analyze_pattern($dom_array);

        // Add source url to the UAD metadata
        $uad['metadata']['source_url'] = $url;

        return $uad;
    }

    private function dom_to_array(\DOMNode $node) {
        $output = [];
        if ($node->nodeType == XML_TEXT_NODE) {
            if (trim($node->nodeValue) !== '') {
                 return ['#text' => $node->nodeValue];
            }
            return null;
        }

        if ($node->nodeType == XML_COMMENT_NODE) {
            return null; // Ignore comments
        }

        $element = [];
        $element['#tag'] = $node->nodeName;

        if ($node->attributes->length) {
            foreach ($node->attributes as $attr) {
                $element['@' . $attr->name] = $attr->value;
            }
        }

        $children = [];
        foreach ($node->childNodes as $child) {
            $child_array = $this->dom_to_array($child);
            if ($child_array !== null) {
                $children[] = $child_array;
            }
        }

        if (!empty($children)) {
            $element['#children'] = $children;
        }

        return $element;
    }
}
