import React from 'react';
import { createRoot } from 'react-dom/client';
import PatientForm from './components/PatientForm';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('patient-form-root');
    if (container) {
        const postId = container.dataset.postId;
        const root = createRoot(container);
        root.render(<PatientForm postId={postId} />);
    }
});