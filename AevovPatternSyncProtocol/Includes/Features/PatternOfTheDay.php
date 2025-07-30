<?php

namespace Aevov\Features;

class PatternOfTheDay
{
    /**
     * The pattern DB.
     *
     * @var \APS\DB\APS_Pattern_DB
     */
    private $pattern_db;

    /**
     * Constructor.
     *
     * @param \APS\DB\APS_Pattern_DB $pattern_db
     */
    public function __construct(\APS\DB\APS_Pattern_DB $pattern_db)
    {
        $this->pattern_db = $pattern_db;
    }

    /**
     * Sets the pattern of the day.
     */
    public function set_pattern_of_the_day()
    {
        $pattern = $this->get_random_pattern();
        if ($pattern) {
            update_option('pattern_of_the_day', $pattern);
        }
    }

    /**
     * Gets the pattern of the day.
     *
     * @return array|null
     */
    public function get_pattern_of_the_day()
    {
        return get_option('pattern_of_the_day');
    }

    /**
     * Gets a random pattern from the database.
     *
     * @return array|null
     */
    private function get_random_pattern()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aps_patterns';
        $pattern = $wpdb->get_row("SELECT * FROM {$table_name} ORDER BY RAND() LIMIT 1", ARRAY_A);

        if ($pattern) {
            $pattern['pattern_data'] = json_decode($pattern['pattern_data'], true);
        }

        return $pattern;
    }
}
