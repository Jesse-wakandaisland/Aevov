<?php

namespace AevovStream;

class PlaylistGenerator {

    public function generate( $pattern_ids ) {
        $playlist = "#EXTM3U\n";
        $playlist .= "#EXT-X-VERSION:3\n";
        $playlist .= "#EXT-X-TARGETDURATION:10\n";
        $playlist .= "#EXT-X-MEDIA-SEQUENCE:0\n";

        foreach ( $pattern_ids as $pattern_id ) {
            $playlist .= "#EXTINF:10.0,\n";
            $playlist .= site_url( '/wp-json/aevov-stream/v1/chunk/' . $pattern_id ) . "\n";
        }

        $playlist .= "#EXT-X-ENDLIST\n";

        return $playlist;
    }
}
