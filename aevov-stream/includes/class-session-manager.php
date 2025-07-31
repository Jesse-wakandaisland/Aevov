<?php

namespace AevovStream;

class SessionManager {

    public function create_session( $params ) {
        // This is a placeholder.
        return 'session-id-' . uniqid();
    }

    public function get_session( $session_id ) {
        // This is a placeholder.
        return [
            'session_id' => $session_id,
            'params' => [],
            'playlist' => []
        ];
    }

    public function delete_session( $session_id ) {
        // This is a placeholder.
        return true;
    }
}
