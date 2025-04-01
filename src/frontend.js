import React from 'react';
import { createRoot } from 'react-dom/client';
import PatientCardList from './components/PatientCardList';

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('wildlink-patient-list');
    if (container) {
        const root = createRoot(container);
        const urlParams = new URLSearchParams(window.location.search);
        const sharedPatientId = urlParams.get('patient');
        
        root.render(<PatientCardList initialPatientId={sharedPatientId} />);
    }
});