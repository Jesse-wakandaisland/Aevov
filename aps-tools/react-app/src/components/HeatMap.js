import React from 'react';

function HeatMap({ data }) {
    // This is a placeholder for a more sophisticated heat map visualization
    return (
        <div>
            <h2>Heat Map</h2>
            <pre>{JSON.stringify(data, null, 2)}</pre>
        </div>
    );
}

export default HeatMap;
