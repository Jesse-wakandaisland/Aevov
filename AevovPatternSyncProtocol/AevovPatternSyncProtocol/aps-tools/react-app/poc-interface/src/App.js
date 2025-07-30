import React from 'react';
import Blockchain from './components/Blockchain';
import NetworkStatus from './components/NetworkStatus';
import TransactionPool from './components/TransactionPool';
import './App.css';

function App() {
  return (
    <div className="App">
      <header className="App-header">
        <h1>Proof of Contribution</h1>
      </header>
      <div className="App-body">
        <Blockchain />
        <NetworkStatus />
        <TransactionPool />
      </div>
    </div>
  );
}

export default App;
