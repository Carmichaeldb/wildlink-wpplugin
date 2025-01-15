import React from 'react';
import { createRoot } from 'react-dom/client';
import PatientForm from './components/PatientForm';
import PatientList from './components/PatientList';
import Settings from './components/Settings';

document.addEventListener('DOMContentLoaded', () => {
    const listContainer = document.getElementById('wildlink-admin-root');
    if (listContainer) {
        const root = createRoot(listContainer);
        root.render(<PatientList />);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('id');
    const formContainer = document.getElementById('patient-form-root');
    if (formContainer) {
        console.log('Form container found, patientId:', patientId); // Debug log
        const root = createRoot(formContainer);
        root.render(<PatientForm patientId={patientId} />);
    }

    const settingsContainer = document.getElementById('wildlink-settings-root');
    if (settingsContainer) {
        const root = createRoot(settingsContainer);
        root.render(<Settings />);
    }
});