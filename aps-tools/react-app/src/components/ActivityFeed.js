import React, { useState, useEffect } from 'react';

function ActivityFeed() {
    const [activities, setActivities] = useState([]);

    useEffect(() => {
        // Fetch activity feed data from the API
        fetch('/wp-json/aps-tools/v1/activity-feed')
            .then(response => response.json())
            .then(data => setActivities(data));
    }, []);

    return (
        <div>
            <h2>Activity Feed</h2>
            <ul>
                {activities.map(activity => (
                    <li key={activity.id}>{activity.message}</li>
                ))}
            </ul>
        </div>
    );
}

export default ActivityFeed;
