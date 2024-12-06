import React from 'react';
import { createRoot } from 'react-dom/client';
import PatientCards from './components/PatientCards'; // Future component

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('patient-cards-root');
    if (container) {
        const root = createRoot(container);
        root.render(<PatientCards />);
    }
});