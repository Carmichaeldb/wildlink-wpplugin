import React from 'react';
import { createRoot } from 'react-dom/client';
import PatientForm from './components/PatientForm';
import PatientList from './components/PatientList';

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
        const root = createRoot(formContainer);
        root.render(<PatientForm patientId={patientId} />);
    }
});