import React from 'react';

function KnowledgeGraph({ data }) {
    // This is a placeholder for a more sophisticated knowledge graph visualization
    return (
        <div>
            <h2>Knowledge Graph</h2>
            <pre>{JSON.stringify(data, null, 2)}</pre>
        </div>
    );
}

export default KnowledgeGraph;
